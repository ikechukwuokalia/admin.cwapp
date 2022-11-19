<?php
namespace IO;

use TymFrontiers\Data;
use \TymFrontiers\Generic,
    \TymFrontiers\InstanceError,
    \TymFrontiers\MultiForm;
use TymFrontiers\Validator;

require_once "../.appinit.php";
\check_access("READ", "/setting-options", "project-admin","", false);

\header("Content-Type: application/json");

$post = !empty($_POST) ? $_POST : $_GET;

$gen = new Generic;
$params = $gen->requestParam([
  "id" => ["id","int", 1,0],
  "disabled" => ["disabled","boolean"],
  "name" => ["name","username",3,72, [], "UPPER", ["-", "."]],
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
$data_db = get_database($server_name, "data");
$count = 0;
$data = new MultiForm($data_db, 'setting_options', 'id', $database);
$data->current_page = $page = (int)$params['page'] > 0 ? (int)$params['page'] : 1;
$qs = "SELECT opt.id, opt.`enabled`, opt.`encrypt`, opt.`name`, opt.multi_val, opt.`type`, 
              opt.`variant`, opt.title, opt.description, opt._updated
      FROM :db:.:tbl: AS opt ";
$cnd = " WHERE 1=1 ";
if (!(bool)$params['disabled']) {
  $cnd .= " AND opt.`enabled` = TRUE ";
}
if (!empty($params['id'])) {
  $cnd .= " AND opt.`id` = {$params['id']} ";
} else {
  if( !empty($params['search']) ){
    $params['search'] = \strtoupper($db->escapeValue(\strtolower($params['search'])));
    $cnd .= " AND (
      opt.`id` = '{$params['search']}'
      OR opt.`name` LIKE '%{$params['search']}%'
    ) ";
  }
}

$count = $data->findBySql("SELECT COUNT(*) AS cnt FROM :db:.:tbl: AS opt {$cnd} ");
// echo $db->last_query;
$count = $data->total_count = $count ? $count[0]->cnt : 0;

$data->per_page = $limit = !empty($params['id']) ? 1 : (
    (int)$params['limit'] > 0 ? (int)$params['limit'] : 25
  );
$qs .= $cnd;
$sort = " ORDER BY opt.`name` ASC ";

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

$valid = new Validator;
foreach ($found as $opt) {
  $result["options"][] = [
    "id" => (int)$opt->id,
    "enabled" => (bool)$opt->enabled,
    "encrypt" => (bool)$opt->encrypt,
    "name" => $opt->name,
    "multi_val" => (bool)$opt->multi_val,
    "type" => $opt->type,
    "type_title" => $valid->validate_type[$opt->type],
    "variant" => $opt->variant,
    "title" => $opt->title,
    "description" => $opt->description
  ];
}
echo \json_encode($result);
exit;
