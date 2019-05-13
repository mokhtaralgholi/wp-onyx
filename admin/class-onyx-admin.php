<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       http://aerezona.net/hassan
 * @since      1.0.0
 *
 * @package    Onyx
 * @subpackage Onyx/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Onyx
 * @subpackage Onyx/admin
 * @author     Mubashir Hassan <aerezona@gmail.com>
 */
class Onyx_Admin {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;
		$api_sync_methods = new Onyx_Admin_API_Sync( $this->plugin_name, $this->version);

		/**
		 * The class responsible for defining all actions that occur in the admin area.
		 */
	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */

	public function enqueue_styles() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Onyx_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Onyx_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/onyx-admin.css', array(), $this->version, 'all' );

	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts($hook) {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Onyx_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Onyx_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */
   global $post;
		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/onyx-admin.js', array( 'jquery' ), $this->version, false );
		if ( $hook == 'post.php' && $_GET['action'] == 'edit') {
        if ( 'product' === $post->post_type ) {
           wp_enqueue_script( $this->plugin_name.'product', plugin_dir_url( __FILE__ ) . 'js/onyx-product-admin.js', array( 'jquery' ), $this->version, false );
        }
    }

	}
	public function personalised_menu() {
     $onyx_admin_pages = new Onyx_Settings_Pages( $this->plugin_name, $this->version);
		 add_menu_page( 'Onyx store settings', 'Onyx Store', 'edit_posts', 'onyx_admin', array($onyx_admin_pages,'api_settings'), 'dashicons-media-spreadsheet' );
     add_submenu_page( 'onyx_admin', 'Onyx API settings', 'API Settings', 'edit_posts', 'onyx_admin', array($onyx_admin_pages,'api_settings_page'));
     add_submenu_page( 'onyx_admin', 'Onyx Sync settings', 'Sync Setings', 'edit_posts', 'onyx_admin_sync', array($onyx_admin_pages,'sync_settings_page'));
		 add_submenu_page( 'onyx_admin', 'Onyx Sync Log', 'Sync Log', 'edit_posts', 'onyx_admin_sync_log', array($onyx_admin_pages,'sync_log_page'));


	}
	public function plugin_settings_link($link){
		//echo '<pre>'; print_r($links); echo '</pre>';
		$settings_link = '<a href="options-general.php?page=plugin_name">' . __( 'Settings' ) . '</a>';
				array_push( $links, $settings_link );
			 return $links;

	}
	public function wc_onyx_product_tab($tabs){
		$tabs['onyxtab'] = array(
			'label'	 => __( 'Onyx Options', 'wcpt' ),
			'target' => 'onyxtab_options'
		 );
    return  $tabs;
	}
	public  function wc_onyx_product_panel() {
// Dont forget to change the id in the div with your target of your product tab
	?><div id='onyxtab_options' class='panel woocommerce_options_panel'><?php
		?><div class='options_group'><?php
				woocommerce_wp_checkbox( array(
					 'id' 	=> '_onyxtab_fromApi',
					 'label' => __( 'Is this ERP product', 'wcpt' ),
				 ),'yes');
			woocommerce_wp_text_input( array(
							'id'          => '_onyxtab_code',
							'label'       => __( 'Code', 'wcpt' ),
							'desc_tip'    => 'true',
							'description' => __( 'ERP Product Code.', 'wcpt' ),
					 ));
			woocommerce_wp_text_input( array(
		 					'id'          => '_onyxtab_unit',
		 					'label'       => __( 'Unit', 'wcpt' ),
		 					'desc_tip'    => 'true',
		 					'description' => __( 'ERP Product Unit.', 'wcpt' ),

		 			 ));

		?></div>
	</div><?php
 }
 public  function register_onyx_erp_posted_order_status() {
		register_post_status( 'wc-pendingerpposting', array(
				'label'                     => 'Pending Posting to ERPP',
				'public'                    => true,
				'show_in_admin_status_list' => true,
				'show_in_admin_all_list'    => true,
				'exclude_from_search'       => false,
				'label_count'               => _n_noop( 'Pending Posting to ERP <span class="count">(%s)</span>', 'Pending Posting to ERP <span class="count">(%s)</span>' )
		) );
	 }
public function add_onyx_erp_posted_to_order_statuses( $order_statuses ) {
    $new_order_statuses = array();
    foreach ( $order_statuses as $key => $status ) {
        $new_order_statuses[ $key ] = $status;
        if ( 'wc-processing' === $key ) {
            $new_order_statuses['wc-pendingerpposting'] = 'Pending ERP Posting';
        }
    }
    return $new_order_statuses;
  }

    public function onyx_post_user_data_to_erp($user_id){
        if( ! $user_id ) return;
        $user_data = get_userdata($user_id );
        $user_meta = get_user_meta($user_id);

        if (! $user_meta['billing_phone'][0] ) return;

            $onyx_api_sync = new Onyx_Admin_API_Sync( $this->plugin_name, $this->version);
            $apiSettings = $onyx_api_sync->get_API_settings();
            $postOptions= array();
            $postOptions['service']= 'RegistrCashCustomer';
            $postOptions['values'] = array(
                'ActivityType'	   =>'1',
                'CompanyName'   =>'CompanyName',
                'Email'			=> $user_data->user_email,
                'Mobile'		=> $user_meta['billing_phone'][0],
                'Name'          => $user_data->nickname,
                'Password'      => '',
                'CountryCode'	=> '1',
                'CityCode'      => '1',
                'Address'       => $user_meta['billing_address_1'][0]
            );
            $isOrderPushed = $onyx_api_sync->push_records($postOptions);
            if($isOrderPushed->SingleObjectHeader!=null){
                echo 'success';
            }else{
                echo 'fail';

                //	echo 'Opps! there might be some issue wile posting order';
                // wp_mail( $adminEmail, 'ERP Order posting Failed ',"Please login to admin panel and check the issue while posting order to ERP. Order status is set to 'Pending Posting To ERP'");
            }
    }

  public function extra_profile_fields( $user ) {
    $selected =   get_the_author_meta( 'onyx_mobile_valid', $user->ID );
	    ?>
      <h3><?php _e('Extra User Details'); ?></h3>
      <table class="form-table">
          <tr>
              <th><label for="gmail">User ERP Statues</label></th>
              <td>
                  <select name="onyx_mobile_valid">
                      <option <?php if($selected == 'yes'){echo("selected");}?> value="yes">Active</option>
                      <option <?php if($selected == 'no'){echo("selected");}?> value="no">In Active</option>
                  </select>
              </br>
                  <span class="description">Select ERP User Statues.</span>
              </td>
          </tr>
      </table>
    <?php

  }

  function save_extra_profile_fields( $user_id ) {
    if ( !current_user_can( 'edit_user', $user_id ) )
      return false;
    update_user_meta( $user_id, 'onyx_mobile_valid', $_POST['onyx_mobile_valid'] );
  }


}
