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
class WC_Gateway_BPD_VA_QRIS extends WC_Payment_gateway {

	/**
	 * Constructor for the gateway
	 */
	public function __construct() {
		$this->id                 = 'bpd-va-qris';
		$this->icon               = '';
		$this->has_fields         = false;
		$this->method_title       = 'BPD VA QRIS';
		$this->method_description = 'Pembayaran dengan QRIS';

		// Load the settings.
		$this->init_form_fields();
		$this->init_settings();

		// Define user set variables.
		$this->title           = $this->get_option( 'title' );
		$this->description     = $this->get_option( 'description' );
		$this->instructions    = $this->get_option( 'instructions' );
		$this->environment     = $this->get_option( 'environment' );
		$this->instansi        = $this->get_option( 'instansi' );
		$this->terminal        = $this->get_option( 'terminal' );
		$this->jenis_transaksi = $this->get_option( 'jenis_transaksi' );
		$this->keterangan      = $this->get_option( 'keterangan' );
		if ( 'sandbox' === $this->environment ) {
			$this->username          = $this->get_option( 'username_sandbox' );
			$this->password          = $this->get_option( 'password_sandbox' );
			$this->merchant_id       = $this->get_option( 'merchant_id_sandbox' );
			$this->merchant_key      = $this->get_option( 'merchant_key_sandbox' );
			$this->api_qris_endpoint = 'http://36.75.213.124:7070/merchant-admin/rest/openapi/generateQrisPost';
			$this->api_soap_endpoint = 'http://36.75.213.124:7070/ws_bpd_payment/interkoneksi/v1/ws_interkoneksi.php?wsdl';
		} else {
			$this->username          = $this->get_option( 'username_production' );
			$this->password          = $this->get_option( 'password_production' );
			$this->merchant_id       = $this->get_option( 'merchant_id_production' );
			$this->merchant_key      = $this->get_option( 'merchant_key_production' );
			$this->api_qris_endpoint = 'http://36.75.213.124:7070/merchant-admin/rest/openapi/generateQrisPost';
			$this->api_soap_endpoint = 'http://36.75.213.124:7070/ws_bpd_payment/interkoneksi/v1/ws_interkoneksi.php?wsdl';
		}

		// Actions.
		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
		add_action( 'admin_print_scripts-woocommerce_page_wc-settings', array( &$this, 'bpd_admin_scripts' ) );
		add_action( 'woocommerce_thankyou_' . $this->id, array( $this, 'thankyou_page' ) );

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
				'label'   => 'Enable BPD VA QRIS',
				'default' => 'no',
			),
			'title'                   => array(
				'title'       => __( 'Title', 'woocommerce' ),
				'type'        => 'text',
				'description' => __( 'This controls the title which the user sees during checkout.', 'woocommerce' ),
				'default'     => 'BPD VA QRIS',
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
			'instansi'                => array(
				'title'       => __( 'Instansi', 'woocommerce' ),
				'type'        => 'text',
				'description' => __( 'Enter you Instansi code.', 'woocommerce' ),
				'default'     => '',
			),
			'terminal'                => array(
				'title'       => __( 'Terminal', 'woocommerce' ),
				'type'        => 'text',
				'description' => __( 'Enter your Terminal.', 'woocommerce' ),
				'default'     => '',
			),
			'jenis_transaksi'         => array(
				'title'   => __( 'Jenis Transaksi', 'woocommerce' ),
				'type'    => 'text',
				'default' => '',
			),
			'keterangan'              => array(
				'title'   => __( 'Keterangan', 'woocommerce' ),
				'type'    => 'text',
				'default' => '',
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
			'username_sandbox'        => array(
				'title'       => __( 'Username', 'woocommerce' ),
				'type'        => 'text',
				'description' => __( 'Enter your <b>Sandbox</b> Username.', 'woocommerce' ),
				'default'     => '',
				'class'       => 'sandbox_settings sensitive',
			),
			'password_sandbox'        => array(
				'title'       => __( 'Password', 'woocommerce' ),
				'type'        => 'text',
				'description' => __( 'Enter your <b>Sandbox</b> Password.', 'woocommerce' ),
				'default'     => '',
				'class'       => 'sandbox_settings sensitive',
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
			'username_production'     => array(
				'title'       => __( 'Username', 'woocommerce' ),
				'type'        => 'text',
				'description' => __( 'Enter your <b>Production</b> Username.', 'woocommerce' ),
				'default'     => '',
				'class'       => 'production_settings sensitive',
			),
			'password_production'     => array(
				'title'       => __( 'Password', 'woocommerce' ),
				'type'        => 'text',
				'description' => __( 'Enter your <b>Production</b> Username.', 'woocommerce' ),
				'default'     => '',
				'class'       => 'production_settings sensitive',
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

	/**
	 * Process the payment and return the result
	 *
	 * @param int $order_id
	 * @return array
	 */
	public function process_payment( $order_id ) {
		global $woocommerce, $wpdb;
		$order    = new WC_ORDER( $order_id );
		$response = $this->generate_request( $order_id );
		$logger   = wc_get_logger();
		$logger->log( 'response generate', wc_print_r( $response, true ) );

		if ( '00' === $response->code ) {
			// Update order status.
			$order->update_status( 'on-hold', 'Awaiting payment via ' . $this->method_title );
			// Update order note with payment code.
			$order->add_order_note( 'Your ' . $this->method_title . ' code is <b>' . $response->data[0]->recordId . '</b>' );
			// Save recordId in post meta.
			add_post_meta( $order_id, '_record_id', $response->data[0]->recordId, true );

			return array(
				'result'   => 'success',
				'redirect' => $this->get_return_url( $order ),
			);
		} else {
			wc_add_notice( $this->method_title . '  error:' . $response->status_code, 'error' );
			return;

		}

	}

	/**
	 * Generate request to the API
	 *
	 * @param int $order_id
	 */
	public function generate_request( $order_id ) {
		global $woocommerce;
		$order = new WC_Order( $order_id );

		$args   = array(
			'username'  => $this->username,
			'password'  => $this->password,
			'noid'      => $order->get_order_number(),
			'nama'      => $order->billing_first_name . ' ' . $order->billing_last_name,
			'tagihan'   => round( $order->get_total(), 0 ),
			'instansi'  => $this->instansi,
			'ket_1_val' => $order->get_billing_address_1(),
			'ket_2_val' => $this->jenis_transaksi,
			'ket_3_val' => $order->billing_phone,
			'ket_4_val' => $this->keterangan,
			'ket_5_val' => round( $order->get_total(), 0 ),
		);
		$logger = wc_get_logger();
		$logger->log( 'DATA INSERT', wc_print_r( $args, true ) );

		// Connect to WSDL.
		$client   = new SoapClient( $this->api_soap_endpoint );
		$response = $client->__soapCall( 'ws_tagihan_insert', $args );
		$logger->log( 'INSERT RESPONSE', wc_print_r( $response, true ) );
		return json_decode( $response );
	}

	/**
	 * Output for the order received page.
	 *
	 * @param int $order_id.
	 */
	public function thankyou_page( $order_id ) {
		$payment_code = get_post_meta( $order_id, '_record_id', true );
		echo '<div style="text-align:center">';
		if ( $this->instructions ) {
			echo wp_kses_post( wpautop( wptexturize( wp_kses_post( $this->instructions ) ) ) );
		}
		echo '<strong>' . esc_html( $payment_code ) . '</strong>';
		echo '</div>';

	}

	/**
	 * Generate QRIS
	 *
	 * @param int $order_id, $record_id
	 */
	public function generate_qris( $order_id ) {

	}
}
