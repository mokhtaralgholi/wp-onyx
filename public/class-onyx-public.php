<?php

/**
 * The public-facing functionality of the plugin.
 *
 * @link       http://aerezona.net/hassan
 * @since      1.0.0
 *
 * @package    Onyx
 * @subpackage Onyx/public
 */

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the public-facing stylesheet and JavaScript.
 *
 * @package    Onyx
 * @subpackage Onyx/public
 * @author     Mubashir Hassan <aerezona@gmail.com>
 */
class Onyx_Public {

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
	 * @param      string    $plugin_name       The name of the plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;
    $this->define_public_shortcodes();

	}

	/**
	 * Register the stylesheets for the public-facing side of the site.
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

		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/onyx-public.css', array(), $this->version, 'all' );

	}

	/**
	 * Register the JavaScript for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {

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

		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/onyx-public.js', array( 'jquery' ), $this->version, false );

	}
	public function define_public_shortcodes(){
		 add_shortcode( 'onyx_verify_mobile', array($this,'onyx_verify_mobile_callback' ));
	}
  public function onyx_verify_mobile_callback(){
		extract($_REQUEST);
		$maybeUser = get_user_by('onyx_mobile_number',$mobilecode);
		if($maybeUser){
			delete_user_meta( $maybeUser->ID, 'onyx_mobile_activation_code');
		  update_user_meta( $maybeUser->ID, 'onyx_mobile_valid', 'yes' );
		}else{
			echo '<div class="row" style="clear:all">';
			  echo '<h2>Mobile Verification</h2>';
				echo '<p>Please enter code recieved at mobile number given in registration process</p>';
	      echo '<form name="verifymobilefrm" method="post">';
				echo '<p><input type="text" placeholder="Mobile Code" name="mobilecode" required></p>';
				echo '<p><input type="submit" name="mobilecodesubmit" value="Verify"></p>';
				echo '</form>';
			echo '</div>';
	   }
	}
	public function woo_mobilenumber_register_field(){
		$mobileNumber = isset($_POST['reg_billing_phone'])?$_POST['reg_billing_phone']:'';
		?>
       <p class="form-row form-row-wide">
       <label for="reg_billing_phone"><?php _e( 'Mobile Number', 'woocommerce' ); ?><span class="required">*</span></label>
       <input type="text" class="input-text" name="billing_phone" id="reg_billing_phone" value="<?php esc_attr_e($mobileNumber); ?>" />
       </p>
			<?php
	}
	public function woo_validate_mobilenumber_field($username, $email, $validation_errors){
		if ( isset( $_POST['billing_phone'] ) && empty( $_POST['billing_phone'] ) ) {
             $validation_errors->add( 'onyx_mobile_number_error', __( '<strong>Error</strong>: Mobile number is required!.', 'woocommerce' ) );
      }
		return $validation_errors;
	}
	public function wooc_save_mobilenumber_field($customer_id){
		if ( isset( $_POST['billing_phone'] ) ) {
				// $apiSettings = $onyx_api_sync->get_API_settings();
			   $mobileValidationCode = $this->randomString(6);
				 update_user_meta( $customer_id, 'billing_phone', sanitize_text_field( $_POST['billing_phone'] ) );
				 $this->onyx_post_user_data_to_erp($customer_id,sanitize_text_field( $_POST['billing_phone'] ) );
				 update_user_meta( $customer_id, 'onyx_mobile_activation_code',$mobileValidationCode);
				 update_user_meta( $customer_id, 'onyx_mobile_valid', 'no' );
				 $onyx_api_sync = new Onyx_Admin_API_Sync( $this->plugin_name, $this->version);
				 $apiSettings = $onyx_api_sync->get_API_settings();
//				 $postfields = array('service'=>'SendSMSMessage','values'=>array("Mobile"=>$_POST['onyx_mobile_number'],'Message'=>$mobileValidationCode));
//				 $curl = curl_init();
//					curl_setopt_array($curl, array(
//					  CURLOPT_URL => $apiSettings['api_uri'],
//					  CURLOPT_RETURNTRANSFER => true,
//					  CURLOPT_ENCODING => "",
//					  CURLOPT_MAXREDIRS => 10,
//					  CURLOPT_TIMEOUT => 30,
//					  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
//					  CURLOPT_CUSTOMREQUEST => "POST",
//					  CURLOPT_POSTFIELDS => json_encode($postfields),
//					  CURLOPT_HTTPHEADER => array(
//					    "Cache-Control: no-cache",
//					    "Content-Type: application/json",
//					    "Postman-Token: 4f264250-f59d-4215-a2b0-ac19236610b3"
//					  ),
//					));
//					$response = curl_exec($curl);
//					$err = curl_error($curl);
//					curl_close($curl);
//            $apiKey = 'RZ7/9tNvEDk-FUs3IrMT6U1pzQYeJ0lZGXiou7NRRk';
//
//            // Message details
//            $numbers = array($_POST['billing_phone']);
//            $sender = urlencode('website acttivation key');
//            $message = rawurlencode('your acctivation code'.$mobileValidationCode);
//
//            $numbers = implode(',', $numbers);
//
//            // Prepare data for POST request
//            $data = array('apikey' => $apiKey, 'numbers' => $numbers, "sender" => $sender, "message" => $message);
//
//            // Send the POST request with cURL
//            $ch = curl_init();
//            curl_setopt($ch, CURLOPT_URL, 'https://api.txtlocal.com/send/');
//            curl_setopt($ch, CURLOPT_POST, true);
//            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
//            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
//            $response = curl_exec($ch);
//            if ($response === FALSE) {
//                $response =  "cURL Error: " . curl_error($ch);
//            }
//            curl_close($ch);
//            // Process your response here
//            echo $response;


    	}
	}
	public function onyx_login_mobile_activation_check($user, $username, $password){
		if( !is_wp_error( $user ) ) {
        if( !empty( $_POST ) ) {
					$maybeUser = get_user_by('login',$username);

					if($maybeUser && !user_can( $maybeUser->ID, 'manage_options' )){
						$maybeActive = get_user_meta($maybeUser->ID,'onyx_mobile_valid','yes');
            if( 'yes' !== $maybeActive ) {
                $error = new WP_Error();
                $error->add( 'custom-login-error', 'Account is inactive.');
                return $error;
            }
					}
        }
    }
    return $user;
	}
	public  function randomString($length)
	{
	  return bin2hex(openssl_random_pseudo_bytes($length));
	}
	public function onyx_wc_registration_redirect($redirect_to){
		$maybeExist = get_page_by_title('Verify Mobile');
		if(!is_page($maybeExist->ID)){

		$redirect_to = get_permalink($maybeExist->ID);
	  }
    return $redirect_to;
	}

    public function onyx_post_user_data_to_erp($user_id,$phone){
        if( ! $user_id ) return;
        $user_data = get_userdata($user_id );
        $user_meta = get_user_meta($user_id);

        if (! $phone ) return;

        $onyx_api_sync = new Onyx_Admin_API_Sync( $this->plugin_name, $this->version);
        $apiSettings = $onyx_api_sync->get_API_settings();
        $postOptions= array();
        $postOptions['service']= 'RegistrCashCustomer';
        $postOptions['values'] = array(
            'ActivityType'	   =>'1',
            'CompanyName'   =>'CompanyName',
            'Email'			=> $user_data->user_email,
            'Mobile'		=> $phone,
            'Name'          => $user_data->nickname,
            'Password'      => '',
            'CountryCode'	=> '1',
            'CityCode'      => '1',
            'Address'       => $user_meta['billing_address_1'][0]
        );
        $isOrderPushed = $onyx_api_sync->push_records($postOptions);
        if($isOrderPushed->SingleObjectHeader!=null){

        }else{
            //	echo 'Opps! there might be some issue wile posting order';
            // wp_mail( $adminEmail, 'ERP Order posting Failed ',"Please login to admin panel and check the issue while posting order to ERP. Order status is set to 'Pending Posting To ERP'");
        }
    }
	/*
   // Pushing order to ONYX ERP
	*/
	public function onyx_post_order_data_to_erp($order_id){
		if( ! $order_id ) return;
		$order = wc_get_order( $order_id);
		$erpOrderNo = get_post_meta($order_id,'sync_erp_orderno',true);
		$_shipping_lat = get_post_meta($order_id, "_shipping_lat");
		$_shipping_lng = get_post_meta($order_id, "_shipping_lng");
		if(!$erpOrderNo){
        $order_data = $order->get_data();
        if ($order_data['status'] == 'auto-draft') return;
        $orderTotal = $order->get_total();
		$user = $order->get_user();
        $user_id = $order->get_user_id();
        $userMobile = $order_data['billing']['phone'];
        $userCode = $order_data['billing']['phone'];
        if ($user_id == 0) {
            $userCode = null;
        }
        $address_1 = $order_data['billing']['address_1'];
        $address_2 = $order_data['billing']['address_2'];
        $city = $order_data['billing']['city'];
        $state = $order_data['billing']['state'];
        $postcode = $order_data['billing']['postcode'];
        $country = $order_data['billing']['country'];
		$line_items = $order->get_items();
		$orderItems =array();
        foreach ($line_items as $item_key => $item_values){
			 $oItem =array();
			 $product_id = $item_values->get_product_id();
			  $pUnit  = get_post_meta($product_id,'_onyxtab_unit',true);
				$pCode  = get_post_meta($product_id,'_onyxtab_code',true);
				$item_data = $item_values->get_data();
				$quantity = $item_data['quantity'];
	 			$tax_class = $item_data['tax_class'];
	 			$line_subtotal = $item_data['subtotal'];
	 			$line_subtotal_tax = $item_data['subtotal_tax'];
	 			$line_total = $item_data['total'];
	 			$line_total_tax = $item_data['total_tax'];
				$oItem = array(
					      'Code'=>$pCode,
								'Unit' =>$pUnit,
								'Quantity' => $quantity,
								'Price'  => $line_subtotal,
								'DiscountPercentage' =>0,
								'DiscountValue' =>0,
								'TaxRate'   =>$line_subtotal_tax,
								'TaxAmount' =>$line_total_tax,
								'CharegeAmt'=> $line_total
				);
				//echo '<pre>'; print_r($product_id); echo '</pre>';
				$orderItems[]=$oItem;

		 }

		 $onyx_api_sync = new Onyx_Admin_API_Sync( $this->plugin_name, $this->version);
		 $apiSettings = $onyx_api_sync->get_API_settings();
		 $postOptions= array();
		 $postOptions['service']= 'SaveOrder';
		 $postOptions['values'] = array(
			          'OrderNo'	   =>-1,
								'OrderSer'   =>-1,
								'Code'			 =>$userCode,
								'Name'			 =>$order_data['billing']['first_name'] .' '. $order_data['billing']['last_name'],
								'CustomerType'=>1,
								'FiscalYear'   => $apiSettings['accounting_year'],
								'Activity'		=>$apiSettings['accounting_unit_number'],
								'BranchNumber'=> $apiSettings['branch_number'],
								'WareHouseCode'=> $apiSettings['warehouse_number'],
								'TotalDemand'  => $orderTotal,
								'TotalDiscount'=> $order_data['discount_total'],
								'TotalTax'     => $order_data['total_tax'],
								'CharegeAmt'   => $orderTotal,
								'CustomerAddress'=> $address_1 .' '. $address_2 .' '.$city.' '.$state.' '.$country,
								'Mobile'        =>$userMobile,
								'Latitude'			=>$_shipping_lat[0],
								'Logitude'      =>$_shipping_lng[0],
								'FileExtension' =>'',
								'ImageValue'		=>'',
								'P_AD_TRMNL_NM' =>0,
								'OrderDetailsList'=>$orderItems
								);
		 $isOrderPushed = $onyx_api_sync->push_records($postOptions);
		 if($isOrderPushed->SingleObjectHeader!=null){
		   update_post_meta($order_id,'sync_erp_orderno', $isOrderPushed->SingleObjectHeader->OrderNo );
			 update_post_meta($order_id, 'sync_erp_orderser', $isOrderPushed->SingleObjectHeader->OrderSer );
		 }else{
			  $order->update_status('pendingerpposting');
				$adminEmail    = get_bloginfo('admin_email');
			  echo 'Opps! there might be some issue wile posting order';
			 wp_mail( $adminEmail, 'ERP Order posting Failed ',"Please login to admin panel and check the issue while posting order to ERP. Order status is set to 'Pending Posting To ERP'");
		 }
	 }else{
		 echo 'Order Already Posted to ERP';
	 }
 }


 function wooc_extra_register_fields() {?>
        <p class="form-row form-row-wide">
            <label for="reg_billing_phone"><?php _e( 'Phone', 'woocommerce' ); ?></label>
            <input type="text" class="input-text" name="billing_phone" id="reg_billing_phone" value="<?php esc_attr_e( $_POST['billing_phone'] ); ?>" />
        </p>
        <p class="form-row form-row-first">
            <label for="reg_billing_first_name"><?php _e( 'First name', 'woocommerce' ); ?><span class="required">*</span></label>
            <input type="text" class="input-text" name="billing_first_name" id="reg_billing_first_name" value="<?php if ( ! empty( $_POST['billing_first_name'] ) ) esc_attr_e( $_POST['billing_first_name'] ); ?>" />
        </p>
        <p class="form-row form-row-last">
            <label for="reg_billing_last_name"><?php _e( 'Last name', 'woocommerce' ); ?><span class="required">*</span></label>
            <input type="text" class="input-text" name="billing_last_name" id="reg_billing_last_name" value="<?php if ( ! empty( $_POST['billing_last_name'] ) ) esc_attr_e( $_POST['billing_last_name'] ); ?>" />
        </p>
        <div class="clear"></div>
        <?php
    }


}// end Class Code
