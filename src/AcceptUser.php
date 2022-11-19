<?php
namespace IO;
require_once "../.appinit.php";
use TymFrontiers\Generic,
    TymFrontiers\Data,
    TymFrontiers\API,
    TymFrontiers\HTTP,
    TymFrontiers\InstanceError;
use TymFrontiers\MultiForm;

\header("Content-Type: application/json");
$post = $_POST;
$gen = new Generic;
$params = $gen->requestParam([
  "code" => ["code","pattern", "/^052([\d]{4,4})([\d]{4,4})$/"],
  "work_group" => ["work_group","option", \array_keys($access_ranks)],

  "message" => ["message","text",25,1024],
  "form" => ["form","text",2,55],
  "CSRF_token" => ["CSRF_token","text",5,500]
], $post, ["code", "work_group", "CSRF_token", "form"] );

if (!$params || !empty($gen->errors)) {
  $errors = (new InstanceError ($gen, false))->get("requestParam",true);
  echo \json_encode([
    "status" => "3." . \count($errors),
    "errors" => $errors,
    "message" => "Request halted"
  ]);
  exit;
}
if ( !$gen->checkCSRF($params["form"],$params["CSRF_token"]) ) {
  $errors = (new InstanceError ($gen, false))->get("checkCSRF",true);
  echo \json_encode([
    "status" => "3." . \count($errors),
    "errors" => $errors,
    "message" => "Request failed."
  ]);
  exit;
}
$server_name  = "CWS";
$base_db = get_database($server_name, "base");
$admin_db = get_database($server_name, "admin");
include \dirname(__DIR__) . "/../helper.cwapp/src/inc-Conn.php";
$params['code'] = \str_replace([" ", "-", "_", "."],"", $params["code"]);
$fq = "SELECT adm.code, adm.status,
              usr.name, usr.surname, usr.email,
              acs.title AS work_group_title
      FROM :db:.:tbl: AS adm
      LEFT JOIN `{$base_db}`.users AS usr ON usr.code = adm.user
      LEFT JOIN `{$base_db}`.access_types AS acs ON acs.name = '{$params['work_group']}'
      WHERE adm.code = '{$params['code']}'
      LIMIT 1";
$admin = (new MultiForm($admin_db, "users", "code", $conn))->findBySql($fq);
if (!$admin) {
  die(\json_encode([
    "status" => "3.1",
    "errors" => ["Account with [code]: {$params['code']} does not exist exist."],
    "message" => "Request halted."
  ]));
} 
$admin = $admin[0];
if ($admin->status !== "REQUESTING") {
  die(\json_encode([
    "status" => "3.1",
    "errors" => ["Account is not a [REQUESTING] user."],
    "message" => "Request halted."
  ]));
}
if (!$conn->query("UPDATE `{$admin_db}`.`users` SET `work_group` = '{$params['work_group']}', `status` = 'ACTIVE' WHERE code='{$admin->code}' LIMIT 1")) {
  die(\json_encode([
    "status" => "4.1",
    "errors" => ["Failed to update account status."],
    "message" => "Request halted."
  ]));
}
$admin->code_split = code_split($admin->code, " ");
// pre process
include get_constant("PRJ_ROOT") . "/src/Pre-Process.php";
$msg = <<<MSG
<p>Dear {$admin->name} {$admin->surname}, </p>
<p>Your request for Admin account at has been accepted. Your work group is <b>{$admin->work_group_title}.</b> </p>
<p>You can <a href="https://central.cataliws.com/index/admin/sign-in">login</a> using conbination of your <b>Admin Profile Code:</b> <code>{$admin->code_split}</code> and your previously set password.</p>
MSG;
if (!empty($params['message'])) {
  $msg .= "<h3>Message from {$session->user->name}</h3>";
  $msg .= "<p>" . \nl2br($params['message']) . "</p>";
}
try {
  $eml = new Email();
  $eml->prep($system_user, "Admin account accepted", $msg);
  if ($acronym = domain_acronym(get_constant("PRJ_DOMAIN"))) {
    $eml->setOrigin($acronym);
  }
  $eml->queue(
    3, 
    (new Mailer\Profile(Generic::splitEmailName(get_constant("PRJ_SUPPORT_EMAIL")))), 
    (new Email\Recipient($eml->code(), Generic::splitEmailName("{$admin->name} {$admin->surname} <{$admin->email}>")))
  );
} catch (\Throwable $th) {
  die(\json_encode([
    "status" => "5.1",
    "errors" => ["Failed to queue/send referal alert.", $th->getMessage()],
    "message" => "Request halted."
  ]));
} 

echo \json_encode([
  "status" => "0.0",
  "errors" => [],
  "message" => "Request completed"
]);
exit;