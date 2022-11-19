<?php
namespace IO;
require_once "../.appinit.php";
use \TymFrontiers\Generic,
    \TymFrontiers\MultiForm,
    \TymFrontiers\InstanceError;

\header("Content-Type: application/json");
\require_login(false);
\check_access("WRITE", "/setting-options", "project-admin","", false);

$post = $_POST;
$gen = new Generic;
$params = $gen->requestParam([
  "id"    => ["id","int",1,0],
  "name"    => ["name","username",3,32,[],'UPPER',['-','.']],
  "domain"  => ["domain","username",3,128,[],'LOWER',['-','.']],
  "multi_val"  => ["multi_val","boolean"],
  "encrypt"  => ["encrypt","boolean"],
  "type" => ["type","option", \array_keys((new \TymFrontiers\Validator)->validate_type)],
  "variant" => ["variant","text",5,512],
  "title" => ["title","text",3,52],
  "description" => ["description","text",5,256],

  "form" => ["form","text",2,72],
  "CSRF_token" => ["CSRF_token","text",5,1024]
],$post,["name", "type", "title", "description", "form", "CSRF_token"]);

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
$is_new = empty($params['id']);
if ($is_new && (new MultiForm($db_name, 'setting_options', 'id', $database))->findBySql("SELECT id FROM :db:.:tbl: WHERE `name`='{$database->escapeValue($params['name'])}' LIMIT 1")){
  echo \json_encode([
    "status" => "3.1",
    "errors" => ["Duplicate value for name/key."],
    "message" => "Request halted."
  ]);
  exit;
}
include PRJ_ROOT . "/src/Pre-Process.php";
$option = !$is_new
  ? (new MultiForm($db_name, 'setting_options', 'id', $database))->findById($params['id'])
  : new MultiForm($db_name, 'setting_options', 'id', $database);

if ( !$option ) {
  echo \json_encode([
    "status" => "3.1",
    "errors" => ["Option -type with ID: '{$params['id']}' not found."],
    "message" => "Request halted."
  ]);
  exit;
}
foreach ($params as $k=>$v) {
  if (!empty($v)) $option->$k = $v;
}
$option->multi_val = (bool)$params['multi_val'] ? 1 : 0;
$option->encrypt = (bool)$params['encrypt'] ? 1 : 0;
if (!$option->save()) {
  $do_errors = [];

  $option->mergeErrors();
  $more_errors = (new InstanceError($option,true))->get('',true);
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
