<?php
namespace IO;

use stdClass;
use \TymFrontiers\Data,
    \TymFrontiers\MySQLDatabase,
    \TymFrontiers\InstanceError,
    \TymFrontiers\Validator,
    TymFrontiers\MultiForm;

class Admin{
  use \TymFrontiers\Helper\MySQLDatabaseObject,
      \TymFrontiers\Helper\Pagination,
      Admin\Profile;

  protected static $_primary_key='code';
  protected static $_db_name;
  protected static $_table_name = "users";
	protected static $_db_fields = ["code", "status", "name", "surname", "email", "phone", "password", "work_group", "country_code", "dob", "sex", "_author", "_created"];
  protected static $_prop_type = [];
  protected static $_prop_size = [];
  protected static $_prefix_code = "052";
  protected static $_code_length = 11;
  protected static $_server_name;

  private $code;
  protected $status = "ACTIVE";
  public $name;
  public $surname;
  public $email;
  public $phone;
  public $password;
  public $work_group = "USER";
  public $country_code;
  public $dob;
  public $sex;

  protected $_author;
  protected $_created;

  public $errors = [];

  function __construct($conn = false, $prefix_code = "", $code_len = 0) {
    $valid = new Validator;
    if (!empty($prefix_code) && $valid->pattern($prefix_code, ["prefix-code", "pattern", "/^(\d{3,3})$/"])) {
      self::$_prefix_code = $prefix_code;
    } if (!empty($code_len) && \is_int($code_len) && $code_len > 10 && $code_len < 16) {
      self::$_code_length = $code_len;
    }
    if (!self::$_server_name = get_constant("PRJ_SERVER_NAME")) throw new \Exception("Server-name constant was not defined", 1);
    
    if (!self::$_db_name = get_database("admin", self::$_server_name)) throw new \Exception("[admin] type database not set for server [" .self::$_server_name . "]", 1);
    global $database;
    $conn = $conn && $conn instanceof MySQLDatabase ? $conn : ($database && $database instanceof MySQLDatabase ? $database : false);
    $conn = query_conn(self::$_server_name, $conn);
    self::_setConn($conn);
  }

  public static function authenticate(string $code, string $password){
    global $database;
    global $access_ranks;
    $server_name = self::$_server_name = get_constant("PRJ_SERVER_NAME");
    $conn = query_conn($server_name, $database);
    self::_setConn($conn);
    self::$_db_name = get_database("admin", self::$_server_name);
    $prefix = self::$_prefix_code;

    $data = new Data();
    $password = $conn->escapeValue($password);
    $valid = new Validator;
    if (!$code = $valid->pattern($code, ["code","pattern", "/^{$prefix}([0-9]{4,4})([0-9]{4,4})([0-9]{1,4})?$/"])) return false;
    $sql = "SELECT adm.`code`, adm.`status`, adm.work_group, adm.password
            FROM :db:.:tbl: AS adm
            WHERE adm.`status` IN('ACTIVE','PENDING') 
            AND adm.`code` = '{$code}'
            AND adm.password IS NOT NULL
            LIMIT 1";
    $result_array = self::findBySql($sql);
    $record = !empty($result_array) ? $data->pwdCheck($password,$result_array[0]->password) : false;
    if( $record && $user = $result_array[0]->profile()){
      // $user = $user[0];
      $usr = new \StdClass();
      $usr->code = $usr->uniqueid = $user->code;
      $usr->access_group = $user->work_group;
      $usr->access_rank = $access_ranks[$user->work_group];
      $usr->name = $user->name;
      $usr->surname = $user->surname;
      $usr->status = $user->status;
      $usr->avatar = $user->avatar;
      $usr->country_code = $user->country_code;
      return $usr;
    }
    return false;
  }

  public function isActive(bool $strict = false){
    if( $strict ){
      return !empty($this->_id) && \in_array($this->status,['ACTIVE']);
    }else{
      return !empty($this->_id) && \in_array($this->status,['ACTIVE','PENDING']);
    }
    return false;
  }
  public function register(string $work_group, string $code = "", bool $verified = false){
    $conn =& self::$_conn;
    $required = ["name", "surname", "email", "phone", "password", "country_code", "dob", "sex"];
    $data = new Data();
    $unset = [];
    foreach ($required as $prop) {
      if ($this->isEmpty($prop, $this->$prop)) $unset[] = $prop;
    }
    if (!empty($unset)) {
      $this->errors["_createNew"][] = [
        @$GLOBALS['access_ranks']['DEVELOPER'],
        256,
        "Required properties [". \implode(", ", $unset) . "] not set", __FILE__,
        __LINE__
      ];
      return false;
    }
    if ($verified) $this->status = "ACTIVE";
    global $code_prefix;
    if (empty($this->code)) {
      if (!\is_array($code_prefix) || empty($code_prefix["profile"]) ) {
        $this->errors["_createNew"][] = [
          @$GLOBALS['access_ranks']['DEVELOPER'],
          256,
          "'code_prefix' variable not set as array.", __FILE__,
          __LINE__
        ];
        return false;
      }
      $this->code = generate_code(self::$_prefix_code, Data::RAND_NUMBERS, self::$_code_length, $this, "code", true);
    } else {
      // validate code
      if (!(new Validator)->pattern($this->code, ["code", "pattern", "/^{$prefix}([0-9]{4,4})([0-9]{4,4})([0-9]{1,4})?$/"])) {
        $this->errors["_createNew"][] = [
          @$GLOBALS['access_ranks']['DEVELOPER'],
          256,
          "[code] does not match valid pattern.", __FILE__,
          __LINE__
        ];
        return false;
      }
    }
    $this->password = $data->pwdHash($this->password);
    // get user connection
    if( $this->_create($conn) ){
      $this->password = null;
      return true;
    } else {
      $this->code = null;
      $this->errors['self'][] = [0,256, "Request failed at this this time.",__FILE__, __LINE__];
      if( \class_exists('\TymFrontiers\InstanceError') ){
        $ex_errors = new \TymFrontiers\InstanceError($conn);
        if( !empty($ex_errors->errors) ){
          foreach( $ex_errors->get("",true) as $key=>$errs ){
            foreach($errs as $err){
              $this->errors['self'][] = [0,256, $err,__FILE__, __LINE__];
            }
          }
        }
      }
    }
    return false;
  }
  public function requestAccount (string $password):bool {
    $this->status = "REQUESTING";
    $this->work_group = "USER";
    $valid = new Validator;

    $required = ["name", "surname", "email", "phone", "country_code", "dob", "sex"];
    $unset = [];
    foreach ($required as $prop) {
      if ($this->isEmpty($prop, $this->$prop)) $unset[] = $prop;
    }
    if (!empty($unset)) {
      $this->errors["requestAccount"][] = [
        0, 256, "Required values [". \implode(", ", $unset) . "] not set", __FILE__,
        __LINE__
      ];
      return false;
    }
    if (!$password = $valid->password($password, ["password","password"])) {
      if ($errs = (new InstanceError($valid))->get("password", true)) {
        unset($valid->errors["password"]);
        foreach ($errs as $er) {
          $this->errors["requestAccount"][] = [0, 256, $er, __FILE__, __LINE__];
        }
      }
    } else {
      // get ready to create
      // get ranked access
      $db_cred = \db_cred(self::$_server_name, "DEVELOPER");
      $conn = new MySQLDatabase(\get_dbserver(self::$_server_name), $db_cred[0], $db_cred[1], self::$_db_name);
      self::$_conn = $conn;
      $this->password = Data::pwdHash($password);
      $this->code = generate_code(self::$_prefix_code, Data::RAND_NUMBERS, self::$_code_length, $this, "code", true);
      return $this->_create();
    }
    return false;
  }

  final public function prefix_code (string $prefix_code = ""):string|null {
    $valid = new Validator;
    if (!empty($prefix_code) && $valid->pattern($prefix_code, ["prefix-code", "pattern", "/^(\d{3,3})$/"])) {
      self::$_prefix_code = $prefix_code;
    }
    return self::$_prefix_code;
  }
  final public function code_length (int $len = 0):int|null {
    if (!empty($len) && $len > 10 && $len < 16) {
      self::$_code_length = $len;
    }
    return self::$_code_length;
  }
  
  final public function server_name (string $server_code = ""):string|null {
    if (!empty($server_code)) self::$_server_name = $server_code;
    return self::$_server_name;
  }
  final public function code () { return $this->code; }
  final public function status () { return $this->status; }
  final public function author () { return $this->_author; }
  final public function delete () { return false; }
  final public function update () { return false; }
  final public function create () { return false; }
}