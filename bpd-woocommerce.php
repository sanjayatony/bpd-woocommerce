<?php
/**
 * Plugin Name: BPD - WooCommerce Payment gateway
 * Version: 0.5
 * Author: BPD IT Team
 * Written based on https://docs.woocommerce.com/document/payment-gateway-api/
 */

/**
 * Init.
 */
function bpd_gateway_init() {
	include 'class/class-wc-gateway-bpd-va-qris.php';
}
add_action( 'plugins_loaded', 'bpd_gateway_init' );

/**
 * Add method to WooCommerce
 */
function add_bpd_qris_payment_gateway( $methods ) {
	$methods[] = 'WC_Gateway_Bpd_Qris';
	return $methods;
}
add_filter( 'woocommerce_payment_gateways', 'add_bpd_qris_payment_gateway' );
