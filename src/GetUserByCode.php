<?php
namespace IO;
use \TymFrontiers\Generic,
    \TymFrontiers\InstanceError,
    \TymFrontiers\MultiForm;
require_once "../.appinit.php";
\header("Content-Type: application/json");

$post = !empty($_POST) ? $_POST : $_GET;

$gen = new Generic;
$params = $gen->requestParam([
  "code" => ["code","pattern", "/^(252|352)([\d]{4,4})([\d]{4,4})$/"],
], $post, ["code"] );

if (!$params || !empty($gen->errors)) {
  $errors = (new InstanceError ($gen, false))->get("requestParam",true);
  echo \json_encode([
    "status" => "3." . \count($errors),
    "errors" => $errors,
    "message" => "Request halted"
  ]);
  exit;
}
$cred = code_storage($params['code'], "BASE");
$found = (new MultiForm($cred[0], $cred[1], $cred[2]))
  ->findBySql("SELECT `name`, `surname`, `email`
              FROM :db:.:tbl:
              WHERE `status` = 'ACTIVE'
              AND `code` = '{$params['code']}' 
              LIMIT 1");
if( !$found ){
  die( \json_encode([
    "message" => "No active user found.",
    "errors" => [],
    "status" => "0.2"
    ]) );
}

// process result
$result = [
  "message" => "Request completed.",
  "errors"  => [],
  "status"  => "0.0",
  "data" => [
    "name" => $found[0]->name,
    "surname" => $found[0]->surname,
    "email" => $found[0]->email,
    "email_mask" => email_mask($found[0]->email),
  ]
];
echo \json_encode($result);
exit;
