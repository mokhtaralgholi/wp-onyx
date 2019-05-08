<?php


class Onyx_wpml_Product_Sync
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
  private $Onyx_Admin_API_Product_Sync;
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
    $this->Onyx_Admin_API_Product_Sync = new Onyx_Admin_API_Product_Sync($this->plugin_name,$this->version);
    global $sitepress;
    $this->sitepress = $sitepress;
  }

  public function wmpl_sync_product ($product, $ref_product_id) {
    $post_type = 'product';
    $product_code = $product->Code;
    $second_lang = $this->ApisettingClass->get_second_language_code();
    $second_lang_wpml = $this->ApisettingClass->get_second_language();
    $opt=array(
      "service"=>"GetItemsOnlineList",
      "prams"=>'&groupCode=-1'.
        '&mainGroupCode=-1'.
        '&subGroupCode=-1'.
        '&assistantGroupCode=-1'.
        '&detailGroupCode=-1'.
        '&wareHouseCode=-1'.
        '&pageNumber=-1'.
        '&rowsCount=-1'.
        '&searchValue='.$product_code.
        '&orderBy=-1'.
        '&sortDirection=-1');
    $products = $this->ApiSyncClass->get_records($opt, $second_lang);
    $product_id = $this->Onyx_Admin_API_Product_Sync->add_product($products->MultipleObjectHeader[0]);

    $trid = $this->sitepress->get_element_trid( $ref_product_id, 'post_' . $post_type );
    $this->sitepress->set_element_language_details( $product_id, 'post_' . $post_type, $trid, $second_lang_wpml );
  }

  public function wmpl_update_product ($product, $product_id) {
    $product_code = $product->Code;
    $second_lang = $this->ApisettingClass->get_second_language_code();
    $opt=array(
      "service"=>"GetItemsOnlineList",
      "prams"=>'&groupCode=-1'.
        '&mainGroupCode=-1'.
        '&subGroupCode=-1'.
        '&assistantGroupCode=-1'.
        '&detailGroupCode=-1'.
        '&wareHouseCode=-1'.
        '&pageNumber=-1'.
        '&rowsCount=-1'.
        '&searchValue='.$product_code.
        '&orderBy=-1'.
        '&sortDirection=-1');
    $products = $this->ApiSyncClass->get_records($opt, $second_lang);
    $product_id = $this->Onyx_Admin_API_Product_Sync->update_product($products->MultipleObjectHeader[0],$product_id);

  }

}