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
	include 'class/class-wc-gateway-bpd-va.php';
	include 'class/class-wc-gateway-bpd-qris.php';
	include 'class/class-wc-gateway-bpd-va-qris.php';
}
add_action( 'plugins_loaded', 'bpd_gateway_init' );

/**
 * Add method to WooCommerce
 */
function add_bpd_payment_gateway( $methods ) {
	$methods[] = 'WC_Gateway_BPD_VA';
	$methods[] = 'WC_Gateway_BPD_QRIS';
	$methods[] = 'WC_Gateway_BPD_VA_QRIS';
	return $methods;
}
add_filter( 'woocommerce_payment_gateways', 'add_bpd_payment_gateway' );

add_action( 'admin_menu', 'bpd_menu' );
function bpd_menu() {
	add_menu_page( 'BPD Payment', 'BPD Payment', 'administrator', 'bpd-payment', 'bpd_menu_callback' );
}

function bpd_menu_callback() {
	if ( isset( $_POST['va_bulk'] ) ) {
		$pg = new WC_Gateway_BPD_VA();
		$pg->bulk_check();
		echo '<p>Bulk check VA selesai.</p>';

	}
	if ( isset( $_POST['va_qris_bulk'] ) ) {
		$pg = new WC_Gateway_BPD_VA();
		$pg->bulk_check();
		echo '<p>Bulk check VA selesai.</p>';

	}
	if ( isset( $_POST['qris_bulk'] ) ) {
		$pg = new WC_Gateway_BPD_VA();
		$pg->bulk_check();
		echo '<p>Bulk check VA selesai.</p>';
	}
	echo '<div class="wrap"><h1 class="">BPD payment bulk checking</h1>';
	$payment_gateways = new WC_Payment_Gateways();
	$gateways         = $payment_gateways->get_available_payment_gateways();
	$enabled_gateways = array();
	foreach ( $gateways as $gateway ) {
		if ( $gateway->enabled == 'yes' ) {
			if ( 'bpd-va' === $gateway->id ) {
				?>
				<form name="va_bulk" action="?page=bpd-payment" method="POST" style="margin-bottom: 20px;">
					<input type="submit" value="VA BUlk Check" name="va_bulk" />
				</form>
				<?php
			}
			if ( 'bpd-va-qris' === $gateway->id ) {
				?>
				<form name="va_qris_bulk" action="?page=bpd-payment" method="POST" style="margin-bottom: 20px;">
					<input type="submit" value="VA QRIS Bulk Check" name="va_qris_bulk" />
				</form>
				<?php
			}
			if ( 'bpd-qris' === $gateway->id ) {
				?>
				<form name="qris_bulk" action="?page=bpd-payment" method="POST" style="margin-bottom: 20px;">
					<input type="submit" value="QRIS Bulk Check" name="qris_bulk" />
				</form>
				<?php
			}
		}
	}
	echo '</div>';
}
