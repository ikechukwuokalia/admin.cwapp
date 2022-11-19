<?php
namespace IO;
require_once "../../.appinit.php";
use \TymFrontiers\Generic,
    \TymFrontiers\MultiForm,
    \TymFrontiers\InstanceError;

\require_login(false);
\check_access("WRITE", "/path-access", "project-admin","", false);
$errors = [];
$gen = new Generic;
$params = $gen->requestParam([
  "user" => ["user","pattern", "/^052(\s|\-|\.)?([\d]{4,4})(\s|\-|\.)?([\d]{4,4})$/"],
  "domain" => ["domain","username",3,98,[],'LOWER',['-','.']],
  "path" => ["path","username",3,98,[],'LOWER',['-','/']],
  "callback" => ["callback","username",3,35,[],'MIXED']
], $_GET, ["user","domain"]);
if (!$params || !empty($gen->errors)) {
  $errs = (new InstanceError($gen,true))->get("requestParam",true);
  foreach ($errs as $er) {
    $errors[] = $er;
  }
}
$access_scope = [];
if (!empty($params["domain"])) $params["domain"] = $database->escapeValue($params["domain"]);
if (!empty($params["user"])) $params["user"] = $database->escapeValue(\str_replace(["-", " ", ".", "_"],"",$params["user"]));
$path_name = empty($params['path']) ? "" : " AND `path` = '{$database->escapeValue($params['path'])}' ";
if( $params ):
  if( $access = (new MultiForm(get_database("CWS", "admin"),'path_access','id'))
    ->findBySql("SELECT * FROM :db:.:tbl: WHERE `user` = '{$params['user']}' AND `domain` = '{$params['domain']}' AND `type` = 'PATH' {$path_name}") ){
    foreach ($access as $acs) {
      $access_scope[$acs->path] = (!empty($acs->access_scope) ? \explode(",", $acs->access_scope) : []);
    }
  }
endif;
// get domain paths
$paths = [];
$data_db = get_database("CWS", "data");
$fpath = (new MultiForm(get_database("CWS", "admin"), "work_paths", "1"))
  ->findBySql("SELECT wpt.`path`, wpt.access_scope, wpt.resource_type, wpt.title, wpt.description,
                      tg.title AS resource_type_title
              FROM :db:.:tbl: AS wpt
              LEFT JOIN `{$data_db}`.resource_types AS tg ON tg.`name` = wpt.resource_type
              WHERE wpt.domain = '{$params['domain']}'
              {$path_name}
              ORDER BY wpt.`sort` ASC");
if ($fpath) {
  foreach ($fpath as $pth) {
    if (empty($paths[$pth->resource_type])) $paths[$pth->resource_type] = [];
    if (empty($paths[$pth->resource_type]["title"])) $paths[$pth->resource_type]["title"] = $pth->resource_type_title;
    if (empty($paths[$pth->resource_type]["paths"])) $paths[$pth->resource_type]["paths"] = [];
    $paths[$pth->resource_type]["paths"][$pth->path] = [
      "title" => $pth->title,
      "description" => $pth->description,
      "scopes" => !empty($pth->access_scope) ? \explode(",",$pth->access_scope) : []
    ];
  }
} else {
  $errors[] = "No [path] was found for the [domain] '{$params['domain']}'";
} if (!$user = (new MultiForm(get_database("CWS", "admin"), "users", "code"))->findById($params['user'])) {
  $errors[] = "No record was found for [user] '{$params['user']}'";
} else if ($user->status !== "ACTIVE") {
  $errors[] = "[user]'s account is not active.";
}
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
          <h1> <i class="fas fa-folder-plus"></i> Path access</h1>
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
            id="path-access-form"
            class="block-ui"
            method="post"
            action="/app/admin/src/PostPathAccess.php"
            data-validate="false"
            onsubmit="postPtAcs(this, doPost);return false;"
            >
            <input type="hidden" name="form" value="path-access-form">
            <input type="hidden" name="CSRF_token" value="<?php echo ($session->createCSRFtoken("path-access-form"));?>">
            <input type="hidden" name="domain" value="<?php echo @ $params['domain']; ?>">
            <input type="hidden" name="user" value="<?php echo @ $params['user']; ?>">
            <div class="grid-12-tablet">
              <h2 class="clear-margin align-center"><?php echo "{$user->name} {$user->surname}'s specific access to Domain: <br> <code class='color-blue'>{$params['domain']}</code>"; ?></h2>
              <?php foreach ($paths as $pt) {
                echo "<h3>{$pt['title']}</h3>";
                foreach ($pt["paths"] as $name => $props) {
                  echo "<p class=\"scope-group\">";
                  echo "<code class=\"color-blue\">{$name}</code> | <span title=\"{$props['description']}\" <i class='fas fa-info-circle'></i> {$props['title']}</span>";
                  echo "<br>";
                  foreach ($props["scopes"] as $scope) {
                    echo "<span>";
                      echo "<input data-path=\"{$name}\" id=\"{$name}-{$scope}\" type=\"checkbox\" name=\"{$name}\" value=\"{$scope}\"";
                        echo !empty($access_scope[$name]) && \in_array($scope, $access_scope[$name]) ? " checked" : "";
                      echo ">";
                      echo "<label for=\"{$name}-{$scope}\">{$scope}</label>";
                    echo "</span>";
                  }
                  echo "</p>";
                }
              } ?>
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
  (function(){
    $("input[type=checkbox]").on("change", "", function(){
      if ($(this).is(":checked") && $(this).val() == "DENY") {
        $(`input[type=checkbox][name='${$(this).attr('name')}']`).prop("checked",false);
        $(this).prop("checked",true);
      } if ($(this).is(":checked") && $(this).val() !== "DENY") {
        $(`input[type=checkbox][name='${$(this).attr('name')}'][value='DENY']`).prop("checked",false);
      }
    });

  })();
</script>
