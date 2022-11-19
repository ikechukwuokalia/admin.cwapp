<?php
namespace IO;
require_once "../../.appinit.php";
use \TymFrontiers\Generic,
    \TymFrontiers\MultiForm,
    \TymFrontiers\InstanceError;

\require_login(false);
\check_access("WRITE", "/work-paths", "project-admin","", false);
$errors = [];
$gen = new Generic;
$path = false;
$params = $gen->requestParam([
  "id" => ["id","int"],
  "domain" => ["domain","username",3,98,[],'LOWER',['-','.']],
  "callback" => ["callback","username",3,35,[],'MIXED']
], $_GET, []);
if (!$params || !empty($gen->errors)) {
  $errs = (new InstanceError($gen,true))->get("requestParam",true);
  foreach ($errs as $er) {
    $errors[] = $er;
  }
}
if( $params ):
  if( !empty($params['id']) && !$path = (new MultiForm(get_database(\IO\get_constant("PRJ_SERVER_NAME"), "admin"),'work_paths','id'))->findById($params['id']) ){
    $errors[] = "No record found for given [id]: {$params['id']}";
  }
  if ($path) $path->access_scope = (!empty($path->access_scope) ? \explode(",", $path->access_scope) : []);
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
          <h1> <i class="fas fa-folder-tree"></i> Work path</h1>
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
            id="work-path-form"
            class="block-ui"
            method="post"
            action="/app/admin/src/PostWorkPath.php"
            data-validate="false"
            onsubmit="cwos.form.submit(this, doPost);return false;"
            >
            <input type="hidden" name="id" value="<?php echo $path ? $path->id : ""; ?>">
            <input type="hidden" name="form" value="work-path-form">
            <input type="hidden" name="CSRF_token" value="<?php echo ($session->createCSRFtoken("work-path-form"));?>">

            <div class="grid-5-tablet">
              <label><i class="fas fa-asterisk fa-border fa-sm"></i> Work domain</label>
              <select name="domain" id="domain" required>
                <option value="">* Choose a domain</option>
                <optgroup label="Work domains">
                  <?php if ($domains = (new MultiForm(get_database(\IO\get_constant("PRJ_SERVER_NAME"), "admin"), "work_domains", "name"))->findAll()) {
                    foreach ($domains as $dmn) {
                      echo "<option value=\"{$dmn->name}\"";
                        echo $path && $path->domain == $dmn->name ? " selected" : (!empty($params['domain']) && $params['domain'] == $dmn->name ? " selected" : "");
                      echo ">{$dmn->name}</option>";
                    }
                  } ?>
                </optgroup>
              </select>
            </div>
            <div class="grid-7-tablet">
              <label for="path"><i class="fas fa-asterisk fa-border fa-sm"></i> Path</label>
              <input type="text" name="path" maxlength="56" id="path" placeholder="/path" required value="<?php echo $path ? $path->path : ''; ?>">
            </div>
            <div class="grid-5-tablet">
              <label><i class="fas fa-asterisk fa-border fa-sm"></i> Task group</label>
              <select name="resource_type" id="task-group" required>
                <option value="">* Choose a group</option>
                <optgroup label="Groups">
                  <?php if ($groups = (new MultiForm(get_database(\IO\get_constant("PRJ_SERVER_NAME"), "data"), "resource_types", "name"))->findAll()) {
                    foreach ($groups as $grp) {
                      echo "<option value=\"{$grp->name}\"";
                        echo $path && $path->resource_type == $grp->name ? " selected" : "";
                      echo " title=\"{$grp->description}\">{$grp->title}</option>";
                    }
                  } ?>
                </optgroup>
              </select>
            </div>
            <div class="grid-5-tablet">
              <label for="icon"> Display icon</label>
              <input type="text" name="icon" maxlength="72" id="icon" placeholder="fas fa-globe" value="<?php echo $path ? $path->icon : ''; ?>">
            </div>
            <div class="grid-6-tablet">
              <label for="classname"> Class name(s)</label>
              <input type="text" name="classname" maxlength="56" id="classname" placeholder="class1 class2" value="<?php echo $path ? $path->classname : ''; ?>">
            </div>
            <div class="grid-5-tablet">
              <label for="onclick"> OnClick (function)</label>
              <input type="text" name="onclick" maxlength="32" id="onclick" placeholder="functionName" value="<?php echo $path ? $path->onclick : ''; ?>">
            </div>
            <br class="c-f">
            
            <div class="grid-6-tablet">
              <label for="title"><i class="fas fa-asterisk fa-border fa-sm"></i> Title</label>
              <input type="text" name="title" required maxlength="56" id="title" placeholder="Path title" value="<?php echo $path ? $path->title : ''; ?>">
            </div>
            <div class="grid-4-tablet">
              <label for="sort"> Sort (ASC)</label>
              <input type="number" name="sort" step="1" id="sort" placeholder="1" value="<?php echo $path ? $path->sort : ''; ?>">
            </div>
            <div class="grid-12-tablet">
              <label for="description"><i class="fas fa-asterisk fa-border fa-sm"></i> Description</label>
              <textarea name="description" maxlength="250" minlegth="5" class="autosize" id="description" placeholder="Domain description" required><?php echo $path ? $path->description : ''; ?></textarea>
            </div>
            <div class="grid-12-tablet">
              <h4>Access scope</h4>
              <?php if ($scopes = (new MultiForm(get_database(\IO\get_constant("PRJ_SERVER_NAME"), "data"), "access_scopes", "name"))->findBySql("SELECT * FROM :db:.:tbl: ORDER BY `rank` ASC")) {
                foreach ($scopes as $scope) {
                  echo "<span>";
                    echo "<input type=\"checkbox\" name=\"access_scope\" id=\"scope-{$scope->name}\" value=\"{$scope->name}\"";
                      echo $path && \in_array($scope->name, $path->access_scope) ? " checked" : "";
                    echo ">";
                    echo "<label title=\"{$scope->description}\" for=\"scope-{$scope->name}\">{$scope->name}</label>";
                  echo "</span>";
                }
              } ?>
            </div>
            <div class="grid-7-tablet">
              <h4>Nav vissibility</h4>
              <input type="checkbox" <?php echo $path && (bool)$path->nav_visible ? " checked" : ""; ?> value="1" name="nav_visible" id="nav-visible">
              <label for="nav-visible">On</label>
            </div>
            <div class="grid-5-tablet"> <br>
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
    $('textarea.autosize').autosize();
  })();
</script>
