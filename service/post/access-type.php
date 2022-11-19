<?php
namespace IO;
require_once "../../.appinit.php";
use \TymFrontiers\Generic,
    \TymFrontiers\MultiForm,
    \TymFrontiers\InstanceError;

\require_login(false);
\check_access("WRITE", "/access-types", "project-admin","", false);
$errors = [];
$gen = new Generic;
$type = false;
$params = $gen->requestParam([
  "name" => ["name","username", 3, 21],
  "callback" => ["callback","username",3,35,[],'MIXED']
], $_GET, []);
if (!$params || !empty($gen->errors)) {
  $errs = (new InstanceError($gen,true))->get("requestParam",true);
  foreach ($errs as $er) {
    $errors[] = $er;
  }
}
if( $params ):
  if( !empty($params['name']) && !$type = (new MultiForm(get_database("CWS", "data"),'access_types','name'))->findById($params['name']) ){
    $errors[] = "No record found for given [name]: {$params['name']}";
  }
  if ($type) $type->scope = (!empty($type->scope) ? \explode(",", $type->scope) : []);
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
      <div class="sec-div theme-color asphalt bg-white drop-shadow">
        <header class="paddn -pall -p20 color-bg">
          <h1> <i class="fas fa-folder-times"></i> Access type</h1>
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
            id="access-type-form"
            class="block-ui"
            method="post"
            action="/app/admin/src/PostAccessType.php"
            data-validate="false"
            onsubmit="cwos.form.submit(this, doPost);return false;"
            >
            <input type="hidden" name="task" value="<?php echo $type ? "UPDATE" : "CREATE"; ?>">
            <input type="hidden" name="form" value="access-type-form">
            <input type="hidden" name="CSRF_token" value="<?php echo ($session->createCSRFtoken("access-type-form"));?>">

            <div class="grid-5-tablet">
              <label for="name"><i class="fas fa-asterisk fa-border fa-sm"></i> Name</label>
              <input type="text" name="name" maxlength="21" <?php echo $type ? 'readonly' : ''; ?> id="name" placeholder="ACCESS" required value="<?php echo $type ? $type->name : ''; ?>">
            </div>
            <div class="grid-4-tablet">
              <label for="rank"><i class="fas fa-asterisk fa-border fa-sm"></i> Rank</label>
              <input type="number" name="rank" step="1" id="rank" placeholder="1" required value="<?php echo $type ? $type->rank : ''; ?>">
            </div>
            <div class="grid-6-tablet">
              <label for="title"><i class="fas fa-asterisk fa-border fa-sm"></i> Title</label>
              <input type="text" name="title" maxlength="56" id="title" placeholder="Access title" required value="<?php echo $type ? $type->title : ''; ?>">
            </div>

            <div class="grid-12-tablet">
              <label for="description"><i class="fas fa-asterisk fa-border fa-sm"></i> Description</label>
              <textarea name="description" maxlength="250" minlegth="5" class="autosize" id="description" placeholder="Domain description" required><?php echo $type ? $type->description : ''; ?></textarea>
            </div>
            <div class="grid-12-tablet">
              <h4>Access scope</h4>
              <?php if ($scopes = (new MultiForm(get_database("CWS", "data"), "access_scopes", "name"))->findBySql("SELECT * FROM :db:.:tbl: ORDER BY `rank` ASC")) {
                foreach ($scopes as $scope) {
                  echo "<span>";
                    echo "<input type=\"checkbox\" name=\"scope\" id=\"scope-{$scope->name}\" value=\"{$scope->name}\"";
                      echo $type && \in_array($scope->name, $type->scope) ? " checked" : "";
                    echo ">";
                    echo "<label title=\"{$scope->description}\" for=\"scope-{$scope->name}\">{$scope->name}</label>";
                  echo "</span>";
                }
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
    if (typeof param.cb !== undefined) delete param.cb;
    $('input[name=name]').blur(function(){
      $(this).val($(this).val().replaceAll(' ','').toUpperCase());
    });
    $('textarea.autosize').autosize();
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
