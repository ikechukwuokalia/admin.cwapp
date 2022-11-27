<?php
namespace IO;
require_once "../.appinit.php";
use \TymFrontiers\Generic,
    \TymFrontiers\MultiForm,
    \TymFrontiers\InstanceError;

\require_login(false);
\check_access("ALTER", "/users", "project-admin","", false);
$errors = [];
$gen = new Generic;
$app = false;
$params = $gen->requestParam([
  "code" => ["code","pattern", "/^052([\d]{4,4})([\d]{4,4})$/"],
  "cb" => ["cb","username",3,35,[],'MIXED'],
  "callback" => ["callback","username",3,35,[],'MIXED']
], $_GET, ["code"]);
if (!$params || !empty($gen->errors)) {
  $errs = (new InstanceError($gen,true))->get("requestParam",true);
  foreach ($errs as $er) {
    $errors[] = $er;
  }
}
if( $params ):
endif;
?>
<script type="text/javascript">
  if (typeof window["param"] == undefined || !window["param"]) window["param"] = {};
  <?php if (!empty($params) && \is_array($params)) { foreach ($params as $k=>$val) { echo "param['{$k}'] = '{$val}';"; } } ?>
</script>

<div id="fader-flow">
  <div class="view-space">
    <div class="paddn -pall -p20">&nbsp;</div>
    <br class="c-f">
    <div class="grid-9-tablet grid-7-laptop center-tablet">
      <div class="sec-div theme-color asphalt bg-white drop-shadow">
        <header class="paddn -pall -p20 color-bg">
          <h1 class="fw-lighter"> <i class="fas fa-user-circle"></i> Accept User</h1>
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
            id="do-post-form"
            class="block-ui"
            method="post"
            action="/app/admin/src/AcceptUser.php"
            data-validate="false"
            onsubmit="cwos.form.submit(this,doPost); return false;"
            >
            <input type="hidden" name="code" value="<?php echo $params['code']; ?>">
            <input type="hidden" name="form" value="devuser-form">
            <input type="hidden" name="CSRF_token" value="<?php echo ($session->createCSRFtoken("devuser-form"));?>">
            
            <div class="grid-6-tablet">
              <label><i class="fas fa-asterisk fa-border"></i> Work group</label>
              <select name="work_group" id="work_group" required>
                <option value="">Choose work group</option>
                <optgroup label="Work Groups">
                  <?php $acs_mx = $session->access_group() == "DEVELOPER" ? "" : " AND `rank` <= {$session->access_rank()} "; if ($work_groups = (new MultiForm(get_database("data","BASE"), "access_types", "name"))->findBySql("SELECT * FROM :db:.:tbl: WHERE `rank` > 0 {$acs_mx} ORDER BY `rank` ASC")) {
                    foreach ($work_groups as $wg) {
                      echo "<option value=\"{$wg->name}\" title=\"{$wg->description}\">{$wg->title}</option>"; 
                    }
                  }?>
                </optgroup>
              </select>
            </div>
            <br class="c-f">
            <div class="grid-12-tablet">
              <label for="message"> Message (User will receive this message)</label>
              <textarea name="message" id="message" class="autosize" placeholder="Type welcome message here"></textarea>
            </div>
            <div class="grid-5-tablet"> 
              <button id="submit-form" type="submit" class="theme-btn asphalt"> <i class="fas fa-check"></i> Accept </button>
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
    $("textarea.autosize").autosize();
  })();
</script>
