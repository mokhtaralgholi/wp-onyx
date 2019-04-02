<?php

/**
 * Fired during plugin activation
 *
 * @link       http://aerezona.net/hassan
 * @since      1.0.0
 *
 * @package    Onyx
 * @subpackage Onyx/includes
 */

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 * @package    Onyx
 * @subpackage Onyx/includes
 * @author     Mubashir Hassan <aerezona@gmail.com>
 */
class Onyx_Activator {

	/**
	 * Short Description. (use period)
	 *
	 * Long Description.
	 *
	 * @since    1.0.0
	 */
	public static function activate(){
		$wpmetetxt = "
	# WP Maximum Execution Time Exceeded
	<IfModule mod_php5.c>
		php_value max_execution_time 3600
		php_value memory_limit 3000M
	</IfModule>";
	$htaccess = get_home_path().'.htaccess';
	$contents = @file_get_contents($htaccess);
	if(!strpos($htaccess,$wpmetetxt))
	file_put_contents($htaccess,$contents.$wpmetetxt);
		global $wpdb;
		$wpdb->query("ALTER TABLE `wp_terms` CHANGE `slug` `slug` VARCHAR(350) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT ''");
		if ( !in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ){
					deactivate_plugins(plugin_basename( __FILE__ ));
					$error_message = __('This plugin requires <a href="http://wordpress.org/extend/plugins/woocommerce/">WooCommerce</a> plugins to be active!', 'onyx');
					wp_die($error_message);
			}
			if ( !in_array( 'woocommerce-nw-location-picker/woocommerce-nw-location-picker.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ){
						deactivate_plugins(plugin_basename( __FILE__ ));
						$error_message = __('This plugin requires woocommerce location picker plugins to be active to send Latitude and Logitude to ERP!', 'onyx');
						wp_die($error_message);
				}
			wp_schedule_event( time(), 'daily', 'onyx_erp_schedule_sync');
		// Adding  page to validate  mobile code
		 $page_title= 'Verify Mobile';
		 $maybeExist = get_page_by_title( $page_title);
		 if(!is_page($maybeExist->ID)){
			 $my_post = array(
				'post_title'    =>  $page_title,
				'post_name'     => 'verify-mobile',
				'post_content'  => '[onyx_verify_mobile]',
				'post_status'   => 'publish',
				'post_author'   => get_current_user_id(),
				'post_type'     => 'page',
			);
		  wp_insert_post( $my_post, '' );
	  }
		update_option('woocommerce_enable_myaccount_registration','yes');


	}

}
