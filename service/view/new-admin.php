<?php
namespace IO;
require_once "../../.appinit.php";
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <title>New Admin | <?php echo get_constant("PRJ_TITLE"); ?></title>
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
  <input type="hidden" data-setup="ui" data-handler="DashUI" data-header="/admin/get/dashui/header?rdt=<?php echo THIS_PAGE; ?>" data-sidebar="/admin/get/dashui/sidebar" data-autoinit="true">
  <input type="hidden" data-setup="uiOption" data-max-cart-item="4" data-max-notice-item="4">
  <input type="hidden" data-setup="uiNotification" data-delete="/app/helper/delete/notification" data-path="/app/user/notifications" data-get="/app/helper/get/notification">
  <input type="hidden" data-setup="uiCart" data-delete="/app/helper/delete/cart" data-path="/index/checkout" data-get="/app/helper/get/cart">

  <input type="hidden" data-setup="page" data-name="new-admin" data-group="admin">
  <input type="hidden" data-setup="dnav" data-group="admin" data-clear-elem="#cwos-content" data-pos="affix" data-container="#cwos-content" data-get="/admin/get/navigation" data-ini-top-pos="0" data-stick-on="">
  <!-- <input type="hidden" data-setup="dnavCartbot" data-path="/index/checkout" data-items="26" data-show="true"> -->


  <section id="cwos-content">
    <p>Lorem ipsum, dolor sit amet consectetur adipisicing elit. Facere eaque dolor quae eveniet hic fugit necessitatibus, possimus quaerat ducimus illum deleniti, ipsa facilis harum ea quam architecto rem amet labore!</p>
    <p>Lorem ipsum, dolor sit amet consectetur adipisicing elit. Facere eaque dolor quae eveniet hic fugit necessitatibus, possimus quaerat ducimus illum deleniti, ipsa facilis harum ea quam architecto rem amet labore!</p>
    <p>Lorem ipsum, dolor sit amet consectetur adipisicing elit. Facere eaque dolor quae eveniet hic fugit necessitatibus, possimus quaerat ducimus illum deleniti, ipsa facilis harum ea quam architecto rem amet labore!</p>
    <p>Lorem ipsum, dolor sit amet consectetur adipisicing elit. Facere eaque dolor quae eveniet hic fugit necessitatibus, possimus quaerat ducimus illum deleniti, ipsa facilis harum ea quam architecto rem amet labore!</p>
    <p>Lorem ipsum, dolor sit amet consectetur adipisicing elit. Facere eaque dolor quae eveniet hic fugit necessitatibus, possimus quaerat ducimus illum deleniti, ipsa facilis harum ea quam architecto rem amet labore!</p>
    <p>Lorem ipsum, dolor sit amet consectetur adipisicing elit. Facere eaque dolor quae eveniet hic fugit necessitatibus, possimus quaerat ducimus illum deleniti, ipsa facilis harum ea quam architecto rem amet labore!</p>
    <p>Lorem ipsum, dolor sit amet consectetur adipisicing elit. Facere eaque dolor quae eveniet hic fugit necessitatibus, possimus quaerat ducimus illum deleniti, ipsa facilis harum ea quam architecto rem amet labore!</p>
    <p>Lorem ipsum, dolor sit amet consectetur adipisicing elit. Facere eaque dolor quae eveniet hic fugit necessitatibus, possimus quaerat ducimus illum deleniti, ipsa facilis harum ea quam architecto rem amet labore!</p>
    <p>Lorem ipsum, dolor sit amet consectetur adipisicing elit. Facere eaque dolor quae eveniet hic fugit necessitatibus, possimus quaerat ducimus illum deleniti, ipsa facilis harum ea quam architecto rem amet labore!</p>
    <p>Lorem ipsum, dolor sit amet consectetur adipisicing elit. Facere eaque dolor quae eveniet hic fugit necessitatibus, possimus quaerat ducimus illum deleniti, ipsa facilis harum ea quam architecto rem amet labore!</p>
    <p>Lorem ipsum, dolor sit amet consectetur adipisicing elit. Facere eaque dolor quae eveniet hic fugit necessitatibus, possimus quaerat ducimus illum deleniti, ipsa facilis harum ea quam architecto rem amet labore!</p>
    <p>Lorem ipsum, dolor sit amet consectetur adipisicing elit. Facere eaque dolor quae eveniet hic fugit necessitatibus, possimus quaerat ducimus illum deleniti, ipsa facilis harum ea quam architecto rem amet labore!</p>
    <p>Lorem ipsum, dolor sit amet consectetur adipisicing elit. Facere eaque dolor quae eveniet hic fugit necessitatibus, possimus quaerat ducimus illum deleniti, ipsa facilis harum ea quam architecto rem amet labore!</p>
    <p>Lorem ipsum, dolor sit amet consectetur adipisicing elit. Facere eaque dolor quae eveniet hic fugit necessitatibus, possimus quaerat ducimus illum deleniti, ipsa facilis harum ea quam architecto rem amet labore!</p>
    <p>Lorem ipsum, dolor sit amet consectetur adipisicing elit. Facere eaque dolor quae eveniet hic fugit necessitatibus, possimus quaerat ducimus illum deleniti, ipsa facilis harum ea quam architecto rem amet labore!</p>
    <p>Lorem ipsum, dolor sit amet consectetur adipisicing elit. Facere eaque dolor quae eveniet hic fugit necessitatibus, possimus quaerat ducimus illum deleniti, ipsa facilis harum ea quam architecto rem amet labore!</p>
    <p>Lorem ipsum, dolor sit amet consectetur adipisicing elit. Facere eaque dolor quae eveniet hic fugit necessitatibus, possimus quaerat ducimus illum deleniti, ipsa facilis harum ea quam architecto rem amet labore!</p>
    <p>Lorem ipsum, dolor sit amet consectetur adipisicing elit. Facere eaque dolor quae eveniet hic fugit necessitatibus, possimus quaerat ducimus illum deleniti, ipsa facilis harum ea quam architecto rem amet labore!</p>
    <p>Lorem ipsum, dolor sit amet consectetur adipisicing elit. Facere eaque dolor quae eveniet hic fugit necessitatibus, possimus quaerat ducimus illum deleniti, ipsa facilis harum ea quam architecto rem amet labore!</p>
    <p>Lorem ipsum, dolor sit amet consectetur adipisicing elit. Facere eaque dolor quae eveniet hic fugit necessitatibus, possimus quaerat ducimus illum deleniti, ipsa facilis harum ea quam architecto rem amet labore!</p>
    <p>Lorem ipsum, dolor sit amet consectetur adipisicing elit. Facere eaque dolor quae eveniet hic fugit necessitatibus, possimus quaerat ducimus illum deleniti, ipsa facilis harum ea quam architecto rem amet labore!</p>
    <p>Lorem ipsum, dolor sit amet consectetur adipisicing elit. Facere eaque dolor quae eveniet hic fugit necessitatibus, possimus quaerat ducimus illum deleniti, ipsa facilis harum ea quam architecto rem amet labore!</p>
    <p>Lorem ipsum, dolor sit amet consectetur adipisicing elit. Facere eaque dolor quae eveniet hic fugit necessitatibus, possimus quaerat ducimus illum deleniti, ipsa facilis harum ea quam architecto rem amet labore!</p>
    <p>Lorem ipsum, dolor sit amet consectetur adipisicing elit. Facere eaque dolor quae eveniet hic fugit necessitatibus, possimus quaerat ducimus illum deleniti, ipsa facilis harum ea quam architecto rem amet labore!</p>
  </section>

  <script src="/app/cataliwos/plugin.cwapp/js/jquery.js"></script>
  <script src="/app/cataliwos/plugin.cwapp/js/functions.js"></script>
  <script src="/app/cataliwos/plugin.cwapp/js/class-object.js"></script>
  <script src="/app/cataliwos/dashui.cwapp/js/dashui.js"></script>
  <script src="/app/cataliwos/plugin.cwapp/js/theme.js"></script>
  <script>
  </script>
</body>
</html>