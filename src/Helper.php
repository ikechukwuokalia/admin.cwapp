<?php
namespace IO\Admin;
use \TymFrontiers\Data,
    \TymFrontiers\MySQLDatabase,
    \TymFrontiers\Session,
    \TymFrontiers\MultiForm,
    \TymFrontiers\Validator;

use function \get_database;
use function \query_conn;
use function IO\get_constant;

// Profile functions
function log_session (string $type = "LOGIN") {
  global $session, $database;
  if (!$db_name = get_database("log", get_constant("PRJ_SERVER_NAME"))) throw new \Exception("Database not found for domain settings.", 1);
  $conn = query_conn(get_constant("PRJ_SERVER_NAME"), $database);

  if ( $session instanceof Session &&  $session->isLoggedIn()) {
    $location = new \TymFrontiers\Location;
    $ip = !empty($location->ip) ? $location->ip : $_SERVER['REMOTE_ADDR'];
    $put = [
      "user" => $conn->escapeValue($session->name),
      "type" => $conn->escapeValue($type)
    ];
    if (!empty($_SERVER['HTTP_USER_AGENT'])) $put["agent"] = $conn->escapeValue($_SERVER['HTTP_USER_AGENT']);
    if (!empty($ip)) $put["ip"] = $conn->escapeValue($ip);
    if (!empty($location->country_code)) $put["country_code"] = $conn->escapeValue($location->country_code);
    if (!empty($location->state_code)) $put["state_code"] = $conn->escapeValue($location->state_code);
    if (!empty($location->city_code)) $put["city_code"] = $conn->escapeValue($location->city_code);
    if ($type == "LOGIN") $put["expiry"] = \date(\TymFrontiers\BetaTym::MYSQL_DATETIME_STRING, $session->expiry());
    $query  = "INSERT INTO `{$db_name}`.user_session ";
    $query .= ("(`" . \implode("`,`", \array_keys($put)) . "`)");
    $query .= " VALUES (" . ("'" . \implode("','", \array_values($put)) . "')");
    if ($conn->query($query)) {
      return true;
    }
  }
  return false;
}
function access_type (string $group = "") {
  global $session, $access_ranks, $reverse_access_ranks;
  if (empty($group)) $group = $session->access_group();
  $acc_sett = get_constant("PRJ_ENABLE_ACCESS_GROUP");
  $max_rank = $access_ranks[$group];
  if (\is_bool($acc_sett) && $acc_sett == false) $max_rank = $session->isLoggedIn() ? $access_ranks["USER"] : $access_ranks["GUEST"];
  if (\is_int($acc_sett)) $max_rank = $max_rank <= $acc_sett ? $max_rank : $acc_sett;
  $access_group = $reverse_access_ranks[$max_rank];
  return [
    $access_group,
    $access_ranks[$access_group]
  ];
}