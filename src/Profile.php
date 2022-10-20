<?php
namespace IO\Admin;
use \TymFrontiers\Data,
    \TymFrontiers\MySQLDatabase,
    \TymFrontiers\Session,
    \TymFrontiers\MultiForm,
    \TymFrontiers\Validator;

use function IO\get_constant;
use function \get_database;
use function \query_conn;

trait Profile {
  public $name;
  public $surname;
  public $avatar;
  public $country;
  public $state_code;
  public $state;
  public $city_code;
  public $city;
  public $zip_code;

  public function profile() {
    $conn =& static::$_conn;

    $base_db = get_database(self::SERVER_NAME, "base");
    $data_db = get_database(self::SERVER_NAME, "data");
    $file_db = get_database(self::SERVER_NAME, "file");
    $file_tbl = "file_meta";
    $file_server = get_constant("PRJ_FILE_SERVER");
    $sql = "SELECT adm.`code`, adm.`status`, adm.`user`, adm.work_group,
                   usr.status AS user_status, usr.email, usr.phone,
                   usr.name, usr.surname,
                   usr.sex, usr.dob, 
                   uad.zip_code,
                   c.`code` AS country_code, c.name AS country,
                   s.name AS `state`, s.code AS state_code,
                   ci.name AS city, ci.code AS city_code,
                   (
                     SELECT CONCAT('{$file_server}/',f._name)
                   ) AS avatar,
                   CONCAT(auth.name, ' ', auth.surname) AS author
            FROM :db:.:tbl: AS adm
            LEFT JOIN `{$base_db}`.users AS usr ON usr.`code` = adm.`user`
            LEFT JOIN `{$base_db}`.users AS auth ON auth.`code` = adm._author
            LEFT JOIN `{$base_db}`.user_addresses AS uad ON uad.user = usr.`code` AND uad.type = 'MAILING'
            LEFT JOIN `{$data_db}`.countries AS c ON c.code = uad.country_code
            LEFT JOIN `{$data_db}`.states AS s ON s.code = uad.state_code
            LEFT JOIN `{$data_db}`.cities AS ci ON ci.code = uad.city_code
            LEFT JOIN `{$file_db}`.`file_default` AS fd ON fd.`user` = usr.`code` AND fd.set_key = 'USER.AVATAR'
            LEFT JOIN `{$file_db}`.`{$file_tbl}` AS f ON f.id = fd.file_id
            WHERE adm.`code` = '{$this->code}' ";
    $found =  self::findBySql($sql);
    if ($found) {
      $found = $found[0];
      if (empty($found->avatar)) $found->avatar = WHOST . "/app/cataliws/php-adminprofile/img/default-avatar.png";
    }
    return $found;
  }
}
// Profile functions
function log_session (string $type = "LOGIN") {
  global $session, $database;
  if (!$db_name = get_database(get_constant("PRJ_SERVER_NAME"), "log")) throw new \Exception("Database not found for domain settings.", 1);
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