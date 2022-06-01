<?php

/**
 * Fired during plugin activation
 *
 * @link       https://sourcestrike.com
 * @since      0.1.0
 *
 * @package    Source_Migrator
 * @subpackage Source_Migrator/includes
 */

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      0.1.0
 * @package    Source_Migrator
 * @subpackage Source_Migrator/includes
 * @author     SourceStrike <justin@sourcestrike.com>
 */
class Source_Migrator_Activator {

	/**
	 * Short Description. (use period)
	 *
	 * Long Description.
	 *
	 * @since    0.1.0
	 */
	public static function activate() {
		update_option('source_migrator_remote_db', json_encode(array(
			'name' => '',
			'user' => '',
			'pass' => '',
			'host' => '',
			'table_prefix' => '',
		)));
	}

}
