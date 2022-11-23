<?php
namespace IO;
require_once "../../.appinit.php";
use \TymFrontiers\HTTP,
    \TymFrontiers\Data,
    \TymFrontiers\MultiForm,
    \TymFrontiers\InstanceError,
    \TymFrontiers\Generic;
use function \get_database;    
\header("Content-Type: application/json");
$post = \json_decode( \file_get_contents('php://input'), true); // json data
$post = !empty($post) ? $post : (
  !empty($_POST) ? $_POST : (
    !empty($_GET) ? $_GET : []
    )
);
$data = new Data;
$gen = new Generic;
$params = $gen->requestParam([
  "domain" => ["domain","username",3,98,[],'LOWER',['-','.', '_']],
  "format" => ["format","option",["json","xml"]]
], $post, []);
if (!$params || !empty($gen->errors)) {
  $errors = (new InstanceError($gen, false))->get("requestParam",true);
  echo \json_encode([
    "status" => "3." . \count($errors),
    "errors" => $errors,
    "message" => "Request halted"
  ]);
  exit;
}

$sidebar = [];
// $sidebar["spotlight"] = [
//   "title" => "Dashboard",
//   "subtitle" => "Admin Dashboard",
//   "path" => "/admin",
//   "cover" => "/admin/img/cover.jpg",
//   "avatar" => "/admin/img/avatar.jpg",
//   "links" => [
//     [
//       "title" => "Developers",
//       "path" => "/developer",
//       "onclick" => ""
//     ]
//   ]
// ];
$sidebar["sidenav"] = [];

// get spotlights from work domains
if (!empty($params['domain'])) {
  if ($spotlight = (new MultiForm(get_database(get_constant("PRJ_SERVER_NAME"), "admin"), "work_domains", "name"))
    ->findBySql("SELECT `path`, title, `description` AS subtitle,
                        CONCAT(`path`, cover_art) AS cover,
                        CONCAT(`path`, avatar) AS avatar
                FROM :db:.:tbl:
                WHERE `name` = '{$database->escapeValue($params['domain'])}'
                LIMIT 1")) {
    // proceed
    $spotlight = $spotlight[0];
    $sidebar["spotlight"] = [];
    $sidebar["spotlight"]["links"] = [];
    $sidebar["spotlight"]['title'] = $spotlight->title;
    $sidebar["spotlight"]['subtitle'] = $spotlight->subtitle;
    $sidebar["spotlight"]['path'] = $spotlight->path;
    $sidebar["spotlight"]['cover'] = $spotlight->cover;
    $sidebar["spotlight"]['avatar'] = $spotlight->avatar;
    // get links
    if ($lnks = (new MultiForm(get_database(get_constant("PRJ_SERVER_NAME"), "admin"), "work_domains", "name"))
      ->findBySql("SELECT title, `path`
                  FROM :db:.:tbl:
                  WHERE `name` <> '{$database->escapeValue($params['domain'])}'
                  AND `name` IN (
                    SELECT DISTINCT(domain)
                    FROM :db:.work_paths
                  )")) {
      // add spotlight links
      foreach ($lnks as $lnk) {
        $sidebar["spotlight"]["links"][] = [
          "title" => $lnk->title,
          "path" => $lnk->path,
          "onclick" => ""
        ];
      }
    }
  }
  // get domain paths
  if ($domain_paths = (new MultiForm(get_database(get_constant("PRJ_SERVER_NAME"), "admin"), "work_paths", "id"))
      ->findBySql("SELECT wkp.title, wkp.path, wkp.onclick, wkp.classname, wkp.icon,
                          CONCAT(wkd.`path`, wkp.`path`) AS full_path,
                          wkd.title AS domain_title
                FROM :db:.:tbl: AS wkp
                LEFT JOIN :db:.work_domains AS wkd ON wkd.`name` = wkp.domain
                WHERE wkp.`domain` = '{$database->escapeValue($params['domain'])}'
                AND wkp.nav_visible = TRUE 
                AND wkp.domain IN(
                  SELECT DISTINCT(domain)
                  FROM :db:.path_access
                  WHERE `user` = '{$session->name}'
                  AND access_scope NOT LIKE '%DENY%'
                )
                ORDER BY wkp.`sort` ASC")) {
    // add domain links
    $dmn_lnk = [
      "name" => $params['domain'],
      "title" => $spotlight->title,
      "links" => [] 
    ];
    foreach ($domain_paths as $lnk) {
      $dmn_lnk["links"][] = [
        "path"=> $lnk->full_path,
        "title" => $lnk->title,
        "newtab" => false,
        "icon" => "<i class=\"{$lnk->icon}\"></i>",
        "onclick" => $lnk->onclick,
        "name" => \str_replace(["/", "#"], "", $lnk->path),
        "classname" => $lnk->classname
      ];
    }
    // push it
    $sidebar["sidenav"][] = $dmn_lnk;
  }
}


// more links
$navs = \file_get_contents(get_constant("PRJ_ROOT") . "/.system/.navigation");
if ($navs && $navs = \json_decode($navs)) {
  foreach ($navs as $name => $navg) {
    $put_nav = [
      "name" => $name,
      "title" => $navg->title,
      "links" => []
    ];
    foreach ($navg->links as $nav) {
      if (
        ((bool)$nav->strict_access && $nav->access_rank == $session->access_rank())
        || (!(bool)$nav->strict_access && $nav->access_rank <= $session->access_rank())
      ) {
        // $nav->path = $path;
        unset($nav->strict_access);
        unset($nav->access_rank);
        $put_nav["links"][] = $nav; 
      }
    }
    $sidebar["sidenav"][] = $put_nav;
  }
}
$sidebar["footer"] = [
  "title" => get_constant("PRJ_TITLE"),
  "path" => WHOST,
  "copyright" => "&copy; " . \date("Y"),
  "license" => "All Rights Reserved",
  "terms" => [
    "title" => "Terms of Use",
    "icon" => "<i class='fas fa-gavel'></i>",
    "path" => "https://" . get_constant("PRJ_BASE_DOMAIN") . "/index/terms",
    "newtab" => true
  ],
  "policy" => [
    "title" => "Privacy Policy",
    "icon" => "<i class='fas fa-user-secrete'></i>",
    "path" => "https://" . get_constant("PRJ_BASE_DOMAIN") . "/index/policy",
    "newtab" => true
  ]
];

echo \json_encode([
  "status" => "0.0",
  "errors" => [],
  "message" => "Request was successful.",
  "data" => $sidebar
]);
exit;

