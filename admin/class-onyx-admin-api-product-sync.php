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
		//echo '<pre>'; print_r($products); echo '</pre>';
        $this->sync_products_attributes();
		return $products->MultipleObjectHeader;
	}
	public function process_erp_products($products){
		$productslog=array();
		$pcount =0;
		//echo'<pre>'; print_r($products); echo '</pre>'; exit;
		foreach($products as $product){
			$maybeExsist = $this->get_product_by_code($product->Code,$product->Unit);
			//echo'<pre>'; print_r($maybeExsist); echo '</pre>'; //exit;
			if(count($maybeExsist)==0){
				 $maybeAdded = $this->add_product($product);
				 if($maybeAdded){
					 $productslog['added'][$pcount]['erpcode']=$product->Code;
					 $productslog['added'][$pcount]['time']=time();
					 $productslog['added'][$pcount]['woopid']=$maybeAdded;
				 }
		  }else{
				 $maybeUpdated =  $this->update_product($product,$maybeExsist[0]->ID);
				 if($maybeUpdated){
					 $productslog['updated'][$pcount]['erpcode']=$product->Code;
					 $productslog['updated'][$pcount]['time']=time();
					 $productslog['updated'][$pcount]['woopid']=$maybeExsist[0]->ID;
				 }
			}
			$pcount++;
		}
        $this->sync_products_variation();
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

	// get Products Attributes
    public function sync_products_attributes() {
        $opt=array(
            "service"=>"GetItemsAttachments",
            "prams"=>
                '&searchValue=-1'.
                '&pageNumber=-1'.
                '&rowsCount=-1'.
                '&orderBy=-1'.
                '&sortDirection=-1'
        );
        $response = $this->ApiSyncClass->get_records($opt);
        //echo '<pre>'; print_r($products); echo '</pre>';
        if ($response->SingleObjectHeader ==! null) {
            $attributes = $response->SingleObjectHeader->Items_Attach_mst;
            $terms = $response->SingleObjectHeader->Items_Attach_Dtl;
            for ($i = 0; $i<sizeof($attributes); $i++) {
                $this->create_product_attribute($attributes[$i]->ATTCH_A_NAME, $attributes[$i]->ATTCH_NO);
            }
            for ($j = 0; $j<sizeof($terms); $j++) {
                $this->create_product_attribute_term($terms[$j]->ATTCH_DESC_A_NAME, $terms[$j]->ATTCH_NO, $terms[$j]->ATTCH_DESC_NO);
            }
        } else {
            echo 'error get products attributes from erp';
        }


    }

    // create product attributes
    public function create_product_attribute( $label_name , $slug ){
        global $wpdb;

        $slug = sanitize_title( $slug);

        if ( strlen( $slug ) >= 28 ) {
            return new WP_Error( 'invalid_product_attribute_slug_too_long', sprintf( __( 'Name "%s" is too long (28 characters max). Shorten it, please.', 'woocommerce' ), $slug ), array( 'status' => 400 ) );
        } elseif ( wc_check_if_attribute_name_is_reserved( $slug ) ) {
            return new WP_Error( 'invalid_product_attribute_slug_reserved_name', sprintf( __( 'Name "%s" is not allowed because it is a reserved term. Change it, please.', 'woocommerce' ), $slug ), array( 'status' => 400 ) );
        } elseif ( taxonomy_exists( wc_attribute_taxonomy_name( $slug) ) ) {
            return new WP_Error( 'invalid_product_attribute_slug_already_exists', sprintf( __( 'Name "%s" is already in use. Change it, please.', 'woocommerce' ), $label_name ), array( 'status' => 400 ) );
        }

        $attribute = array(
            'attribute_label'   => $label_name,
            'attribute_name'    => $slug,
            'attribute_type'    => 'select',
            'attribute_orderby' => 'menu_order',
            'attribute_public'  => 0,
        );
        $wpdb->insert( $wpdb->prefix . 'woocommerce_attribute_taxonomies', $attribute );

        $return = array(
            'attribute_name'     => $slug,
            'attribute_taxonomy' => 'pa_' . $slug,
            'attribute_id'       => $wpdb->insert_id,
            'term_ids'           => array(),
        );

        // Register the taxonomy.
        $name  = wc_attribute_taxonomy_name( $slug );
        $label = $slug;

        delete_transient( 'wc_attribute_taxonomies' );

        register_taxonomy( 'pa_' . $slug, array( 'product' ), array(
            'labels' => array(
                'name' => $slug,
            ),
        ) );
    }

    // create term attribute
    public function create_product_attribute_term ($label_name , $attribute, $slug) {

	    $args = array(
	        'slug' => $slug
        );
        if (!term_exists( $slug, 'pa_'.$attribute)) {
            wp_insert_term( $label_name, 'pa_'.$attribute, $args );
        }
    }

    // sync products variations
    public function sync_products_variation() {
        $opt=array(
            "service"=>"GetItemAttachmentAvailableQuantity",
            "prams"=>
                '&searchValue=-1'.
                '&pageNumber=-1'.
                '&rowsCount=-1'.
                '&orderBy=-1'.
                '&sortDirection=-1'
        );
        $variation_data = array(
            'attributes' => array(
            ),
            'stock_qty'     => 0
        );

        $response = $this->ApiSyncClass->get_records($opt);
        if ($response->MultipleObjectHeader ==! null) {
            $variation = $response->MultipleObjectHeader;
            for ($i = 0; $i<sizeof($variation); $i++) {
                $wc_product_id = $this->get_wc_products_id($variation[$i]->I_CODE);
                $this->add_single_product_attribute($wc_product_id);
                $variation_data = array(
                    'attributes' => array(
                        $variation[$i]->ATTCH_DESC_NO1 => $variation[$i]->ATTCH_NO1,
                        $variation[$i]->ATTCH_DESC_NO2 => $variation[$i]->ATTCH_NO2,
                        $variation[$i]->ATTCH_DESC_NO3 => $variation[$i]->ATTCH_NO3,
                        $variation[$i]->ATTCH_DESC_NO4 => $variation[$i]->ATTCH_NO4,
                        $variation[$i]->ATTCH_DESC_NO5 => $variation[$i]->ATTCH_NO5
                    ),
                    'stock_qty'     => $variation[$i]->AVL_QTY,
                    'erp_id'        => $variation[$i]->FLEX_NO
                );
                $this->create_product_variation($wc_product_id, $variation_data);
            }
        } else {
            echo 'error get products variations from erp';
        }

    }

    public function get_wc_products_id( $value ) {
        $products = get_posts(
            array(
                'numberposts' => -1,
                'post_type'		=> 'product',
                )
        );
        $id = 0;
        foreach ($products as $product) {
            $id = $product->ID;
            if (get_post_meta($id,'_onyxtab_code',true) === $value ) {
                break;
            }
        }
        return $id ;
    }

    public function add_single_product_attribute ($product_id) {
	    for ($i = 1; $i<6; $i++) {
            $terms = get_terms([
                'taxonomy' => 'pa_'.$i,
                'hide_empty' => false,
            ]);
            for ($j=0; $j<sizeof($terms); $j++) {
                $termsValues[$j] = $terms[$j]->name;
            }
            wp_set_object_terms( $product_id, $termsValues, 'pa_'.$i );
            $taxOptions[$i] = Array(
                'name' => 'pa_'.$i,
                'value' => '',
                'is_visible' => '1',
                'is_variation' => '1',
                'is_taxonomy' => '1'
            );
        }
        update_post_meta($product_id, '_product_attributes', $taxOptions);
    }

    /**
     * @param $product_id
     * @param $variation_data
     */
    public function create_product_variation ($product_id, $variation_data) {
        // Get the Variable product object (parent)
        $product = wc_get_product($product_id);

        if (!taxonomy_exists('product_type')) {
            register_taxonomy('product_type', array('product_type'));
        }

        $variation_post = array(
            'post_title'  => $product->get_title(),
            'post_name'   => 'product-'.$product_id.'-variation',
            'post_status' => 'publish',
            'post_parent' => $product_id,
            'post_type'   => 'product_variation',
            'guid'        => $product->get_permalink()
        );
        // Creating the product variation
        $variation_id = wp_insert_post( $variation_post );

        // Get an instance of the WC_Product_Variation object
        $variation = new WC_Product_Variation( $variation_id );

        // Iterating through the variations attributes
        $i = 0;
        foreach ($variation_data['attributes'] as $attribute => $term_slug) {
            if ($attribute ) {
            $taxonomy = 'pa_' . $attribute; // The attribute taxonomy
            $term = get_term_by('slug', $term_slug, $taxonomy);
            $term_name = $term->name;
            // If taxonomy doesn't exists we create it (Thanks to Carl F. Corneil)
            if (!taxonomy_exists($taxonomy)) {
                register_taxonomy(
                    $taxonomy,
                    'product_variation',
                    array(
                        'hierarchical' => false,
                        'label' => ucfirst($taxonomy),
                        'query_var' => true,
                        'rewrite' => array('slug' => '$taxonomy'), // The base slug
                    )
                );
            }

            // Check if the Term name exist and if not we create it.
            if (!term_exists($term_name, $taxonomy))
                wp_insert_term($term_name, $taxonomy); // Create the term


            // Set/save the attribute data in the product variation
            update_post_meta($variation_id, 'attribute_' . $taxonomy, $term_slug);

        }
        }

        ## Set/save all other data

        // Prices
        $variation_price = get_post_meta( $product_id, '_regular_price' ,true);
        $variation->set_price($variation_price);
        $variation->set_regular_price($variation_price);
        $variation->set_sale_price( $variation_price );
        $variation->set_slug('variation_'.$variation_data['erp_id']);

        // Stock
        if( ! empty($variation_data['stock_qty']) ){
            $variation->set_stock_quantity( $variation_data['stock_qty'] );
            $variation->set_manage_stock(true);
            $variation->set_stock_status('');
        } else {
            $variation->set_manage_stock(false);
        }

        $variation->set_weight(''); // weight (reseting)

        $variation->save(); // Save the data
        $product->save();
        wc_delete_product_transients($variation_id);
    }
}

