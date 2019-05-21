<?php


  class onyx_api_variations_sync
  {
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

    public function sync_products_attributes_batches () {
      $opt=array(
        "service"=>"GetBatchConfigDetails",
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
        $attributes = $response->SingleObjectHeader->IAS_BATCH_COLUMNS_LABELS;
        $terms = $response->SingleObjectHeader->IAS_BATCH_NO_CONTENTS;
        for ($i = 0; $i<sizeof($attributes); $i++) {
          $this->create_product_attribute($attributes[$i]->CAPTION_DET, $i+1);
        }
        for ($j = 0; $j<sizeof($terms); $j++) {
          $this->create_product_attribute_term($terms[$j]->BATCH_DESC_A_NAME, $terms[$j]->COL_NO, $terms[$j]->BATCH_DESC_NO);
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
      //echo '<pre>'; print_r($response); echo '</pre>';
      if ($response->MultipleObjectHeader ==! null) {
        $variation = $response->MultipleObjectHeader;
        for ($i = 0; $i<sizeof($variation); $i++) {
          //$wc_product = $this->get_product_by_code($variation[$i]->I_CODE,"باكت");
          $wc_product = $this->get_product_by_code($variation[$i]->I_CODE,$variation[$i]->ITM_UNT);
          if(count($wc_product)>0){
            $wc_product_id =$wc_product[0]->ID;
            wp_set_object_terms ($wc_product_id,'variable','product_type', true);
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
            $product = wc_get_product($product_id);
            WC_Product_Variable::sync( $product_id , 'yes');
            do_action( 'woocommerce_variable_product_sync_data', $product );
            delete_transient( 'wc_product_children_' . $product_id );
            delete_transient( 'wc_var_prices_' . $product_id );
          }// endig for Matching of product.
        }

      } else {
        echo 'error get products variations from erp';
      }

    }

    public function sync_products_variation_batches() {
      $opt=array(
        "service"=>"GetItemsBatchAvailableQuantity",
        "prams"=>
          '&searchValue=-1'.
          '&pageNumber=-1'.
          '&rowsCount=-1'.
          '&orderBy=-1'.
          '&sortDirection=-1'.
          '&itemCode=-1'.
          '&batchNumber=-1'
      );


      $variation_data = array(
        'attributes' => array(
        ),
        'stock_qty'     => 0
      );

      $response = $this->ApiSyncClass->get_records($opt);
      //echo '<pre>'; print_r($response); echo '</pre>';
      if ($response->MultipleObjectHeader ==! null) {
        $variation = $response->MultipleObjectHeader;
        for ($i = 0; $i<sizeof($variation); $i++) {
          //$wc_product = $this->get_product_by_code($variation[$i]->I_CODE,"باكت");
          $wc_product = $this->get_product_by_code($variation[$i]->I_CODE,$variation[$i]->ITM_UNT);
          if(count($wc_product)>0){
            $wc_product_id =$wc_product[0]->ID;
            wp_set_object_terms ($wc_product_id,'variable','product_type', true);
            $this->add_single_product_attribute($wc_product_id);
            $single_product_attributes = $this->get_single_product_attributes($variation[$i]->I_CODE,$variation[$i]->BATCH_NO);
            $variation_data = array(
              'attributes' => $single_product_attributes,
              'stock_qty'  => $variation[$i]->AVL_QTY
            );
            $this->create_product_variation($wc_product_id, $variation_data);
            $product = wc_get_product($product_id);
            WC_Product_Variable::sync( $product_id , 'yes');
            do_action( 'woocommerce_variable_product_sync_data', $product );
            delete_transient( 'wc_product_children_' . $product_id );
            delete_transient( 'wc_var_prices_' . $product_id );
          }// endig for Matching of product.
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
        $taxOptions['pa_'.$i] = Array(
          'name' => 'pa_'.$i,
          'value' => '',
          'position'=>$i-1,
          'is_variation' => '1',
          'is_visible' => '1',
          'is_taxonomy' => '1'
        );
      }
      update_post_meta($product_id, '_product_attributes', $taxOptions);
    }

    public function get_single_product_attributes($item_code, $batch_no) {
      $opt=array(
        "service"=>"GetItemsBatchDetails",
        "prams"=>
          '&searchValue=-1'.
          '&pageNumber=-1'.
          '&rowsCount=-1'.
          '&orderBy=-1'.
          '&sortDirection=-1'.
          '&itemCode='.$item_code.
          '&batchNumber='.$batch_no
      );

      $attributes = array();

      $response = $this->ApiSyncClass->get_records($opt);
      if ($response->MultipleObjectHeader ==! null) {
        $variation = $response->MultipleObjectHeader;
        $attributes = array(
          $variation[0]->COL_NO1 => $variation[0]->BATCH_DESC_NO1,
          $variation[0]->COL_NO2 => $variation[0]->BATCH_DESC_NO2,
          $variation[0]->COL_NO3 => $variation[0]->BATCH_DESC_NO3,
          $variation[0]->COL_NO4 => $variation[0]->BATCH_DESC_NO4,
          $variation[0]->COL_NO5 => $variation[0]->BATCH_DESC_NO5
        );
      }
      return $attributes;
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

      $i = 0;
      foreach ($variation_data['attributes'] as $attribute => $term_slug) {
        if ($attribute ) {
          $taxonomy = 'pa_' . $attribute; // The attribute taxonomy
          $term = get_term_by('slug', $term_slug, $taxonomy);
          $term_name = $term->name;
          if (!taxonomy_exists($taxonomy)) {
            register_taxonomy(
              $taxonomy,
              'product_variation',
              array(
                'hierarchical' => false,
                'label' => ucfirst($taxonomy),
                'query_var' => true,
                'rewrite' => array('slug' => $taxonomy), // The base slug
              )
            );
          }
          if (!term_exists($term_name, $taxonomy))
            wp_insert_term($term_name, $taxonomy); // Create the term
          update_post_meta($variation_id, 'attribute_'.$taxonomy, $term_slug);
          do_action('woocommerce_api_save_product_variation', $variation_id, 0, $variation);
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

  }