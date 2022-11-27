<?php
namespace IO;
require_once "../.appinit.php";
use \TymFrontiers\Generic,
    \TymFrontiers\MultiForm,
    \TymFrontiers\InstanceError;

\require_login(false);
$errors = [];
?>
<script type="text/javascript">
  if (typeof window["param"] == undefined || !window["param"]) window["param"] = {};
</script>
<div id="fader-flow">
  <div class="view-space">
    <div class="paddn -pall -p20">&nbsp;</div>
    <br class="c-f">
    <div class="grid-10-tablet grid-8-laptop center-tablet">
      <div class="sec-div theme-color asphalt bg-white drop-shadow">
        <header class="paddn -pall -p20 color-bg">
          <h1> <i class="fas fa-key"></i> Change your login password</h1>
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
            action="/app/admin/src/PatchPassword.php"
            data-validate="false"
            onsubmit="cwos.form.submit(this, doPost);return false;"
            >
            
            <div class="grid-6-tablet">
              <label for="password"><i class="fas fa-asterisk fa-border fa-sm"></i> Current Password</label>
              <input type="password" name="old_password" required maxlength="32" id="password" placeholder="old password">
            </div> <br class="c-f">
            <div class="grid-6-tablet">
              <label for="new-password"><i class="fas fa-asterisk fa-border fa-sm"></i> New Password</label>
              <input type="password" name="password" required maxlength="32" id="new-password" placeholder="new password">
            </div>
            <div class="grid-6-tablet">
              <label for="password-repeat"><i class="fas fa-asterisk fa-border fa-sm"></i> Repeat Password</label>
              <input type="password" name="password_repeat" required maxlength="32" id="password-repeat" placeholder="repeat password">
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
</script>
