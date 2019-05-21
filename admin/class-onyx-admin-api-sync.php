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
class Onyx_Admin_API_Sync {

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
		$this->ApisettingClass = new Onyx_Settings_Pages($this->plugin_name,$this->version);

	}
	public function sync_modules(){

		$settingsModules = $this->ApisettingClass->onyx_module_to_sync();
		$doSyncModules = array();
		foreach($settingsModules as $key){
			$dosync = get_option($key['key']);
			if($dosync=="yes"){
			 $doSyncModules[str_replace($this->plugin_name.'_','',$key['key'])] = $dosync ;
		  }
		}
		return  $doSyncModules;
	}
	public function get_API_settings(){
    $settingsAPI = $this->ApisettingClass->api_settings();
		$apivars = array();
		foreach($settingsAPI as $key){
			 $apivars[str_replace('onyx_','',$key['key'])] = get_option($key['key']);
		}
		return  $apivars;
	}
	public function get_sync_users(){
		$opt=array(
			"service"=>"GetCashCustumerDataList",
		  "prams"=>'&searchValue=-1&pageNumber=-1&rowsCount=-1&orderBy=-1&sortDirection=-1'
		);
		return $this->get_records($opt);
	}
	public function process_erp_users(){
		  $erpUsers = $this->get_sync_users();
			//print_r($erpUsers); exit;
			$erpUsersData = $erpUsers->MultipleObjectHeader;
			if($erpUsersData!=null){
        $maybeSynced =   $this->sync_users($erpUsersData);
			return $maybeSynced;
		}else{
			return "Some error in Users sync";
		}
	}
	public function sync_users($erpUsersData){
		$userslog=array();
		$ucount =0;
		foreach($erpUsersData as $user){
		//if($user->Password ==null) return;
		$userReg = get_users(array('meta_key' => 'erp_code', 'meta_value' => $user->Code));
		//print_r($userReg); exit;
		if(!$userReg){
			$user_data = array(
	    'ID' => '', // automatically created
	    'user_pass' => rand(), //$user->Password,
	    'user_login' => $user->Name,
	    'user_nicename' => $user->Name,
	    'user_email' => $user->Email,
	    'display_name' => $user->Name,
	    'nickname' => $user->Name,
	    'first_name' => $user->Name,
	    'role' => 'subscriber' // administrator, editor, subscriber, author, etc
	    );
	    $user_id = wp_insert_user( $user_data );
			if(!is_wp_error($user_id)) :
				  update_user_meta($user_id,'erp_code', $user->Code);
					update_user_meta($user_id,'ActivityType', $user->ActivityType);
					update_user_meta($user_id,'Address', $user->Address);
					update_user_meta($user_id,'BranchNumber', $user->BranchNumber);
					update_user_meta($user_id,'CALC_VAT_AMT_TYPE', $user->CALC_VAT_AMT_TYPE);
					update_user_meta($user_id,'CityCode', $user->CityCode);
					update_user_meta($user_id,'CompanyName', $user->CompanyName);
					update_user_meta($user_id,'CountryCode', $user->CountryCode);
					update_user_meta($user_id,'CurrencyCode', $user->CurrencyCode);
					update_user_meta($user_id,'DiscountOffer', $user->DiscountOffer);
					update_user_meta($user_id,'Mobile', $user->Mobile);
					update_user_meta($user_id,'ShowOldPrice', $user->ShowOldPrice);
					update_user_meta($user_id,'Telephone', $user->Telephone);
					update_user_meta($user_id,'UseTax', $user->UseTax);
					update_user_meta($user_id,'WareHouseCode', $user->WareHouseCode);
					update_user_meta($user_id,'onyx_mobile_valid', 'yes');
					$userslog['added'][$ucount]['erpcode'] =$user->Code;
					$userslog['added'][$ucount]['time']=time();
					$userslog['added'][$ucount]['wpuserid']=$user_id;
			endif;
	  }else{
			$me =$userReg[0]->ID;
       wp_update_user( array(
				 'ID' => $me,
				 'display_name' => $user->Name,
				 'first_name' => $user->Name,
			 ) );
			 update_user_meta($me,'ActivityType', $user->ActivityType);
			 update_user_meta($me,'Address', $user->Address);
			 update_user_meta($me,'BranchNumber', $user->BranchNumber);
			 update_user_meta($me,'CALC_VAT_AMT_TYPE', $user->CALC_VAT_AMT_TYPE);
			 update_user_meta($me,'CityCode', $user->CityCode);
			 update_user_meta($me,'CompanyName', $user->CompanyName);
			 update_user_meta($me,'CountryCode', $user->CountryCode);
			 update_user_meta($me,'CurrencyCode', $user->CurrencyCode);
			 update_user_meta($me,'DiscountOffer', $user->DiscountOffer);
			 update_user_meta($me,'Mobile', $user->Mobile);
			 update_user_meta($me,'ShowOldPrice', $user->ShowOldPrice);
			 update_user_meta($me,'Telephone', $user->Telephone);
			 update_user_meta($me,'UseTax', $user->UseTax);
			 update_user_meta($me,'WareHouseCode', $user->WareHouseCode);
			 $userslog['updated'][$ucount]['erpcode'] =$user->Code;
			 $userslog['updated'][$ucount]['time']=time();
			 $userslog['updated'][$ucount]['wpuserid']=$me;
	  	}
			$ucount ++;
	  }
		return $userslog;
	}


	public function get_records($opt,$lang=null,$method="GET",$contentType=''){
		$apiSettings = $this->get_API_settings();
		if ($lang) {
		  $lang_code = $lang;
    } else {
		  $lang_code = $this->ApisettingClass->get_default_language_code();
    }
		$hasPort = parse_url($apiSettings['api_uri'],PHP_URL_PORT);
    $currentlog = '';
		$urlprams =$opt['service'].'?type=ORACLE'.'&year='.$apiSettings["accounting_year"].'&activityNumber='. $apiSettings["accounting_unit_number"].'&languageID='.$lang_code.$opt['prams'];
		$APIuri = rtrim($apiSettings['api_uri'], '/') . '/';
		$newlog = '==========================API REQUEST ======================= <br />
		           ['. date('d-m-Y h:i:s').']' . '[API Request] '. $APIuri.$urlprams .'
				   =========================== END =============================<br/>';
		$curl = curl_init();
		curl_setopt_array($curl, array(
		  CURLOPT_URL => $apiSettings['api_uri'].$urlprams,
		  CURLOPT_RETURNTRANSFER => true,
		  CURLOPT_ENCODING => "",
		  CURLOPT_MAXREDIRS => 10,
		  CURLOPT_TIMEOUT => 30,
		  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
		  CURLOPT_CUSTOMREQUEST => $method,
		  CURLOPT_HTTPHEADER => array(
		    "Cache-Control: no-cache",
				$contentType
		  ),
		));
		if($hasPort){
			curl_setopt($curl, CURLOPT_PORT , $hasPort );
		}
		$response = curl_exec($curl);
	    $newlog  .= '==========================API RESPONSE ======================= <br />
		          ['. date('d-m-Y h:i:s').']' . '[API Response] '. $response .'
				  ==========================ENDS ======================= <br/>'
				  .$currentlog;
		$err = curl_error($curl);
		if($err){
		$newlog  .= '==========================API RESPONSE ERROR ======================= <br />
		          ['. date('d-m-Y h:i:s').']' . '[API Response Error] '. $err.'
				  ==========================ENDS ======================= <br/>'
				  .$currentlog;
		}
		$onyxLogs = new Onyx_Admin_Sync_Logs( $this->plugin_name, $this->version);
		$onyxLogs->save_log($newlog);
		curl_close($curl);
			if ($err) {
				echo $err;
			} else {
			  $obj = json_decode($response);
				if($obj->_Result->_ErrMsg!="The operation accomplished successfully. "):
		            return $obj;
				else:
					return  array('_status'=>"404");
				endif;
			}
	}
	public function push_records($opt){
		$apiSettings = $this->get_API_settings();
		$hasPort = parse_url($apiSettings['api_uri'],PHP_URL_PORT);
		$apiSettings = $this->get_API_settings();
		$postFields = array(
			'type'			     =>'ORACLE',
			'year'           =>$apiSettings['accounting_year'],
			'activityNumber' => $apiSettings['accounting_unit_number'],
			'value'					 => $opt['values']
		);
		$curl = curl_init();
		curl_setopt_array($curl, array(
		  CURLOPT_URL => $apiSettings['api_uri'].$opt['service'],
		  CURLOPT_RETURNTRANSFER => true,
		  CURLOPT_ENCODING => "",
		  CURLOPT_MAXREDIRS => 10,
		  CURLOPT_TIMEOUT => 30,
		  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
		  CURLOPT_CUSTOMREQUEST => "POST",
			CURLOPT_POSTFIELDS    =>json_encode($postFields),
		  CURLOPT_HTTPHEADER => array(
		    "Cache-Control: no-cache",
				"Content-Type: application/json"
		  ),
		));
		if($hasPort){
			curl_setopt($curl, CURLOPT_PORT , $hasPort );
		}
		$response = curl_exec($curl);
		$err = curl_error($curl);
		curl_close($curl);
			if ($err) {
				echo $err;
			} else {
			  $obj = json_decode($response);
				if($obj->_Result->_ErrMsg!="The operation accomplished successfully. "):
		      return $obj;
				else:
					return  array('_status'=>"404");
				endif;
			}
	}
	public  function wc_onyx_process_product_options( $post_id ) {
 		update_post_meta( $post_id, '_onyxtab_code', $_POST['_onyxtab_code']);
 		update_post_meta( $post_id, '_onyxtab_unit', $_POST['_onyxtab_unit']);
 		$fromApi = isset( $_POST['_onyxtab_fromApi'] ) ? 'yes' : 'no';
 		update_post_meta( $post_id, '_onyxtab_fromApi', $fromApi);
 	}



}
