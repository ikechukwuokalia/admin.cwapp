<?php
namespace IO;
require_once "../../.appinit.php";
use \TymFrontiers\Generic,
    \TymFrontiers\MultiForm,
    \TymFrontiers\InstanceError;

\require_login(false);
\check_access("WRITE", "/work-domains", "project-admin","", false);
$errors = [];
$gen = new Generic;
$domain = false;
$params = $gen->requestParam([
  "name" => ["name","username",3,98,[],'LOWER',['-','.']],
  "callback" => ["callback","username",3,35,[],'MIXED']
], $_GET, []);
if (!$params || !empty($gen->errors)) {
  $errs = (new InstanceError($gen,true))->get("requestParam",true);
  foreach ($errs as $er) {
    $errors[] = $er;
  }
}
if( $params ):
  if( !empty($params['name']) && !$domain = (new MultiForm(get_database("CWS", "admin"),'work_domains','name'))->findById($params['name']) ){
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
    <div class="grid-10-tablet grid-8-laptop center-tablet">
      <div class="sec-div theme-color asphalt bg-white drop-shadow">
        <header class="paddn -pall -p20 color-bg">
          <h1> <i class="fas fa-globe"></i> Work domain</h1>
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
            id="work-domain-form"
            class="block-ui"
            method="post"
            action="/app/admin/src/PostWorkDomain.php"
            data-validate="false"
            onsubmit="cwos.form.submit(this, doPost);return false;"
            >
            <input type="hidden" name="task" value="<?php echo $domain ? "UPDATE" : "CREATE"; ?>">
            <input type="hidden" name="form" value="work-domain-form">
            <input type="hidden" name="CSRF_token" value="<?php echo ($session->createCSRFtoken("work-domain-form"));?>">

            <div class="grid-8-tablet">
              <label for="name"><i class="fas fa-asterisk fa-border fa-sm"></i> Domain name</label>
              <input type="text" name="name" maxlength="98" <?php echo $domain ? 'readonly' : ''; ?> id="name" placeholder="domain.com" required value="<?php echo $domain ? $domain->name : ''; ?>">
            </div>
            <div class="grid-4-tablet">
              <label for="acronym"><i class="fas fa-asterisk fa-border fa-sm"></i> Acronym</label>
              <input type="text" name="acronym" maxlength="16" id="acronym" placeholder="ACR" required value="<?php echo $domain ? $domain->acronym : ''; ?>">
            </div>
            <div class="grid-7-tablet">
              <label for="path"><i class="fas fa-asterisk fa-border fa-sm"></i> Admin path</label>
              <input type="text" name="path" maxlength="72" id="path" placeholder="/" required value="<?php echo $domain ? $domain->path : ''; ?>">
            </div>
            <div class="grid-6-tablet">
              <label for="title"><i class="fas fa-asterisk fa-border fa-sm"></i> Display title</label>
              <input type="text" required name="title" maxlength="56" id="title" placeholder="Title" value="<?php echo $domain ? $domain->title : ''; ?>">
            </div>
            
            <div class="grid-12-tablet">
              <label for="description"><i class="fas fa-asterisk fa-border fa-sm"></i> Description</label>
              <textarea name="description" maxlength="128" minlegth="5" class="autosize" id="description" placeholder="Domain description" required><?php echo $domain ? $domain->description : ''; ?></textarea>
            </div>
            <div class="grid-6-tablet">
              <label for="avatar"> Display avatar</label>
              <input type="text" name="avatar" maxlength="256" id="avatar" placeholder="/path/to/avatar" value="<?php echo $domain ? $domain->avatar : ''; ?>">
            </div>
            <div class="grid-6-tablet">
              <label for="cover-art"> Cover art</label>
              <input type="text" name="cover_art" maxlength="256" id="cover-art" placeholder="/path/to/cover-art" value="<?php echo $domain ? $domain->cover_art : ''; ?>">
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
      $(this).val($(this).val().replaceAll(' ','-'));
    });
    $('input[name=acronym]').blur(function(){
      $(this).val($(this).val().replaceAll(' ','').toUpperCase());
    })
    $('textarea.autosize').autosize();
  })();
</script>
