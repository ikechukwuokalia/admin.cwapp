<?php
namespace IO;
require_once "../.appinit.php";

use PhpOffice\PhpSpreadsheet\Calculation\DateTimeExcel\Helpers;
use \TymFrontiers\Data,
    \TymFrontiers\MySQLDatabase,
    \TymFrontiers\Generic,
    \TymFrontiers\InstanceError,
    \TymFrontiers\MultiForm,
    \TymFrontiers\Location,
    \TymFrontiers\Validator;
$data = new Data;
\header("Content-Type: application/json");
$gen = new Generic();
$valid = new Validator;
$post = $_POST;
$expected_params = [
  "code" => ["code","pattern", "/^052(\s|\-|\.)?([\d]{4,4})(\s|\-|\.)?([\d]{4,4})$/"],
  "password" =>["password","password"],
  "remember" => ["remember","boolean"],
  "user" => ["user","email"],
  "rdt" => ["rdt","url"],
  "otp" =>["otp","username", 3, 28, [], "mixed", [" ", "-", "_", "."]],
  "form" => ["form","text",2,55],
  "CSRF_token" => ["CSRF_token","text",5,500]
];
$params = $gen->requestParam($expected_params, $post, ["password",'CSRF_token','form'] );
if (!$params || !empty($gen->errors)) {
  $errors = (new InstanceError ($gen, false))->get("requestParam",true);
  echo \json_encode([
    "status" => "3." . \count($errors),
    "errors" => $errors,
    "otp_req" => false,
    "email" => "",
    "message" => "Request failed",
    "rdt" => ""
  ]);
  exit;
}
if ( !$gen->checkCSRF($params["form"],$params["CSRF_token"]) ) {
  $errors = (new InstanceError ($gen, false))->get("checkCSRF",true);
  echo \json_encode([
    "status" => "3." . \count($errors),
    "errors" => $errors,
    "message" => "Request failed.",
    "rdt" => ""
  ]);
  exit;
}

if( !$gen->checkCSRF($params["form"],$params["CSRF_token"]) ){
  $errors = (new InstanceError ($gen, false))->get("checkCSRF",true);
  echo \json_encode([
    "status" => "3." . \count($errors),
    "errors" => $errors,
    "otp_req" => false,
    "email" => "",
    "message" => "Request failed.",
    "rdt" => ""
  ]);
  exit;
}
$params['code'] = \str_replace([" ","-","_"], "", $params['code']);
$user = Admin::authenticate($params["code"], $params["password"]);
if( !$user ){
  echo \json_encode([
    "status" => "3.1",
    "errors" => ["Credentials validation failed."],
    "otp_req" => false,
    "email" => "",
    "message" => "Login failed",
    "rdt" => ""
  ]);
  exit;
}
// check if otp is needed
// if (empty($params['otp'])) {
//   $log_db = get_database(get_constant("PRJ_SERVER_NAME"), "log");
//   $base_db = get_database(get_constant("PRJ_SERVER_NAME"), "base");
//   $admin_db = get_database(get_constant("PRJ_SERVER_NAME"), "admin");


//   $last_login = (new MultiForm($log_db, "user_session", "id", $database))
//     ->findBySql("SELECT lg.ip, lg.country_code, lg.state_code, lg.city_code, lg.expiry, lg._created,
//                         adm.`code`, usr.email
//                 FROM :db:.:tbl: AS lg
//                 LEFT JOIN `{$admin_db}`.users AS adm ON adm.`code` = '{$params['code']}'
//                 LEFT JOIN `{$base_db}`.users AS usr ON usr.`code` = adm.`user`
//                 WHERE lg.`user` = adm.`code`
//                 AND lg.`type` = 'LOGIN'
//                 ORDER BY lg._created DESC 
//                 LIMIT 1");
//   if (!$last_login) {
//     $email = false;
//     if ($email = (new MultiForm(get_database(\IO\get_constant("PRJ_SERVER_NAME"), "admin"), "users", "code", $database))->findBySql("SELECT email FROM `{$base_db}`.`users` WHERE `code` = (SELECT `user` FROM :db:.:tbl: WHERE `code` = '{$params['code']}' LIMIT 1) LIMIT 1")) {
//       // find email
//       $email = $email[0]->email;
//     } else {
//       echo \json_encode([
//         "status" => "2.1",
//         "errors" => ["OTP required but no valid email is available."],
//         "otp_req" => false,
//         "email" => "",
//         "message" => "Login failed",
//         "rdt" => ""
//       ]);
//       exit;
//     }
//     echo \json_encode([
//       "status" => "0.3",
//       "errors" => [],
//       "otp_req" => true,
//       "email" => $email,
//       "message" => "OTP required",
//       "rdt" => ""
//     ]);
//     exit;
//   } else {
//     // check if OTP is needed
//     $last_login = $last_login[0];
//     $location = new Location();
//     if (
//         $last_login->ip !== $location->ip
//         // more Conditions here
//       ) {
//       echo \json_encode([
//         "status" => "0.3",
//         "errors" => [],
//         "otp_req" => true,
//         "email" => $last_login->email,
//         "message" => "OTP required",
//         "rdt" => ""
//       ]);
//       exit;
//     }
//   }
// } else {
//   // validate otp;
//   $otp = new OTP\ByEmail();
//   if (!$otp->verify($params['user'], $params['otp'])) {
//     echo \json_encode([
//       "status" => "3.1",
//       "errors" => ["You have entered an invalid/expired OTP"],
//       "message" => "Request halted.",
//       "otp_req" => true,
//       "email" => $params['user'],
//       "rdt" => ""
//     ]);
//     exit;
//   }
// }
$remember = !(bool)$params['remember'] ? \strtotime("+ 1 Hour") : \strtotime("+ 24 Hours");
$max_access = Admin\access_type($user->access_group);
if (\is_array($max_access) && \count($max_access) > 1 ) {
  $user->access_group = $max_access[0];
  $user->access_rank = $max_access[1];
}
$session->login($user,$remember);
Admin\log_session("LOGIN"); // log
if (!$db_user = get_dbuser(get_constant("PRJ_SERVER_NAME"), $session->access_group()) ) $db_user = get_dbuser(get_constant("PRJ_SERVER_NAME"), "USER");
@ $database->closeConnection();
$database = new MySQLDatabase(get_dbserver(get_constant("PRJ_SERVER_NAME")), $db_user[0], $db_user[1]);
$db =& $database;
$rdt = empty($params['rdt'])
  ? WHOST . "/admin"
  : $params['rdt'];

echo \json_encode([
  "status" => "0.0",
  "errors" => [],
  "otp_req" => false,
  "email" => "",
  "message" => "You are now signed in!",
  "rdt" => $rdt
]);
exit;
