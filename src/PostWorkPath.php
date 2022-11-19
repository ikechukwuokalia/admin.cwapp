<?php
namespace IO;
require_once "../.appinit.php";
use \TymFrontiers\Generic,
    \TymFrontiers\MultiForm,
    \TymFrontiers\InstanceError;

\header("Content-Type: application/json");
\require_login(false);
\check_access("WRITE", "/work-paths", "project-admin","", false);

$post = $_POST;
$gen = new Generic;
$params = $gen->requestParam([
  "id"          => ["id","int"],
  "domain"      => ["domain","username",3,98,[],'LOWER',['-','.', '_']],
  "access_scope" => ["access_scope","script",3,128],
  "path" => ["path","text",1,56],
  "resource_type"  => ["resource_type","username",3,98,[],'LOWER',['-']],
  "nav_visible" => ["nav_visible","boolean"],
  "onclick"  => ["onclick","username",3,98,[],'MIXED', ['_',"."]],
  "classname"  => ["classname","username",3,98,[],'LOWER', ['_',"-"]],
  "title" => ["title","text",3,56],
  "icon" => ["icon","script",3,128],
  "sort" => ["sort","int"],
  "description" => ["description","text",5,128],
  
  "form" => ["form","text",2,72],
  "CSRF_token" => ["CSRF_token","text",5,1024]
],$post,["domain", "access_scope", "path", "resource_type", "title", "description", "form", "CSRF_token"]);

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
$db_name = get_database("CWS", "admin");

unset($params["form"]);
unset($params["CSRF_token"]);
$is_new = empty($params['id']);
$path = $is_new
  ? new MultiForm($db_name,'work_paths','id', $database)
  : (new MultiForm($db_name,'work_paths','id', $database))->findById($params['id']);
$path->empty_prop = ["nav_visible", "onclick", "classname", "icon", "sort"];
foreach ($params as $prop=>$value) {
  $path->$prop = $value;
}
$path->nav_visible = (bool)$params['nav_visible'];
$action = $is_new
  ? $path->create()
  : $path->update();
if (!$action) {
  $do_errors = [];

  $path->mergeErrors();
  $more_errors = (new InstanceError($path))->get('',true);
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
