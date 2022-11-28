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
  public $avatar;
  public $country;

  public function profile() {
    $conn =& static::$_conn;

    $base_db = get_database("base", self::$_server_name);
    $data_db = get_database("data", self::$_server_name);
    $file_db = get_database("file", self::$_server_name);
    $file_tbl = "file_meta";
    $file_server = get_constant("PRJ_FILE_SERVER");
    $sql = "SELECT adm.`code`, adm.`status`, adm.work_group,
                   adm.email, adm.phone, adm.name, adm.surname,
                   adm.sex, adm.dob,
                   c.`code` AS country_code, c.name AS country,
                   (
                     SELECT CONCAT('{$file_server}/',f._name)
                   ) AS avatar,
                   CONCAT(auth.name, ' ', auth.surname) AS author
            FROM :db:.:tbl: AS adm
            LEFT JOIN `{$base_db}`.users AS auth ON auth.`code` = adm._author
            LEFT JOIN `{$data_db}`.countries AS c ON c.`code` = adm.country_code
            LEFT JOIN `{$file_db}`.`file_default` AS fd ON fd.`user` = adm.`code` AND fd.set_key = 'USER.AVATAR'
            LEFT JOIN `{$file_db}`.`{$file_tbl}` AS f ON f.id = fd.file_id
            WHERE adm.`code` = '{$this->code}' ";
    $found =  self::findBySql($sql);
    if ($found) {
      $found = $found[0];
      if (empty($found->avatar)) $found->avatar = "/app/ikechukwuokalia/admin.cwapp/img/default-avatar.png";
    }
    return $found;
  }
}
