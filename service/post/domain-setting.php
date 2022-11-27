<?php
namespace IO;
require_once "../../.appinit.php";

use TymFrontiers\Data;
use \TymFrontiers\Generic,
    \TymFrontiers\MultiForm,
    \TymFrontiers\MySQLDatabase,
    \TymFrontiers\InstanceError;
use TymFrontiers\Validator;

\require_login(false);
\check_access("WRITE", "/domain-settings", "project-admin","", false);
$errors = [];
$gen = new Generic;
$req = [];
if (empty($_GET['id'])) {
  $req[] = "key";
} else {
  $req[] = "domain";
}

$params = $gen->requestParam([
  "id"    => ["id","int",1,0],
  "key"    => ["key","username",3,32,[],'UPPER',['-','.']],
  "domain" => ["domain","username",3,72,[],'LOWER',['-','.']],
  "callback" => ["callback","username",3,35,[],'MIXED']
], $_GET, $req);
if (!$params || !empty($gen->errors)) {
  $errs = (new InstanceError($gen,true))->get("requestParam",true);
  foreach ($errs as $er) {
    $errors[] = $er;
  }
}
$setting = false;
$option = null;
$variant = null;
if( $params ):
  if (!empty($params["id"]) && !empty($params['domain'])) {
    // find setting from doamin/server
    $server_name = domain_server($params["domain"]);
    if (!$server_name) {
      $errors[] = "No server found for given domain";
    } else {
      if ($server_name !== get_constant("PRJ_SERVER_NAME")) {
        $new_conn = true;
        $cred = get_dbuser($server_name, $session->access_group());
        $conn = new MySQLDatabase(get_dbserver($server_name), $cred[0], $cred[1]);
      } else {
        $new_conn = false;
        $conn = $database;
      }
      if (!$conn instanceof MySQLDatabase) {
        $errors[] = "Failed to connect to server.";
      }
    }

    $db_name = get_database("base", $server_name);
    if (empty($errors)) {
      if (!$setting = (new MultiForm($db_name, "settings", "id", $conn))->findById($params['id'])) {
        $errors[] = "No record was found for setting [id] {$params['id']}.";
      } else {
        $usr = \explode("{$params['domain']}.", $setting->user)[1];
        $setting->user = ($usr == "SYSTEM" ? $usr : code_split($usr, " "));
        $option = setting_option ($setting->skey);
        if ($option && !empty($option) && !empty($option['variant'])) {
          $variant = setting_variant($option['variant']);
        }
      }
    }
  } else {
    $option = setting_option ($params['key']);
    if ($option && !empty($option) && !empty($option['variant'])) {
      $variant = setting_variant($option['variant']);
    }
  }
endif;
if (empty($option)) $errors[] = "Setting [option] not found for " . ($setting ? $setting->skey : $params['key']) ;
$valid = new Validator;
$data_obj = new Data;
$enc_key = $setting ? encKey(domain_server($params['domain'])) : "";
?>
<script type="text/javascript">
  if (typeof window["param"] == undefined || !window["param"]) window["param"] = {};
  <?php if (!empty($params) && \is_array($params)) { foreach ($params as $k=>$val) { echo "param['{$k}'] = '{$val}';"; } } ?>
</script>
<div id="fader-flow">
  <div class="view-space">
    <div class="paddn -pall -p20">&nbsp;</div>
    <br class="c-f">
    <div class="grid-10-tablet grid-8-laptop center-tablet">
      <div class="sec-div theme-color asphalt bg-white drop-shadow">
        <header class="paddn -pall -p20 color-bg">
          <h1> <i class="fas fa-globe"></i> Domain Setting</h1>
        </header>

        <div class="paddn -pall -p20">
          <?php if(!empty($errors)){ ?>
            <h3>Unresolved error(s)</h3>
            <ol>
              <?php foreach($errors as $err){
                echo " <li>{$err}</li>";
              } ?>
            </ol>
          <?php }else{ ?>
            <form
            id="domain-setting-form"
            class="block-ui"
            method="post"
            action="/app/admin/src/PostDomainSetting.php"
            data-validate="false"
            onsubmit="cwos.form.submit(this, doPost);return false;"
            >
            <input type="hidden" name="id" value="<?php echo $setting ? $setting->id : ""; ?>">
            <input type="hidden" name="key" value="<?php echo $setting ? $setting->skey : $params['key']; ?>">
            <input type="hidden" name="form" value="domain-setting-form">
            <input type="hidden" name="CSRF_token" value="<?php echo ($session->createCSRFtoken("domain-setting-form"));?>">

            <div class="grid-12-tablet">
              <h3><?php echo $option['title']; ?></h3>
              <p><?php echo $option['description']; ?></p>
            </div>
            <div class="grid-7-tablet">
              <lable><i class="fas fa-asterisk fa-sm fa-border"></i> Domain</lable>
              <select name="domain" required>
                <option value="">* Setting domain</option>
                <optgroup label="Domains">
                  <?php if ($domains = (new MultiForm(get_database("admin", \IO\get_constant("PRJ_SERVER_NAME")), "work_domains", "name"))->findAll()) {
                    foreach ($domains as $dmn) {
                      echo " <option value=\"{$dmn->name}\" title=\"{$dmn->description}\" ";
                        echo $setting && $params["domain"] == $dmn->name ? ' selected' : '';
                      echo ">{$dmn->name}</option>";
                    }
                  } ?>
                </optgroup>
              </select>
            </div>
            <div class="grid-5-tablet">
              <label for="user"><i class="fas fa-asterisk fa-sm fa-border"></i> User</label>
              <input type="text" name="user" id="user" placeholder="SYSTEM | 000 0000 0000" required pattern="(SYSTEM|(([\d]{3,3})([\-|\s]{1,1})?([\d]{4,4})([\-|\s]{1,1})?([\d]{4,4})))" value="<?php echo $setting ? $setting->user : ""; ?>">
            </div>
            <?php if (\in_array($option['type'], ["name", "username"])) { ?>
              <div class="grid-12-tablet">
                <label for="value"> <i class="fas fa-asterisk fa-sm fa-border"></i> <b>Value</b> (<?php echo $option["type_title"]; ?>)</label>
                <input
                  type="text"
                  <?php echo @ $variant && (int)$variant["minval"] > 0 ? " minlength=\"{$variant["minval"]}\"" : "" ?>
                  <?php echo @ $variant && (int)$variant["maxval"] > 0 ? " maxlength=\"{$variant["maxval"]}\"" : "" ?>
                  name="value"
                  id="value" required
                  autocomplete="off"
                  value="<?php echo $setting ? ((bool)$option['encrypt'] ? $data_obj->decodeDecrypt($setting->sval, $enc_key) : $setting->sval) : ''; ?>"
                  placeholder="Enter <?php echo $option["type_title"]; ?>"
                >
              </div>
            <?php } else if ($option['type'] == "option") { 
              if ($variant && !empty($variant['options']) && !empty($variant['optiontype']) && \in_array($variant["optiontype"],["radio", "checkbox"])) {
            ?>
              <div class="grid-12-tablet">
                <label> <i class="fas fa-asterisk fa-sm fa-border"></i> <b>Value</b> (<?php echo $option['type_title']; ?>)</label> <br>
                <?php $set_opt_vals = $setting ? \explode(",",((bool)$option['encrypt'] ? $data_obj->decodeDecrypt($setting->sval, $enc_key) : $setting->sval)) : ""; foreach ($variant["options"] as $index => $value): ?>
                  <input
                    type="<?php echo $variant["optiontype"]; ?>"
                    name="value"
                    value="<?php echo $value; ?>"
                    id="<?php echo "option-{$index}"; ?>"
                    <?php echo ($setting && \in_array($value,$set_opt_vals)) || (!$setting && $index==0) ? " checked " : "" ?>
                  >
                  <label for="<?php echo "option-{$index}"; ?>"> <?php echo $value; ?></label>
                <?php endforeach; ?>
              </div>
            <?php } } else if (\in_array($option['type'], ["email","tel","url","password"])) { ?>
              <div class="grid-12-tablet">
                <label for="value"> <i class="fas fa-asterisk fa-sm fa-border"></i> <b>Value</b> (<?php echo $option['type_title']; ?>)</label>
                <input
                  type="<?php echo $option['type']; ?>"
                  name="value"
                  id="value" required
                  value="<?php echo $setting ? ((bool)$option['encrypt'] ? $data_obj->decodeDecrypt($setting->sval, $enc_key) : $setting->sval) : ''; ?>"
                  placeholder="Enter <?php echo $option['type_title']; ?>"
                  autocomplete="off"
                >
              </div>
            <?php } else if (\in_array($option['type'], ["text","ip"])) { ?>
              <div class="grid-12-tablet">
                <label for="value"> <i class="fas fa-asterisk fa-sm fa-border"></i> <b>Value</b> (<?php echo $option['type_title']; ?>)</label>
                <input
                  type="text"
                  name="value"
                  <?php if ($option['type'] !== "ip"): ?>
                    <?php echo @ $variant && (int)$variant["minval"] > 0 ? " minlength=\"{$variant["minval"]}\"" : "" ?>
                    <?php echo @ $variant && (int)$variant["maxval"] > 0 ? " maxlength=\"{$variant["maxval"]}\"" : "" ?>
                  <?php endif; ?>
                  autocomplete="off"
                  id="value" required
                  value="<?php echo $setting ? ((bool)$option['encrypt'] ? $data_obj->decodeDecrypt($setting->sval, $enc_key) : $setting->sval) : ''; ?>"
                  placeholder="Enter <?php echo $option['type_title']; ?>"
                >
              </div>
            <?php } else if (\in_array($option['type'], ["html","markdown","mixed","script"])) { ?>
              <div class="grid-12-tablet">
                <label for="value"> <i class="fas fa-asterisk fa-sm fa-border"></i> <b>Value</b> (<?php echo $option['type_title']; ?>)</label>
                <textarea
                  name="value"
                  <?php echo @ $variant && (int)$variant["minval"] > 0 ? " minlength=\"{$variant["minval"]}\"" : "" ?>
                  <?php echo @ $variant && (int)$variant["maxval"] > 0 ? " maxlength=\"{$variant["maxval"]}\"" : "" ?>
                  id="value" required
                  class="autosize"
                  placeholder="Enter <?php echo $option['type_title']; ?>"
                ><?php echo $setting ? ((bool)$option['encrypt'] ? $data_obj->decodeDecrypt($setting->sval, $enc_key) : $setting->sval) : ''; ?></textarea>
              </div>
            <?php } else if ($option['type'] == "boolean") { ?>
              <div class="grid-12-tablet">
                <label> <i class="fas fa-asterisk fa-sm fa-border"></i> <b>Value</b> (<?php echo $option['type_title']; ?>)</label> <br>
                  <input
                    type="radio"
                    name="value"
                    value="1"
                    id="bool-true"
                    <?php echo ($setting && (bool)((bool)$option['encrypt'] ? $data_obj->decodeDecrypt($setting->sval, $enc_key) : $setting->sval) ===  true) || !$setting ? " checked " : "" ?>
                  >
                  <label for="bool-true"> True</label>
                  <input
                    type="radio"
                    name="value"
                    value="0"
                    id="bool-false"
                    <?php echo ($setting && (bool)((bool)$option['encrypt'] ? $data_obj->decodeDecrypt($setting->sval, $enc_key) : $setting->sval) ===  false) ? " checked " : "" ?>
                  >
                  <label for="bool-false"> False</label>
              </div>
            <?php } else if (\in_array($option['type'], ["date","time","datetime"])) { ?>
              <div class="grid-12-tablet">
                <label for="value"> <i class="fas fa-asterisk fa-sm fa-border"></i> <b>Value</b> (<?php echo $option['type_title']; ?>)</label>
                <input
                  name="value"
                  <?php if (\in_array($option['type'], ["date","datetime"])): ?>
                    <?php echo @ $variant && !empty($variant["minval"]) > 0 ? " min=\"{$variant["minval"]}\"" : "" ?>
                    <?php echo @ $variant && !empty($variant["maxval"]) > 0 ? " max=\"{$variant["maxval"]}\"" : "" ?>
                  <?php else: ?>
                    <?php echo @ $variant && !empty($variant["minval"]) > 0 ? " min=\"{$variant["minval"]}\"" : "" ?>
                    <?php echo @ $variant && !empty($variant["maxval"]) > 0 ? " max=\"{$variant["maxval"]}\"" : "" ?>
                  <?php endif; ?>
                  id="value" required
                  value="<?php echo $setting ? ((bool)$option['encrypt'] ? $data_obj->decodeDecrypt($setting->sval, $enc_key) : $setting->sval) : ''; ?>"
                  type="<?php echo $option['type']; ?>"
                  placeholder="Enter <?php echo $option['type_title']; ?>"
                >
              </div>
            <?php } else if (\in_array($option['type'], ["int","float"])) { ?>
              <div class="grid-12-tablet">
                <label for="value"> <i class="fas fa-asterisk fa-sm fa-border"></i> <b>Value</b> (<?php echo $option['type_title']; ?>)</label>
                <input
                  name="value"
                  <?php echo @ $variant && !empty($variant["minval"]) > 0 ? " min=\"{$variant["minval"]}\"" : "" ?>
                  <?php echo @ $variant && !empty($variant["maxval"]) > 0 ? " max=\"{$variant["maxval"]}\"" : "" ?>
                  id="value" required
                  value="<?php echo $setting ? ((bool)$option['encrypt'] ? $data_obj->decodeDecrypt($setting->sval, $enc_key) : $setting->sval) : ''; ?>"
                  type="number"
                  <?php echo $option['type'] == "float" ? " step='any' " : ""; ?>
                  placeholder="Enter <?php echo $option['type_title']; ?>"
                >
              </div>
            <?php } else if($option['type'] == "pattern") { ?>
              <div class="grid-12-tablet">
                <label for="value"> <i class="fas fa-asterisk fa-sm fa-border"></i> <b>Value</b> (<?php echo $option['type_title']; ?>)</label>
                <input
                  name="value"
                  id="value" required
                  value="<?php echo $setting ? ((bool)$option['encrypt'] ? $data_obj->decodeDecrypt($setting->sval, $enc_key) : $setting->sval) : ''; ?>"
                  type="text"
                  pattern="<?php echo \rtrim(\trim($variant["pattern"], "/"), "/"); ?>"
                  placeholder="Enter <?php echo $option['type_title']; ?>"
                >
              </div>
            <?php } else {  echo "<p>Invalid setting configuration, contact Developer.</p>"; } ?>

            <div class="grid-5-tablet">
              <button id="submit-form" type="submit" class="theme-button asphalt"> <i class="fas fa-save"></i> Save </button>
            </div>

            <br class="c-f">
          </form>
        <?php } ?>
      </div>
    </div>
  </div>
  <br class="c-f">
</div>
</div>
<?php if ($new_conn) $conn->closeConnection(); ?>
<script type="text/javascript">
  (function(){
    $('input[name=name]').blur(function(){
      $(this).val($(this).val().replaceAll(' ','.').toUpperCase());
    });
    $('textarea.autosize').autosize();
  })();
</script>
