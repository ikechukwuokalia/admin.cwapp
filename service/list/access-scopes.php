<?php
namespace IO;
require_once "../../.appinit.php";
use \TymFrontiers\Generic,
    \TymFrontiers\MultiForm,
    \TymFrontiers\InstanceError;
    
\require_login(false);
\check_access("READ", "/access-scopes", "project-admin","", false);

$errors = [];
$found = false;
$data_db = get_database("data", \IO\get_constant("PRJ_SERVER_NAME"));
$count = 0;
$data = new MultiForm($data_db, 'access_scopes', 'name', $database);
$data->current_page = $page = 1;
$dq = "SELECT scp.`name`, scp.`rank`, scp.description
      FROM :db:.:tbl: AS scp ";
$count = $data->findBySql("SELECT COUNT(*) AS cnt FROM :db:.:tbl: AS scp ");
$count = $data->total_count = $count ? $count[0]->cnt : 0;
$data->per_page = $limit = 6;
$dq .= " ORDER BY scp.`rank` ASC ";
$dq .= " LIMIT {$data->per_page} ";
$dq .= " OFFSET {$data->offset()}";
$found = $data->findBySql($dq);
?>
<div class="fader-flow">
  <div class="view-space-large">
    <div class="paddn -pall -p20">&nbsp;</div>
    <br class="c-f">
    <div class="grid-10-tablet grid-8-laptop  grid-6-desktop center-tablet">
      <div class="sec-div theme-color amber bg-white drop-shadow">
        <header class="paddn -pall -p20 color-bg">
          <h1 class="fw-lighter"> <i class="fas fa-microscope"></i> Access Scope</h1>
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
              id="del4rm-asco-form"
              method="post"
              action="/app/admin/src/DeleteAccessScope.php"
              data-validate="false"
              onsubmit="cwos.form.submit(this, requery1); return false;"
              >
              <input type="hidden" name="form" value="del-asco-form">
              <input type="hidden" name="CSRF_token" value="<?php echo $session->createCSRFtoken("del-asco-form", \strtotime("+10 Hours"));?>">
              <input type="hidden" name="name" value="">
            </form>

            <form
              id="sub1-query-form"
              class="block-ui color amber"
              method="post"
              action="/app/admin/src/GetAccessScope.php"
              data-validate="false"
              onsubmit="cwos.form.submit(this, doFetch1); return false;"
              >
              <input type="hidden" name="form" value="acsco-query-form">
              <input type="hidden" name="CSRF_token" value="<?php echo $session->createCSRFtoken("acsco-query-form", \strtotime("+10 Hours"));?>">

              <input type="hidden" name="page" class="sub1-page-val" value="1">
              <input type="hidden" name="limit" class="sub1-page-limit" value="<?php echo (int)$limit; ?>">

              <div class="grid-7-tablet">
                <input type="search" name="search" value="" placeholder="Search keyword">
              </div>
              <div class="grid-5-phone grid-2-tablet">
                <button type="submit" class="cwos-btn asphalt no-shadow"> <i class="fas fa-search"></i></button>
              </div>
              <div class="grid-7-phone grid-3-tablet">
                <button type="button" onclick="cwos.faderBox.url('/app/admin/post/access-scope',{cb:'requery1'},{exitBtn : true});" class="cwos-btn amber no-shadow"> <i class="fas fa-plus"></i> Add</button>
              </div>
              <br class="c-f">
            </form>
            <div class="sec-div paddn -pall -p20">
              <p class="align-center">
                <b>Access Scopes:</b> <span class="sub1-records-text"><?php echo $data->total_count; ?></span> |
                <b>Pages:</b> <span class="sub1-pages-text"><?php echo $data->totalPages(); ?></span>
              </p>
              <br class="c-f">
            </div>
            <div class="tbl-wrp">
              <table class="vertical theme-color orange">
                <thead class="color-text align-left border -bthin -bbottom">
                  <tr>
                    <th>Name</th>
                    <th>Rank</th>
                    <th>Description</th>
                    <th>Action</th>
                  </tr>
                </thead>
                <tbody id="acsco-list">
                  <?php if ($found) {
                    foreach ($found as $acso) {
                      echo "<tr>";
                        echo "<td>";
                          echo "<code class=\"bold color-blue\"";
                            echo " onclick=\"clipboardCopy('{$acso->name}')\"";
                          echo ">{$acso->name}</code>";
                        echo "</td>";
                        echo "<td>{$acso->rank}</td>";
                        echo "<td>{$acso->description}</td>";
                        echo "<td>";
                          echo "<button onclick=\"cwos.faderBox.url('/app/admin/post/access-scope', {name:'{$acso->name}', cb: 'requery1'}, {exitBtn:true});\" type=\"button\" class=\"theme-button mini blue no-shadow\"><i class=\"fas fa-edit\"></i> Edit</button>";
                          echo "<button onclick=\"delAcsco('{$acso->name}');\" type=\"button\" class=\"theme-button mini red no-shadow\" title=\"Delete\"><i class=\"fas fa-trash\"></i> Delete</button>";
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
      container: "#acsco-list",
      handler: "lsAcsco",
      pager: "#sub1-data-pager",
      search: "scopes",
      nextPage: <?php echo (bool)$data->hasNextPage() ? $data->nextPage() : 'false'; ?>,
      previousPage: <?php echo (bool)$data->hasPreviousPage() ? $data->previousPage() : 'false'; ?>,
      limit: <?php echo (int)$limit; ?>,
      page: 1,
      pages: 1,
      records: <?php echo (int)$count; ?>
    }, 1);
  })();
</script>
