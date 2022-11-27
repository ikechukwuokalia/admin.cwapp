<?php
namespace IO;

use TymFrontiers\BetaTym;
use TymFrontiers\Data;
use \TymFrontiers\Generic,
    \TymFrontiers\MySQLDatabase,
    \TymFrontiers\InstanceError,
    \TymFrontiers\MultiForm;
use TymFrontiers\Validator;

require_once "../.appinit.php";
\check_access("READ", "/domain-settings", "project-admin","", false);

\header("Content-Type: application/json");

$post = !empty($_POST) ? $_POST : $_GET;

$gen = new Generic;
$params = $gen->requestParam([
  "domain" => ["domain","username",3,98,[],'LOWER',['-','.']],
  "search" => ["search","text",3,56],
  "page" =>["page","int",1,0],
  "limit" =>["limit","int",1,0],

  "form" => ["form","text",2,55],
  "CSRF_token" => ["CSRF_token","text",5,500]
], $post, ["domain", "CSRF_token", "form"] );

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
$server_name = domain_server($params["domain"]);
if (!$server_name) {
  echo \json_encode([
    "status" => "3.1",
    "errors" => ["No server found for given domain"],
    "message" => "Request failed."
  ]);
  exit;
}
if ($server_name !== get_constant("PRJ_SERVER_NAME")) {
  $new_conn = true;
  $cred = get_dbuser($server_name, $session->access_group());
  $conn = new MySQLDatabase(get_dbserver($server_name), $cred[0], $cred[1]);
} else {
  $new_conn = false;
  $conn = $database;
}
if (!$conn instanceof MySQLDatabase) {
  echo \json_encode([
    "status" => "4.1",
    "errors" => ["Failed to connect to server."],
    "message" => "Request failed."
  ]);
  exit;
}
$db_name = get_database("base", $server_name);
$conn->changeDB($db_name);
$count = 0;
$data = new MultiForm($db_name, 'settings', 'id', $conn);
$data->current_page = $page = (int)$params['page'] > 0 ? (int)$params['page'] : 1;
$qs = "SELECT stt.`id`, stt.`user`, stt.`skey`, stt.sval, stt._updated
      FROM :db:.:tbl: AS stt ";
$cnd = " WHERE stt.`user` LIKE '{$params['domain']}.%'";
if( !empty($params['search']) ){
  $params['search'] = \strtoupper($db->escapeValue(\strtolower($params['search'])));
  $cnd .= " AND (
    LOWER(stt.`user`) LIKE '%{$params['search']}'
    OR LOWER(stt.`user`) LIKE '{$params['search']}%'
    OR LOWER(stt.`skey`) = '{$params['search']}'
    OR LOWER(stt.`skey`) LIKE '%{$params['search']}%'
  ) ";
}

$count = $data->findBySql("SELECT COUNT(*) AS cnt FROM :db:.:tbl: AS stt {$cnd} ");
// echo $db->last_query;
$count = $data->total_count = $count ? $count[0]->cnt : 0;

$data->per_page = $limit = (int)$params['limit'] > 0 ? (int)$params['limit'] : 25;
$qs .= $cnd;
$sort = " ORDER BY stt.`user` ASC ";

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
if ($new_conn) $conn->closeConnection();
// $q_conn = query_conn(\IO\get_constant("PRJ_SERVER_NAME"));
$enc_list = [];
$enc_names = [];
foreach ($found as $f) {
  $enc_names[] = $f->skey;
} if (!empty($enc_names)) {
  $qs = "SELECT `name`, `encrypt`, `title` FROM :db:.:tbl: WHERE `name` IN ('" . \implode("','", $enc_names) . "')";
  if ($qf = (new MultiForm(get_database("data", \IO\get_constant("PRJ_SERVER_NAME")), "setting_options", "id", $database))->findBySql($qs)) {
    foreach ($qf as $f) {
      $enc_list[$f->name] = [
        "encrypt" => (bool)$f->encrypt,
        "title" => $f->title
      ];
    }
  }
}
// echo "<tt><pre>";
// print_r($enc_list);
// echo "</pre></tt>";
// exit;

$tym = new BetaTym;
$data_obj = new Data;
foreach ($found as $stt) {
  // try to decrypt
  if ( \array_key_exists($stt->skey, $enc_list) && (bool)$enc_list[$stt->skey]["encrypt"]) {
    try {
      if ($decrypted = @ $data_obj->decodeDecrypt($stt->sval, encKey(domain_server($params['domain'])))) {
        $stt->sval = $decrypted;
      }
    } catch (\Throwable $th) {
      //throw $th;
      $result["message"] .= " | Decrypting [{$stt->skey}] failed: {$th->getMessage()}";
    }
  }
  $usr = \explode("{$params['domain']}.", $stt->user)[1];
  $result["settings"][] = [
    "id" => (int)$stt->id,
    "domain" => $params['domain'],
    "user" => ($usr == "SYSTEM" ? $usr : code_split($usr, " ")) ,
    "key" => $stt->skey,
    "value" => $stt->sval,
    "title" => @ $enc_list[$stt->skey]["title"],
    "updated" => $tym->MDY($stt->updated()),
  ];
}
echo \json_encode($result);
exit;
