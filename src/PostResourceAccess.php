<?php
namespace IO;
require_once "../.appinit.php";
use \TymFrontiers\Generic,
    \TymFrontiers\MultiForm,
    \TymFrontiers\InstanceError;

\header("Content-Type: application/json");
\require_login(false);
\check_access("WRITE", "/resource-access", "project-admin","", false);

$post = $_POST;
$gen = new Generic;
$params = $gen->requestParam([
  "id" => ["id","int"],
  "resource" => ["resource","username", 3, 21, [], "LOWER", ["-"]],
  "group_name" => ["group_name","username", 3, 21, [], "UPPER"],
  "scope" => ["scope","text",4,128],

  "form" => ["form","text",2,72],
  "CSRF_token" => ["CSRF_token","text",5,1024]
],$post,["resource", "group_name", "scope", "form", "CSRF_token"]);

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
include PRJ_ROOT . "/src/Pre-Process.php";
$db_name = get_database(\IO\get_constant("PRJ_SERVER_NAME"), "data");

$is_new = empty($params['id']);
$access = $is_new
  ? new MultiForm($db_name,'resource_access','id', $database)
  : (new MultiForm($db_name,'resource_access','id', $database))->findById($params['id']);
unset($params["form"]);
unset($params["CSRF_token"]);

foreach ($params as $prop => $value) {
  $access->$prop = $value;
}
$action = $is_new
  ? $access->create()
  : $access->update();
if (!$action) {
  $do_errors = [];

  $access->mergeErrors();
  $more_errors = (new InstanceError($access))->get('',true);
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
