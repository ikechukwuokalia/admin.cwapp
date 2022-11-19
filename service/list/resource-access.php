<?php
namespace IO;
require_once "../../.appinit.php";
use \TymFrontiers\Generic,
    \TymFrontiers\MultiForm,
    \TymFrontiers\InstanceError;
    
\require_login(false);
\check_access("READ", "/resource-access", "project-admin","", false);

$errors = [];
$found = false;
$gen = new Generic;
$params = $gen->requestParam([
  "resource" => ["resource","username", 3, 21, [], "LOWER", ["-"]]
], $_GET, ["resource"]);
if (!$params || !empty($gen->errors)) {
  $errs = (new InstanceError($gen,true))->get("requestParam",true);
  foreach ($errs as $er) {
    $errors[] = $er;
  }
}
if ($params && !empty($params['resource'])) {
  $data_db = get_database(\IO\get_constant("PRJ_SERVER_NAME"), "data");
  $count = 0;
  $data = new MultiForm($data_db, 'resource_access', 'id', $database);
  $data->current_page = $page = 1;
  $dq = "SELECT racs.`id`, racs.`resource`, racs.group_name, racs.`scope`
        FROM :db:.:tbl: AS racs 
        WHERE racs.`resource` = '{$database->escapeValue($params['resource'])}' ";
  $count = $data->findBySql("SELECT COUNT(*) AS cnt FROM :db:.:tbl: AS racs ");
  $count = $data->total_count = $count ? $count[0]->cnt : 0;
  $data->per_page = $limit = 6;
  $dq .= " ORDER BY racs.`_created` DESC ";
  $dq .= " LIMIT {$data->per_page} ";
  $dq .= " OFFSET {$data->offset()}";
  $found = $data->findBySql($dq);
}
?>
<div class="fader-flow">
  <div class="view-space-large">
    <div class="paddn -pall -p20">&nbsp;</div>
    <br class="c-f">
    <div class="grid-10-tablet grid-8-laptop  grid-6-desktop center-tablet">
      <div class="sec-div theme-color amber bg-white drop-shadow">
        <header class="paddn -pall -p20 color-bg">
          <h1 class="fw-lighter"> <i class="fas fa-universal-access"></i> Resource access</h1>
        </header>

        <div class="paddn -pall -p20">
          <?php if(!empty($errors)){ ?>
            <h3>Unresolved error(s)</h3>
            <ol>
              <?php foreach($errors as $err){
                echo " <li>{$err}</li>";
              } ?>
            </ol>
          <?php } else { ?>
            <form
              id="del4rm-reacs-form"
              method="post"
              action="/app/admin/src/DeleteResourceAccess.php"
              data-validate="false"
              onsubmit="cwos.form.submit(this, requery1); return false;"
              >
              <input type="hidden" name="form" value="del-reacs-form">
              <input type="hidden" name="CSRF_token" value="<?php echo $session->createCSRFtoken("del-reacs-form", \strtotime("+10 Hours"));?>">
              <input type="hidden" name="id" value="">
            </form>

            <form
              id="sub1-query-form"
              class="block-ui color amber"
              method="post"
              action="/app/admin/src/GetResourceAccess.php"
              data-validate="false"
              onsubmit="cwos.form.submit(this, doFetch1); return false;"
              >
              <input type="hidden" name="resource" value="<?php echo @ $params['resource']; ?>">
              <input type="hidden" name="form" value="resacs-query-form">
              <input type="hidden" name="CSRF_token" value="<?php echo $session->createCSRFtoken("resacs-query-form", \strtotime("+10 Hours"));?>">

              <input type="hidden" name="page" class="sub1-page-val" value="1">
              <input type="hidden" name="limit" class="sub1-page-limit" value="<?php echo (int)$limit; ?>">

              <div class="grid-7-tablet">
                <input type="search" name="search" value="" placeholder="Search keyword">
              </div>
              <div class="grid-5-phone grid-2-tablet">
                <button type="submit" class="cwos-btn asphalt no-shadow"> <i class="fas fa-search"></i></button>
              </div>
              <div class="grid-7-phone grid-3-tablet">
                <button type="button" onclick="cwos.faderBox.url('/app/admin/post/resource-access',{resource: '<?php echo $params['resource']; ?>', cb:'requery1'},{exitBtn : true});" class="cwos-btn amber no-shadow"> <i class="fas fa-plus"></i> Add</button>
              </div>
              <br class="c-f">
            </form>
            <div class="sec-div paddn -pall -p20">
              <p class="align-center">
                <b>Record(s):</b> <span class="sub1-records-text"><?php echo $data->total_count; ?></span> |
                <b>Pages:</b> <span class="sub1-pages-text"><?php echo $data->totalPages(); ?></span>
              </p>
              <br class="c-f">
            </div>
            <div class="tbl-wrp">
              <table class="vertical theme-color orange">
                <thead class="color-text align-left border -bthin -bbottom">
                  <tr>
                    <th>Group</th>
                    <th>Scope</th>
                    <th>Action</th>
                  </tr>
                </thead>
                <tbody id="resource-access-list">
                  <?php if ($found) {
                    foreach ($found as $asc) {
                      echo "<tr>";
                        echo "<td>";
                          echo "<code class=\"bold color-blue\"";
                            echo " onclick=\"clipboardCopy('{$asc->group_name}')\"";
                          echo ">{$asc->group_name}</code>";
                        echo "</td>";
                        echo "<td>".\str_replace(",", ", ", $asc->scope)."</td>";
                        echo "<td>";
                          echo "<button onclick=\"cwos.faderBox.url('/app/admin/post/resource-access', {id:{$asc->id}, resource: '{$asc->resource}', cb: 'requery1'}, {exitBtn:true});\" type=\"button\" class=\"theme-button mini blue no-shadow\"><i class=\"far fa-edit\"></i> Edit</button>";
                          echo "<button onclick=\"delResAccess({$asc->id});\" type=\"button\" class=\"theme-button mini red no-shadow\" title=\"Delete\"><i class=\"far fa-trash\"></i> Delete</button>";
                        echo "</td>";
                      echo "</tr>";
                    }
                  } ?>
                </tbody>
              </table>
            </div>
            <div id="sub1-data-pager" class="data-pager empty">
              <?php if ($data->hasPreviousPage()): ?>
                <button class='cwos-btn asphalt' id="sub1-previous-page-btn" onclick="pageTo(<?php echo $data->previousPage() ?>, 1);"> <i class="fas fa-lg fa-angle-left"></i></button>
              <?php endif; ?>
              <?php if ($data->hasNextPage()): ?>
                <button class='cwos-btn asphalt' id="sub1-next-page-btn" onclick="pageTo(<?php echo $data->nextPage(); ?>, 1);"> <i class="fas fa-lg fa-angle-right"></i></button>
              <?php endif; ?>
            </div>
            <br class="c-f">

        <?php } ?>
      </div>
    </div>
  </div>
  <br class="c-f">
</div>
</div>

<script type="text/javascript">
  function doFetch1 (data) {
    doFetch(data, 1);
  }
  function requery1 () {
    $("#sub1-query-form").submit();
  }
  (function(){
    pageSub({
      container: "#resource-access-list",
      handler: "lsResAccess",
      pager: "#sub1-data-pager",
      search: "access",
      nextPage: <?php echo (bool)$data->hasNextPage() ? $data->nextPage() : 'false'; ?>,
      previousPage: <?php echo (bool)$data->hasPreviousPage() ? $data->previousPage() : 'false'; ?>,
      limit: <?php echo (int)$limit; ?>,
      page: 1,
      pages: 1,
      records: <?php echo (int)$count; ?>
    }, 1);
  })();
</script>
