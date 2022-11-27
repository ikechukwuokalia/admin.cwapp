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
  "domain" => ["domain", "pattern", '/^(?:[-A-Za-z0-9]+\.)+[A-Za-z]{2,6}$/m'],
  "rdt" => ["rdt","url"],
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
$header = [
  "title" => get_constant("PRJ_TITLE"),
  "path" => "/admin",
  "logo" => "/assets/img/bosh-admin-white.png",
  "user" => [
    "avatar"      => $session->isLoggedIn() ? $session->user->avatar : "/app/admin/img/default-avatar.png",
    "title"       => $session->isLoggedIn() ? $session->user->name : "Welcome",
    "subtitle"    => $session->isLoggedIn() ? "Profile Code: ". code_split($session->name, " ") : "",
    "description" => $session->isLoggedIn() ? "{$session->user->name} {$session->user->surname}" : "You are not logged in.",
    "links" => [],
  ]
];
if ($session->isLoggedIn()) {
  $header["user"]["links"] = [
    [
      "title" => "<i class='fas fa-user-cog'></i> Change Password",
      "path" => "#",
      "onclick" => "cwos.faderBox.url('/app/admin/popup/change-password', {}, {exitBtn: true});",
      "newtab" => false
    ],
    [
      "title" => "<i class='fas fa-sign-out-alt'></i> Sign Out",
      "path" => "/admin/sign-out",
      "onclick" => "",
      "newtab" => false
    ]
  ];
} else {
  $header["user"]["links"] = [
    [
      "title" => "<i class='fas fa-sign-in-alt'></i> Sign In",
      "path" => "/admin/sign-in?rdt={$params['rdt']}",
      "onclick" => "",
      "newtab" => false
    ],
    [
      "title" => "<i class='fas fa-plus'></i> Sign Up",
      "path" => "/admin/sign-up?rdt={$params['rdt']}",
      "onclick" => "",
      "newtab" => false
    ],
    [
      "title" => "<i class='fas fa-info-circle'></i> Learn More",
      "path" => "https://" . get_constant("PRJ_BASE_DOMAIN"),
      "onclick" => "",
      "newtab" => true
    ]
  ];
}
echo \json_encode([
  "status" => "0.0",
  "errors" => [],
  "message" => "Request was successful.",
  "data" => $header
]);
exit;

