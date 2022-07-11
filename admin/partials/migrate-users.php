<style>
  .badge {
    display: inline-block;
    padding: 0.5rem;
    color: #24292d;
    background: #e5e5e5;
    border-radius: 5rem;
    border: 1px solid #ccc;
    margin-right: 1rem;
    margin-bottom: 1rem;
  }

  .badge-taxonomy {
    background: aliceblue;
  }

  .badge-group {
    background: aqua;
  }

  .badge-image {
    background-color: aquamarine;
  }

  .badge-text {
    background-color: deepskyblue;
  }
</style>
<h3>Migrate Users</h3>
<?php
$import_log_url = get_site_url() . '/wp-content/uploads/source-migrator-logs/import-posts.txt';
?>

<form id="import-users-form" method="post" action="<?php echo get_site_url(); ?>/wp-admin/tools.php?page=source-migrator&tab=migrate-product-reviews" style="margin-bottom: 1rem;">
  <div id="import-alert" class="alert" role="alert" style="display: none;"></div>
  <h3>Import</h3>
  <p>Import users from remote/external website.</p>
  <table class="form-table">
    <tbody>
      <tr>
        <th><label for="site_url">Site Url</label></th>
        <td>
          <input type="url" id="site_url" name="site_url" class="large-text" required />
        </td>
      </tr>
      <tr>
        <th><label for="auth_token">Auth Token</label></th>
        <td>
          <input type="text" id="auth_token" name="auth_token" class="large-text" required />
        </td>
      </tr>
    </tbody>
  </table>
  <button type="submit" class="button button-primary">Run Import</button>
  <div id="import-results" style="display: none;">
    <div id="progress">
      <div class="bar" style="width: 0%">
        <p class="percent">0%</p>
      </div>
    </div>
    <p>Importing... <span class="count">0</span> of <span class="total"></span> imported. Read logs <a href="<?php echo $import_log_url; ?>" target="_blank">here</a>.</p>
  </div>
</form>