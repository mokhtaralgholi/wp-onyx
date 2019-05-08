<?php

/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       http://aerezona.net/hassan
 * @since      1.0.0
 *
 * @package    Onyx
 * @subpackage Onyx/includes
 */

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    Onyx
 * @subpackage Onyx/includes
 * @author     Mubashir Hassan <aerezona@gmail.com>
 */
class Onyx {

	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      Onyx_Loader    $loader    Maintains and registers all hooks for the plugin.
	 */
	protected $loader;

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $plugin_name    The string used to uniquely identify this plugin.
	 */
	protected $plugin_name;

	/**
	 * The current version of the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $version    The current version of the plugin.
	 */
	protected $version;

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the admin area and
	 * the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		ini_set('MAX_EXECUTION_TIME', 3600);
		if ( defined( 'PLUGIN_NAME_VERSION' ) ) {
			$this->version = PLUGIN_NAME_VERSION;
		} else {
			$this->version = '1.0.0';
		}
		$this->plugin_name = 'onyx';

		$this->load_dependencies();
		$this->set_locale();
		$this->define_admin_hooks();
		$this->define_public_hooks();
		$this->define_admin_filters();
		$this->define_public_filters();


	}

	/**
	 * Load the required dependencies for this plugin.
	 *
	 * Include the following files that make up the plugin:
	 *
	 * - Onyx_Loader. Orchestrates the hooks of the plugin.
	 * - Onyx_i18n. Defines internationalization functionality.
	 * - Onyx_Admin. Defines all hooks for the admin area.
	 * - Onyx_Public. Defines all hooks for the public side of the site.
	 *
	 * Create an instance of the loader which will be used to register the hooks
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function load_dependencies() {

		/**
		 * The class responsible for orchestrating the actions and filters of the
		 * core plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-onyx-loader.php';

		/**
		 * The class responsible for defining internationalization functionality
		 * of the plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-onyx-i18n.php';

		/**
		 * The class responsible for defining all actions that occur in the admin area.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-onyx-admin.php';
		/**
		 * The class responsible for defining all actions related to setting pages.
		 */
    require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-onyx-setting-pages.php';
		/**
		 * The class responsible for defining all actions that occur in the public-facing
		 * side of the site.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'public/class-onyx-public.php';


		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-onyx-admin-api-sync.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-onyx-admin-api-terms-sync.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-onyx-admin-api-product-sync.php';
    require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-onyx-wpml-product-sync.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-onyx-admin-api-orders-sync.php';


		$this->loader = new Onyx_Loader();

	}

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * Uses the Onyx_i18n class in order to set the domain and to register the hook
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function set_locale() {

		$plugin_i18n = new Onyx_i18n();

		$this->loader->add_action( 'plugins_loaded', $plugin_i18n, 'load_plugin_textdomain' );

	}

	/**
	 * Register all of the hooks related to the admin area functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_admin_hooks() {
		$onyx_admin_orders_sync_Class = new Onyx_Admin_API_Orders_Sync( $this->get_plugin_name(), $this->get_version() );
		$onyx_API_Sync_Class = new Onyx_Admin_API_Sync( $this->get_plugin_name(), $this->get_version() );
		$onyx_admin_pages = new Onyx_Settings_Pages( $this->get_plugin_name(), $this->get_version() );
		$plugin_admin = new Onyx_Admin( $this->get_plugin_name(), $this->get_version() );
		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_styles' );
		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts' );
		$this->loader->add_action( 'admin_menu', $plugin_admin, 'personalised_menu' );
		$this->loader->add_action( 'admin_init', $onyx_admin_pages, 'register_onyx_plugin_settings');
		$this->loader->add_action( 'woocommerce_product_data_tabs', $plugin_admin, 'wc_onyx_product_tab' );
		$this->loader->add_action( 'woocommerce_product_data_panels', $plugin_admin, 'wc_onyx_product_panel' );
		$this->loader->add_action( 'woocommerce_process_product_meta', $plugin_admin, 'wc_onyx_process_product_options' );
    $this->loader->add_action('onyx_erp_schedule_sync', $onyx_admin_pages,'process_erp_sync_job');
		$this->loader->add_action( 'product_cat_edit_form_fields',$onyx_admin_pages, 'product_cat_taxonomy_custom_fields', 10, 2 );
		$this->loader->add_action( 'woocommerce_admin_order_data_after_billing_address', $onyx_admin_orders_sync_Class,'onyx_display_order_erp_id', 10, 1 );
		$this->loader->add_action( 'init', $plugin_admin,'register_onyx_erp_posted_order_status' );
        $this->loader->add_action( 'user_register', $plugin_admin,'onyx_post_user_data_to_erp', 10,1 );
        $this->loader->add_action( 'profile_update', $plugin_admin,'onyx_post_user_data_to_erp', 10,2 );
	}

	private function define_admin_filters() {
		$onyx_API_Sync_Class = new Onyx_Admin_API_Sync( $this->get_plugin_name(), $this->get_version() );
		$onyx_admin_pages = new Onyx_Settings_Pages( $this->get_plugin_name(), $this->get_version() );
		$plugin_admin = new Onyx_Admin( $this->get_plugin_name(), $this->get_version() );
		$this->loader->add_filter('pre_update_option_onyx_api_key',$onyx_admin_pages,'on_update_onyx_api_key', 10, 2 );
		$this->loader->add_filter('pre_update_option_onyx_api_token',$onyx_admin_pages,'on_update_onyx_api_token', 10, 2 );
    $this->loader->add_filter('manage_edit-product_cat_columns',$onyx_admin_pages, 'add_product_cat_columns');
		$this->loader->add_filter('manage_product_cat_custom_column', $onyx_admin_pages,'add_product_cat_column_content',10,3);
		$this->loader->add_filter( 'cron_schedules', $onyx_admin_pages,'onyx_sync_intervals');
		$this->loader->add_filter( 'manage_users_columns', $onyx_admin_pages,'onyx_modify_user_table' );
		$this->loader->add_filter( 'manage_users_custom_column', $onyx_admin_pages,'onyx_is_uer_active_col', 10, 3 );
		$this->loader->add_filter( 'wc_order_statuses',$plugin_admin, 'add_onyx_erp_posted_to_order_statuses' );
	}
	/**
	 * Register all of the hooks related to the public-facing functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */

	private function define_public_hooks() {

		$plugin_public = new Onyx_Public( $this->get_plugin_name(), $this->get_version() );

		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_styles' );
		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_scripts' );
		$this->loader->add_action( 'woocommerce_register_form_start', $plugin_public, 'woo_mobilenumber_register_field' );
		$this->loader->add_action( 'woocommerce_register_post',  $plugin_public, 'woo_validate_mobilenumber_field', 10, 3 );
		$this->loader->add_action( 'woocommerce_created_customer',  $plugin_public, 'wooc_save_mobilenumber_field' );
		$this->loader->add_action('woocommerce_registration_redirect', $plugin_public, 'onyx_wc_registration_redirect',10);
		$this->loader->add_action( 'woocommerce_thankyou',$plugin_public, 'onyx_post_order_data_to_erp', 10, 1 );
        $this->loader->add_action( 'save_post_shop_order',$plugin_public, 'onyx_post_order_data_to_erp', 10, 1 );
        // $this->loader->add_filter( 'woocommerce_register_form_start',$plugin_public, 'wooc_extra_register_fields' );
        // $this->loader->add_action( 'user_register', $plugin_public,'onyx_post_user_data_to_erp', 10,1 );

	}
	private function define_public_filters() {
		$plugin_public = new Onyx_Public( $this->get_plugin_name(), $this->get_version() );
		$this->loader->add_filter( 'authenticate', $plugin_public,'onyx_login_mobile_activation_check', 100, 3 );

	}

	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 *
	 * @since    1.0.0
	 */
	public function run() {
		$this->loader->run();
	}

	/**
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality.
	 *
	 * @since     1.0.0
	 * @return    string    The name of the plugin.
	 */
	public function get_plugin_name() {
		return $this->plugin_name;
	}

	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 *
	 * @since     1.0.0
	 * @return    Onyx_Loader    Orchestrates the hooks of the plugin.
	 */
	public function get_loader() {
		return $this->loader;
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @since     1.0.0
	 * @return    string    The version number of the plugin.
	 */
	public function get_version() {
		return $this->version;
	}

}
