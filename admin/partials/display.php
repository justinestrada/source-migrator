<?php
/**
 * Provide a admin area view for the plugin
 *
 * This file is used to markup the admin-facing aspects of the plugin.
 *
 * @link       https://sourcestrike.com
 * @since      0.1.0
 *
 * @package    Source_Migrator
 * @subpackage Source_Migrator/admin/partials
 */
$site_url = get_site_url();
?>
<div id="source-migrator" class="wrap">
  <h1>Source Migrator</h1>
  <p>Tools to migrate data; import external data to local databse</p>
  <?php
  $active_tab = (isset($_GET['tab'])) ? $_GET['tab'] : 'connection';
  $db_settings = sm_get_db_settings();
  ?>
  <div class="nav-tab-wrapper">
    <a href="<?php echo $site_url; ?>/wp-admin/tools.php?page=source-migrator&amp;tab=connection" class="nav-tab <?php echo ($active_tab === 'connection') ? 'nav-tab-active' : ''; ?>">
      <h2>Connection</h2>
    </a>
    <?php if ($db_settings) { ?>
      <a href="<?php echo $site_url; ?>/wp-admin/tools.php?page=source-migrator&amp;tab=migrate" class="nav-tab <?php echo ($active_tab === 'migrate') ? 'nav-tab-active' : ''; ?>">
        <h2>Migrate</h2>
      </a>
      <a href="<?php echo $site_url; ?>/wp-admin/tools.php?page=source-migrator&amp;tab=misc" class="nav-tab <?php echo ($active_tab === 'misc') ? 'nav-tab-active' : ''; ?>">
        <h2>Misc</h2>
      </a>
    <?php } ?>
    <a href="<?php echo $site_url; ?>/wp-admin/tools.php?page=source-migrator&amp;tab=migrate-featured-images" class="nav-tab <?php echo ($active_tab === 'migrate') ? 'nav-tab-active' : ''; ?>">
        <h2>Migrate Feature Images</h2>
      </a>
      <a href="<?php echo $site_url; ?>/wp-admin/tools.php?page=source-migrator&amp;tab=migrate-product-reviews" class="nav-tab <?php echo ($active_tab === 'migrate-product-reviews') ? 'nav-tab-active' : ''; ?>">
        <h2>Migrate Product Reviews</h2>
      </a>
      <a href="<?php echo $site_url; ?>/wp-admin/tools.php?page=source-migrator&amp;tab=migrate-users" class="nav-tab <?php echo ($active_tab === 'migrate-users') ? 'nav-tab-active' : ''; ?>">
        <h2>Migrate Users</h2>
      </a>
  </div>
  <?php if ($active_tab === 'connection') {
    require_once( plugin_dir_path( __FILE__ ) . '/connection.php' );
  } else if ($active_tab === 'migrate' && $db_settings) {
    require_once( plugin_dir_path( __FILE__ ) . '/migrate.php' );
  } else if ($active_tab === 'misc' && $db_settings) {
    require_once( plugin_dir_path( __FILE__ ) . '/misc.php' );
  } else if ($active_tab === 'migrate-featured-images') {
    require_once( plugin_dir_path( __FILE__ ) . '/migrate-featured-images.php' );
  } else if($active_tab === 'migrate-product-reviews') {
    require_once( plugin_dir_path( __FILE__ ) . '/migrate-product-reviews.php' );
  } else if($active_tab === 'migrate-users') {
    require_once( plugin_dir_path( __FILE__ ) . '/migrate-users.php' );
  } ?>
</div>
