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
class Onyx_Admin_Sync_Logs {

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
	private $logFile;
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
	}
	public function read_log_by_day($day){
		$this->logFile = WP_PLUGIN_DIR. '/'.$this->plugin_name.'/sync-'.$day.'.txt';
		if(file_exists($this->logFile)){
			return  file_get_contents( $this->logFile);
		}else{
			return  '';
		}
	}
	public function save_log($newLog){
		$logTxt =  $this->read_log_file();
		$this->write_logs($logTxt.$newLog);
		$this->set_log_options();
	}
	private function read_log_file(){
		$day =  date('D');
		$date = date('Y-m-d');
		$LogedDay  = get_option('log_day');
		$LogedDate = get_option('log_date');
		$this->logFile = WP_PLUGIN_DIR. '/'.$this->plugin_name.'/sync-'.$day.'.txt';
		if($LogedDay == $day && $LogedDate == $date){
			if(file_exists($this->logFile)){
				return  file_get_contents( $this->logFile);
			}else{

				$loGfile = fopen($this->logFile, "w");
				fclose($loGfile);
				return '';
			}
		}else{
			return '';
		}
	}
	private function write_logs($newLog ){
		file_put_contents($this->logFile, $newLog);
	}
	private function set_log_options(){
		$day =  date('D');
		$date = date('Y-m-d');
		update_option('log_day',$day);
		update_option('log_date',$date);
	}
}
