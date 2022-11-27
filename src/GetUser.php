<?php
namespace IO;

use TymFrontiers\Data;
use \TymFrontiers\Generic,
    \TymFrontiers\InstanceError,
    \TymFrontiers\MultiForm;
require_once "../.appinit.php";
\header("Content-Type: application/json");

$post = !empty($_POST) ? $_POST : $_GET;

$gen = new Generic;
$params = $gen->requestParam([
  "code" => ["code","pattern", "/^(052|252)([\d]{4,4})([\d]{4,4})$/"],
  "status" => ["status","option", ["ACTIVE", "PENDING", "REQUESTED", "INVITED", "BANNED", "SUSPENDED"]],
  "search" => ["search","text",3,56],
  "page" =>["page","int",1,0],
  "limit" =>["limit","int",1,0],
  "form" => ["form","text",2,55],
  "CSRF_token" => ["CSRF_token","text",5,500]
], $post, ["CSRF_token", "form"] );

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
  $errors = (new InstanceError ($gen,false))->get("checkCSRF",true);
  echo \json_encode([
    "status" => "3." . \count($errors),
    "errors" => $errors,
    "message" => "Request failed."
  ]);
  exit;
}
$server_name = "BASE";
$admin_db = get_database("admin", $server_name);
$file_db = get_database("file", $server_name);
$data_db = get_database("data", $server_name);
$count = 0;
$data = new MultiForm($admin_db, 'users', 'code', $database);
$data->current_page = $page = (int)$params['page'] > 0 ? (int)$params['page'] : 1;
$file_server = get_constant("PRJ_FILE_SERVER");
$uq = "SELECT adm.`code`, adm.work_group, adm.status,
              adm.name, adm.surname, adm.`status`, adm.country_code,
              adm.email, adm.phone, adm.dob, adm.sex,
              c.`name` AS country,
              (
                SELECT CONCAT('{$file_server}/',f._name)
              ) AS avatar
      FROM :db:.:tbl: AS adm 
      LEFT JOIN {$data_db}.countries AS c ON c.`code` = adm.country_code
      LEFT JOIN `{$file_db}`.`file_default` AS fd ON fd.`user` = adm.`code` AND fd.set_key = 'USER.AVATAR'
      LEFT JOIN `{$file_db}`.`file_meta` AS f ON f.id = fd.file_id ";
$cnd = " WHERE 1=1 ";
if (!empty($params['code'])) {
  $cnd .= " AND (
    adm.`code` = '{$params['code']}'
  ) ";
} else {
  if (!empty($params['status'])) {
    $cnd .= " AND adm.`status` = '{$params['status']}'";
  }
  if( !empty($params['search']) ){
    $params['search'] = $db->escapeValue(\strtolower($params['search']));
    $cnd .= " AND (
      adm.`code` = '{$params['search']}'
      OR adm.`email` = '{$params['search']}'
      OR adm.phone LIKE '%{$params['search']}%'
      OR adm.name LIKE '%{$params['search']}%'
      OR adm.surname LIKE '%{$params['search']}%'
    ) ";
  }
}

$count = $data->findBySql("SELECT COUNT(*) AS cnt FROM :db:.:tbl: AS adm {$cnd} ");
// echo $db->last_query;
$count = $data->total_count = $count ? $count[0]->cnt : 0;

$data->per_page = $limit = !empty($params['code']) ? 1 : (
    (int)$params['limit'] > 0 ? (int)$params['limit'] : 25
  );
$uq .= $cnd;
$sort = " ORDER BY adm._created DESC ";

$uq .= $sort;
$uq .= " LIMIT {$data->per_page} ";
$uq .= " OFFSET {$data->offset()}";

$found = $data->findBySql($uq);
$tym = new \TymFrontiers\BetaTym;

if( !$found ){
  die( \json_encode([
    "message" => "No result found for your query.",
    "errors" => [],
    "status" => "0.2"
    ]) );
}
$result = [
  'status' => '0.0',
  'errors' => [],
  'message' => 'Request completed',
  'records' => (int)$count,
  'page'  => $data->current_page,
  'pages' => $data->totalPages(),
  'limit' => $limit,
  'previousPage' => $data->hasPreviousPage() ? $data->previousPage() : false,
  'nextPage' => $data->hasNextPage() ? $data->nextPage() : false
];

$data_obj = new Data;
foreach ($found as $user) {
  $result["users"][] = [
    "code" => $user->code,
    "status" => $user->status,
    "workGroup" => $user->work_group,
    "codeSplit" => code_split($user->code, " "),
    "name" => $user->name,
    "surname" => $user->surname,
    "email" => $user->email,
    "emailMask" => email_mask($user->email),
    "phone" => $user->phone,
    "phoneMask" => phone_mask($user->phone),
    "phoneLocal" => $data_obj->phoneToLocal($user->phone),
    "country" => $user->country,
    "countryCode" => $user->country_code,
    "avatar" => (!empty($user->avatar) ? $user->avatar : WHOST . "/app/ikechukwuokalia/admin.cwapp/img/default-avatar.png"),
  ];
}
echo \json_encode($result);
exit;
