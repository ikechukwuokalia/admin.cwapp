<?php
namespace IO;
require_once "../.appinit.php";
use TymFrontiers\Generic,
    TymFrontiers\Data,
    TymFrontiers\API,
    TymFrontiers\HTTP,
    TymFrontiers\InstanceError;

\header("Content-Type: application/json");
$post = $_POST;
$gen = new Generic;
$params = $gen->requestParam([
  "user" => ["user","pattern", "/^(252|352)(\s|\-|\.)?([\d]{4,4})(\s|\-|\.)?([\d]{4,4})$/"],
  "email" => ["email","email"],
  "password" => ["password","password"],
  "password_repeat" => ["password_repeat","password"],
  "otp" =>["otp","username", 3, 28, [], "mixed", [" ", "-", "_", "."]],

  "form" => ["form","text",2,55],
  "CSRF_token" => ["CSRF_token","text",5,500]
], $post, ["user", "email", "password", "otp", "CSRF_token", "form"] );

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
$params['user'] = \str_replace([" ", "-", "_", "."],"", $params["user"]);
$admin = new Admin();
$base_db = get_database("BASE", "base");
if ($admin->valExist($params['user'], "user")) {
  die(\json_encode([
    "status" => "3.1",
    "errors" => ["[Account]: already exist."],
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

// save application
try {
  $success = $admin->requestAccount($params['user'], $params['password']);
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
  "rdt" => "/index"
]);
exit;