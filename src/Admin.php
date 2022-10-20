<?php
namespace IO;

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
	protected static $_db_fields = ["code", "status", "user", "name", "surname", "password", "work_group", "_author", "_created"];
  protected static $_prop_type = [];
  protected static $_prop_size = [];

  const SERVER_NAME = "CWS";
  const CODE_PREFIX = "052";

  private $code;
  protected $status = "ACTIVE";
  public $user;
  public $name;
  public $surname;
  public $password;
  public $work_group = "USER";

  protected $_author;
  protected $_created;

  public $errors = [];

  function __construct($conn = false) {
    if (!self::$_db_name = get_database(self::SERVER_NAME, "admin")) throw new \Exception("[base] type database not set for server [CWS]", 1);
    global $database;
    $conn = $conn && $conn instanceof MySQLDatabase ? $conn : ($database && $database instanceof MySQLDatabase ? $database : false);
    $conn = query_conn(self::SERVER_NAME, $conn);
    self::_setConn($conn);
  }

  public static function authenticate(string $code, string $password){
    global $database;
    global $access_ranks;
    $conn = query_conn(self::SERVER_NAME, $database);
    self::_setConn($conn);
    self::$_db_name = get_database(self::SERVER_NAME, "admin");

    $data = new Data();
    $password = $conn->escapeValue($password);
    $valid = new Validator;
    if (!$code = $valid->pattern($code, ["code","pattern", "/^052([0-9]{4,4})([0-9]{4,4})$/"])) return false;
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
      $user->avatar = $user->avatar;
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
  public function register(string $work_group, string $user, string $code = ""){
    $conn =& self::$_conn;

    $data = new Data();
    $unset = [];
    foreach ($this->_req_params as $prop) {
      if (empty($user[$prop])) $unset[] = $prop;
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
    foreach($user as $key=>$val){
      if( \property_exists(__CLASS__, $key) && !empty($val) ){
        $this->$key = $conn->escapeValue($val);
      }
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
      $prfx = $code_prefix["profile"];
      $this->code = generate_code($prfx, Data::RAND_NUMBERS, 11, $this, "code", true);
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
  public function requestAccount (string $user, string $password):bool {
    $this->status = "REQUESTING";
    $this->work_group = "USER";
    $valid = new Validator;
    if (!$this->user = $valid->pattern($user, ["user","pattern", "/^(252|352)([\d]{4,4})([\d]{4,4})$/"])) {
      if ($errs = (new InstanceError($valid))->get("pattern", true)) {
        unset($valid->errors["pattern"]);
        foreach ($errs as $er) {
          $this->errors["requestAccount"][] = [0, 256, $er, __FILE__, __LINE__];
        }
      }
    } else if (!$password = $valid->password($password, ["password","password"])) {
      if ($errs = (new InstanceError($valid))->get("password", true)) {
        unset($valid->errors["password"]);
        foreach ($errs as $er) {
          $this->errors["requestAccount"][] = [0, 256, $er, __FILE__, __LINE__];
        }
      }
    } else {
      // get ready to create
      $this->password = Data::pwdHash($password);
      $this->code = generate_code(self::CODE_PREFIX, Data::RAND_NUMBERS, 11, $this, "code", true);
      $utype = code_storage($user, "BASE");
      if ($utype && $ustatus = (new MultiForm($utype[0], $utype[1], $utype[2]))->findById($user)) {
        if ($ustatus->status == "ACTIVE") {
          $this->name = @ $ustatus->name;
          $this->surname = @ $ustatus->surname;
          return $this->_create();
        } else {
          $this->errors["requestAccount"][] = [0, 256, "[user] profile is not active.", __FILE__, __LINE__];
        }
      } else {
        $this->errors["requestAccount"][] = [0, 256, "[user] profile was not found.", __FILE__, __LINE__];
      }
    }
    return false;
  }

  final public function code () { return $this->code; }
  final public function status () { return $this->status; }
  final public function author () { return $this->_author; }
  final public function delete () { return false; }
  final public function update () { return false; }
  final public function create () { return false; }
}