<?php
namespace IO;

use TymFrontiers\BetaTym;
use \TymFrontiers\Generic,
    \TymFrontiers\InstanceError,
    \TymFrontiers\MultiForm;
use TymFrontiers\Validator;

require_once "../.appinit.php";
\check_access("READ", "/resource-types", "project-admin","", false);

\header("Content-Type: application/json");

$post = !empty($_POST) ? $_POST : $_GET;

$gen = new Generic;
$params = $gen->requestParam([
  "id" => ["id","int"],
  "keyword" => ["keyword","username", 3, 21, [], "LOWER", ['-']],
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
$data = new MultiForm($data_db, 'reserved_keywords', 'id', $database);
$data->current_page = $page = (int)$params['page'] > 0 ? (int)$params['page'] : 1;
$qs = "SELECT kwd.id, kwd.`keyword`, kwd.type, kwd._created
      FROM :db:.:tbl: AS kwd  ";
$cnd = " WHERE 1=1 ";
if (!empty($params['id'])) {
  $cnd .= " AND kwd.`id` = {$params['id']} ";
} else {
  if (!empty($params['keyword'])) {
    $cnd .= " AND kwd.`keyword` = '{$params['keyword']}' ";
  }
  if( !empty($params['search']) ){
    $params['search'] = \strtolower($db->escapeValue(\strtolower($params['search'])));
    $cnd .= " AND (
      kwd.`id` = '{$params['search']}'
      OR kwd.`keyword` = '{$params['search']}'
      OR kwd.`keyword` LIKE '%{$params['search']}%'
    ) ";
  }
}

$count = $data->findBySql("SELECT COUNT(*) AS cnt FROM :db:.:tbl: AS kwd {$cnd} ");
// echo $db->last_query;
$count = $data->total_count = $count ? $count[0]->cnt : 0;

$data->per_page = $limit = !empty($params['id']) ? 1 : (
    (int)$params['limit'] > 0 ? (int)$params['limit'] : 25
  );
$qs .= $cnd;
$sort = " ORDER BY kwd.`keyword` ASC ";

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
foreach ($found as $kwd) {
  $result["keywords"][] = [
    "id" => (int)$kwd->id,
    "keyword" => $kwd->keyword,
    "type" => $kwd->type,
    "created" => $tym->MDY($kwd->created()),
    "created_date" => $kwd->created()
  ];
}
echo \json_encode($result);
exit;
