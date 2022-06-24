<?php
$success_msg = '';

if ( isset($_POST['action']) && $_POST['action'] === 'update_remote_db_settings' ) {
  $new_db_settings = array(
    'name' => $_POST['remote_db_name'],
    'user' => $_POST['remote_db_user'],
    'pass' => $_POST['remote_db_pass'],
    'host' => $_POST['remote_db_host'],
    'table_prefix' => $_POST['remote_table_prefix'],
  );
  update_option('source_migrator_remote_db', json_encode($new_db_settings));
}

$db_settings = get_option('source_migrator_remote_db');
$db_settings = json_decode($db_settings);
?>

<?php if ( ! empty( $success_msg ) ) { ?>
  <div class="notice notice-success is-dismissible">
    <p><?php echo $success_msg; ?></p>
  </div>
<?php } ?>

<?php // var_dump($db_settings); ?>

<form method="post" action="<?php echo get_site_url(); ?>/wp-admin/tools.php?page=source-migrator" >
  <h3>Remote database info</h3>
  <p>Database we pull data from.</p>
  <table class="form-table">
    <tbody>
      <tr>
        <th>
          <label for="remote_db_name">Name</label>
        </th>
        <td>
          <input type="text" id="remote_db_name" name="remote_db_name" class="regular-text" value="<?php echo (isset($db_settings->name)) ? $db_settings->name : ''; ?>" required/>
        </td>
      </tr>
      <tr>
        <th>
          <label for="remote_db_user">User</label>
        </th>
        <td>
          <input type="text" id="remote_db_user" name="remote_db_user" class="regular-text" value="<?php echo (isset($db_settings->user)) ? $db_settings->user : ''; ?>" required/>
        </td>
      </tr>
      <tr>
        <th>
          <label for="remote_db_pass">Password</label>
        </th>
        <td>
          <input type="text" id="remote_db_pass" name="remote_db_pass" class="regular-text" value="<?php echo (isset($db_settings->pass)) ? $db_settings->pass : ''; ?>" required/>
        </td>
      </tr>
      <tr>
        <th>
          <label for="remote_db_host">Host</label>
        </th>
        <td>
          <input type="text" id="remote_db_host" name="remote_db_host" class="regular-text" value="<?php echo (isset($db_settings->host)) ? $db_settings->host : ''; ?>" required/>
        </td>
      </tr>
      <tr>
        <th>
          <label for="remote_table_prefix">Table Prefix</label>
        </th>
        <td>
          <input type="text" id="remote_table_prefix" name="remote_table_prefix" class="regular-text" value="<?php echo (isset($db_settings->table_prefix)) ? $db_settings->table_prefix : ''; ?>" required/>
        </td>
      </tr>
    </tbody>
  </table>
  <button type="submit" name="action" value="update_remote_db_settings" class="button button-primary" >Save Changes</button>
</form>

<hr/>

<?php
if (! empty($db_settings)) { ?>
  <form method="post" action="<?php echo get_site_url(); ?>/wp-admin/tools.php?page=source-migrator" >
    <button type="submit" name="action" value="test_db_connection" class="button button-primary" >Test Connection</button>
  </form>
  <?php
  global $wpdb;
  if (isset($_POST['action']) && $_POST['action'] === 'test_db_connection') { ?>
    <h3>Remote database connection</h3>
    <?php
    echo 'test';
    $remote_db = new wpdb($db_settings->user, $db_settings->pass, $db_settings->name, $db_settings->host);
    echo 'test2';
    $remote_site_url = $remote_db->get_var("SELECT option_value FROM " . $db_settings->table_prefix . "options WHERE option_name = 'siteurl'");
    if ($wpdb->last_error !== '') {
      $wpdb->print_error();
    }
    if ($remote_site_url) { ?>
      <p>Connected to: <?php echo $remote_site_url; ?></p>
    <?php }
  }
} ?>
