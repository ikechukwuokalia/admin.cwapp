<?php
namespace IO;
require_once "../.appinit.php";
use \TymFrontiers\Generic,
    \TymFrontiers\MultiForm,
    \TymFrontiers\InstanceError;

\header("Content-Type: application/json");
\require_login(false);
\check_access("WRITE", "/resource-types", "project-admin","", false);

$post = $_POST;
$gen = new Generic;
$params = $gen->requestParam([
  "id"  => ["id", "int"],
  "keyword"  => ["keyword", "username", 2, 21, [], "LOWER", ["-"]],
  "type" => ["type", "option", ["RESERVED", "RESTRICTED"]],

  "form" => ["form","text",2,72],
  "CSRF_token" => ["CSRF_token","text",5,1024]
],$post,["keyword", "type", "form", "CSRF_token"]);

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
$db_name = get_database("CWS", "data");

$is_new = empty($params['id']);
$kwd = $is_new
  ? new MultiForm($db_name,'reserved_keywords','id', $database)
  : (new MultiForm($db_name,'reserved_keywords','id', $database))->findById($params['id']);
$kwd->keyword = $params['keyword'];
$kwd->type = $params['type'];
$action = $is_new
  ? $kwd->create()
  : $kwd->update();
if (!$action) {
  $do_errors = [];

  $kwd->mergeErrors();
  $more_errors = (new InstanceError($kwd))->get('',true);
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
