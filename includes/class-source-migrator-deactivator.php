<?php

/**
 * Fired during plugin deactivation
 *
 * @link       https://sourcestrike.com
 * @since      0.1.0
 *
 * @package    Source_Migrator
 * @subpackage Source_Migrator/includes
 */

/**
 * Fired during plugin deactivation.
 *
 * This class defines all code necessary to run during the plugin's deactivation.
 *
 * @since      0.1.0
 * @package    Source_Migrator
 * @subpackage Source_Migrator/includes
 * @author     SourceStrike <justin@sourcestrike.com>
 */
class Source_Migrator_Deactivator {

	/**
	 * Short Description. (use period)
	 *
	 * Long Description.
	 *
	 * @since    0.1.0
	 */
	public static function deactivate() {
		delete_option('source_migrator_remote_db');
	}

}
