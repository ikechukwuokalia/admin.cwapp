<?php
namespace IO;
require_once "../.appinit.php";
use \TymFrontiers\Generic,
    \TymFrontiers\MultiForm,
    \TymFrontiers\InstanceError;

\header("Content-Type: application/json");
\require_login(false);
\check_access("WRITE", "/path-access", "project-admin","", false);
$post = \json_decode( \file_get_contents('php://input'), true); // json data
// echo "<tt><pre>";
// echo \gettype($post);
// echo \gettype($post["access"]);
// print_r($post);
// echo "</pre></tt>";
// exit;
$gen = new Generic;
$params = $gen->requestParam([
  "domain"      => ["domain","username",3,98,[],'LOWER',['-','.', '_']],
  "user" => ["user","pattern", "/^052(\s|\-|\.)?([\d]{4,4})(\s|\-|\.)?([\d]{4,4})$/"],
  
  "form" => ["form","text",2,72],
  "CSRF_token" => ["CSRF_token","text",5,1024]
],$post,["domain", "user", "form", "CSRF_token"]);

if (!$params || !empty($gen->errors)) {
  $errors = (new InstanceError($gen,true))->get("requestParam",true);
  echo \json_encode([
    "status" => "3." . \count($errors),
    "errors" => $errors,
    "message" => "Request halted"
  ]);
  exit;
}
if ( !$gen->checkCSRF($params["form"],$params["CSRF_token"]) ) {
  $errors = (new InstanceError($gen,true))->get("checkCSRF",true);
  echo \json_encode([
    "status" => "3." . \count($errors),
    "errors" => $errors,
    "message" => "Request halted."
  ]);
  exit;
}
if (empty($post['access']) || \gettype($post["access"]) !== "array") {
  echo \json_encode([
    "status" => "3.1",
    "errors" => ["Invalid set of access list"],
    "message" => "Request halted."
  ]);
  exit;
}
include PRJ_ROOT . "/src/Pre-Process.php";
$db_name = get_database(\IO\get_constant("PRJ_SERVER_NAME"), "admin");
unset($params["form"]);
unset($params["CSRF_token"]);

$params["user"] = \str_replace(["-", " ", ".", "_"],"",$params["user"]);
// find exisiting
$existing = [];
if ($found  = (new MultiForm($db_name, "path_access", "id", $database))->findBySql("SELECT * FROM :db:.:tbl: WHERE `domain` = '{$params['domain']}' AND `user` = '{$params['user']}' AND `type` = 'PATH'")) {
  foreach ($found as $pth) {
    if (\array_key_exists($pth->path, $post['access'])) $existing[$pth->path] = ["id"=> $pth->id, "scope" => $pth->access_scope];
  }
}
$new = [];
foreach ($post['access'] as $name => $scope) {
  if (\array_key_exists($name, $existing)) {
    $existing[$name]["scope"] = $scope;
  } else {
    $new[$name] = $scope;
  }
}
// run new first
if (!empty($new)) {
  $iq = "INSERT INTO `{$db_name}`.`path_access` (`domain`, `user`, `type`, `path`, `access_scope`, `_author`) VALUES ";
  $iq_r = [];
  foreach ($new as $name => $scope) {
    $iq_r[] = "(
      '{$params['domain']}',
      '{$params['user']}',
      'PATH',
      '{$database->escapeValue($name)}',
      '{$database->escapeValue($scope)}',
      '{$session->name}'
    )";
  }
  $iq .= \implode(", ", $iq_r);
  if (!$database->query($iq)) {
    $do_errors = [$db->lastQuery()];
    $more_errors = (new InstanceError($database, true))->get('',true);
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
}
if (!empty($existing)) {
  // do updates
  $uq = [];
  foreach ($existing as $path => $prop) {
    $uq[] = "UPDATE `{$db_name}`.path_access SET access_scope = '{$database->escapeValue($prop['scope'])}' WHERE id = {$prop['id']}";
  }
  if (!empty($uq) && !$database->multiQuery(\implode("; ", $uq))) {
    $do_errors = [];
    $more_errors = (new InstanceError($database, true))->get('',true);
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
} 


echo \json_encode([
  "status" => "0.0",
  "errors" => [],
  "message" => "Request was successful!"
]);
exit;
