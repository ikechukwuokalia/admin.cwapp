<?php
namespace IO;
require_once "../.appinit.php";
use \TymFrontiers\Generic,
    \TymFrontiers\MultiForm,
    \TymFrontiers\InstanceError;

\header("Content-Type: application/json");
\require_login(false);
\check_access("DELETE", "/resource-access", "project-admin","", false);

$post = $_POST;
$gen = new Generic;
$params = $gen->requestParam([
  "id" => ["id","int"],

  "form" => ["form","text",2,72],
  "CSRF_token" => ["CSRF_token","text",5,1024]
],$post,["id", "form", "CSRF_token"]);

if (!$params || !empty($gen->errors)) {
  $errors = (new InstanceError($gen,true))->get("requestParam",true);
  echo \json_encode([
    "status" => "3." . \count($errors),
    "errors" => $errors,
    "message" => "Request failed"
  ]);
  exit;
}
if ( !$gen->checkCSRF($params["form"],$params["CSRF_token"]) ) {
  $errors = (new InstanceError($gen,true))->get("checkCSRF",true);
  echo \json_encode([
    "status" => "3." . \count($errors),
    "errors" => $errors,
    "message" => "Request failed."
  ]);
  exit;
}
$db_name = get_database("CWS", "data");
// check for duplicate
include PRJ_ROOT . "/src/Pre-Process.php";
$rct = (new MultiForm($db_name, 'resource_access', 'id', $database))->findById($params['id']);

if ( !$rct ) {
  echo \json_encode([
    "status" => "3.1",
    "errors" => ["Resource access with [id]: '{$params['id']}' was not found."],
    "message" => "Request halted."
  ]);
  exit;
}
if (!$rct->delete()) {
  $do_errors = [];

  $rct->mergeErrors();
  $more_errors = (new InstanceError($rct,true))->get('',true);
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
