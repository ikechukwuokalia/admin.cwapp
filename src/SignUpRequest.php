<?php
namespace IO;
require_once "../.appinit.php";
use TymFrontiers\Generic,
    TymFrontiers\Data,
    TymFrontiers\API,
    TymFrontiers\BetaTym,
    TymFrontiers\InstanceError;

\header("Content-Type: application/json");
$post = $_POST;
$gen = new Generic;
$data = new Data;
if( !empty($post['phone']) || !empty($post['country_code']) ){
  $post['phone'] = $data->phoneToIntl(\trim($post['phone']),\trim($post['country_code']));
}
if ($user_max_age = setting_get_value("SYSTEM", "USER.MAX-AGE", get_constant("PRJ_BASE_DOMAIN"))) {
  $user_max_age = (int)$user_max_age;
} else {
  $user_max_age = 85;
}
if ($user_min_age = setting_get_value("SYSTEM", "USER.MIN-AGE", get_constant("PRJ_BASE_DOMAIN"))) {
  $user_min_age = (int)$user_min_age;
} else {
  $user_min_age = 18;
}
$params = $gen->requestParam([
  "country_code" => ["country_code","username",2,2,[],"UPPER"],
  "name" => ["name","name"],
  "surname" => ["surname","name"],
  "email" => ["email","email"],
  "phone" => ["phone","tel"],
  "password" => ["password","password"],
  "password_repeat" => ["password_repeat","password"],
  "otp" =>["otp","username", 3, 28, [], "mixed", [" ", "-", "_", "."]],
  "sex" =>["sex","option", ["MALE", "FEMALE"]],
  "dob" =>[
    "dob",
    "date",
    \date(BetaTym::MYSQL_DATETIME_STRING,\strtotime("- {$user_max_age} Years")),
    \date(BetaTym::MYSQL_DATETIME_STRING,\strtotime("- {$user_min_age} Years"))
  ],

  "form" => ["form","text",2,55],
  "CSRF_token" => ["CSRF_token","text",5,500]
], $post, ["country_code", "name", "surname", "email", "phone", "password", "password_repeat", "otp", "sex", "dob", "form", "CSRF_token"] );

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
if( $params['password'] !== $params['password_repeat'] ){
  echo \json_encode([
    "status" => "3.1",
    "errors" => ["[password]: does not match [password_repeat]"],
    "message" => "Request halted."
  ]);
  exit;
}
$params['otp'] = \str_replace([" ", "-", "_", "."],"", $params["otp"]);
$admin = new Admin();
if ($admin->valExist($params['email'], "email")) {
  die(\json_encode([
    "status" => "3.1",
    "errors" => ["[email] is not available."],
    "message" => "Request halted."
  ]));
} if ($admin->valExist($params['phone'], "phone")) {
  die(\json_encode([
    "status" => "3.1",
    "errors" => ["[phone] is not available."],
    "message" => "Request halted."
  ]));
}
// validate otp
try {
  $otp = new OTP\ByEmail();
} catch (\Throwable $th) {
  //throw $th;
  echo \json_encode([
    "status" => "5.1",
    "errors" => [$th->getMessage()],
    "message" => "Request halted."
  ]);
  exit;
}
if (!$otp->verify($params["email"], $params["otp"])) {
  echo \json_encode([
    "status" => "2.1",
    "errors" => ["Invalid OTP code entered"],
    "message" => "Request halted.",
    "otp" => false
  ]);
  exit;
}
unset($params['CSRF_token']);
unset($params['form']);
foreach ($params as $prop => $value) {
  if (!\in_array($prop, ["password", "password_repeat", "otp"])) {
    $admin->$prop = $value;
  }
}
try {
  $success = $admin->requestAccount($params['password']);
} catch (\Throwable $th) {
  die(\json_encode([
    "status" => "4.1",
    "message" => "Request failed",
    "errors" => [$th->getMessage()]
  ]));
}
if (!$success) {
  $errors = ["Unable to perform account request at this time."];
  if ($errs = (new InstanceError($admin))->get("requestAccount", true)) {
    foreach ($errs as $err) {
      $errors[] = $err;
    }
  }
  die(\json_encode([
    "status" => "4." . \count($errors),
    "message" => "Request failed",
    "errors" => $errors
  ]));
}

echo \json_encode([
  "status" => "0.0",
  "errors" => [],
  "message" => "Your account request have been received, you will hear from us soon. ".PHP_EOL."Thank you.",
  "rdt" => "/admin"
]);
exit;