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
class Onyx_Admin_API_Orders_Sync {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;
	private $parent_term;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;
	private $doSyncModules;
	private $settingPages;

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
		$this->ApiSyncClass = new Onyx_Admin_API_Sync($this->plugin_name,$this->version);
		$this->ApisettingClass = new Onyx_Settings_Pages($this->plugin_name,$this->version);
	}
	public function get_onyx_erp_woo_orders(){
		$args = array(
		    'limit' => -1,
		    'offset' => 1
		);
		$wooOrders_Array = wc_get_orders($args  );
		foreach($wooOrders_Array as $order){
		 $this->get_order_status($order);
		}
	//	echo '<pre>'; print_r($meta); echo '</pre>';
	}
	public function get_order_status($order){
		$order_id = $order->get_id();
		$erpOrderNo = get_post_meta($order_id,'sync_erp_orderno',true);
		$erpOrderSer = get_post_meta($order_id,'sync_erp_orderser',true);
		if($erpOrderNo!=null && $erpOrderSer!=null){
		$opt=array(
			"service"=>"GetOrderStatus",
			"prams"=>'&orderNumber='.$erpOrderNo.'&orderSerial='.$erpOrderSer);
		$orderErpStatus = $this->ApiSyncClass->get_records($opt);
		if(isset($orderErpStatus->SingleObjectHeader) && $orderErpStatus->SingleObjectHeader!=null){
			$oERPStatus = $SingleObjectHeader->SingleObjectHeader;
			$newOrderStatus = get_option('onyx_order_map_'.$oERPStatus);
			$order->update_status($newOrderStatus);


		  }
	  }

	}
	public function onyx_display_order_erp_id($order){
		$order_data = $order->get_data();
		 echo '<table class="woocommerce_order_items"><tbody>';
		 echo '<tr><th colspan="2"><strong>'.__('ERP Order No').':</strong></th><td>' . get_post_meta( $order_data["id"], 'sync_erp_orderno', true ) . '</td></tr>';
		 echo '<tr><th colspan="2"><strong>'.__('ERP Order Serial').':</strong></th><td>  ' . get_post_meta( $order_data["id"], 'sync_erp_orderser', true ) . '</td></tr>';
		 echo '</tbody></table>';

	}
	public function onyx_post_order_data_to_erp($order_id){
		if( ! $order_id ) return;
		$order = wc_get_order( $order_id);
		$erpOrderNo = get_post_meta($order_id,'sync_erp_orderno',true);
		$_shipping_lat = get_post_meta($order_id, "_shipping_lat");
		$_shipping_lng = get_post_meta($order_id, "_shipping_lng");
		if(!$erpOrderNo){
		$order_data = $order->get_data();
		$orderTotal = $order->get_total();
		$user = $order->get_user();
		$user_id = $order->get_user_id();
		$userMobile = get_user_meta($user_id,'onyx_mobile_number',true);
		$address_1 = $order_data['shipping']['address_1'];
		$address_2 = $order_data['shipping']['address_2'];
		$city = $order_data['shipping']['city'];
		$state = $order_data['shipping']['state'];
		$postcode = $order_data['shipping']['postcode'];
		$country = $order_data['shipping']['country'];
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
								'Code'			 =>$userMobile,
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
			//	echo 'Opps! there might be some issue wile posting order';
			// wp_mail( $adminEmail, 'ERP Order posting Failed ',"Please login to admin panel and check the issue while posting order to ERP. Order status is set to 'Pending Posting To ERP'");
		 }
	 }else{
		// echo 'Order Already Posted to ERP';
	 }
	}

}
