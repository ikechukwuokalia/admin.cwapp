<?php
namespace IO;
require_once "../.appinit.php";
use \TymFrontiers\Generic,
    \TymFrontiers\MultiForm,
    \TymFrontiers\InstanceError;

\header("Content-Type: application/json");
\require_login(false);
\check_access("WRITE", "/path-access", "project-admin","", false);

$post = $_POST;
$gen = new Generic;
$params = $gen->requestParam([
  "id"          => ["id","int"],
  "user" => ["user","pattern", "/^052(\s|\-|\.)?([\d]{4,4})(\s|\-|\.)?([\d]{4,4})$/"],
  "domain"      => ["domain","username",3,98,[],'LOWER',['-','.', '_']],
  "access_scope" => ["scope","script",3,128],
  
  "form" => ["form","text",2,72],
  "CSRF_token" => ["CSRF_token","text",5,1024]
],$post,["domain", "user", "access_scope", "form", "CSRF_token"]);

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
$db_name = get_database("admin", \IO\get_constant("PRJ_SERVER_NAME"));
unset($params["form"]);
unset($params["CSRF_token"]);

$params['type'] = "DOMAIN";
$params["user"] = $database->escapeValue(\str_replace(["-", " ", ".", "_"],"",$params["user"]));
$is_new = empty($params['id']);
// get domain path
if ($is_new) {
  if (!$domain = (new MultiForm($db_name, "work_domains", "name"))->findById($params["domain"])) {
    echo \json_encode([
      "status" => "3.1",
      "errors" => ["Domain info was not found"],
      "message" => "Request halted."
    ]);
    exit;
  } else {
    $params['path'] = $domain->path;
  }
}

$access = $is_new
  ? new MultiForm($db_name,'path_access','id', $database)
  : (new MultiForm($db_name,'path_access','id', $database))->findById($params['id']);
$access->empty_prop = [];
foreach ($params as $prop=>$value) {
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
