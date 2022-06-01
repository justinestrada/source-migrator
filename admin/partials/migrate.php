
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

<h3>Migration</h3>
<?php
$post_types = array(
  'post' => 'Blog post',
  // 'customer_story' => 'Customer Story',
  // 'resource_hub' => 'Research & Guide',
  // 'press' => 'Press item',
);
$import_log_url = get_site_url() . '/wp-content/uploads/source-migrator-logs/import-posts.txt'; ?>

<div id="import-alert" class="alert" role="alert" style="display: none;"></div>

<form id="import-form" method="post" action="<?php echo get_site_url(); ?>/wp-admin/tools.php?page=source-migrator&tab=migrate" style="margin-bottom: 1rem;">
  <h3>Import</h3>
  <p>Import post_type(s) from remote/external database by oldest.</p>
  <table class="form-table">
    <tbody>
      <tr>
        <th><label for="post_type_input">Post Type</label></th>
        <td>
          <?php /*
          <input type="text" id="post_type_input" name="sm_import_post_type" class="regular-text" value="<?php echo (isset($_POST['sm_import_post_type'])) ? $_POST['sm_import_post_type'] : ''; ?>" required/>
          */ ?>
          <!-- <p>Ex: press, customer_story, resource_hub, post</p> -->
          <select name="sm_import_post_type" id="post_type_input" required>
            <?php foreach($post_types as $key => $post_type) { ?>
                <option value="<?php echo $key; ?>"><?php echo $post_type; ?></option>
            <?php } ?>
          </select>
        </td>
      </tr>
      <tr>
        <th><label for="include_only_ids">Include Only (Optional)</label></th>
        <td>
          <textarea id="include_only_ids" name="include_only_ids" class="regular-text" readonly></textarea>
          <p>Post IDs to include. Separate IDs by comma and space: ", ". If not empty then ONLY these posts will be imported.</p>
        </td>
      </tr>
      <tr class="acf-tr">
        <th><label>ACF</label></th>
        <td>
          <div class="acf-fields"></div>
          <!-- <textarea id="acf" name="acf" class="regular-text" required></textarea>
          <p>Separate acf fields by comma.</p> -->
          <p>1:1 matching fields to import</p>
        </td>
      </tr>
      <tr>
        <th><label for="sm_limit">Limit</label></th>
        <td>
          <input type="number" id="sm_limit" name="sm_limit" class="small-text" value="<?php echo (isset($_POST['sm_limit'])) ? $_POST['sm_limit'] : '1'; ?>" min="1" required/>
          <p>Leave blank if you want to import all items.</p>
        </td>
      </tr>
      <tr>
        <th><label for="offset">Offset</label></th>
        <td>
          <input type="number" id="offset" name="offset" class="small-text" value="<?php echo (isset($_POST['offset'])) ? $_POST['offset'] : '0'; ?>" min="0" required/>
          <p>Offset the import, starts importing at oldest.</p>
        </td>
      </tr>
      <tr>
        <th><label for="match_taxonomies">Match Taxonomies</label></th>
        <td>
          <input type="checkbox" id="match_taxonomies" name="match_taxonomies" value="1"/> Yes match the hard coded taxonomies.
        </td>
      </tr>
      <tr>
        <th><label for="confirm">Are you sure?</label></th>
        <td>
          <input type="checkbox" id="confirm" name="confirm" value="1" required/> Are you sure?.
        </td>
      </tr>
    </tbody>
  </table>
  <!-- name="action" value="run_migrate_press"  -->
  <button type="submit" class="button button-primary">Run Import</button>
</form>

<div id="import-results" style="display: none;">
  <div id="progress">
    <div class="bar" style="width: 0%">
      <p class="percent">0%</p>
    </div>
  </div>
  <p>Importing... <span class="count">0</span> of <span class="total"></span> imported. Read logs <a href="<?php echo $import_log_url; ?>" target="_blank">here</a>.</p>
</div>
