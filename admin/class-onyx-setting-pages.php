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
class Onyx_Settings_Pages {

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

	}

	function onyx_is_uer_active_col( $val, $column_name, $user_id ) {
    switch ($column_name) {
        case 'mobileactive' :
            $maybeActive = get_user_meta( $user_id ,'onyx_mobile_valid',true);
						if(isset($maybeActive) && $maybeActive ==='yes'){
							$val ='&#9989;';
						}else{
							$val ='&#10060;';
						}
            break;
        default:
    }
    return $val;
}
	public function  onyx_modify_user_table( $column ) {
    $column['mobileactive'] = 'Active';
    return $column;
  }
	public function add_product_cat_columns($columns){
    $columns['onyxids'] = "ERP Group ID's";
		unset($columns['image']);
		unset($columns['slug']);
		unset($columns['description']);
		//unset($columns['cb']);
    return $columns;
   }
	public function add_product_cat_column_content($content, $column, $term_id){
    $term= get_term($term_id, 'product');
	 $allmeta = get_term_meta($term_id);
	 //echo '<pre>';print_r($allmeta);echo '</pre>';


    switch ($column) {
        case 'onyxids':
						$Code 							= get_term_meta($term_id,'Code',true);
						$GroupCode 					= get_term_meta($term_id,'GroupCode',true);
						$MainGroupCode 			= get_term_meta($term_id,'MainGroupCode',true);
						$SubGroupCode 			= get_term_meta($term_id,'SubGroupCode',true);
						$AssistantGroupCode = get_term_meta($term_id,'AssistantGroupCode',true);
						$DetailGroupCode 		= get_term_meta($term_id,'DetailGroupCode',true);
						$content = ($Code!='0' && $Code!='')  ? "Code => ".$Code:'';
						$content .= ($GroupCode!='0' && $GroupCode!='')  ? "<br/>GroupCode => ".$GroupCode:'';
						$content .= ($MainGroupCode!='0' && $MainGroupCode!='')  ? "<br/>MainGroupCode => ".$MainGroupCode:'';
						$content .= ($SubGroupCode!='0' && $SubGroupCode!='')  ? "<br/>SubGroupCode => ".$SubGroupCode:'';
						$content .= ($AssistantGroupCode!='0' && $AssistantGroupCode!='') ? "<br/>AssistantGroupCode => ".$AssistantGroupCode:'';
						$content .= ($DetailGroupCode!='0' && $DetailGroupCode!='')    ?  "<br/>DetailGroupCode => ".$DetailGroupCode:'';

            break;
        default:
            break;
    }
    return $content;
   }
	 function product_cat_taxonomy_custom_fields($tag) {
    // Check for existing taxonomy meta for the term you're editing
     $term_id = $tag->term_id; // Get the ID of the term you're editing
     //$term_meta = get_option( "taxonomy_term_$t_id" ); // Do the check
		 $childgroup='';
		 $content ='';
		 $Code 							= get_term_meta($term_id,'Code',true);
		 $GroupCode 					= get_term_meta($term_id,'GroupCode',true);
		 $MainGroupCode 			= get_term_meta($term_id,'MainGroupCode',true);
		 $SubGroupCode 			= get_term_meta($term_id,'SubGroupCode',true);
		 $AssistantGroupCode = get_term_meta($term_id,'AssistantGroupCode',true);
		 $DetailGroupCode 		= get_term_meta($term_id,'DetailGroupCode',true);
		 $content = ($Code!='0' && $Code!='')  ? "Code => ".$Code:'';
		 $content .= ($GroupCode!='0' && $GroupCode!='')  ? "<br/>GroupCode => ".$GroupCode:'';
		 $content .= ($MainGroupCode!='0' && $MainGroupCode!='')  ? "<br/>MainGroupCode => ".$MainGroupCode:'';
		 $content .= ($SubGroupCode!='0' && $SubGroupCode!='')  ? "<br/>SubGroupCode => ".$SubGroupCode:'';
		 $content .= ($AssistantGroupCode!='0' && $AssistantGroupCode!='') ? "<br/>AssistantGroupCode => ".$AssistantGroupCode:'';
		 $content .= ($DetailGroupCode!='0' && $DetailGroupCode!='')    ?  "<br/>DetailGroupCode => ".$DetailGroupCode:'';
 ?>

 <tr class="form-field">
     <th scope="row" valign="top">
         <label for="presenter_id"><?php _e('ERP Group Ids'); ?></label>
     </th>
     <td>
        <?php echo $content;?>
     </td>
 </tr>

 <?php
 }
  public function register_onyx_plugin_settings(){
		  $apiSettingsobj = $this->api_settings();
			foreach($apiSettingsobj as $apisetting){
				register_setting( 'onyx-api-settings-group', $apisetting["key"]);
			}
			$orderstatusMapping = $this->order_status_mapping();
			foreach($orderstatusMapping as $ordermap){
				register_setting( 'onyx-order-settings-group', $ordermap["key"]);
			}

      $syncfields = $this->sync_settings_fields();
      foreach($syncfields as $syncfield){
         register_setting( 'onyx-sync-settings-group', $syncfield["key"]);
      }
      register_setting( 'onyx-sync-settings-group', 'onyx_sync_hours');
      register_setting( 'onyx-sync-settings-group', 'onyx_sync_mins');

      $modulesTosync = $this->onyx_module_to_sync();
      foreach($modulesTosync as $mods){
        register_setting( 'onyx-sync-fields-group', $mods["key"]);
      }
	}
  public function sync_settings_fields(){
		$days =array();
		for($i=1; $i<32; $i++){
			$days[$i] =$i;
		}
    $syncFields = array(
          array('key'=>'onyx_sync_every','title'=>'Sync Schedule','type'=>"select", "options"=>array("0"=>"Select Sync Schedule","1"=>"Every Hour","24"=>"Daily","168"=>"Weekly","720"=>"Monthly")),
					array('key'=>'onyx_sync_weekdays','title'=>'Select Day of week','type'=>"select", "options"=>array("sunday"=>"Sunday","monday"=>"Monday","tuesday"=>"Tuesday","wednesday"=>"Wednesday","thursday"=>"Thursday","friday"=>"Friday")),
					array('key'=>'onyx_sync_monthdays','title'=>'Select Date in month ','type'=>"select", "options"=>$days),
//

    );
    return $syncFields;
  }
  public function onyx_module_to_sync(){
    $modulesTosync = array(
          array('key'=>'onyx_sync_users','title'=>'User Accounts', 'type'=>'checkbox'),
					array('key'=>'onyx_sync_categories','title'=>'Categories ', 'type'=>'checkbox'),
          array('key'=>'onyx_sync_products','title'=>'Products ', 'type'=>'checkbox'),
					array('key'=>'onyx_sync_pending_orders','title'=>'Pending ERP Posting Orders', 'type'=>'checkbox'),
          array('key'=>'onyx_sync_orders','title'=>'Orders ', 'type'=>'checkbox')
    );
    return $modulesTosync;
  }
	public function on_update_onyx_api_key($new_value, $old_value ){
		echo substr($old_value,0,3);
		  if(substr($old_value,0,3)!="AZK"){
	        $new_value ="AZK".base64_encode($new_value);
        }
				return $new_value;
	}
	public function on_update_onyx_api_token($new_value, $old_value ){
		if(substr($old_value,0,3)!="AZT"){
				$new_value ="AZT".base64_encode($new_value);
			}
			return $new_value;
	}
	public function api_settings(){
		$apiSettingsArray = array(
		   array('key'=>'onyx_api_uri','title'=>'API Url', 'type'=>'url'),
			 array('key'=>'onyx_api_key','title'=>'API Key'),
			 array('key'=>'onyx_api_token','title'=>'API Token'),
			 array('key'=>'onyx_accounting_year','title'=>'Accounting Year'),
			 array('key'=>'onyx_accounting_unit_number','title'=>'Accounting Unit'),
			 array('key'=>'onyx_branch_number','title'=>'Branch'),
			 array('key'=>'onyx_warehouse_number','title'=>'Warehouse'),
			 array('key'=>'onyx_language_number','title'=>'Language'),
			 array('key'=>'onyx_shipping_method_number','title'=>'Shipping Method'),
			 array('key'=>'onyx_sms_uri','title'=>'SMS Url','type'=>'url'),
			 array('key'=>'onyx_images_uri','title'=>'Images Base Url','type'=>'url'),
			 array('key'=>'onyx_product_quantity','title'=>'Product Quantity','type'=>'radio', "options"=>array("AvailableQuantity"=>"Available Quantity","AvailableWithReservedQuantity"=>"Available With Reserved"))
		);
		return $apiSettingsArray;
	}
	public function order_status_mapping(){
		$opt = array("pending"=>"Not Approved","processing"=>"Approved","failed"=>"Refused","on-hold"=>"In Progress","completed"=>"Used In Invoice");
		$orderstatusMapping = array(
			 array('key'=>'onyx_order_map_0','title'=>'Pending', 'type'=>"select", "options"=>$opt),
			 array('key'=>'onyx_order_map_1','title'=>'Processing', 'type'=>"select", "options"=>$opt),
			 array('key'=>'onyx_order_map_2','title'=>'Failed', 'type'=>"select", "options"=>$opt),
       array('key'=>'onyx_order_map_3','title'=>'On-Hold', 'type'=>"select", "options"=>$opt),
			 array('key'=>'onyx_order_map_4','title'=>'Completed', 'type'=>"select", "options"=>$opt)
		);
		return $orderstatusMapping;
	}
  public function get_hours_select(){
    $hours = get_option("onyx_sync_hours");
    $mins = get_option("onyx_sync_mins");
		$selhours = (0==$hours)?"selected=selected":"";
		$selmins = (0==$mins)?"selected=selected":"";
    echo '<tr valign="top" class="onyx_sync_hours_min"><th scope="row">'.__("Sync Time(Hour)").'</th>';
    echo '<td>Hour  &nbsp; <select name="onyx_sync_hours"><option value="0" '.$selhours.'>'.__("Select").'</option>';
    for($h=1; $h<=24; $h++){
      $sel = ($h==$hours)?"selected=selected":"";
			$lable = ($h>12)? ($h-12)." PM": $h." AM";
			if($h>12 && $h<22){ $lable = "0".$lable;}
			if($h<10){$lable = "0".$h." AM";}
			if($h==24){$lable ="00 AM";}
      echo '<option value="'.$h.'" '.$sel.'>'.$lable.'</option>';
    }
    echo '</select>';
    echo '  Minutes &nbsp;<select name="onyx_sync_mins"><option value="0" '.$selmins.'>'.__("Select").'</option>';
    for($m=1; $m<=60; $m++){
      $selm = ($m==$mins)?"selected=selected":"";
			$lable = ($m==60)? "00": $m;
			if($m<10){$lable = "0".$m;}
      echo '<option value="'.$m.'" '.$selm.'>'.$lable.'</option>';
    }
    echo '</select></td>';
    echo '</tr>';
  }
 /* Fucntion adds  Fields to Option pages
 */
	public function add_field($obj){
      $type = isset($obj['type'])? $obj['type']: "text";
  		echo '<tr valign="top" class=" '.$obj['key'].'">
  		<th scope="row">'.$obj["title"].'</th>';
      if($type=='text' || $type=='url' || $type=='time' ){
      echo '<td><input class="regular-text" type="'.$type.'" name="'.$obj['key'].'" value="'.esc_attr( get_option($obj['key'])).'" /></td>';
      }
			if($type=="select"){
          $opti = $obj['options'];
					$selVal = get_option($obj['key']);
          echo '<td><select id="'.$obj['key'].'" name="'.$obj['key'].'" class="regular-text">';
						foreach($opti as $key=>$val){
							$isSelected = ($key ==$selVal) ? 'Selected="selected"':'';
            echo '<option value="'.$key.'" '.$isSelected.'>'.$val.'</option>';
          }
           echo '</select></td>';
      }
			if($type=="checkbox"){
				$checked='';
				if( get_option($obj['key'])) { $checked = ' checked="checked" '; }
      echo '<td><input '.$checked.' class="regular-text" type="'.$type.'" name="'.$obj['key'].'"  /></td>';
      }
			if($type=='checkbox_multi'){
				  $opti = $obj['options'];
				 $selVal = get_option($obj['key']);
				 print_r($selVal);
				 //echo '<td>';
         foreach($opti as $key=>$val){
					 $ischecked = ($key ==$selVal) ? 'checked="checked"':'';
				 echo '<td class="fl"><input type="checkbox" name="'.$obj['key']. "[" .$key. "]". '" value="'.$key.'"' .$ischecked.'>'.$val.'</td>';
			    }
				 //echo '</td>';
			}
			if($type=='radio'){
				  $opti = $obj['options'];
				 $selVal = get_option($obj['key']);
         foreach($opti as $key=>$val){
					 $ischecked = ($key ==$selVal) ? 'checked="checked"':'';
				 echo '<td class="fl"><input type="radio" name="'.$obj['key'].'" value="'.$key.'"' .$ischecked.'>'.$val.'</td>';
			    }

			}
			echo '</tr>';
	}
  /*     API Setting PAge
  *      page contains all API settings avialble for Connecting to ERP
  */
  public function api_settings_page(){
    $active_tab = isset($_GET[ 'tab' ])? $_GET[ 'tab' ] :"api_settings";
	?>
    <div class="wrap">
    <?php settings_errors(); ?>
    <h1> Settings</h1>
    <div class="description"><?php echo __("Settings to Connect to ERP ");?></div>

    <h2 class="nav-tab-wrapper">
                    <a href="?page=onyx_admin" class="nav-tab <?php echo $active_tab == 'api_settings' ? 'nav-tab-active' : ''; ?>">Api Settings</a>
                    <a href="?page=onyx_admin&tab=order_status" class="nav-tab <?php echo $active_tab == 'order_status' ? 'nav-tab-active' : ''; ?>">Order status Mapping</a>
    </h2>
    <form method="post" action="options.php">
        <table class="form-table">
    			<?php
           if( $active_tab == 'api_settings' ) {
             settings_fields( 'onyx-api-settings-group' );
             do_settings_sections( 'onyx-api-settings-group' );
    				  //$this->block_heading("API Settings");
    				 	$apiSettingsobj = $this->api_settings();
    					foreach($apiSettingsobj as $apisetting){
    				 			$this->add_field($apisetting);
    			  		}

    				}elseif( $active_tab == 'order_status' ) {
              settings_fields( 'onyx-order-settings-group' );
              do_settings_sections( 'onyx-order-settings-group' );
    				//$this->block_heading("Order Status Mapping");
    				$orderstatusMapping = $this->order_status_mapping();
    				  foreach($orderstatusMapping as $ordermap){
    					  $this->add_field($ordermap);
    				  }
    		   }
    			?>
        </table>
        <?php submit_button(); ?>
    </form>
    </div>
 <?php
}
  /*     Sync Data Setting PAge
  *      page contains all settings avialble for scheduling  Cron in wordpress.
  */
	public function Sync_settings_page(){
      $active_tab = isset($_GET[ 'tab' ])? $_GET[ 'tab' ] :"sync_schedule";
    ?>
  <div class="wrap">
   <?php settings_errors(); ?>
  <h1> Sync Settings</h1>
  <div class="description"><?php echo __("Settings to sync data with ERP ");?></div>

  <h2 class="nav-tab-wrapper">
                  <a href="?page=onyx_admin_sync" class="nav-tab <?php echo $active_tab == 'sync_schedule' ? 'nav-tab-active' : ''; ?>">Sync Setting</a>
                  <a href="?page=onyx_admin_sync&tab=sync_modules" class="nav-tab <?php echo $active_tab == 'sync_modules' ? 'nav-tab-active' : ''; ?>">Sync Modules</a>
  </h2>
  <form method="post" action="options.php">
      <table class="form-table">
  			<?php
         if( $active_tab == 'sync_schedule' ) {
            settings_fields( 'onyx-sync-settings-group' );
            do_settings_sections( 'onyx-sync-settings-group' );
  				  //$this->block_heading("API Settings");
  				 	$syncFields = $this->sync_settings_fields();
  					foreach($syncFields as $syncField){
  				 			$this->add_field($syncField);
  			  		}
            $this->get_hours_select();
		     if(get_option('onyx_sync_every')!=0){
					 $doScheduleAt =  $this->get_next_sync_schedule_time();
					 	//wp_schedule_event( time() + $cronSchedule, 'onyxSchedule', 'onyx_erp_schedule_sync');
					}else{
            wp_clear_scheduled_hook('onyx_erp_schedule_sync');
					}
  				}elseif( $active_tab == 'sync_modules' ) {
            settings_fields( 'onyx-sync-fields-group' );
            do_settings_sections( 'onyx-sync-fields-group' );
  				  $modulesTosync = $this->onyx_module_to_sync();
  				  foreach($modulesTosync as $mod){
  					  $this->add_field($mod);
  				  }

  		   }
  			?>
      </table>

      <?php
			submit_button();?>
			<H2> Sync Now </h2>
				<?php $checkAPISettings = get_option('onyx_api_uri');
            $syncDisabled = ($checkAPISettings=='') ? 'disabled':'';
				?>

			<p class="description">Use this option to sync all selected modules.<br />
			 To select modules go to <a href="?page=onyx_admin_sync&tab=sync_modules">sync Modules</a> tab.</p>
			 <p> <a href="?page=onyx_admin_sync&do=syncnow" class="button button-primary button-large">Sync Now</a></p>

			<?php
			if(isset($_GET[ 'do' ]) && $_GET[ 'do'] =="syncnow"):
				$logFile = WP_PLUGIN_DIR. '/'.$this->plugin_name.'/synclog.txt';
				$currentlog = file_get_contents( $logFile);
			 // file_put_contents( $file,'' );
				$onyx_term_sync = new Onyx_Admin_API_Terms_Sync( $this->plugin_name, $this->version);
				if(get_option('onyx_sync_categories')){
	         $erpTerms = $onyx_term_sync->get_sync_categories();
					 if($erpTerms!=null && !isset($erpTerms['-status'])){
					 $onyx_term_sync->process_terms($erpTerms);
					 echo "Categories Synced successfully <br />";
				 }else{
					 echo "some error in categories sunc <br />";
				 }
			   }
				 if(get_option('onyx_sync_users')){
					    $logcontents ='';
							 $onyx_api_sync = new Onyx_Admin_API_Sync( $this->plugin_name, $this->version);
						   $erpUserslog =  $onyx_api_sync->process_erp_users();
							 //echo '<pre>'; print_r($erpUserslog); echo '</pre>';
							 if(isset($erpUserslog['added'])){
							 echo '<div class="alert alert-succes"><strong>'.count($erpUserslog['added']) .'</strong> Users Added';
								  foreach($erpUserslog['added'] as $line){
								    $newlog = date('d-m-Y h:i:s',$line['time'])." User with ERP CODE || ".$line['erpcode']." ||       Added as WP User ID  || ".$line['wpuserid']." ||";
										$currentlog = $newlog ."<br />". $currentlog;
							    }
							 }
							 if(isset($erpUserslog['updated'])){
							 echo '<div class="alert alert-succes"><strong>'.count($erpUserslog['updated']) .'</strong> Users Updated';
							    foreach($erpUserslog['updated'] as $line){
										 $newlog = date('d-m-Y h:i:s',$line['time'])." User with ERP CODE || ".$line['erpcode']." ||         Updated for WP User ID || ".$line['wpuserid']." ||";
										 $currentlog = $newlog ."<br />". $currentlog;
								  }
								}
               file_put_contents( $logFile,$currentlog );
			    }
								$onyx_sync_products = new Onyx_Admin_API_Product_Sync( $this->plugin_name, $this->version);
								if(get_option('onyx_sync_products')){
		 				    $erpPro =  $onyx_sync_products->get_erp_products();
								$syncLog = $onyx_sync_products->process_erp_products($erpPro);
							  $this->remove_deleted_products($syncLog);

								if(isset($syncLog['added'])){
								echo '<div class="alert alert-succes"><strong>'.count($syncLog['added']) .'</strong> Products Added';
								 foreach($syncLog['added'] as $line){
									$plog = date('d-m-Y h:i:s',$line['time'])." Product with ERP CODE || ".$line['erpcode']." ||       Added as Woo Product ID  || ".$line['woopid']." ||";
									$currentlog = $plog ."<br />". $currentlog;

								 }
							  }
								if(isset($syncLog['updated'])){
								  echo '<div class="alert alert-succes"><strong>'.count($syncLog['updated']) .'</strong> Products Updated';
									foreach($syncLog['updated'] as $line){
										$plog = date('d-m-Y h:i:s',$line['time'])." Product with ERP CODE || ".$line['erpcode']." ||      Updated for Woo Product ID  || ".$line['woopid']." ||";
										$currentlog = $plog ."<br />". $currentlog;
									}
							  }
								file_put_contents( $logFile,$currentlog );
							}
							if(get_option('onyx_sync_pending_orders')){
								$args = array(
									    'status' => 'pendingerpposting',
									);
								$pendingOrders = wc_get_orders( $args );
								$onyx_sync_Orders = new Onyx_Admin_API_Orders_Sync( $this->plugin_name, $this->version);
								foreach($pendingOrders as $order){
								  $onyx_sync_Orders->onyx_post_order_data_to_erp($order->get_id());
								}
							}
							if(get_option('onyx_sync_orders')){
							$onyx_sync_Orders = new Onyx_Admin_API_Orders_Sync( $this->plugin_name, $this->version);
							$onyx_sync_Orders->get_onyx_erp_woo_orders();
						  }


		  endif;
			?>

  </form>
  </div>
  <?php

	}
	public function get_next_sync_schedule_time(){
		wp_clear_scheduled_hook('onyx_erp_schedule_sync');
		$cronSchedule	= get_option('onyx_sync_every');
		$syncmin 			 		= get_option('onyx_sync_mins');
		$syncHou    			= get_option('onyx_sync_hours');
		$synctime = ($syncHou * 60 * 60) + $syncmin;
		//echo date('Y-m-d H:i s');
		$h =  date('H',time()) ;
		$m =  date('i',time()) ;
		$difftime  = ($h * 3600)+ ($m * 60);
		if($cronSchedule == 168){
			$cronSchweekDay		= get_option('onyx_sync_weekdays');
			$selectedWeekDay 	= strtotime("next ".$cronSchweekDay);
			$syncSch    			= $selectedWeekDay +$synctime;
		}
		if($cronSchedule == 720){
				$cronSchmonthDay		= get_option('onyx_sync_monthdays') * 24 * 3600;
				$startOfthismonth = strtotime('first day of this month');
				if(time() < $startOfthismonth + $cronSchmonthDay){
					$nDate = $startOfthismonth + $cronSchmonthDay -$difftime;
	        $syncSch  = $nDate +  (($syncHou * 60 * 60) + $syncmin);
				}elseif(time() > $startOfthismonth + $cronSchmonthDay){
	       $startOfNextmonth = strtotime('first day of next month');
				 	$nDate = $startOfNextmonth + $cronSchmonthDay -$difftime;
					$syncSch  = $nDate +  (($syncHou * 60 * 60) + $syncmin);
				}
		}
	  if($cronSchedule == 24){ $syncSch = 24 * 60 * 60 + $synctime - $difftime; }
		if($cronSchedule == 1){  $syncSch = 60 * 60;  }

		wp_schedule_event( $syncSch, 'onyxSchedule', 'onyx_erp_schedule_sync' );
	}
  public function remove_deleted_products($synclog){
      global $wpdb;
			$existingProductIds = get_option('onyx_woo_products');
			$newProductsList = array();
			if(isset($synclog['added'])){
         foreach($synclog['added'] as $line){
					 $newProductsList[]= $line['woopid'];
				 }
			}elseif(isset($synclog['updated'])){
				foreach($synclog['updated'] as $line){
					$newProductsList[]= $line['woopid'];

				}
			}
			if($existingProductIds){
				foreach($existingProductIds as $exPid){
					 if(!in_array($exPid,$newProductsList)){
							wp_delete_post( $exPid, true );
			        $wpdb->query( $wpdb->prepare( "DELETE FROM $wpdb->postmeta WHERE post_id = %d", $exPid ) );
					 }
				}
				update_option('onyx_woo_products',$newProductsList);
			}
	}
	public function Sync_log_page(){
		$logFile = WP_PLUGIN_DIR. '/'.$this->plugin_name.'/synclog.txt';
		$currentlog = file_get_contents( $logFile);
		if(isset($_GET['do']) && $_GET['do']=='clearnow'){
      file_put_contents( $logFile,'' );
			wp_redirect("?page=onyx_admin_sync_log" );
      exit;
		}

    ?>
  <div class="wrap">
   <?php settings_errors(); ?>
  <h1> Sync Log</h1>
  <div class="description"><?php echo __("Sync Log for all operations over API");?></div>
   <div class="onyxsynclogcontainer">
		 <?php echo $currentlog; ?>
	 </div>
  <p> <a href="?page=onyx_admin_sync_log&do=clearnow" class="button button-primary button-large">Clear Log</a></p>
  <?php

	}

 	public function process_erp_sync_job(){
		$logFile = WP_PLUGIN_DIR. '/'.$this->plugin_name.'/synclog.txt';
		$currentlog = file_get_contents( $logFile);
		$onyx_term_sync = new Onyx_Admin_API_Terms_Sync( $this->plugin_name, $this->version);
		if(get_option('onyx_sync_categories')){
			 $erpTerms = $onyx_term_sync->get_sync_categories();
			 if($erpTerms!=null){
			 $onyx_term_sync->process_terms($erpTerms);
			 echo "Categories Synced successfully <br />";
		 }else{
			 echo "some error in categories sunc <br />";
		 }
		 }
		 if(get_option('onyx_sync_users')){
				 $onyx_api_sync = new Onyx_Admin_API_Sync( $this->plugin_name, $this->version);
				 $erpUserslog =  $onyx_api_sync->process_erp_users();
				 if(isset($erpUserslog['added'])){
						foreach($erpUserslog['added'] as $line){
							$newlog = date('d-m-Y h:i:s',$line['time'])." User with ERP CODE || ".$line['erpcode']." ||       Added as WP User ID  || ".$line['wpuserid']." ||";
							$currentlog = $newlog ."<br />". $currentlog;
						}
				 }
				 if(isset($erpUserslog['updated'])){
				 		foreach($erpUserslog['updated'] as $line){
							 $newlog = date('d-m-Y h:i:s',$line['time'])." User with ERP CODE || ".$line['erpcode']." ||         Updated for WP User ID || ".$line['wpuserid']." ||";
							 $currentlog = $newlog ."<br />". $currentlog;
						}
					}
					file_put_contents( $logFile,$currentlog );
			}
			if(get_option('onyx_sync_products')){
				$onyx_sync_products = new Onyx_Admin_API_Product_Sync( $this->plugin_name, $this->version);
				$erpPro =  $onyx_sync_products->get_erp_products();
				$syncLog = $onyx_sync_products->process_erp_products($erpPro);
				$this->remove_deleted_products($syncLog);
				if(isset($syncLog['added'])){
				foreach($syncLog['added'] as $line){
					$plog = date('d-m-Y h:i:s',$line['time'])." Product with ERP CODE || ".$line['erpcode']." ||       Added as Woo Product ID  || ".$line['woopid']." ||";
					$currentlog = $plog ."<br />". $currentlog;
				}
				}
				if(isset($syncLog['updated'])){
					foreach($syncLog['updated'] as $line){
						$plog = date('d-m-Y h:i:s',$line['time'])." Product with ERP CODE || ".$line['erpcode']." ||      Updated for Woo Product ID  || ".$line['woopid']." ||";
						$currentlog = $plog ."<br />". $currentlog;
					}
				}
				file_put_contents( $logFile,$currentlog );
			}
			if(get_option('onyx_sync_pending_orders')){
				$args = array(
							'status' => 'pendingerpposting',
					);
				$pendingOrders = wc_get_orders( $args );
				$onyx_sync_Orders = new Onyx_Admin_API_Orders_Sync( $this->plugin_name, $this->version);
				foreach($pendingOrders as $order){
					$onyx_sync_Orders->onyx_post_order_data_to_erp($order->get_id());
				}
			}
			if(get_option('onyx_sync_orders')){
				$onyx_sync_Orders = new Onyx_Admin_API_Orders_Sync( $this->plugin_name, $this->version);
				$onyx_sync_Orders->get_onyx_erp_woo_orders();
			}
			file_put_contents( $logFile,$currentlog );
		//	$cronSchweekDay	= get_option('onyx_sync_weekdays');
	//		$selectedWeekDay = strtotime("next ".$cronSchweekDay);
	//		wp_schedule_event( $selectedWeekDay, 'onyxSchedule', 'onyx_erp_schedule_sync' );


 		// echo  wp_next_scheduled( 'onyx_erp_schedule_sync');
 	}
 	public function onyx_sync_intervals($schedules){
		if(!isset($schedules["onyxSchedule"])){
		    $cronSchedule	= get_option('onyx_sync_every');
				$cronSchDayis='';
				if($cronSchedule ==168){
				   $cronSchDayis	= ' ('.get_option('onyx_sync_weekdays').')';
			   }elseif($cronSchedule ==720){
					 $cronSchDayis	= ' ('.get_option('onyx_sync_monthdays').'th)';
				 }
			  $syncFields = $this->sync_settings_fields();
			  $syncOpt = 	$syncFields[0]['options'][$cronSchedule];
				//$cronSchedule	= $selectedWeekDay;
			  $cronSchedule	=  $cronSchedule * 60 * 60;
			  $schedules['onyxSchedule'] = array(
				 'interval' => $cronSchedule ,
				 'display' => __($syncOpt.$cronSchDayis)
			  );
		}
	  return $schedules;
 	}
}
