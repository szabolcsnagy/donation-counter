<?php
/*
Plugin Name: Donation counter for Woocommerce
Version: 1.0.1
Description: Donation counter for Woocommerce
Author: OnlineVagyok
Author URI: https://onlinevagyok.hu
*/

if ( ! defined( 'ABSPATH' ) ) {
	header( 'Status: 403 Forbidden' );
	header( 'HTTP/1.1 403 Forbidden' );
	exit;
}

add_action( 'woocommerce_product_options_general_product_data', 'dcfwc_woo_custom_fields' );
/**
* Add a select Field at the bottom
*/
function dcfwc_woo_custom_fields() {
 
  woocommerce_wp_text_input( array(
    'id' => 'dcfwc_woo_custom_fields',
    'label' => __( 'Adomány:', 'textdomain' ),

  ));
}


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
		echo '<span class="donate_value">Adományra szánt összeg: ' . esc_attr($donate_value, 'dcfwc_woo_custom_fields') . ' Ft</span>';
	}
	
}

add_action( 'woocommerce_cart_contents', 'dcfwc_woocommerce_cart_contents' );
 
function dcfwc_woocommerce_cart_contents(){

  global $woocommerce;
  $items = $woocommerce->cart->get_cart();

      foreach($items as $item => $values) { 
          $_product =  wc_get_product( $values['data']->get_id()); 
          $donate = get_post_meta($values['product_id'] , 'dcfwc_woo_custom_fields', true);
          if ( $donate ){
              echo "<b>".$_product->get_title().'</b>  <br> Quantity: '.$values['quantity'].'<br>'; 
              echo "  Price: ".$donate."<br>";
          }
          
      } 

}