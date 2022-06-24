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
<h3>Migrate Images</h3>
<?php
$args       = array(
  'public' => true,
);
$post_types = get_post_types( $args, 'objects' );
$import_log_url = get_site_url() . '/wp-content/uploads/source-migrator-logs/import-posts.txt';
?>

<form id="import-featured-image-form" method="post" action="<?php echo get_site_url(); ?>/wp-admin/tools.php?page=source-migrator&tab=migrate-featured-images" style="margin-bottom: 1rem;">
<div id="import-alert" class="alert" role="alert" style="display: none;"></div>  
<h3>Import</h3>
  <p>Import featured images of dofferent post_type(s) from remote/external website.</p>
  <table class="form-table">
    <tbody>
      <tr>
        <th><label for="post_type">Post Type</label></th>
        <td>
          <select name="post_type" id="post_type" required>
            <?php foreach($post_types as $key => $post_type) { ?>
                <option value="<?php echo $post_type->name; ?>"><?php echo $post_type->label; ?></option>
            <?php } ?>
          </select>
        </td>
      </tr>
      <tr>
        <th><label for="site_url">Site Url</label></th>
        <td>
          <input type="url" id="site_url" name="site_url" class="large-text" required/>
        </td>
      </tr>
      <tr>
        <th><label for="auth_token">Auth Token</label></th>
        <td>
          <input type="text" id="auth_token" name="auth_token" class="large-text" required/>
        </td>
      </tr>
      <tr>
        <th><label for="matching_type">Matching Type</label></th>
        <td>
          <input type="radio" id="matching_type" name="matching_type" value="ID" required/> ID <br><br>
          <input type="radio" id="matching_type" name="matching_type" value="permalink" required/> Permalink
        </td>
      </tr>
      <tr>
        <th><label for="import_content_images">Import Content Images Too</label></th>
        <td>
          <input type="checkbox" id="import_content_images" name="import_content_images" value="1"/>
        </td>
      </tr>
      
    </tbody>
  </table>
  <!-- name="action" value="run_migrate_press"  -->
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
