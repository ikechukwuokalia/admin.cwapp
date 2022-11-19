<?php
namespace IO;
require_once "../../.appinit.php";

use PhpOffice\PhpSpreadsheet\Calculation\Information\Value;
use \TymFrontiers\Generic,
    \TymFrontiers\MultiForm,
    \TymFrontiers\InstanceError;

\require_login(false);
\check_access("WRITE", "/path-access", "project-admin","", false);
$errors = [];
$gen = new Generic;
$access = false;
$params = $gen->requestParam([
  "id" => ["id","int"],
  "resource" => ["resource","username", 3, 21, [], "LOWER", ["-"]],
  "cb" => ["cb","username",3,35,[],'MIXED']
], $_GET, ["resource"]);
if (!$params || !empty($gen->errors)) {
  $errs = (new InstanceError($gen,true))->get("requestParam",true);
  foreach ($errs as $er) {
    $errors[] = $er;
  }
}
if( $params ):
  if( !empty($params['id']) && !$access = (new MultiForm(get_database(\IO\get_constant("PRJ_SERVER_NAME"), "data"),'resource_access','id'))->findById($params['id']) ){
    $errors[] = "No record found for given [id]: {$params['id']}";
  } if ($access) {
    if ($access->resource !== $params['resource']) {
      $errors[] = "There was conflict with the [resource] presented and that from record matched to the [id] given in the request.";
    } else {
      $access->scope = (empty($access->scope) ? [] : \explode(",",$access->scope));
    }
  }
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
    <div class="grid-10-tablet grid-8-laptop center-tablet">
      <div class="sec-div theme-color amber bg-white drop-shadow">
        <header class="paddn -pall -p20 color-bg">
          <h1> <i class="fas fa-user-universal-access"></i> Resource types</h1>
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
            id="resource-access-form"
            class="block-ui"
            method="post"
            action="/app/admin/src/PostResourceAccess.php"
            data-validate="false"
            onsubmit="cwos.form.submit(this, doPost);return false;"
            >
            <input type="hidden" name="id" value="<?php echo $access ? $access->id : ""; ?>">
            <input type="hidden" name="resource" value="<?php echo @ $params['resource']; ?>">
            <input type="hidden" name="form" value="resource-access-form">
            <input type="hidden" name="CSRF_token" value="<?php echo ($session->createCSRFtoken("resource-access-form"));?>">
            <?php if ($access): ?>
              <input type="hidden" name="group_name" value="<?php echo $access->group_name; ?>">
              <?php else: ?>
                <div class="grid-6-tablet">
                  <label><i class="fas fa-asterisk fa-border fa-sm"></i> Access group</label>
                  <select name="group_name" id="post-group-name" required>
                    <option value="">* Choose group</option>
                    <optgroup label="Groups">
                      <?php if ($groups = (new MultiForm(get_database(\IO\get_constant("PRJ_SERVER_NAME"), "data"), "access_types", "name", $database))->findBySql("SELECT * FROM :db:.:tbl: WHERE `rank` > 1 AND `name` NOT IN (SELECT `group_name` FROM :db:.resource_access WHERE `resource` = '{$database->escapeValue($params['resource'])}') ORDER BY `rank` ASC")) {
                        foreach ($groups as $grp) {
                          echo "<option value=\"{$grp->name}\" title=\"{$grp->description}\">{$grp->title}</option>";
                        }
                      } ?>
                    </optgroup>
                  </select>
                </div> 
            <?php endif; ?>
            <br class="c-f">

            <div class="grid-12-tablet">
              <h3>Access scope(s)</h3>
              <?php if ($scopes = (new MultiForm(get_database(\IO\get_constant("PRJ_SERVER_NAME"), "data"), "access_scopes", "name", $database))->findBySql("SELECT * FROM :db:.:tbl: ORDER BY `rank`")) {
                foreach ($scopes as $scope) {
                  echo "<span>";
                    echo "<input type=\"checkbox\" name=\"scope\" id=\"scope-{$scope->name}\" value=\"{$scope->name}\"";
                      echo $access && \in_array($scope->name, $access->scope) ? " checked" : "";
                    echo ">";
                    echo "<label for=\"scope-{$scope->name}\" title=\"{$scope->description}\">{$scope->name}</label>";
                  echo "</span>";
                }
              } ?>
            </div>
            <div class="grid-5-tablet">
              <button id="submit-form" type="submit" class="theme-button amber"> <i class="fas fa-save"></i> Save </button>
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
    $("input[type=checkbox]").on("change", "", function(){
      if ($(this).is(":checked") && $(this).val() == "DENY") {
        $(`input[type=checkbox][name='${$(this).attr('name')}']`).prop("checked",false);
        $(this).prop("checked",true);
      } if ($(this).is(":checked") && $(this).val() !== "DENY") {
        $(`input[type=checkbox][name='${$(this).attr('name')}'][value='DENY']`).prop("checked",false);
      }
    });
  })();
</script>
