<?php
/*
 * Plugin Name: WooCommerce Name Your Price - Loop Input
 * Plugin URI: http://www.woocommerce.com/products/name-your-price/
 * Description: Add Name Your Price inputs to shop loop
 * Version: 1.0.0
 * Author: Kathy Darling
 * Author URI: http://kathyisawesome.com
 * Requires at least: 5.0.0
 * Tested up to: 5.4.2
 * WC requires at least: 4.0.0    
 * WC tested up to: 4.3.0   
 *
 *
 * Copyright: © 2019 Kathy Darling.
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 *
 */

defined( 'ABSPATH' ) || exit;

/**
 * Initialized hooks/filters for NYP in loop.
 */
function kia_nyp_loop_init() {
	add_filter( 'post_class', 'kia_nyp_loop_add_post_class', 99, 3 );
	add_filter( 'woocommerce_product_supports', 'kia_nyp_loop_supports_ajax_add_to_cart', 99, 3 );
	add_action( 'woocommerce_after_shop_loop_item', 'kia_nyp_loop_display', 8 );
}
add_action( 'wc_nyp_loaded' , 'kia_nyp_loop_init' );


/**
 * Add nyp to post class.
 *
 * @param  array  $classes - post classes
 * @param  string $class
 * @param  int    $post_id
 * @return array
 */
function kia_nyp_loop_add_post_class( $classes, $class = '', $post_id = '' ) {
	if ( ! $post_id || get_post_type( $post_id ) !== 'product' ) {
		return $classes;
	}

	if ( WC_Name_Your_Price_Helpers::is_nyp( $post_id ) || WC_Name_Your_Price_Helpers::has_nyp( $post_id ) ) {
		$classes[] = 'cart';
	}

	return $classes;

}


/**
 * Disable ajax add to cart and redirect to product page.
 * Supported by WC2.5+
 *
 * @param string $url
 * @return string
 */
function kia_nyp_loop_supports_ajax_add_to_cart( $supports_ajax, $feature, $product ) {

	if ( 'ajax_add_to_cart' === $feature && WC_Name_Your_Price_Helpers::is_nyp( $product ) ) {
		$supports_ajax = true;
	}

	return $supports_ajax;

}


/**
 * Display an NYP input in the loop
 * 
 * @return  void
 */
function kia_nyp_loop_display() {
	global $product;
	if( WC_Name_Your_Price_Helpers::is_nyp( $product ) ) {
		echo WC_Name_Your_Price()->display->display_price_input();

		remove_filter( 'woocommerce_product_add_to_cart_text', array( WC_Name_Your_Price()->display, 'add_to_cart_text' ), 10, 2 );
		remove_filter( 'woocommerce_product_add_to_cart_url', array( WC_Name_Your_Price()->display, 'add_to_cart_url' ), 10, 2 );
		remove_filter( 'woocommerce_product_supports', array( WC_Name_Your_Price()->display, 'supports_ajax_add_to_cart' ), 10, 3 );
		add_action( 'wp_print_footer_scripts', 'kia_nyp_loop_display_scripts', 20 );
	}
}


/**
 * Patch for ajax handling in loop.
 * 
 * @return  void
 */
function kia_nyp_loop_display_scripts() { ?>
	<script type="text/javascript">
		jQuery( document ).ready(function($) {

			/**
			 * Validate on ajax add to cart.
			 */
			$( document.body ).on( 'should_send_ajax_request.adding_to_cart', function(e, $button ) {

				var nypForm = $button.closest('.nyp-product').wc_nyp_get_script_object();

				if ( nypForm && ! nypForm.isValid() ) {
					return false
				}

				return true;
			});

			// Initialize NYP scripts in the loop.
			$( 'body' ).find( '.nyp-product' ).each( function() {
				$( this ).wc_nyp_form();

				// Patch NYP value to loop add to cart link.
				$( this ).on( 'wc-nyp-updated', function( e, nypProduct ) {
					$( this ).find('.ajax_add_to_cart').data( $( this ).find( '.nyp-input' ).attr('name'), nypProduct.user_price );
				});

			} );

		} );
	</script>
	<?php 
}