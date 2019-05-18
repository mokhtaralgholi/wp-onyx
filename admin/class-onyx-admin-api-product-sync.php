<?php

/**
 * The admin-specific functionality of the plugin for products.
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
class Onyx_Admin_API_Product_Sync {

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
  private $sitepress;
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
    global $sitepress;
    $this->sitepress = $sitepress;
	}
	public function get_erp_products(){
		$opt=array(
			"service"=>"GetItemsOnlineList",
		  "prams"=>'&groupCode=-1'.
			         '&mainGroupCode=-1'.
							 '&subGroupCode=-1'.
							 '&assistantGroupCode=-1'.
							 '&detailGroupCode=-1'.
							 '&wareHouseCode=-1'.
							 '&searchValue=-1'.
							 '&pageNumber=-1'.
							 '&rowsCount=-1'.
							 '&orderBy=-1'.
							 '&sortDirection=-1');
		$products = $this->ApiSyncClass->get_records($opt);
    $onyx_api_variations_sync = new onyx_api_variations_sync($this->plugin_name,$this->version);
    $onyx_api_variations_sync->sync_products_attributes_batches();
        return $products->MultipleObjectHeader;
	}
	public function process_erp_products($products){
		$productslog=array();
		$pcount =0;
    $Onyx_WPML_Product_Sync = new Onyx_wpml_Product_Sync($this->plugin_name,$this->version);
		foreach($products as $product){
			$maybeExsist = $this->get_product_by_code($product->Code,$product->Unit);

			if(count($maybeExsist)==0){
				 $maybeAdded = $this->add_product($product);
				 if($maybeAdded){
					 $productslog['added'][$pcount]['erpcode']=$product->Code;
					 $productslog['added'][$pcount]['time']=time();
					 $productslog['added'][$pcount]['woopid']=$maybeAdded;
					 if ($this->sitepress) {
             $Onyx_WPML_Product_Sync->wmpl_sync_product($product, $maybeAdded);
           }
				 }
		  }else{
				 $maybeUpdated =  $this->update_product($product,$maybeExsist[0]->ID);
				 if($maybeUpdated){
					 $productslog['updated'][$pcount]['erpcode']=$product->Code;
					 $productslog['updated'][$pcount]['time']=time();
					 $productslog['updated'][$pcount]['woopid']=$maybeExsist[0]->ID;
           if ($this->sitepress && $maybeExsist[1]->ID ) {
             $Onyx_WPML_Product_Sync->wmpl_sync_product($product, $maybeExsist[1]->ID);
           }
				 }
			}
			$pcount++;
		}
    $onyx_api_variations_sync = new onyx_api_variations_sync($this->plugin_name,$this->version);
    $onyx_api_variations_sync->sync_products_variation_batches();
	return $productslog;
	}
	public function  set_groups_data($obj){
		$lastchild ='';
		$grpArray =array('GroupCode','MainGroupCode','SubGroupCode','AssistantGroupCode','DetailGroupCode');
		foreach($grpArray as $val){
			//echo $val .'='.$obj->$val;
			if($obj->{$val} !== null && $obj->{$val}>0){
				$obj->{$val} = $obj->{$val};
			}else{
				$obj->{$val}='0';
			}
		}
	/*	$obj->GroupCode 				 = isset($obj->GroupCode) ?  $obj->GroupCode:0;
		$obj->MainGroupCode			 = isset($obj->MainGroupCode) ?  $obj->MainGroupCode:0;
		$obj->SubGroupCode			 = isset($obj->SubGroupCode) ? $obj->SubGroupCode:0;
		$obj->AssistantGroupCode = isset($obj->AssistantGroupCode) ? $obj->AssistantGroupCode:0;
		$obj->DetailGroupCode		 = isset($obj->DetailGroupCode) ? $obj->DetailGroupCode:0;
*/
		return $obj;
  }
	public function add_product($product){
		$data = array( // Set up the basic post data to insert for our product
        'post_author'  => 1,
        'post_content' =>  $product->Description,
        'post_status'  => 'publish',
        'post_title'   => $product->Name,
        'post_type'    => 'product'
    );

		//$terms = $this->get_categories($product);
        $productID = wp_insert_post($data);
		if(!is_wp_error($productID)){
			$pqty = get_option('onyx_product_quantity');
		    update_post_meta( $productID, '_price',$product->Price);
	        update_post_meta( $productID, '_regular_price',$product->Price);
			update_post_meta($productID, '_sku', $product->Code.'-'.$product->Unit);
			update_post_meta($productID, '_manage_stock', 'yes');
			$pqty = (isset($pqty))? $pqty : 'AvailableQuantity';
            update_post_meta($productID, '_stock', $product->$pqty);
			update_post_meta($productID, '_onyxtab_unit', $product->Unit);
			update_post_meta($productID, '_onyxtab_code', $product->Code);
			$erpImageUrl = $this->get_erp_image_url($product->Image);
			$attachmentID = $this->upload_attachment($erpImageUrl, $productID);
			if($attachmentID){
			   update_post_meta($productID, '_thumbnail_id', $attachmentID);
		   }
			 $papro = $this->set_groups_data($product);
			 $padterms = $this->get_categories($papro);
			 wp_set_object_terms($productID, $padterms, 'product_cat', true); // Set up its categories
	   }
		 return $productID;
	}
	public function update_product($product,$productID){
		$updProduct = array(
				'ID'           => $productID,
				'post_title'   => $product->Name,
				'post_content' => iconv('ISO-8859-1','UTF-8', $product->Description),
				'post_type'    => 'product'
		);
		$isUpdated = wp_update_post($updProduct);

        $args = array(
            'post_type' => 'product_variation',
            'post_status' => 'publish',
            'numberposts' => -1,
            'post_parent' => $productID
        );
        $variations = get_posts($args);

        foreach ($variations as $variate) {
            wp_delete_post($variate->ID , true);
        }

        if(!is_wp_error($isUpdated)){
		 update_post_meta( $productID, '_price',$product->Price);
		 update_post_meta( $productID, '_regular_price',$product->Price);
		 //update_post_meta($productID, '_sku', $product->Code.'-'.$product->Unit);
		 update_post_meta($productID, '_manage_stock', 'yes');
		 $pqty = get_option('onyx_product_quantity');
		 $pqty = (isset($pqty))? $pqty : 'AvailableQuantity';
		 update_post_meta($productID, '_stock', $product->$pqty);
		 //update_post_meta($productID, '_sku', $product->Code);
		 //update_post_meta($productID, '_onyxtab_unit', .'-'.$product->Unit);
		// update_post_meta($productID, '_onyxtab_code', $product->Code);
		 $erpImageUrl = $this->get_erp_image_url($product->Image);
		 $productAttachment = wp_get_attachment_url(get_post_thumbnail_id( $productID));
		 if($this->get_image_name($productAttachment) != $product->Image){
			 $attachmentID = $this->upload_attachment($erpImageUrl, $productID);
			 if($attachmentID){
				update_post_meta($productID, '_thumbnail_id', $attachmentID);
			  }
		  }
		 $proUp = $this->set_groups_data($product);
		 $paterms = $this->get_categories($proUp);
		 //echo '<pre>'; print_r($paterms); echo '<pre>'; exit;
		 wp_set_object_terms($productID, $paterms, 'product_cat', true);
		}
		return $productID;

	}
	public function get_categories($obj){
    $grpArray =array('GroupCode','MainGroupCode','SubGroupCode','AssistantGroupCode','DetailGroupCode');
    $GrpCode ='';
		for($i=0; $i<5; $i++){
			if($obj->{$grpArray[$i]}==0){
				$GrpCode = $obj->{$grpArray[$i-1]};
				$obj->{$grpArray[$i-1]} =0;
				break;
			}
		}
		

		$metaQuery =array();
		$metaQuery[]= array('key' => 'Code','value' => $GrpCode,'compare'   => '=');
		$metaQuery[]= array('key' => 'GroupCode','value' => $obj->GroupCode,'compare'   => '=');
		$metaQuery[]= array('key' => 'MainGroupCode','value' =>$obj->MainGroupCode,'compare'   => '=');
		$metaQuery[]= array('key' => 'SubGroupCode','value' =>$obj->SubGroupCode,'compare'   => '=');
		$metaQuery[]= array('key' => 'AssistantGroupCode','value' =>$obj->AssistantGroupCode,'compare'   => '=');
		$metaQuery[]= array('key' => 'DetailGroupCode','value' =>$obj->DetailGroupCode,'compare'   => '=');
		 $args = array(
				 'hide_empty' => false, // also retrieve terms which are not used yet
				 'meta_query' => array(
						 $metaQuery
				 )
			);

		$termExists = get_terms('product_cat', $args );
							//echo '<pre>'; print_r($termExists); echo '<pre>'; exit;
		if($termExists){
			$termIds = array($termExists[0]->term_id);
			$termParents = get_ancestors($termExists[0]->term_id, 'product_cat' );
			$termIdsd = array_merge($termIds,$termParents) ;
			//echo '<pre>';print_r($termIdsd); echo '<pre>'; exit;
			return $termIdsd;
		}
		
		//echo '<pre>';print_r($termExists); echo '<pre>';
	}
	public function get_erp_image_url($image){
		return rtrim(get_option('onyx_images_uri'),'/').'/'.$image;
	}
	public function  get_product_by_code($code,$unit){
				$args = array(
		    'post_type'  => 'product',
		    'meta_query' => array(
		       'relation' => 'AND',
		        array(
		            'key'     => '_onyxtab_code',
		            'value'   => $code,
		            'compare' => '='
		        ),
		        array(
		            'key'     => '_onyxtab_unit',
		            'value'   => $unit,
		            'compare' => '='
		        )
		    )
		);
	  $search_query = new WP_Query( $args );

		if(isset($search_query->posts)){
			 return $search_query->posts;
		 }else{
			 return null;
		 }

	}
	public function  get_image_name($url){
	    preg_match('/[^\?]+\.(jpg|jpe|jpeg|gif|png)/i', $url, $matches);
		$name = basename($matches[0]);
		return $name;
	}
	public function upload_attachment($url,$post_id){
	if ( !function_exists('media_handle_upload') ) {
			require_once(ABSPATH . "wp-admin" . '/includes/image.php');
			require_once(ABSPATH . "wp-admin" . '/includes/file.php');
			require_once(ABSPATH . "wp-admin" . '/includes/media.php');
		}
		$tmp = download_url( $url );
		if( is_wp_error( $tmp ) ){
		}
		$desc = "";
		$file_array = array();
		preg_match('/[^\?]+\.(jpg|jpe|jpeg|gif|png)/i', $url, $matches);
		$file_array['name'] = basename($matches[0]);
		$file_array['tmp_name'] = $tmp;
		if ( is_wp_error( $tmp ) ) {
			@unlink($file_array['tmp_name']);
			$file_array['tmp_name'] = '';
		}
		$id = media_handle_sideload( $file_array, $post_id, $desc );
		if ( is_wp_error($id) ) {
			@unlink($file_array['tmp_name']);
			return false;
		}else{
	   return $id;
		}
		//$src = wp_get_attachment_url( $id );
	}


}

