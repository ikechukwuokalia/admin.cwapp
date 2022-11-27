<?php
namespace IO;
require_once "../../.appinit.php";

use IO\Email\Recipient;
use \TymFrontiers\HTTP,
    \TymFrontiers\API,
    \TymFrontiers\Generic,
    \TymFrontiers\InstanceError,
    \TymFrontiers\MultiForm,
    \TymFrontiers\MySQLDatabase,
    \Mailgun\Mailgun;
\header("Content-Type: application/json");
$server_name = get_constant("PRJ_SERVER_NAME");
include \dirname(\dirname(__DIR__)) . "/../helper.cwapp/src/inc-Conn.php";
if (!empty($conn) && $conn instanceof MySQLDatabase) {
  $conn->changeDB(get_database("developer", $server_name));
}
$post = \json_decode( \file_get_contents('php://input'), true); // json data
$post = !empty($post) ? $post : (
  !empty($_POST) ? $_POST : $_GET
);
$gen = new Generic;
$auth = new API\Authentication ((!empty($api_sign_patterns) ? $api_sign_patterns : []), "", 0, false, $conn);
$http_auth = $auth->validApp ();
if ( !$http_auth && ( empty($post['form']) || empty($post['CSRF_token']) ) ){
  HTTP\Header::unauthorized (false,'', Generic::authErrors ($auth,"Request [Auth-App]: Authetication failed.",'self',true));
}

$rqp = [
  "origin" => ["origin", "username", 2, 56, [], "UPPER", ["-", ".", "_"]],
  "null_origin" => ["null_origin","boolean"],
  "limit" => ["limit","int"],

  "form" => ["form","text",2,72],
  "CSRF_token" => ["CSRF_token","text",5,1024]
];
$req = [];
if (!$http_auth) {
  $req[] = 'form';
  $req[] = 'CSRF_token';
}

$params = $gen->requestParam($rqp, $post, $req);
if (!$params || !empty($gen->errors)) {
  $errors = (new InstanceError($gen, false))->get("requestParam",true);
  echo \json_encode([
    "status" => "3." . \count($errors),
    "errors" => $errors,
    "message" => "Request halted"
  ]);
  exit;
}
if( !$http_auth ){
  if ( !$gen->checkCSRF($params["form"],$params["CSRF_token"]) ) {
    $errors = (new InstanceError($gen, false))->get("checkCSRF",true);
    echo \json_encode([
      "status" => "3." . \count($errors),
      "errors" => $errors,
      "message" => "Request halted."
    ]);
    exit;
  }
}
// Begin dev process
$limit = $params['limit'] > 0 ? $params['limit'] : 25;
$send_errors = [];
$sent = [];
$failed = [];
$mdb = get_database("email", get_constant("PRJ_SERVER_NAME"));
if ((bool)$params['null_origin']) {
  $null_cnd = " OR `origin` IS NULL OR `origin` = '' ";
} else {
  $null_cnd = "";
}
$cnd = "";
if (!empty($params['origin'])) {
  $cnd .= " AND mlg.`email` IN (
    SELECT `code`
    FROM :db:.`emails` 
    WHERE `folder` = 'OUTBOX'
    AND `origin` = '{$conn->escapeValue($params['origin'])}'
    {$null_cnd}
  )";
} else {
  $cnd .= " AND mlg.`email` IN (
    SELECT `code`
    FROM :db:.`emails` 
    WHERE `folder` = 'OUTBOX'
    AND (
      `origin` IS NULL
      OR `origin` = ''
    )
  )";
}
$mq = "SELECT mlg.id, mlg.email,
              CONCAT(mp.name, ' ', mp.surname, ' <', mp.`address`, '>') as sender,
              CONCAT(emr.name, ' ', emr.surname, ' <', emr.`address`, '>') as recipient,
              emr.type
      FROM :db:.:tbl: AS mlg
      LEFT JOIN :db:.`mailer_profiles` AS mp ON mp.id = mlg.`sender`
      LEFT JOIN :db:.`email_recipients` AS emr ON emr.id = mlg.`recipient`
      WHERE mlg.`sent` = FALSE
      {$cnd}
      ORDER BY mlg.priority ASC, mlg._created ASC
      LIMIT {$params['limit']}";
$mails = (new MultiForm($mdb, 'email_log','id', $conn))->findBySql($mq);
if( $mails ):
  foreach($mails as $ml){
    if ($eml = (new Email ("", "", $conn))->findById($ml->email)) {
      $reci = Generic::splitEmailName($ml->recipient);
      $recipient = Recipient::propagate($reci['email'], $reci['name'], $reci['surname'], $ml->type);
      $recipient->email = $ml->email;
      if (!$eml->send(
        (new Mailer\Profile(Generic::splitEmailName($ml->sender), "", "", $conn)), // Saved Email sender
        $recipient, // Saved Email recipient
        $ml->id // Email log [id]
      )) {
        if ($errs = (new InstanceError($eml, true))->get("send", true)) {
          foreach ($errs as $err) {
            $send_errors[] = $err;
          }
        } else {
          $send_errors[] = "Failed to send email with [code] {$ml->email}.";
        }
        $failed[] = $ml->id;
      } else {
        $sent[] = $ml->id;
      }
    } else {
      $send_errors[] = "Email was not found for queue [id] {$ml->id}";
      $failed[] = $ml->id;
    }
  }
  if (!empty($send_errors)) {
    echo \json_encode([
      "status" => "4." . \count($send_errors),
      "message" => "Request inconclusive. Failed [ids] \\ ".\implode(", ", $failed),
      "errors" => $send_errors
    ]);
    exit;
  }
  echo \json_encode([
    "status" => "0.0",
    "message" => \count($sent) . " Email(s) sent successfully",
    "errors" => [],
    "id" => $sent
  ]);
  exit;
else:
  echo \json_encode([
    "status" => "0.2",
    "message" => "No Email(s) found for sending. ",
    "errors" => [],
    "id" => []
  ]);
  exit;
endif;
