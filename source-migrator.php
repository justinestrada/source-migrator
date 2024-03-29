<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://sourcestrike.com
 * @since             0.2
 * @package           Source_Migrator
 *
 * @wordpress-plugin
 * Plugin Name:       Source Migrator
 * Plugin URI:        https://sourcestrike.com
 * Description:       Migrate custom WordPress data from site to site. This does not work out of the box, you'll need to customize the mirgation code per website.
 * Version:           0.2
 * Author:            SourceStrike
 * Author URI:        https://sourcestrike.com
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       source-migrator
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Currently plugin version.
 * Start at version 0.1.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
define( 'VERSION', '0.2' );

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-source-migrator-activator.php
 */
function activate_source_migrator() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-source-migrator-activator.php';
	Source_Migrator_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-source-migrator-deactivator.php
 */
function deactivate_source_migrator() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-source-migrator-deactivator.php';
	Source_Migrator_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_source_migrator' );
register_deactivation_hook( __FILE__, 'deactivate_source_migrator' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-source-migrator.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    0.1.0
 */
function run_source_migrator() {

	$plugin = new Source_Migrator();
	$plugin->run();

}
run_source_migrator();
