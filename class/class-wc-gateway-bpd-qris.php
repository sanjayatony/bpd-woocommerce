<?php
/**
 * Class WC_Gateway_BPD_QRIS
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
/**
 * BPD QRIS
 *
 * @class WC_Gateway_BPD_QRIS
 * @extends WC_Payment_Gateway
 */
class WC_Gateway_BPD_QRIS extends WC_Payment_gateway {

	/**
	 * Constructor for the gateway
	 */
	public function __construct() {
		$this->id                 = 'bpd-qris';
		$this->icon               = '';
		$this->has_fields         = false;
		$this->method_title       = 'BPD QRIS';
		$this->method_description = 'Pembayaran dengan QRIS';

		// Load the settings.
		$this->init_form_fields();
		$this->init_settings();

		// Define user set variables.
		$this->title         = $this->get_option( 'title' );
		$this->description   = $this->get_option( 'description' );
		$this->instructions  = $this->get_option( 'instructions' );
		$this->environment   = $this->get_option( 'environment' );
		$this->merchant_code = $this->get_option( 'merchant_code' );
		if ( 'sandbox' === $this->environment ) {
			$this->merchant_id  = $this->get_option( 'merchant_id_sandbox' );
			$this->merchant_key = $this->get_option( 'merchant_key_sandbox' );
			$this->api_endpoint = 'http://36.75.213.124:7070/merchant-admin/rest/openapi/generateQrisPost';
		} else {
			$this->merchant_id  = $this->get_option( 'merchant_id_production' );
			$this->merchant_key = $this->get_option( 'merchant_key_production' );
			$this->api_endpoint = '';
		}

		// Actions.
		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
		add_action( 'admin_print_scripts-woocommerce_page_wc-settings', array( &$this, 'bpd_admin_scripts' ) );

		// Customer emails.
	}

	/**
	 * Initialise Gateway Settings Form Fields.
	 */
	public function init_form_fields() {

		$this->form_fields = array(
			'enabled'                 => array(
				'title'   => __( 'Enabled/Disable', 'woocommerce' ),
				'type'    => 'checkbox',
				'label'   => 'Enable BPD QRIS',
				'default' => 'no',
			),
			'title'                   => array(
				'title'       => __( 'Title', 'woocommerce' ),
				'type'        => 'text',
				'description' => __( 'This controls the title which the user sees during checkout.', 'woocommerce' ),
				'default'     => 'BPD QRIS',
				'desc_tip'    => true,
			),
			'description'             => array(
				'title'       => __( 'Description', 'woocommerce' ),
				'type'        => 'textarea',
				'description' => __( 'This controls the description which the user sees during checkout', 'woocommerce' ),
				'default'     => '',
				'desc_tip'    => true,
			),
			'instructions'            => array(
				'title'       => __( 'Instructions', 'woocommerce' ),
				'type'        => 'textarea',
				'description' => __( 'This controls the instruction which the user sees in thankyou page', 'woocommerce' ),
				'default'     => __( 'Silahkan bayar dengan QRIS ini.' ),
				'desc_tip'    => true,
			),
			'environment'             => array(
				'title'       => __( 'Environment', 'woocommerce' ),
				'type'        => 'select',
				'default'     => 'sandbox',
				'description' => __( 'Select the Environment', 'woocommerce' ),
				'options'     => array(
					'sandbox'    => __( 'Sandbox', 'woocommerce' ),
					'production' => __( 'Production', 'woocommerce' ),
				),
				'class'       => 'bpd_environment',
			),
			'merchant_id_sandbox'     => array(
				'title'       => __( 'Merchant ID', 'woocommerce' ),
				'type'        => 'text',
				'description' => __( 'Enter your <b>Sandbox</b> BPD Merchant ID.', 'woocommerce' ),
				'default'     => '',
				'class'       => 'sandbox_settings sensitive',
			),
			'merchant_key_sandbox'    => array(
				'title'       => __( 'Merchant Key', 'woocommerce' ),
				'type'        => 'text',
				'description' => __( 'Enter your <b>Sandbox</b> BPD Authentification key', 'woocommerce' ),
				'default'     => '',
				'class'       => 'sandbox_settings sensitive',
			),
			'merchant_id_production'  => array(
				'title'       => __( 'Merchant ID', 'woocommerce' ),
				'type'        => 'text',
				'description' => __( 'Enter your <b>Production</b> BPD Merchant ID.', 'woocommerce' ),
				'default'     => '',
				'class'       => 'production_settings sensitive',
			),
			'merchant_key_production' => array(
				'title'       => __( 'Merchant Key', 'woocommerce' ),
				'type'        => 'text',
				'description' => __( 'Enter your <b>Production</b> BPD Authentification key', 'woocommerce' ),
				'default'     => '',
				'class'       => 'production_settings sensitive',
			),
		);

	}

	/**
	 * Add JS to admin page
	 */
	public function bpd_admin_scripts() {
		wp_enqueue_script( 'bpd-admin-js', plugin_dir_url( __FILE__ ) . '../js/admin.js', array( 'jquery' ), '0.1', true );
	}


}
