<?php
/*
Plugin Name: Donation counter for Woocommerce
Version: 1.0.7
Description: Donation counter for Woocommerce
Author: OnlineVagyok
Author URI: https://onlinevagyok.hu
Text Domain: donation-counter
*/

if ( ! defined( 'ABSPATH' ) ) {
	header( 'Status: 403 Forbidden' );
	header( 'HTTP/1.1 403 Forbidden' );
	exit;
}

/**
 * Check if WooCommerce is active
 **/
if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {

  require_once(__DIR__.'/post-type.php');
  require_once(__DIR__.'/woocommerce-menu-addons.php');

  /**
   * Admin field to define the donation amount
   */
  add_action( 'woocommerce_product_options_general_product_data', 'dcfwc_woo_custom_fields' );
  function dcfwc_woo_custom_fields() {
    // Define the custom field for WooCommerce
    woocommerce_wp_text_input( array(
      'id' => 'dcfwc_woo_custom_fields',
      'label' => __( 'Adomány:', 'donation-counter' ),

    ));
  }

  /**
   * Save the donation that the admin defines for each product into 
   * the product's meta (postmeta)
   */
  add_action( 'woocommerce_process_product_meta', 'dcfwc_save_custom_field' );
  function dcfwc_save_custom_field( $post_id ) {
    // Tertiary operator
    // kérdés ? igaz : hamis
    $custom_field_value = isset( $_POST['dcfwc_woo_custom_fields'] ) ? $_POST['dcfwc_woo_custom_fields'] : '';

    update_post_meta($post_id, 'dcfwc_woo_custom_fields', $custom_field_value);

  }


  add_action( 'woocommerce_after_add_to_cart_button', 'dcfwc_after_add_to_cart_btn' );
  
  function dcfwc_after_add_to_cart_btn(){
    
    $productID = get_the_ID();
    $donate_value = get_post_meta($productID, 'dcfwc_woo_custom_fields', true);
    if ( $donate_value ){
      echo '<div class="donate_value">Adományra szánt összeg: ' . esc_attr($donate_value, 'dcfwc_woo_custom_fields') . ' Ft</div>';
    }
    
  }

  /**
   * Display the sum of donations in the totals section.
   */
  add_action('woocommerce_cart_totals_before_order_total','dcfwc_woocommerce_cart_contents');
  add_action('woocommerce_review_order_after_cart_contents','dcfwc_woocommerce_cart_contents');
  function dcfwc_woocommerce_cart_contents(){

    global $woocommerce;
    $items = $woocommerce->cart->get_cart();
    $all_donation = 0;
    foreach($items as $item => $values) { 
      $_product =  wc_get_product( $values['data']->get_id()); 
      $donate = get_post_meta($values['product_id'] , 'dcfwc_woo_custom_fields', true);
      if ( $donate ){
        $all_donation += ($values['quantity']*$donate);
      }  
    }

    
    render_donations($all_donation);
  }

  /**
   * Render donations line
   */
  function render_donations($all_donation) {
    $donationId = get_option('donation-counter-id');
    if($all_donation>0 && is_numeric($donationId)){
      echo '<tr class="donation-subtotal">';
			echo '<th>Összes Adomány</th>';
      echo '<td data-title="Összes Adomány"><span class="amount">';
      printf(get_woocommerce_price_format(),get_woocommerce_currency_symbol(),$all_donation);
      echo '</span></span></td></tr>';
    } 
  }

  /**
   * Get all donations from the order
   */
  function dcfwc_get_all_donation($order){
    $donationId = get_option('donation-counter-id');
    $all_donation = 0;
    if (is_numeric($donationId)) {
      
      $order_items = $order->get_items();
      foreach ( $order_items as $item_id => $item ) {
        $product_id = $item->get_product_id();
        $quantity = $item->get_quantity();
        $donate = get_post_meta($product_id , 'dcfwc_woo_custom_fields', true);
        $all_donation += $donate * $quantity;
      }
      
    }
    return $all_donation;
  }

  /**
   * Save donation amount to order meta
   */
  add_action('woocommerce_checkout_create_order','dcfwc_save_donation_amount');
  function dcfwc_save_donation_amount($order) {
    $donationId = get_option('donation-counter-id');
    $all_donation = dcfwc_get_all_donation($order);
    
    if(is_numeric($donationId) && $all_donation>0) {
      $donationKey = sprintf('_donation_amount_%d',$donationId);
      $order->update_meta_data( $donationKey, $all_donation );
    }
    
  }

  /** 
   * Show donations on order receipt
   */
  add_action('woocommerce_order_details_after_order_table_items','dcfwc_woocommerce_order_receipt');
  function dcfwc_woocommerce_order_receipt($order){
    $all_donation = dcfwc_get_all_donation($order);
    render_donations($all_donation);
  }

  add_shortcode('campaign_summa','dcfwc_render_campaign_summa');
  function dcfwc_render_campaign_summa() {
    $campaign_id = get_option('donation-counter-id');
    $campaign_name = get_option('donation-counter-name');
    if(is_numeric($campaign_id)) {
      $donationAmount = dcfwc_add_up_donation($campaign_id);
      $donationAmount = empty($donationAmount)?0:$donationAmount;
      $formattedSum = sprintf(get_woocommerce_price_format(),get_woocommerce_currency_symbol(),$donationAmount);
      printf('<div class="campaignSumma"><span class="label">%s</span>: <span class="sum">%s</span></div>',$campaign_name,$formattedSum);
    }
  }

}