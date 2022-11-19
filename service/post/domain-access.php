<?php
namespace IO;
require_once "../../.appinit.php";

use PhpOffice\PhpSpreadsheet\Calculation\Information\Value;
use \TymFrontiers\Generic,
    \TymFrontiers\MultiForm,
    \TymFrontiers\InstanceError;

\require_login(false);
\check_access("WRITE", "/domain-access", "project-admin","", false);
$errors = [];
$gen = new Generic;
$path = false;
$params = $gen->requestParam([
  "user" => ["user","pattern", "/^052(\s|\-|\.)?([\d]{4,4})(\s|\-|\.)?([\d]{4,4})$/"],
  "domain" => ["domain","username",3,98,[],'LOWER',['-','.']],
  "callback" => ["callback","username",3,35,[],'MIXED']
], $_GET, ["domain", "user"]);
if (!$params || !empty($gen->errors)) {
  $errs = (new InstanceError($gen,true))->get("requestParam",true);
  foreach ($errs as $er) {
    $errors[] = $er;
  }
}
if (!empty($params["domain"])) $params["domain"] = $database->escapeValue($params["domain"]);
if (!empty($params["user"])) $params["user"] = $database->escapeValue(\str_replace(["-", " ", ".", "_"],"",$params["user"]));
$access = false;
if( $params ):
  if( $access = (new MultiForm(get_database(\IO\get_constant("PRJ_SERVER_NAME"), "admin"),'path_access','id'))
    ->findBySql("SELECT * FROM :db:.:tbl: WHERE `user` = '{$params['user']}' AND `domain` = '{$params['domain']}' AND `type` = 'DOMAIN' LIMIT 1") ){
    $access = $access[0];
  }
endif;
if (!$access_types = (new MultiForm(get_database(\IO\get_constant("PRJ_SERVER_NAME"), "data"), "access_types", "name"))
  ->findBySql("SELECT * FROM :db:.:tbl: WHERE `rank` > 1 ORDER BY `rank` ASC")) {
    $errors[] = "No access types found, contact Dev";
  } if (!$user = (new MultiForm(get_database(\IO\get_constant("PRJ_SERVER_NAME"), "admin"), "users", "code"))->findById($params['user'])) {
    $errors[] = "No record was found for [user] '{$params['user']}'";
  } else if ($user->status !== "ACTIVE") {
    $errors[] = "[user]'s account is not active.";
  }
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
          <h1> <i class="fas fa-universal-access"></i> Domain access</h1>
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
            id="domain-access-form"
            class="block-ui"
            method="post"
            action="/app/admin/src/PostDomainAccess.php"
            data-validate="false"
            onsubmit="cwos.form.submit(this, doPost);return false;"
            >
            <input type="hidden" name="id" value="<?php echo $access ? $access->id : ""; ?>">
            <input type="hidden" name="form" value="domain-access-form">
            <input type="hidden" name="CSRF_token" value="<?php echo ($session->createCSRFtoken("domain-access-form"));?>">
            <input type="hidden" name="domain" value="<?php echo @ $params['domain']; ?>">
            <input type="hidden" name="user" value="<?php echo @ $params['user']; ?>">

            <div class="grid-12-tablet">
              <h2 class="clear-margin align-center"><?php echo "{$user->name} {$user->surname}'s group access to Domain: <br> <code class='color-blue'>{$params['domain']}</code>"; ?></h2>
              <?php foreach ($access_types as $acs) {
                echo "<p>";
                  echo "<input type=\"radio\" name=\"access_scope\" id=\"scope-{$acs->name}\" value=\"{$acs->name}\"";
                    echo $access && $access->access_scope == $acs->name ? " checked" : "";
                  echo ">";
                  echo "<label for=\"scope-{$acs->name}\"><code>{$acs->name}</code></label>";
                    echo " | <a href=\"#\" onclick=\"alert('<h3>{$acs->title}</h3><p>". \htmlentities(\str_replace(["\r\n", "\n"],"<br>", $acs->description)). "</p>')\"> <i class=\"fas fa-info-circle\"></i> {$acs->title}</a>";
                echo "</p>";
              } ?>
            </div>
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

<script type="text/javascript">
  (function(){
  })();
</script>
