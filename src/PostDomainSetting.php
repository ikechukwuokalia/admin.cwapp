<?php
namespace IO;
require_once "../.appinit.php";
use \TymFrontiers\Generic,
    \TymFrontiers\MultiForm,
    \TymFrontiers\MySQLDatabase,
    \TymFrontiers\InstanceError;

\header("Content-Type: application/json");
\require_login(false);
\check_access("WRITE", "/domain-settings", "project-admin","", false);

$post = $_POST;
$gen = new Generic;
$rqp = [
  "id"    => ["id","int",1,0],
  "user"    => ["user","pattern", "/^(SYSTEM|(([\d]{3,3})([\-|\s]{1,1})?([\d]{4,4})([\-|\s]{1,1})?([\d]{4,4})))$/"],
  "domain"  => ["domain","username",3,128,[],'LOWER',['-','.']],
  "key"    => ["key","username",3,32,[],'UPPER',['-','.']],

  "form" => ["form","text",2,72],
  "CSRF_token" => ["CSRF_token","text",5,1024]
];
$rqd = ["user", "domain", "key", "value", "form", "CSRF_token"];

$option = null;
$variant = null;
if ( !empty($post) && !empty($post['key']) ):
  $option = setting_option ($post['key']);
  if ($option && !empty($option) && !empty($option['variant'])) {
    $variant = setting_variant($option['variant']);
  }
  if ($option) {
    $filt_arr = ["value", $option['type']];
    if ($option['type'] == "pattern") {
     if (empty($variant["pattern"])) {
       echo \json_encode([
         "status" => "3.1",
         "errors" => ["No pre-set [pattern], contact Developer"],
         "message" => "Request failed"
       ]);
       exit;
     }
     $filt_arr[2] = $variant["pattern"];
     $rqp["value"] = $filt_arr;
    } else if (\in_array($option['type'], ["username","text","html","markdown","mixed","script","date","time","datetime","int","float"])) {
      $filt_arr[2] = !empty($variant["minval"]) ? (int)$variant["minval"] : (!empty($variant["minlen"]) ? (int)$variant["minlen"] : 0);
      $filt_arr[3] = !empty($variant["maxval"]) ? (int)$variant["maxval"] : (!empty($variant["maxlen"]) ? (int)$variant["maxlen"] : 0);
    } if ($option['type'] == "option" && !empty($variant["optiontype"]) && $variant["optiontype"]=="checkbox") {
      $filt_arr[1] = "text";
      $filt_arr[2] = 3;
      $filt_arr[3] = 127;
    } if ($option['type'] == "option" && !empty($variant["optiontype"]) && $variant["optiontype"]=="radio") {
      if (empty($variant["options"])) {
        echo \json_encode([
          "status" => "3.1",
          "errors" => ["No pre-set options, contact Developer"],
          "message" => "Request failed"
        ]);
        exit;
      }
      $filt_arr[2] = $variant["options"];
    }
    $rqp["value"] = $filt_arr;
  } else {
    echo \json_encode([
      "status" => "3.1",
      "errors" => ["Setting option not found for {$params['key']}"],
      "message" => "Request failed"
    ]);
    exit;
  }
endif;

$params = $gen->requestParam($rqp, $post, $rqd);

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
// find setting from doamin/server
$server_name = domain_server($params["domain"]);
if (!$server_name) {
  echo \json_encode([
    "status" => "4.1",
    "errors" => ["No server found for given domain."],
    "message" => "Request failed."
  ]);
  exit;
}
$params['user'] = \str_replace(["-", " ", "."], "", $params['user']);
// echo "<tt><pre>";
// print_r($params);
// echo "</pre></tt>";
// exit;
include PRJ_ROOT . "/src/Pre-Process.php";
try {
  $is_set = setting_set_value($params['user'], $params["key"], $params["value"], $params["domain"]);
} catch (\Exception $e) {
  echo \json_encode([
    "status" => "4.1",
    "errors" => [$e->getMessage()],
    "message" => "Request failed."
  ]);
  exit;
}

echo \json_encode([
  "status" => "0.0",
  "errors" => [],
  "message" => "Request was successful!"
]);
exit;
