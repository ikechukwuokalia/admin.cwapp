<?php
namespace IO;
require_once "../../.appinit.php";
use \TymFrontiers\HTTP,
    \TymFrontiers\Generic,
    \TymFrontiers\Data,
    \TymFrontiers\MultiForm,
    \TymFrontiers\InstanceError;
use function \get_database;
\header("Content-Type: application/json");
$post = !empty($_POST) ? $_POST : $_GET;
$gen = new Generic;
$params = $gen->requestParam([
  "group" => ["group","text",3,56],
  "format" => ["format","option", ["json"]]
], $post, ["group"]);
if (!$params || !empty($gen->errors)) {
  $errors = (new InstanceError ($gen, false))->get("requestParam",true);
  echo \json_encode([
    "status" => "3." . \count($errors),
    "errors" => $errors,
    "message" => "Request halted"
  ]);
  exit;
}

$params["user"] = $session->name;
$navigation = [];
$nav_group = $params['group'];
// check for domain matching group
if ($domain_paths = (new MultiForm(get_database("admin", get_constant("PRJ_SERVER_NAME")), "work_paths", "id"))
->findBySql("SELECT wkp.title, wkp.path, wkp.onclick, wkp.classname, wkp.icon,
                    CONCAT(wkd.`path`, wkp.`path`) AS full_path
          FROM :db:.:tbl: AS wkp
          LEFT JOIN :db:.work_domains AS wkd ON wkd.`name` = wkp.domain
          WHERE wkp.`domain` = '{$database->escapeValue($params['group'])}'
          AND wkp.nav_visible = TRUE 
          AND wkp.domain IN(
            SELECT DISTINCT(domain)
            FROM :db:.path_access
            WHERE `user` = '{$session->name}'
            AND access_scope NOT LIKE '%DENY%'
          )
          ORDER BY wkp.`sort` ASC")) {
  // add domain links
  foreach ($domain_paths as $lnk) {
    $navigation[$nav_group][] = [
      "path"=> $lnk->full_path,
      "title" => $lnk->title,
      "newtab" => false,
      "icon" => "<i class=\"{$lnk->icon}\"></i>",
      "onclick" => $lnk->onclick,
      "name" => \str_replace(["/", "#"], "", $lnk->path),
      "classname" => $lnk->classname
    ];
  }
} else {
  $navs = \file_get_contents(get_constant("PRJ_ROOT") . "/.system/.navigation");
  if ($navs && $navs = \json_decode($navs)) {
    if (!empty($navs->$nav_group)) {
      foreach ($navs->$nav_group->links as $nav) {
        if (
          ((bool)$nav->strict_access && $nav->access_rank == $session->access_rank())
          || (!(bool)$nav->strict_access && $nav->access_rank <= $session->access_rank())
        ) {
          // $nav->path = $path;
          unset($nav->strict_access);
          unset($nav->access_rank);
          $navigation[$nav_group][] = $nav; 
        }
      }
    }
  }
}

if (empty($navigation[$params['group']])) {
  die( \json_encode([
    "message" => "No result found.",
    "errors" => [],
    "status" => "0.2"
    ]) );
}
$result = [];
$result["message"] = "Request completed.";
$result["errors"] = [];
$result["status"] = "0.0";
$result["data"] = $navigation[$params['group']];
echo \json_encode($result);
exit;