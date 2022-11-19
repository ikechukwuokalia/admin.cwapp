<?php
namespace TymFrontiers;
require_once "../.appinit.php";
if (!$session->isLoggedIn()) HTTP\Header::redirect(WHOST);
\Catali\Admin\log_session("LOGOUT"); // log
$session->logout();
HTTP\Header::redirect(WHOST);
?>
<!DOCTYPE html>
<html lang="en" dir="ltr" manifest="/site.webmanifest">
  <head>
    <meta charset="utf-8">
    <title>Logging you out | <?php echo \Catali\get_constant("PRJ_TITLE"); ?></title>
    <?php include \Catali\get_constant("PRJ_INC_ICONSET"); ?>
    <meta name='viewport' content='width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=0'>
    <meta name="robots" content='nofollow'>
  </head>
  <body>
  </body>
</html>
