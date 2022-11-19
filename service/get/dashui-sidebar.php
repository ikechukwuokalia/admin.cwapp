<?php
namespace IO;
require_once "../../.appinit.php";
use \TymFrontiers\HTTP,
    \TymFrontiers\Data,
    \TymFrontiers\MultiForm,
    \TymFrontiers\InstanceError,
    \TymFrontiers\Generic;
    
\header("Content-Type: application/json");
$post = \json_decode( \file_get_contents('php://input'), true); // json data
$post = !empty($post) ? $post : (
  !empty($_POST) ? $_POST : (
    !empty($_GET) ? $_GET : []
    )
);
$gen = new Generic;
$params = $gen->requestParam([
  // "domain" => ["domain", "pattern", '/^(?:[-A-Za-z0-9]+\.)+[A-Za-z]{2,6}$/m'],
  "domain" => ["domain", "text", 2, 128],
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
$sidebar["spotlight"] = [
  "title" => "Dashboard",
  "subtitle" => "Admin Dashboard",
  "path" => "/admin",
  "cover" => "/admin/img/cover.jpg",
  "avatar" => "/admin/img/avatar.jpg",
  "links" => [
    [
      "title" => "Developers",
      "path" => "/developer",
      "onclick" => ""
    ]
  ]
];
$sidebar["sidenav"] = [
];

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

