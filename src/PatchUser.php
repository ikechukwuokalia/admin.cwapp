<?php
namespace IO;
require_once "../.appinit.php";
use \TymFrontiers\Generic,
    \TymFrontiers\MultiForm,
    \TymFrontiers\MySQLDatabase,
    \TymFrontiers\InstanceError;

\header("Content-Type: application/json");
\require_login(false);
\check_access("ALTER", "/users", "project-admin","", false);

$post = $_POST;
$gen = new Generic;
$params = $gen->requestParam([
  "code" => ["code","pattern", "/^052([\d]{4,4})([\d]{4,4})([\d]{1,4})?$/"],
  "status" => ["status","option", ["ACTIVE", "SUSPENDED", "BANNED", "DISABLED"]]
],$post,["code", "status"]);

if (!$params || !empty($gen->errors)) {
  $errors = (new InstanceError($gen,true))->get("requestParam",true);
  echo \json_encode([
    "status" => "3." . \count($errors),
    "errors" => $errors,
    "message" => "Request failed"
  ]);
  exit;
}
$params["code"] = \str_replace([" ", "-", ".", "_"],"",$params["code"]);
include PRJ_ROOT . "/src/Pre-Process.php";

$server_name = get_constant("PRJ_SERVER_NAME");
include \dirname(__DIR__) . "/../helper.cwapp/src/inc-Conn.php";
$db_name = get_database("admin", $server_name);
$conn->changeDB($db_name);
$user = (new MultiForm($db_name, "users", "code", $conn))->findById($params["code"]);

if ( !$user ) {
  echo \json_encode([
    "status" => "3.1",
    "errors" => ["Admin with [code]: '{$params['code']}' was not found."],
    "message" => "Request halted."
  ]);
  exit;
}
if ($user->status == "BANNED") {
  echo \json_encode([
    "status" => "3.1",
    "errors" => ["User is banned and cannot be updated."],
    "message" => "Request halted."
  ]);
  exit;
}
if ( !$conn->query("UPDATE `{$db_name}`.`users` SET `status` = '{$conn->escapeValue($params['status'])}' WHERE `code` = '{$conn->escapeValue($params['code'])}' LIMIT 1") ) {
  $do_errors = [];
  $more_errors = (new InstanceError($conn,true))->get('',true);
  if (!empty($more_errors)) {
    foreach ($more_errors as $method=>$errs) {
      foreach ($errs as $err){
        $do_errors[] = $err;
      }
    }
    echo \json_encode([
      "status" => "4." . \count($do_errors),
      "errors" => $do_errors,
      "message" => "Request incomplete."
    ]);
    exit;
  } else {
    echo \json_encode([
      "status" => "0.1",
      "errors" => [],
      "message" => "Request completed with no changes made."
    ]);
    exit;
  }
}

echo \json_encode([
  "status" => "0.0",
  "errors" => [],
  "message" => "Request was successful!"
]);
exit;
