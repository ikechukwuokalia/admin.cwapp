<?php
namespace IO;
require_once "../../.appinit.php";
use \TymFrontiers\Generic,
    \TymFrontiers\MultiForm,
    \TymFrontiers\InstanceError;

\require_login(false);
\check_access("WRITE", "/access-scopes", "project-admin","", false);
$errors = [];
$gen = new Generic;
$scope = false;
$params = $gen->requestParam([
  "name" => ["name","username", 3, 21],
  "cb" => ["cb","username",3,35,[],'MIXED']
], $_GET, []);
if (!$params || !empty($gen->errors)) {
  $errs = (new InstanceError($gen,true))->get("requestParam",true);
  foreach ($errs as $er) {
    $errors[] = $er;
  }
}
if( $params ):
  if( !empty($params['name']) && !$scope = (new MultiForm(get_database(\IO\get_constant("PRJ_SERVER_NAME"), "data"),'access_scopes','name'))->findById($params['name']) ){
    $errors[] = "No record found for given [name]: {$params['name']}";
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
    <div class="grid-8-tablet grid-6-laptop center-tablet">
      <div class="sec-div theme-color amber bg-white drop-shadow">
        <header class="paddn -pall -p20 color-bg">
          <h1> <i class="fas fa-microscope"></i> Access Scope</h1>
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
            id="acsco-form"
            class="block-ui"
            method="post"
            action="/app/admin/src/PostAccessScope.php"
            data-validate="false"
            onsubmit="cwos.form.submit(this, doPost);return false;"
            >
            <input type="hidden" name="task" value="<?php echo $scope ? "UPDATE" : "CREATE"; ?>">
            <input type="hidden" name="form" value="acsco-form">
            <input type="hidden" name="CSRF_token" value="<?php echo ($session->createCSRFtoken("acsco-form"));?>">

            <div class="grid-6-tablet">
              <label for="name"><i class="fas fa-asterisk fa-border fa-sm"></i> Name (unique)</label>
              <input type="text" name="name" maxlength="21" <?php echo $scope ? 'readonly' : ''; ?> id="name" placeholder="SCOPE" required value="<?php echo $scope ? $scope->name : ''; ?>">
            </div>
            <div class="grid-4-tablet">
              <label for="rank"><i class="fas fa-asterisk fa-border fa-sm"></i> Order rank</label>
              <input type="number" name="rank" min="1" step="1" id="rank" placeholder="1" required value="<?php echo $scope ? $scope->rank : ''; ?>">
            </div>

            <div class="grid-12-tablet">
              <label for="description"><i class="fas fa-asterisk fa-border fa-sm"></i> Description</label>
              <textarea name="description" maxlength="128" minlegth="5" class="autosize" id="description" placeholder="Domain description" required><?php echo $scope ? $scope->description : ''; ?></textarea>
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
    $('input[name=name]').blur(function(){
      $(this).val($(this).val().replaceAll(' ','').toUpperCase());
    });
    $('textarea.autosize').autosize();
  })();
</script>
