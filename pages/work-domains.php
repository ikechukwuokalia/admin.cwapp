<?php
namespace IO;
require_once "../.appinit.php";

\require_login(true);
$work_domain = "project-admin";
$nav_group = "index";
$page_name = "work-domains";
\check_access("READ", "/work-domains", $work_domain,"", true);
// get default API app
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <title>Work Domains | <?php echo get_constant("PRJ_TITLE"); ?></title>
  <meta charset="UTF-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <?php include get_constant("PRJ_INC_ICONSET"); ?>
  <meta name='viewport' content='width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=0'>
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
  <link rel="stylesheet" href="/app/admin/css/admin.min.css">
</head>
<body class="theme-<?php echo \IO\get_constant("PRJ_THEME"); ?>">
  <div id="cwos-uiloadr"></div>
  <input type="hidden" data-setup="page" data-pager="#data-pager" data-container="#domain-list" data-search="domains" data-handler="lsWkDomain" data-name="<?php echo $page_name; ?>" data-group="<?php echo $nav_group; ?>">
  <input type="hidden" data-setup="ui" data-handler="DashUI" data-header="/admin/get/dashui/header?rdt=<?php echo THIS_PAGE; ?>" data-sidebar="<?php echo \TymFrontiers\Generic::setGet("/admin/get/dashui/sidebar", ["domain" => $work_domain]); ?>" data-autoinit="true">
  <input type="hidden" data-setup="uiOption" data-max-cart-item="6" data-max-notice-item="6">
  <input type="hidden" data-setup="uiNotification" data-delete="/app/helper/delete/notification" data-path="/app/user/notifications" data-get="/app/helper/get/notification">
  <input type="hidden" data-setup="uiCart" data-delete="/app/helper/delete/cart" data-path="/index/checkout" data-get="/app/helper/get/cart">
  <input type="hidden" data-setup="dnav" data-group="<?php echo $work_domain; ?>" data-clear-elem="#cwos-content" data-pos="affix" data-container="#cwos-content" data-get="/admin/get/navigation" data-ini-top-pos="0" data-stick-on="">

  <section id="cwos-content">
    <form id="delete-domain-form" method="post" action="/app/admin/src/DeleteWorkDomain.php" data-validate="false" onsubmit="cwos.form.submit(this,checkPost); return false;" >
      <input type="hidden" name="form" value="delete-domain-form">
      <input type="hidden" name="CSRF_token" value="<?php echo $session->createCSRFtoken("delete-domain-form");?>">
      <input type="hidden" name="name" value="">
    </form>
    <div class="view-space-max">
      <br class="c-f">
        <div class="grid-10-laptop grid-8-desktop center-laptop">
          <form
            id="query-form"
            class="block-ui theme-color asphalt paddn -pall -p20"
            method="post"
            action="/app/admin/src/GetWorkDomain.php"
            data-validate="false"
            onsubmit="cwos.form.submit(this, doFetch); return false;"
            >
            <input type="hidden" name="form" value="wdomain-query-form">
            <input type="hidden" name="CSRF_token" value="<?php echo $session->createCSRFtoken("wdomain-query-form");?>">

            <div class="grid-5-tablet">
              <label for="search"> <i class="fas fa-search"></i> Search</label>
              <input type="search" name="search" value="<?php echo !empty($_GET['search']) ? $_GET['search'] :''; ?>" id="search" placeholder="Keyword search">
            </div> 
            <div class="grid-6-phone grid-2-tablet">
              <label for="page"> <i class="fas fa-file-alt"></i> Page</label>
              <input type="number" name="page" id="page" class="page-val" placeholder="1" value="1">
            </div>
            <div class="grid-6-phone grid-2-tablet">
              <label for="limit"> <i class="fas fa-sort-numeric-up"></i> Limit</label>
              <input type="number" name="limit" id="limit" class="page-limit limit-val" placeholder="25" value="25">
            </div>
            <div class="grid-6-phone grid-3-tablet"> <br>
              <button type="submit" class="theme-button asphalt"> <i class="fas fa-search"></i></button>
            </div>
            <br class="c-f">
          </form>
          <p class="align-c">
            <b>Records:</b> <span class="records-text">00</span> |
            <b>Pages:</b> <span class="pages-text">00</span>
          </p>
        </div>

        <div class="sec-div paddn -pall -p20">
          <h2>Domains</h2>
          <table data-role="table" class="vertical theme-color asphalt clear-padding">
            <thead class="color-text align-l border -bthin -bbottom">
              <tr>
                <th><span>Name</span></th>
                <th><span>Title</span></th>
                <th><span>Description</span></th>
                <th><span>Work paths</span></th>
                <th><span>Action</span></th>
              </tr>
            </thead>
            <tbody id="domain-list"></tbody>
          </table>
          <div id="data-pager">
          </div>
          <br class="c-f">
        </div>

      <br class="c-f">
    </div>
    <div class="push-foot">&nbsp;</div>
  </section>

    <div id="actn-btns">
      <div id="actn-btn-wrp">
        <div id="scrl-wrp">
          <button class="theme-button asphalt block" onclick="cwos.faderBox.url('/app/admin/post/work-domain', {callback : 'requery'}, {exitBtn: true});"> <i class="fas fa-plus fa-lg"></i> New domain</button>
        </div>
      </div>
      <button id="actvt" type="button" class="cwos-button"> <i class="fas fa-angle-right"></i> <span class="btn-txt">Start</span></button>
    </div>
    <!-- Required scripts -->
    <script src="/app/cataliwos/plugin.cwapp/js/jquery.min.js"></script>
    <script src="/app/cataliwos/plugin.cwapp/js/functions.min.js"></script>
    <script src="/app/cataliwos/plugin.cwapp/js/class-object.min.js"></script>
    <script src="/app/cataliwos/dashui.cwapp/js/dashui.min.js"></script>
    <script src="/app/cataliwos/plugin.cwapp/js/theme.min.js"></script>
    <script src="/app/ikechukwuokalia/helper.cwapp/js/helper.min.js"></script>
    <script src="/assets/js/base.js"></script>
    <script src="/app/admin/js/admin.js"></script>
    <script type="text/javascript">
      $(document).ready(function() {
        requery();
      });
    </script>
  </body>
</html>
