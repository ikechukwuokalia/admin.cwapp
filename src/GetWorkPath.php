<?php
namespace IO;

use TymFrontiers\BetaTym;
use TymFrontiers\Data;
use \TymFrontiers\Generic,
    \TymFrontiers\InstanceError,
    \TymFrontiers\MultiForm;
use TymFrontiers\Validator;

require_once "../.appinit.php";
\check_access("READ", "/work-paths", "project-admin","", false);

\header("Content-Type: application/json");

$post = !empty($_POST) ? $_POST : $_GET;

$gen = new Generic;
$params = $gen->requestParam([
  "id" => ["id","int"],
  "access_scope" => ["access_scope","text",3,128],
  "domain" => ["domain","username",3,98,[],'LOWER',['-','.']],
  "resource_type" => ["resource_type","username",3,98,[],'LOWER',['-']],
  "search" => ["search","text",3,56],
  "page" =>["page","int",1,0],
  "limit" =>["limit","int",1,0],

  "form" => ["form","text",2,55],
  "CSRF_token" => ["CSRF_token","text",5,500]
], $post, ["CSRF_token", "form"] );

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
$server_name = "BASE";
$data_db = get_database($server_name, "admin");
$dt_db = get_database(\IO\get_constant("PRJ_SERVER_NAME"), "data");
$count = 0;
$data = new MultiForm($data_db, 'work_paths', 'id', $database);
$data->current_page = $page = (int)$params['page'] > 0 ? (int)$params['page'] : 1;
$qs = "SELECT wpt.`id`, wpt.domain, wpt.`access_scope`, wpt.resource_type, wpt.icon, wpt.`path`,
              wpt.nav_visible, wpt.onclick, wpt.classname, wpt.`sort`, 
              wpt.title, wpt.`description`, wpt._created,
              dmn.`path` AS domain_path,
              tg.title AS resource_type_title
      FROM :db:.:tbl: AS wpt 
      LEFT JOIN :db:.work_domains AS dmn ON dmn.`name` = wpt.`domain` 
      LEFT JOIN `{$dt_db}`.resource_types AS tg ON tg.`name` = wpt.resource_type ";
$cnd = " WHERE 1=1 ";
if (!empty($params['id'])) {
  $cnd .= " AND wpt.`id` = {$params['id']} ";
} else {
  if (!empty($params['domain'])) {
    $cnd .= " AND wpt.`domain` = '{$params['domain']}' ";
  } if (!empty($params['access_scope'])) {
    $scope_r = [];
    foreach (\explode(",",$params['access_scope']) as $scope) {
      $scope = \trim(\strtoupper($scope));
      $scope_r[] = "wpt.access_scope LIKE '%{$database->escapeValue($scope)}%' ";
    }
    if (!empty($scope_r)) {
      $cnd .= " AND (".\implode(" OR ", $scope_r). ") ";
    }
  } if (!empty($params['resource_type'])) {
    $cnd .= " AND wpt.`resource_type` = '{$database->escapeValue($params['resource_type'])}' ";
  }
  if( !empty($params['search']) ){
    $params['search'] = \strtoupper($db->escapeValue(\strtolower($params['search'])));
    $cnd .= " AND (
      wpt.`id` = '{$params['search']}'
      OR wpt.`path` LIKE '%{$params['search']}%'
    ) ";
  }
}

$count = $data->findBySql("SELECT COUNT(*) AS cnt FROM :db:.:tbl: AS wpt {$cnd} ");
// echo $db->last_query;
$count = $data->total_count = $count ? $count[0]->cnt : 0;

$data->per_page = $limit = !empty($params['id']) ? 1 : (
    (int)$params['limit'] > 0 ? (int)$params['limit'] : 25
  );
$qs .= $cnd;
$sort = " ORDER BY wpt.`domain` ASC, wpt.`sort` ASC, wpt._created DESC ";

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
foreach ($found as $wpt) {
  $result["paths"][] = [
    "id" => (int)$wpt->id,
    "domain" => $wpt->domain,
    "domainPath" => $wpt->domain_path,
    "accessScope" => (empty($wpt->access_scope) ? [] : \explode(",", $wpt->access_scope)),
    "path" => $wpt->path,
    "resourceType" => $wpt->resource_type,
    "resourceTypeTitle" => $wpt->resource_type_title,
    "navVisible" => (bool)$wpt->nav_visible,
    "onclick" => $wpt->onclick,
    "classname" => $wpt->classname,
    "title" => $wpt->title,
    "icon" => $wpt->icon,
    "sort" => (int)$wpt->sort,
    "description" => $wpt->description,
    "created" => $tym->MDY($wpt->created()),
    "createdDate" => $wpt->created()
  ];
}
echo \json_encode($result);
exit;
