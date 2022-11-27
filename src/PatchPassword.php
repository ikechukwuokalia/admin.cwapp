<?php
namespace IO;
require_once "../.appinit.php";
use \TymFrontiers\Generic,
    \TymFrontiers\MySQLDatabase,
    \TymFrontiers\Data,
    \TymFrontiers\InstanceError;

\header("Content-Type: application/json");
\require_login(false);

$post = $_POST;
$gen = new Generic;
$params = $gen->requestParam([
  "old_password" => ["old_password","password"],
  "password" => ["password","password"],
  "password_repeat" => ["password_repeat","password"]
],$post,["old_password", "password", "password_repeat"]);

if (!$params || !empty($gen->errors)) {
  $errors = (new InstanceError($gen,true))->get("requestParam",true);
  echo \json_encode([
    "status" => "3." . \count($errors),
    "errors" => $errors,
    "message" => "Request failed"
  ]);
  exit;
}
if( $params['password'] !== $params['password_repeat'] ){
  echo \json_encode([
    "status" => "3.1",
    "errors" => ["[password]: does not match [password_repeat]"],
    "message" => "Request halted."
  ]);
  exit;
}
if (!$user = Admin::authenticate($session->name, $params["old_password"])) {
  echo \json_encode([
    "status" => "3.1",
    "errors" => ["[current-password] is incorrect"],
    "message" => "Request halted."
  ]);
  exit;
}

$db_name = get_database("admin", \IO\get_constant("PRJ_SERVER_NAME"));
$cred = db_cred("BASE", "DEVELOPER");
$conn = new MySQLDatabase(get_dbserver("BASE"), $cred[0], $cred[1], $db_name);
$pwd = Data::pwdHash($params['password']);

if (!$conn->query("UPDATE {$db_name}.`users` SET `password`='{$conn->escapeValue($pwd)}' WHERE `code` = '{$conn->escapeValue($session->name)}' LIMIT 1")) {
  echo \json_encode([
    "status" => "4.1",
    "errors" => ["Failed to change password, please try again later."],
    "message" => "Request failed."
  ]);
  exit;
}

echo \json_encode([
  "status" => "0.0",
  "errors" => [],
  "message" => "Request was successful!"
]);
exit;
