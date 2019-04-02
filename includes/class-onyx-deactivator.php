<?php

/**
 * Fired during plugin deactivation
 *
 * @link       http://aerezona.net/hassan
 * @since      1.0.0
 *
 * @package    Onyx
 * @subpackage Onyx/includes
 */

/**
 * Fired during plugin deactivation.
 *
 * This class defines all code necessary to run during the plugin's deactivation.
 *
 * @since      1.0.0
 * @package    Onyx
 * @subpackage Onyx/includes
 * @author     Mubashir Hassan <aerezona@gmail.com>
 */
class Onyx_Deactivator {

	/**
	 * Short Description. (use period)
	 *
	 * Long Description.
	 *
	 * @since    1.0.0
	 */
	public static function deactivate() {
		   // removing maximum execution time limit ..
	$wpmetetxt = "
	# WP Maximum Execution Time Exceeded
	<IfModule mod_php5.c>
		php_value max_execution_time 3600
		php_value memory_limit 3000M
	</IfModule>";
	$htaccess = get_home_path().'.htaccess';
	$contents = @file_get_contents($htaccess);
	file_put_contents($htaccess,str_replace($wpmetetxt,'',$contents));
		   // Cleare All Crones
       wp_clear_scheduled_hook('onyx_erp_schedule_sync');
			 $onyx_admin_pages = new Onyx_Settings_Pages( 'onyx','1.0.1');
			 $orderstatusMapping = $onyx_admin_pages->order_status_mapping();
			 // Clear all Order Mapping Fields
			 foreach($orderstatusMapping as $ordermap){
					 delete_option($ordermap['key']);
			 }

			 // Clear All API setting Fields
			 $apiSettingsobj = $onyx_admin_pages->api_settings();
			 foreach($apiSettingsobj as $apisetting){
					  delete_option($apisetting['key']);
			 }
			 //Clear  All sync fields
			 $syncFields = $onyx_admin_pages->sync_settings_fields();
		   foreach($syncFields as $syncField){
				 delete_option($syncField['key']);
			 }
			 // Clear All modules options .
			 $modulesTosync = $onyx_admin_pages->onyx_module_to_sync();
 			 foreach($modulesTosync as $mod){
 				 delete_option($mod['key']);
 			 }
			 // Clearing Log File
	 	  $file = WP_PLUGIN_DIR. '/onyx/synclog.txt';
	 	  $current = file_get_contents( $file);
	 	  file_put_contents( $file,'' );
	}

}
