<?php
namespace IO;

use TymFrontiers\BetaTym;
use TymFrontiers\Data;
use \TymFrontiers\Generic,
    \TymFrontiers\InstanceError,
    \TymFrontiers\MultiForm;
use TymFrontiers\Validator;

require_once "../.appinit.php";
\check_access("READ", "/path-access", "project-admin","", false);

\header("Content-Type: application/json");

$post = !empty($_POST) ? $_POST : $_GET;

$gen = new Generic;
$params = $gen->requestParam([
  "id" => ["id","int"],
  "user" => ["user","pattern", "/^052(\s|\-|\.)?([\d]{4,4})(\s|\-|\.)?([\d]{4,4})$/"],
  "domain" => ["domain","username",3,98,[],'LOWER',['-','.']],
  "path" => ["path","username",3,98,[],'LOWER',['-','/']],
  "search" => ["search","text",3,56],
  "page" =>["page","int",1,0],
  "limit" =>["limit","int",1,0],

  "form" => ["form","text",2,55],
  "CSRF_token" => ["CSRF_token","text",5,500]
], $post, ["domain","CSRF_token", "form"] );

if (!$params || !empty($gen->errors)) {
  $errors = (new InstanceError ($gen, false))->get("requestParam",true);
  echo \json_encode([
    "status" => "3." . \count($errors),
    "errors" => $errors,
    "message" => "Request halted"
  ]);
  exit;
}
if ( !$gen->checkCSRF($params["form"],$params["CSRF_token"]) ) {
  $errors = (new InstanceError ($gen,false))->get("checkCSRF",true);
  echo \json_encode([
    "status" => "3." . \count($errors),
    "errors" => $errors,
    "message" => "Request failed."
  ]);
  exit;
}
if (!empty($params["user"])) $params["user"] = \str_replace(["-", " ", ".", "_"],"",$params["user"]);
$server_name = "BASE";
$data_db = get_database($server_name, "admin");
$dt_db = get_database(\IO\get_constant("PRJ_SERVER_NAME"), "data");
$count = 0;
$data = new MultiForm($data_db, 'path_access', 'id', $database);
$data->current_page = $page = (int)$params['page'] > 0 ? (int)$params['page'] : 1;
$qs = "SELECT pta.`id`, pta.domain, pta.`user`, pta.`access_scope`, pta.`type`, pta.`path`, pta._created,
              dmn.`path` AS domain_path,
              CONCAT(auth.name, ' ', auth.surname) AS author,
              CONCAT(usr.name, ' ', usr.surname) AS 'user_name'
      FROM :db:.:tbl: AS pta 
      LEFT JOIN :db:.work_domains AS dmn ON dmn.`name` = pta.`domain` 
      LEFT JOIN :db:.users AS auth ON auth.`code` = pta.`_author` 
      LEFT JOIN :db:.users AS usr ON usr.`code` = pta.`user` ";
$cnd = " WHERE 1=1 ";
if (!empty($params['id'])) {
  $cnd .= " AND pta.`id` = {$params['id']} ";
} else {
  if (!empty($params['domain'])) {
    $cnd .= " AND pta.`domain` = '{$params['domain']}' ";
  } if (!empty($params['path'])) {
    $cnd .= " AND pta.`path` = '{$database->escapeValue($params['path'])}' ";
  }
  if( !empty($params['search']) ){
    $params['search'] = \strtoupper($db->escapeValue(\strtolower($params['search'])));
    $cnd .= " AND (
      pta.`id` = '{$params['search']}'
      OR pta.`user` = '{$params['search']}'
      OR pta.`user` LIKE '%{$params['search']}%'
      OR pta.`path` LIKE '%{$params['search']}%'
    ) ";
  }
}

$count = $data->findBySql("SELECT COUNT(*) AS cnt FROM :db:.:tbl: AS pta {$cnd} ");
// echo $db->last_query;
$count = $data->total_count = $count ? $count[0]->cnt : 0;

$data->per_page = $limit = !empty($params['id']) ? 1 : (
    (int)$params['limit'] > 0 ? (int)$params['limit'] : 25
  );
$qs .= $cnd;
$sort = " ORDER BY pta.`domain` ASC, pta._created DESC ";

$qs .= $sort;
$qs .= " LIMIT {$data->per_page} ";
$qs .= " OFFSET {$data->offset()}";

$found = $data->findBySql($qs);
$tym = new \TymFrontiers\BetaTym;

if( !$found ){
  die( \json_encode([
    "message" => "No result found for your query.",
    "errors" => [],
    "status" => "0.2"
    ]) );
}
$result = [
  'status' => '0.0',
  'errors' => [],
  'message' => 'Request completed',
  'records' => (int)$count,
  'page'  => $data->current_page,
  'pages' => $data->totalPages(),
  'limit' => $limit,
  'previousPage' => $data->hasPreviousPage() ? $data->previousPage() : false,
  'nextPage' => $data->hasNextPage() ? $data->nextPage() : false
];

$tym = new BetaTym;
foreach ($found as $pta) {
  $result["paths"][] = [
    "id" => (int)$pta->id,
    "domain" => $pta->domain,
    "type" => $pta->type,
    "user" => $pta->user,
    "userName" => $pta->user_name,
    "userSplit" => code_split($pta->user, ' '),
    "domainPath" => $pta->domain_path,
    "accessScope" => (empty($pta->access_scope) ? [] : \explode(",", $pta->access_scope)),
    "path" => $pta->path,
    "author" => $pta->author,
    "created" => $tym->MDY($pta->created()),
    "createdDate" => $pta->created()
  ];
}
echo \json_encode($result);
exit;
