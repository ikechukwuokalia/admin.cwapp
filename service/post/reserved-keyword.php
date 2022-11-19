<?php
namespace IO;
require_once "../../.appinit.php";
use \TymFrontiers\Generic,
    \TymFrontiers\MultiForm,
    \TymFrontiers\InstanceError;

\require_login(false);
\check_access("WRITE", "/reserved-keyword", "project-admin","", false);
$errors = [];
$gen = new Generic;
$kwd = false;
$params = $gen->requestParam([
  "id" => ["id","int"],
  "callback" => ["callback","username",3,35,[],'MIXED']
], $_GET, []);
if (!$params || !empty($gen->errors)) {
  $errs = (new InstanceError($gen,true))->get("requestParam",true);
  foreach ($errs as $er) {
    $errors[] = $er;
  }
}
if( $params ):
  if( !empty($params['id']) && !$kwd = (new MultiForm(get_database(\IO\get_constant("PRJ_SERVER_NAME"), "data"),'reserved_keywords','id'))->findById($params['id']) ){
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
          <h1> <i class="fas fa-ban"></i> Reserved keyword</h1>
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
            id="reserved-keyword-form"
            class="block-ui"
            method="post"
            action="/app/admin/src/PostReservedKeyword.php"
            data-validate="false"
            onsubmit="cwos.form.submit(this, doPost);return false;"
            >
            <input type="hidden" name="id" value="<?php echo $kwd ? $kwd->id : ""; ?>">
            <input type="hidden" name="form" value="reserved-keyword-form">
            <input type="hidden" name="CSRF_token" value="<?php echo ($session->createCSRFtoken("reserved-keyword-form"));?>">

            <div class="grid-7-tablet">
              <label for="keyword"><i class="fas fa-asterisk fa-border fa-sm"></i> Keyword</label>
              <input type="text" name="keyword" maxlength="21" id="keyword" placeholder="keyword" required value="<?php echo $kwd ? $kwd->keyword : ''; ?>">
            </div> 

            <div class="grid-12-tablet">
              <label>Type</label> <br>
              <span>
                <input type="radio" <?php echo !$kwd || ($kwd && $kwd->type == "RESERVED") ? " checked" : ""; ?> name="type" id="type-resv" value="RESERVED">
                <label for="type-resv">Reserved</label>
              </span>
              <span>
                <input type="radio" <?php echo $kwd && $kwd->type == "RESTRICTED" ? " checked" : ""; ?> name="type" id="type-rst" value="RESTRICTED">
                <label for="type-rst">Restricted</label>
              </span>
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
    $('input[name=keyword]').blur(function(){
      $(this).val($(this).val().replaceAll(' ','-'));
    });
  })();
</script>
