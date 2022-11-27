<?php
namespace IO;
require_once "../../.appinit.php";
use \TymFrontiers\Generic,
    \TymFrontiers\MultiForm,
    \TymFrontiers\InstanceError;

\require_login(false);
\check_access("WRITE", "/setting-options", "project-admin","", false);
$errors = [];
$gen = new Generic;
$option = false;
$params = $gen->requestParam([
  "id"    => ["id","int",1,0],
  "callback" => ["callback","username",3,35,[],'MIXED']
], $_GET, []);
if (!$params || !empty($gen->errors)) {
  $errs = (new InstanceError($gen,true))->get("requestParam",true);
  foreach ($errs as $er) {
    $errors[] = $er;
  }
}
if( $params ):
  if( !empty($params['id']) && !$option = (new MultiForm(get_database("data", \IO\get_constant("PRJ_SERVER_NAME")),'setting_options','id'))->findById($params['id']) ){
    $errors[] = "No record found for given [id]: {$params['id']}";
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
      <div class="sec-div theme-color asphalt bg-white drop-shadow">
        <header class="paddn -pall -p20 color-bg">
          <h1> <i class="fas fa-cogs"></i> Setting option</h1>
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
            id="setting-option-form"
            class="block-ui"
            method="post"
            action="/app/admin/src/PostSettingOption.php"
            data-validate="false"
            onsubmit="cwos.form.submit(this, doPost);return false;"
            >
            <input type="hidden" name="id" value="<?php echo $option ? $option->id : ""; ?>">
            <input type="hidden" name="form" value="setting-option-form">
            <input type="hidden" name="CSRF_token" value="<?php echo ($session->createCSRFtoken("setting-option-form"));?>">

            <div class="grid-6-tablet">
              <lable class="bold"><i class="fas fa-asterisk fa-sm fa-border"></i> Type</lable>
              <select name="type" required>
                <option value="">* Choose option type</option>
              <?php foreach ((new \TymFrontiers\Validator)->validate_type as $type=>$desc) {
                  echo " <option value=\"{$type}\" title=\"{$desc}\" ";
                  echo $option && $option->type == $type ? 'selected' : '';
                  echo ">{$desc}</option>";
                }?>
              </select>
            </div>
            <div class="grid-12-tablet">
              <label for="variant"> Type variant</label>
              <input type="text" name="variant" maxlength="512" id="variant" placeholder="options-:VALUE1-,VALUE2-,VALUE3-;minlen-:34-;maxlen-:235" value="<?php echo $option ? $option->variant : ''; ?>">
            </div>
            <div class="grid-8-tablet">
              <label for="name"><i class="fas fa-asterisk fa-sm fa-border"></i> Name</label>
              <input type="text" name="name" maxlength="32" id="name" <?php echo $option ? 'readonly' : ''; ?> placeholder="OPTION-NAME" required value="<?php echo $option ? $option->name : ''; ?>">
            </div>

            <div class="grid-10-tablet">
              <label for="title"><i class="fas fa-asterisk fa-sm fa-border"></i> Title</label>
              <input type="text" name="title" maxlength="52" id="title" placeholder="Title" required value="<?php echo $option ? $option->title : ''; ?>">
            </div>
            <div class="grid-12-tablet">
              <label for="description"><i class="fas fa-asterisk fa-sm fa-border"></i> Description</label>
              <textarea name="description" required maxlength="256" minlegth="5" class="autosize" id="description" placeholder="Path description"><?php echo $option ? $option->description : ''; ?></textarea>
            </div>

            <div class="grid-6-tablet">
              <input type="checkbox" name="multi_val" value="1" <?php echo !$option || ($option && (bool)$option->multi_val) ? "checked" : ""; ?> id="multi-val">
              <label for="multi-val"> Allow multiple setting values</label>
            </div>
            <div class="grid-6-tablet">
              <input type="checkbox" name="encrypt" value="1" <?php echo !$option || ($option && (bool)$option->encrypt) ? "checked" : ""; ?> id="encrypt-val">
              <label for="encrypt-val"> Encrypt setting value</label>
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
    $('input[name=name]').blur(function(){
      $(this).val($(this).val().replaceAll(' ','.').toUpperCase());
    });
    $('textarea.autosize').autosize();
  })();
</script>
