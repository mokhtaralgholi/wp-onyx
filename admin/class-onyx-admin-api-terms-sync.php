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
class Onyx_Admin_API_Terms_Sync {

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
		$this->parent_term ='';
		$this->ApiSyncClass = new Onyx_Admin_API_Sync($this->plugin_name,$this->version);
		$this->ApisettingClass = new Onyx_Settings_Pages($this->plugin_name,$this->version);

	}
	public function get_sync_categories(){
		$opt=array(
			"service"=>"GetGroupDetails",
		  "prams"  =>'&searchValue=-1'.
								 '&pageNumber=-1'.
								 '&rowsCount=-1'.
								 '&orderBy=-1'.
								 '&sortDirection=-1'
		);
		$respondTerms = $this->ApiSyncClass->get_records($opt);
		return $respondTerms->MultipleObjectHeader;
	}
	public function term_updated_log($code,$termid){
		 $logFile = WP_PLUGIN_DIR. '/'.$this->plugin_name.'/synclog.txt';
	   $currentlog = file_get_contents( $logFile);
		 $newlog = date('d-m-Y h:i:s',time())." Group with ERP CODE || ".$code." || Synced for WP Term ID  || ".$termid." ||";
		 $currentlog = $newlog ."<br />". $currentlog;
		 file_put_contents( $logFile,$currentlog );
	}
	public function process_terms($terms){
      foreach($terms as $term){
					$insertedId =  $this->insert_woo_terms($term);
		  }
	}
	public function  insert_woo_terms($obj){
		 $obj = $this->set_groups_data($obj);
		 $maybeExists = $this->maybe_term_exists($obj);
		 if(count($maybeExists)==0){
				 $term = wp_insert_term($obj->Name,'product_cat');
				 if(!is_wp_error($term)){
					 $termID = $term['term_id'];
					 $thisTerm =  get_term( $termID, 'product_cat');
					 $this->update_erp_term_metadata($termID, $obj);
				}
		 }else{
			 $termID = $maybeExists[0]->term_id;
			 wp_update_term($termID, 'product_cat', array('name' =>$obj->Name));
			 $this->update_erp_term_metadata($termID, $obj);
     }
		 $child = isset($obj->IAS_MAIN_SUB_GRP_DTL_List) ? $obj->IAS_MAIN_SUB_GRP_DTL_List : null;
		 if($child != null){
		   $this->process_level_two_terms($child,$termID);
	    }
	}
	public function process_level_two_terms($terms,$termID){
		foreach($terms as $term){
				$insertedId =  $this->insert_woo_terms_childs_level($term,$termID);
				if($insertedId){
					$child = isset($term->IAS_SUB_GRP_DTL_List) ? $term->IAS_SUB_GRP_DTL_List : null;
					if($child != null){
		 		   $this->process_level_three_terms($child,$insertedId);
		 	    }
			}
		}
	}
	public function process_level_three_terms($terms,$termID){
		foreach($terms as $term){
				$insertedId =  $this->insert_woo_terms_childs_level($term,$termID);
				if($insertedId){
					$child = isset($term->IAS_ASSISTANT_GRP_DTL_List) ? $term->IAS_ASSISTANT_GRP_DTL_List : null;
					if($child != null){
		 		   $this->process_level_two_terms($child,$insertedId);
		 	    }
			  }
		}
	}
	public function process_level_four_terms($terms,$termID){
		foreach($terms as $term){
				$insertedId =  $this->insert_woo_terms_childs_level($term,$termID);
				if($insertedId){
					$child = isset($term->IAS_DETAIL_GRP_List) ? $term->IAS_DETAIL_GRP_List : null;
					if($child != null){
		 		   $this->process_level_fifth_terms($child,$insertedId);
		 	    }
			  }
		}
	}
	public function process_level_fifth_terms($terms,$termID){
		foreach($terms as $term){
				$insertedId =  $this->insert_woo_terms_childs_level($term,$termID);
		}
	}
  public function insert_woo_terms_childs_level($obj,$parent_id){
		//echo '<pre>'; print_r($obj); echo '</pre>';
		$obj = $this->set_groups_data($obj);
		$maybeExists = $this->maybe_term_exists($obj);
		if(!$maybeExists){
				$term = wp_insert_term($obj->Name,'product_cat', array('parent'=>$parent_id));
				if(!is_wp_error($term)){
					$termID = $term['term_id'];
					$this->update_erp_term_metadata($termID, $obj);
					return $termID;
			 }else{
				// echo strlen($obj->Name);
				// echo '<pre>'; print_r($obj); echo '</pre>';
				// print_r($term);
			 }

		}
		if(isset($maybeExists[0]->term_id)){
			$termID = $maybeExists[0]->term_id;
			wp_update_term($termID, 'product_cat', array('name' =>$obj->Name,'parent'=>$parent_id));
			$this->update_erp_term_metadata($termID, $obj);
			return $termID;
		}

	}
	public function  set_groups_data($obj){
		$obj->Code 				      = isset($obj->Code) ? $obj->Code:'0';
		$obj->GroupCode 				 = isset($obj->GroupCode) ?  $obj->GroupCode:'0';
		$obj->MainGroupCode			 = isset($obj->MainGroupCode) ?  $obj->MainGroupCode:'0';
		$obj->SubGroupCode			  = isset($obj->SubGroupCode) ? $obj->SubGroupCode:'0';
		$obj->AssistantGroupCode        = isset($obj->AssistantGroupCode) ?$obj->AssistantGroupCode:'0';
		$obj->DetailGroupCode		   = isset($obj->DetailGroupCode) ?  $obj->DetailGroupCode:'0';
		return $obj;
}
	public function update_erp_term_metadata($termID, $obj){
		update_term_meta($termID, "Code", $obj->Code);
		update_term_meta($termID, "GroupCode", $obj->GroupCode);
		update_term_meta($termID, "MainGroupCode", $obj->MainGroupCode);
		update_term_meta($termID, "SubGroupCode", $obj->SubGroupCode);
		update_term_meta($termID, "AssistantGroupCode", $obj->AssistantGroupCode);
		update_term_meta($termID, "DetailGroupCode", $obj->DetailGroupCode);
		$logcode = "Code=>".$obj->Code." | GroupCode=>".$obj->GroupCode." | MainGroupCode=>". $obj->MainGroupCode." | SubGroupCode=>".$obj->SubGroupCode ." | AssistantGroupCode=>".$obj->AssistantGroupCode . " | DetailGroupCode =>". $obj->DetailGroupCode;
		$this->term_updated_log($logcode,$termID);
	}
	public function get_parent_term($obj){
		$metaQuery =array();
		if($obj->DetailGroupCode!=0){

		}
		 $metaQuery[]= array('key' => 'Code','value' => $obj->GroupCode,'compare'   => '=');
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
		$maybeExists =  get_terms('product_cat', $args );
		return $maybeExists;
	}
	public function  maybe_term_exists($obj){
		$parent = '';
			$metaQuery =array();
			 $metaQuery[]= array('key' => 'Code','value' => $obj->Code,'compare'=> '=');
			 $metaQuery[]= array('key' => 'GroupCode','value' => $obj->GroupCode,'compare'=> '=');
			 $metaQuery[]= array('key' => 'MainGroupCode','value' => $obj->MainGroupCode,'compare' => '=');
			 $metaQuery[]= array('key' => 'SubGroupCode','value' => $obj->SubGroupCode,'compare'=> '=');
			 $metaQuery[]= array('key' => 'AssistantGroupCode','value' => $obj->AssistantGroupCode,'compare'=> '=');
			 $metaQuery[]= array('key' => 'DetailGroupCode','value' => $obj->DetailGroupCode,'compare'   => '=');
			 $args = array(
					 'hide_empty' => false, // also retrieve terms which are not used yet
					 'meta_query' => array(
							 $metaQuery
					 )
				);
			$maybeExists =  get_terms('product_cat', $args );
			return $maybeExists;
	}
}
