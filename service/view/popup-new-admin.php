<?php
namespace IO;
require_once "../../.appinit.php";

use \TymFrontiers\HTTP,
    \TymFrontiers\Generic,
    \TymFrontiers\Data,
    \TymFrontiers\InstanceError;

\require_login(false);
$errors = [];
$gen = new Generic;
$params = $gen->requestParam([
  "user" => ["user", "pattern", "/^(252|352)(\s|\-|\.)?([0-9]{4,4})(\s|\-|\.)?([0-9]{4,4})$/"],
  "work_group" => ["work_group", "option", \array_keys($access_ranks)]
], $_GET, []);
if (!$params || !empty($gen->errors)) {
  $errs = (new InstanceError ($gen, false))->get("requestParam",true);
  foreach ($errs as $er) {
    $errors[] = $er;
  }
}
if( $params ):
endif;
?>
<div id="fader-flow">
  <div class="view-space-midi">
    <div class="paddn -pall -p30">&nbsp;</div>
    <br class="c-f">
    <div class="grid-10-tablet grid-8-laptop  grid-6-desktop center-tablet">
      <div class="sec-div theme-color native bg-white drop-shadow">
        <header class="paddn -pall -p30 color-bg">
            <h1 class="fw-lighter"> <i class="fas fa-bell-on"></i> Notification</h1>
        </header>
        <div class="paddn -pall -p30">
          <?php if(!empty($errors)){ ?>
            <h3>Unresolved error(s)</h3>
            <ol>
              <?php foreach($errors as $err){  echo " <li>{$err}</li>"; } ?>
            </ol>
          <?php } else { ?>
            <!-- views here -->
          <?php } ?>
          <br class="c-f">
        </div>
      </div>
    </div>
    <br class="c-f">
  </div>
</div>

<script type="text/javascript">
</script>
