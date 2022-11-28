<?php
namespace IO;

use TymFrontiers\MultiForm,
    TymFrontiers\HTTP,
    TymFrontiers\Location;
use function \get_database;
require_once "../.appinit.php";
if ($session->isLoggedIn()) HTTP\Header::redirect(WHOST . "/index");
$location = new Location();
if ($user_max_age = setting_get_value("SYSTEM", "USER.MAX-AGE", get_constant("PRJ_BASE_DOMAIN"))) {
  $user_max_age = (int)$user_max_age;
} else {
  $user_max_age = 95;
}
if ($user_min_age = setting_get_value("SYSTEM", "USER.MIN-AGE", get_constant("PRJ_BASE_DOMAIN"))) {
  $user_min_age = (int)$user_min_age;
} else {
  $user_min_age = 14;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <title>Request Admin account | <?php echo get_constant("PRJ_TITLE"); ?></title>
  <meta charset="UTF-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <?php include get_constant("PRJ_INC_ICONSET"); ?>
  <meta name='viewport' content='width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=0'>
  <meta name="keywords" content="new, invite, admin">
  <meta name="description" content="Invite Admin to join <?php echo get_constant("PRJ_TITLE"); ?>">
  <meta name="author" content="<?php echo get_constant("PRJ_AUTHOR"); ?>">
  <meta name="creator" content="<?php echo get_constant("PRJ_CREATOR"); ?>">
  <meta name="publisher" content="<?php echo get_constant("PRJ_PUBLISHER"); ?>">
  <meta name="robots" content='nofollow'>
  
  <!-- Project styling -->
  <link rel="stylesheet" href="/app/cataliwos/plugin.cwapp/css/font-awesome.min.css">
  <link rel="stylesheet" href="/app/cataliwos/plugin.cwapp/css/theme.min.css">
  <link rel="stylesheet" href="/app/cataliwos/dashui.cwapp/css/dashui.min.css">
  <link rel="stylesheet" href="/app/ikechukwuokalia/helper.cwapp/css/helper.min.css">
  <link rel="stylesheet" href="/assets/css/base.min.css">
  <link rel="stylesheet" href="/app/ikechukwuokalia/admin.cwapp/css/admin.min.css">
  <script type="text/javascript">
    if (typeof window["param"] == undefined || !window["param"]) window["param"] = {};
    <?php if (!empty($params) && \is_array($params)) { foreach ($params as $k=>$val) { echo "param['{$k}'] = '{$val}';"; } } ?>
  </script>

</head>
<body class="theme-<?php echo get_constant("PRJ_THEME"); ?>">
  <div id="cwos-uiloadr"></div>
  <input type="hidden" data-setup="page" data-name="sign-up" data-group="admin">

  <input type="hidden" data-setup="ui" data-handler="DashUI" data-header="/admin/get/dashui/header?rdt=<?php echo THIS_PAGE; ?>" data-sidebar="/admin/get/dashui/sidebar" data-autoinit="true">
  <input type="hidden" data-setup="uiOption" data-max-cart-item="4" data-max-notice-item="4" data-hide="true">
  <input type="hidden" data-setup="uiNotification" data-delete="/app/helper/delete/notification" data-path="/app/user/notifications" data-get="/app/helper/get/notification">
  <input type="hidden" data-setup="uiCart" data-delete="/app/helper/delete/cart" data-path="/index/checkout" data-get="/app/helper/get/cart">

  <input type="hidden" data-setup="dnav" data-group="user" data-clear-elem="#cwos-content" data-pos="affix" data-container="#cwos-content" data-get="/admin/get/navigation" data-ini-top-pos="0" data-stick-on="">


  <section id="cwos-content">
    <div class="view-space">
      <br class="c-f">
      <div class="grid-10-tablet grid-8-laptop grid-6-desktop center-tablet">
        <div class="sec-div theme-color asphalt paddn -pall -p30 bg-white drop-shadow">
          <h2><i class="fas fa-plus-circle"></i> Sign up</h2>
          <p>Signup for an Admin account, your request will be reviewed and you will be notified via email.</p>
          <form
          autocomplete="off" 
          id="adm-signup-form" 
          method="post"
          action="/app/admin/src/SignUpRequest.php"
          data-validate="false"
          onsubmit="chkSignUp(this); return false;"
          class="block-ui margn -mtop -m10">
          <input type="hidden" name="CSRF_token" value="<?php echo $session->createCSRFtoken('adm-signup-form'); ?>">
          <input type="hidden" name="form" value="adm-signup-form">
          <input type="hidden" name="otp" id="signup-otp" value="">
          <input type="hidden" name="email" id="signup-email" value="">

          <div class="grid-7-tablet push-left">
            <label><i class="fas fa-flag"></i> Country </label>
            <select name="country_code" id="country-code" required>
              <option value="">* Choose country/region</option>
              <optgroup label="Countries">
                <?php if ($countries = (new MultiForm(get_database("data", get_constant("PRJ_SERVER_NAME")), "countries", "code"))->findAll()):
                  foreach ($countries as $country):
                    echo "<option value=\"{$country->code}\"";
                      echo $location && $location->country_code == $country->code ? " selected" : "";
                    echo ">{$country->name}</option>";
                  endforeach;
                endif; ?>
              </optgroup>
            </select>
          </div> <br class="c-f">
          <div class="grid-6-tablet">
            <label for="name"><i class="fas fa-user"></i> Name</label>
            <input type="text" placeholder="First name" name="name" autocomplete="email" required id="name">
          </div>
          <div class="grid-6-tablet">
            <label for="surname"><i class="fas fa-user"></i> Surname</label>
            <input type="text" placeholder="Surname" name="surname" autocomplete="new-surname" required id="surname">
          </div>
          <div class="grid-6-tablet">
            <label for="dob"><i class="fas fa-calendar-day"></i> Date of Birth</label>
            <input 
              type="date" 
              placeholder="YYYY-MM-DD" 
              name="dob" 
              autocomplete="off"
              id="dob"
              max="<?php echo \date("Y-m-d",\strtotime("- {$user_min_age} Years")); ?>"
              min="<?php echo \date("Y-m-d",\strtotime("- {$user_max_age} Years")); ?>"
            required>
          </div>
          <div class="grid-6-tablet">
            <label><i class="fas fa-venus-mars"></i> Gender</label> <br>
            <span>
              <input type="radio" name="sex" id="sex-male" value="MALE" checked>
              <label for="sex-male">Male</label>
            </span>
            <span>
              <input type="radio" name="sex" id="sex-female" value="FEMALE">
              <label for="sex-female">Female</label>
            </span>
          </div>
          <br class="c-f">
          <div class="grid-7-tablet">
            <label for="email"><i class="fas fa-envelope"></i> Email</label>
            <input type="email" autocomplete="new-email" name="email" id="email" required placeholder="valid-email@domain.ext">
          </div>
          <div class="grid-5-tablet">
            <label for="phone"><i class="fas fa-phone"></i> Phone</label>
            <input type="tel" autocomplete="new-phone" name="phone" id="phone" required placeholder="0801 234 5678">
          </div>
          <div class="grid-6-tablet">
            <label for="new-password"><i class="fas fa-key"></i> New password</label>
            <input type="password" placeholder="Password" name="password" autocomplete="off" required id="new-password">
          </div>
          <div class="grid-6-tablet">
            <label class="placeholder" for="password-repeat"><i class="fas fa-key"></i> Repeat password </label>
            <input type="password" placeholder="Repeat-Password" name="password_repeat" autocomplete="off" required id="password-repeat">
          </div>

          <div class="grid-6-tablet push-right">
            <button class="theme-button asphalt" type="submit"> <i class="fas fa-plus-circle"></i> Sign Up</button>
          </div>

          <br class="c-f">
        </form>
        </div>
      </div>

      <br class="c-f">
    </div>
  </section>

  <script src="/app/cataliwos/plugin.cwapp/js/jquery.min.js"></script>
  <script src="/app/cataliwos/plugin.cwapp/js/functions.min.js"></script>
  <script src="/app/cataliwos/plugin.cwapp/js/class-object.min.js"></script>
  <script src="/app/cataliwos/dashui.cwapp/js/dashui.min.js"></script>
  <script src="/app/cataliwos/plugin.cwapp/js/theme.min.js"></script>
  <script src="/app/ikechukwuokalia/helper.cwapp/js/helper.min.js"></script>
  <script src="/assets/js/base.min.js"></script>
  <script src="/app/admin/js/admin.min.js"></script>
  <script>
  </script>
</body>
</html>