<?php
namespace IO;
require_once "../.appinit.php";
use \TymFrontiers\HTTP,
    \TymFrontiers\Data,
    \TymFrontiers\Generic;

if ($session->isLoggedIn()) HTTP\Header::redirect(WHOST . "/index");
$gen = new Generic;
$data = new Data;
$params = $gen->requestParam([
  "rdt" => ["rdt","url"]
],'get',[]);
if (!$params) HTTP\Header::badRequest(true);
?>
<!DOCTYPE html>
<html lang="en" dir="ltr" manifest="/site.webmanifest">
  <head>
    <meta charset="utf-8">
    <title>Sign In | <?php echo get_constant("PRJ_TITLE"); ?></title>
    <?php include get_constant("PRJ_INC_ICONSET"); ?>
    <meta name='viewport' content='width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=0'>
    <meta name="keywords" content="password, reset, forgot, forget, login">
    <meta name="description" content="Reset your forgotten login password | <?php echo get_constant("PRJ_TITLE"); ?>">
    <meta name="author" content="<?php echo get_constant("PRJ_AUTHOR"); ?>">
    <meta name="creator" content="<?php echo get_constant("PRJ_CREATOR"); ?>">
    <meta name="publisher" content="<?php echo get_constant("PRJ_PUBLISHER"); ?>">
    <meta name="robots" content='index'>
    <!-- Theming styles -->
    <link rel="stylesheet" href="/app/cataliwos/plugin.cwapp/css/font-awesome.min.css">
    <link rel="stylesheet" href="/app/cataliwos/plugin.cwapp/css/theme.min.css">
    <link rel="stylesheet" href="/app/cataliwos/dashui.cwapp/css/dashui.min.css">
    <link rel="stylesheet" href="/app/ikechukwuokalia/helper.cwapp/css/helper.min.css">
    <link rel="stylesheet" href="/assets/css/base.min.css">
    <script type="text/javascript">
      if (typeof window["param"] == undefined || !window["param"]) window["param"] = {};
      <?php if (!empty($params) && \is_array($params)) { foreach ($params as $k=>$val) { echo "param['{$k}'] = '{$val}';"; } } ?>
      </script>
  </head>
  <body class="theme-aphalt">
    <input type="hidden" data-setup="page" data-name="sign-in" data-group="admin">
    
    <input type="hidden" data-setup="ui" data-handler="DashUI" data-header="/admin/get/dashui/header?rdt=<?php echo THIS_PAGE; ?>" data-sidebar="/admin/get/dashui/sidebar" data-autoinit="true">
    <input type="hidden" data-setup="uiOption" data-max-cart-item="4" data-max-notice-item="4">
    <input type="hidden" data-setup="uiNotification" data-delete="/app/helper/delete/notification" data-path="/app/user/notifications" data-get="/app/helper/get/notification">
    <input type="hidden" data-setup="uiCart" data-delete="/app/helper/delete/cart" data-path="/index/checkout" data-get="/app/helper/get/cart">
    
    <input type="hidden" data-setup="dnav" data-group="admin" data-clear-elem="#cwos-content" data-pos="affix" data-container="#cwos-content" data-get="/admin/get/navigation" data-ini-top-pos="0" data-stick-on="">
    
    <section id="cwos-content">
      <div class="view-space">
        <div class="padding -p30">&nbsp;</div>
        <div class="grid-10-tablet grid-8-laptop grid-6-desktop center-tablet">
          <div class="paddn -pall -p30 bg-white drop-shadow theme-color asphalt">
            <form 
            autocomplete="off" 
            id="admin-sign-in" 
            method="post"
            action="/app/admin/src/SignIn.php"
            data-validate="false"
            onsubmit="cwos.form.submit(this, signIn); return false;"
            class="actn-sectn block-ui paddn -pall -p20">
            
            <input type="hidden" name="CSRF_token" value="<?php echo $session->createCSRFtoken('admin-sign-in-form'); ?>">
            <input type="hidden" name="form" value="admin-sign-in-form">
            <input type="hidden" name="rdt" value="<?php echo $params['rdt']; ?>">
            <input type="hidden" name="otp" value="">
            <input type="hidden" name="user" value="">
            <div class="grid-12-tablet">
                <h2>Sign in to continue</h2>
                <p>Enter Login Credentials and hit <b>Sign In</b></p>
              </div>
              <div class="grid-12-tablet">
                <label for="code"><i class="fas fa-hashtag"></i> <i class="fas fa-asterisk fa-sm rq-tag"></i> Account ID</label>
                <input type="text" name="code" pattern="052([\-|\s]{1,1})?([\d]{4,4})([\-|\s]{1,1})?([\d]{4,4})" id="code" autocomplete="off" required placeholder="052 0000 0000">
              </div>
              <div class="grid-8-tablet">
                <label for="password"><i class="fas fa-key"></i> <i class="fas fa-asterisk fa-sm rq-tag"></i> Password</label>
                <input type="password" name="password" autocomplete="off" id="password" required placeholder="Password">
              </div>
              <div class="grid-4-tablet"> <label class="match-input"></label>
                <button type="submit" class="theme-button asphalt no-shadow"><i class="fas fa-sign-in-alt"></i> Sign In</button>
              </div>
              <div class="grid-12-tablet align-center">
                <input type="checkbox" name="remember" id="remember-1" class="solid">
                <label for="remember-1">Keep me logged in for the whole day.</label>
              </div>
              <br class="c-f">
              <!-- <p class="align-right paddn -pall -p20"><a href="<?php //echo Generic::setGet("/app/user/password-reset", ["rdt"=>$params["rdt"]]); ?>"><i class="fas fa-key"></i> Reset Password</a></p> -->
            </form>
          </div>
        </div>
        <br class="c-f">
      </div>
    </section>
    <!-- Required scripts -->
    <script src="/app/cataliwos/plugin.cwapp/js/jquery.min.js"></script>
  <script src="/app/cataliwos/plugin.cwapp/js/functions.min.js"></script>
  <script src="/app/cataliwos/plugin.cwapp/js/class-object.min.js"></script>
  <script src="/app/cataliwos/dashui.cwapp/js/dashui.min.js"></script>
  <script src="/app/cataliwos/plugin.cwapp/js/theme.min.js"></script>
  <script src="/app/ikechukwuokalia/helper.cwapp/js/helper.min.js"></script>
  <script src="/assets/js/base.min.js"></script>
  <script src="/app/admin/js/admin.min.js"></script>
    <script type="text/javascript">
    </script>
  </body>
</html>
