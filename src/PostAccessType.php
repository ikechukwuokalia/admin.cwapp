<?php
namespace IO;
require_once "../.appinit.php";
use \TymFrontiers\Generic,
    \TymFrontiers\MultiForm,
    \TymFrontiers\InstanceError;

\header("Content-Type: application/json");
\require_login(false);
\check_access("WRITE", "/access-types", "project-admin","", false);

$post = $_POST;
$gen = new Generic;
$params = $gen->requestParam([
  "name"          => ["name","username", 2, 21],
  "rank" => ["rank","int"],
  "scope" => ["scope","text",2,256],
  "title" => ["title","text",3,48],
  "description" => ["description","text",5,256],
  "task" => ["task", "option", ["CREATE", "UPDATE"]],
  "form" => ["form","text",2,72],
  "CSRF_token" => ["CSRF_token","text",5,1024]
],$post,["name", "description", "form", "CSRF_token"]);

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

$is_new = $params['task'] == 'CREATE';
$scope = $is_new
  ? new MultiForm($db_name,'access_types','name', $database)
  : (new MultiForm($db_name,'access_types','name', $database))->findById($params['name']);
$scope->empty_prop = ["rank"];
$scope->empty_prop = ["scope"];
$scope->name = $params['name'];
$scope->title = $params['title'];
$scope->rank = $params['rank'];
$scope->scope = $params['scope'];
$scope->description = $params['description'];
$action = $is_new
  ? $scope->create()
  : $scope->update();
if (!$action) {
  $do_errors = [];

  $scope->mergeErrors();
  $more_errors = (new InstanceError($scope))->get('',true);
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
