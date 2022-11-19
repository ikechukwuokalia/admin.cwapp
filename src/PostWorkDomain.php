<?php
namespace IO;
require_once "../.appinit.php";
use \TymFrontiers\Generic,
    \TymFrontiers\MultiForm,
    \TymFrontiers\InstanceError;

\header("Content-Type: application/json");
\require_login(false);
\check_access("WRITE", "/work-domains", "project-admin","", false);

$post = $_POST;
$gen = new Generic;
$params = $gen->requestParam([
  "name"          => ["name","username",3,98,[],'LOWER',['-','.', '_']],
  "acronym"          => ["acronym","username",3,16],
  "task"          => ["task","option",['CREATE','UPDATE']],
  "path" => ["path","text",1,72],
  "avatar" => ["avatar","text",5,256],
  "cover_art" => ["cover_art","text",5,256],
  "title" => ["title","text",5,56],
  "description" => ["description","text",15,128],

  "form" => ["form","text",2,72],
  "CSRF_token" => ["CSRF_token","text",5,1024]
],$post,["name", "title", "acronym", "task", "path", "description", "form", "CSRF_token"]);

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
$db_name = get_database(\IO\get_constant("PRJ_SERVER_NAME"), "admin");

$is_new = $params['task'] == 'CREATE';
if ($is_new && (new MultiForm($db_name, 'work_domains', 'name', $database))->findBySql("SELECT * FROM :db:.:tbl: WHERE `name`='{$params['name']}' OR `acronym`='{$params['acronym']}' LIMIT 1")) {
  echo \json_encode([
    "status" => "3.1",
    "errors" => ["Domain [name] or [acronym] already exist."],
    "message" => "Request halted."
  ]);
  exit;
}
$domain = $is_new
  ? new MultiForm($db_name,'work_domains','name', $database)
  : (new MultiForm($db_name,'work_domains','name', $database))->findById($params['name']);
$domain->empty_prop = ["avatar", "cover_art"];
unset($params['form']);
unset($params['CSRF_token']);
foreach ($params as $prop=>$value) {
  $domain->$prop = $value;
}
$action = $is_new
  ? $domain->create()
  : $domain->update();
if (!$action) {
  $do_errors = [];

  $domain->mergeErrors();
  $more_errors = (new InstanceError($domain))->get('',true);
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
