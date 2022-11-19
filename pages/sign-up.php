<?php
namespace IO;
require_once "../.appinit.php";
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
  
    <!-- Theming styles -->
  <link rel="stylesheet" href="/app/cataliwos/plugin.cwapp/css/font-awesome.min.css">
  <!-- Project styling -->
  <link rel="stylesheet" href="/app/cataliwos/plugin.cwapp/css/theme.min.css">
  <link rel="stylesheet" href="/app/cataliwos/dashui.cwapp/css/dashui.min.css">
  <link rel="stylesheet" href="/app/ikechukwuokalia/helper.cwapp/css/helper.min.css">
  <link rel="stylesheet" href="/assets/css/base.min.css">
  <script type="text/javascript">
    if (typeof window["param"] == undefined || !window["param"]) window["param"] = {};
    <?php if (!empty($params) && \is_array($params)) { foreach ($params as $k=>$val) { echo "param['{$k}'] = '{$val}';"; } } ?>
  </script>

</head>
<body class="theme-native">
  <div id="cwos-uiloadr"></div>
  <input type="hidden" data-setup="page" data-name="sign-up" data-group="admin">

  <input type="hidden" data-setup="ui" data-handler="DashUI" data-header="/admin/get/dashui/header?rdt=<?php echo THIS_PAGE; ?>" data-sidebar="/admin/get/dashui/sidebar" data-autoinit="true">
  <input type="hidden" data-setup="uiOption" data-max-cart-item="4" data-max-notice-item="4">
  <input type="hidden" data-setup="uiNotification" data-delete="/app/helper/delete/notification" data-path="/app/user/notifications" data-get="/app/helper/get/notification">
  <input type="hidden" data-setup="uiCart" data-delete="/app/helper/delete/cart" data-path="/index/checkout" data-get="/app/helper/get/cart">

  <input type="hidden" data-setup="dnav" data-group="admin" data-clear-elem="#cwos-content" data-pos="affix" data-container="#cwos-content" data-get="/admin/get/navigation" data-ini-top-pos="0" data-stick-on="">


  <section id="cwos-content">
    <div class="view-space">
      <br class="c-f">
      <div class="grid-10-tablet grid-8-laptop grid-6-desktop center-tablet">
        <div class="sec-div theme-color asphalt paddn -pall -p30 bg-white drop-shadow">
          <h2><i class="fas fa-plus-circle"></i> Sign up</h2>
          <p>Request Admin account using your existing Catali User/Developer profile.</p>
          <p>You can create a Catali Profile <a href="https://dashboard.cataliws.com/app/user/sign-up?rdt=<?php echo THIS_PAGE; ?>">here <i class="fas fa-angle-double-right"></i></a></p>
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

          <div class="grid-12-tablet">
            <label id="user-name"></label>
          </div>
          <div class="grid-6-tablet push-left">
            <label for="user"><i class="fas fa-hashtag"></i> <i class="fas fa-asterisk fa-sm rq-tag"></i> Profile Code </label>
            <input type="text" name="user" id="user" autocomplete="off" placeholder="000 0000 0000" required pattern="(252|352)([\-|\s]{1,1})?([\d]{4,4})([\-|\s]{1,1})?([\d]{4,4})">
          </div> <br class="c-f">
          <div class="grid-6-tablet">
            <label for="new-password"><i class="fas fa-key"></i> <i class="fas fa-asterisk fa-sm rq-tag"></i> New password</label>
            <input type="password" placeholder="Password" name="password" autocomplete="off" required id="new-password">
          </div>
          <div class="grid-6-tablet">
            <label class="placeholder" for="password-repeat"><i class="fas fa-key"></i> <i class="fas fa-asterisk fa-sm rq-tag"></i> Repeat password </label>
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