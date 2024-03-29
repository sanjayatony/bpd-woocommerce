<?php
/**
 * Class WC_Gateway_BPD_VA
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
/**
 * BPD VA
 *
 * @class WC_Gateway_BPD_VA
 * @extends WC_Payment_Gateway
 */
class WC_Gateway_BPD_VA extends WC_Payment_gateway {

	/**
	 * Constructor for the gateway
	 */
	public function __construct() {
		$this->id                 = 'bpd-va';
		$this->icon               = '';
		$this->has_fields         = false;
		$this->method_title       = 'BPD VA';
		$this->method_description = 'Pembayaran dengan VA';

		// Load the settings.
		$this->init_form_fields();
		$this->init_settings();

		// Define user set variables.
		$this->title           = $this->get_option( 'title' );
		$this->description     = $this->get_option( 'description' );
		$this->instructions    = $this->get_option( 'instructions' );
		$this->environment     = $this->get_option( 'environment' );
		$this->instansi        = $this->get_option( 'instansi' );
		$this->prefix_va       = $this->get_option( 'prefix_va' );
		$this->kode_bank       = $this->get_option( 'kode_bank' );
		$this->jenis_transaksi = $this->get_option( 'jenis_transaksi' );
		$this->keterangan      = $this->get_option( 'keterangan' );
		if ( 'sandbox' === $this->environment ) {
			$this->username          = $this->get_option( 'username_sandbox' );
			$this->password          = $this->get_option( 'password_sandbox' );
			$this->api_soap_endpoint = 'http://36.75.213.124:7070/ws_bpd_payment/interkoneksi/v1/ws_interkoneksi.php?wsdl';
		} else {
			$this->username          = $this->get_option( 'username_production' );
			$this->password          = $this->get_option( 'password_production' );
			$this->api_soap_endpoint = 'http://36.75.213.124:7070/ws_bpd_payment/interkoneksi/v1/ws_interkoneksi.php?wsdl';
		}

		// Actions.
		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
		add_action( 'admin_print_scripts-woocommerce_page_wc-settings', array( &$this, 'bpd_admin_scripts' ) );
		add_action( 'wp_enqueue_scripts', array( &$this, 'bpd_scripts' ) );
		add_action( 'woocommerce_thankyou_' . $this->id, array( $this, 'thankyou_page' ) );
		// Callback.
		add_action( 'woocommerce_api_' . strtolower( get_class( $this ) ), array( $this, 'callback_handler' ) );
		add_action( 'woocommerce_order_details_after_order_table', array( $this, 'show_va_my_account' ) );

		// Button
		// add_action( 'admin_notices', array( $this, 'add_va_check_bulk_button' ), 1 );
		// add_action( 'admin_menu', array( $this, 'register_my_bpd_menu' ) );

		// Customer emails.
	}

	/**
	 * Initialise Gateway Settings Form Fields.
	 */
	public function init_form_fields() {

		$this->form_fields = array(
			'enabled'             => array(
				'title'   => __( 'Enabled/Disable', 'woocommerce' ),
				'type'    => 'checkbox',
				'label'   => 'Enable BPD VA',
				'default' => 'no',
			),
			'title'               => array(
				'title'       => __( 'Title', 'woocommerce' ),
				'type'        => 'text',
				'description' => __( 'This controls the title which the user sees during checkout.', 'woocommerce' ),
				'default'     => 'BPD VA',
				'desc_tip'    => true,
			),
			'description'         => array(
				'title'       => __( 'Description', 'woocommerce' ),
				'type'        => 'textarea',
				'description' => __( 'This controls the description which the user sees during checkout', 'woocommerce' ),
				'default'     => '',
				'desc_tip'    => true,
			),
			'instructions'        => array(
				'title'       => __( 'Instructions', 'woocommerce' ),
				'type'        => 'textarea',
				'description' => __( 'This controls the instruction which the user sees in thankyou page', 'woocommerce' ),
				'default'     => __( 'Silahkan bayar dengan VA ini.' ),
				'desc_tip'    => true,
			),
			'instansi'            => array(
				'title'       => __( 'Instansi', 'woocommerce' ),
				'type'        => 'text',
				'description' => __( 'Enter you Instansi code.', 'woocommerce' ),
				'default'     => '',
			),
			'prefix_va'           => array(
				'title'       => __( 'Previx VA', 'woocommerce' ),
				'type'        => 'text',
				'description' => __( 'Enter your Prefix VA.', 'woocommerce' ),
				'default'     => '',
			),
			'kode_bank'           => array(
				'title'       => __( 'Kode Bank', 'woocommerce' ),
				'type'        => 'text',
				'description' => __( 'Enter your Kode Bank.', 'woocommerce' ),
				'default'     => '',
			),
			'jenis_transaksi'     => array(
				'title'   => __( 'Jenis Transaksi', 'woocommerce' ),
				'type'    => 'text',
				'default' => '',
			),
			'keterangan'          => array(
				'title'   => __( 'Keterangan', 'woocommerce' ),
				'type'    => 'text',
				'default' => '',
			),
			'environment'         => array(
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
			'username_sandbox'    => array(
				'title'       => __( 'Username', 'woocommerce' ),
				'type'        => 'text',
				'description' => __( 'Enter your <b>Sandbox</b> Username.', 'woocommerce' ),
				'default'     => '',
				'class'       => 'sandbox_settings sensitive',
			),
			'password_sandbox'    => array(
				'title'       => __( 'Password', 'woocommerce' ),
				'type'        => 'text',
				'description' => __( 'Enter your <b>Sandbox</b> Password.', 'woocommerce' ),
				'default'     => '',
				'class'       => 'sandbox_settings sensitive',
			),
			'username_production' => array(
				'title'       => __( 'Username', 'woocommerce' ),
				'type'        => 'text',
				'description' => __( 'Enter your <b>Production</b> Username.', 'woocommerce' ),
				'default'     => '',
				'class'       => 'production_settings sensitive',
			),
			'password_production' => array(
				'title'       => __( 'Password', 'woocommerce' ),
				'type'        => 'text',
				'description' => __( 'Enter your <b>Production</b> Username.', 'woocommerce' ),
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
	 * Add JS to front page
	 */
	public function bpd_scripts() {
		wp_enqueue_script( 'bpd-js', plugin_dir_url( __FILE__ ) . '../js/bpd.js', array( 'jquery' ), '0.1', true );
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
			$atm     = $this->prefix_va . '-' . $this->makefive( $order_id );
			$kliring = $this->kode_bank . '-' . $this->prefix_va . '-' . $this->makefive( $order_id );
			$rtgs    = $this->kode_bank . '-' . $this->prefix_va . '-' . $this->makefive( $order_id );
			$order->add_order_note( 'Your ' . $this->method_title . ' code is <b>' . $atm . ', ' . $kliring . ', ' . $rtgs . '</b>' );
			add_post_meta( $order_id, '_atm', $atm, true );
			add_post_meta( $order_id, '_kliring', $kliring, true );
			add_post_meta( $order_id, '_rtgs', $rtgs, true );

			add_post_meta( $order_id, '_check_payment', '0', true );

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
	 * @return json
	 */
	public function generate_request( $order_id ) {
		global $woocommerce;
		$order = new WC_Order( $order_id );

		$args   = array(
			'username'  => $this->username,
			'password'  => $this->password,
			'noid'      => $this->makefive( $order->get_order_number() ),
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
	 * Make order number min 5 digits
	 */
	public function makefive( $order_id ) {
		$no_digit = strlen( (string) $order_id );
		if ( $no_digit < 5 ) {
			$add_digit = 5 - $no_digit;
			$digit     = '';
			for ( $i = 1;$i <= $add_digit;$i++ ) {
				$digit .= '0';
			}
			return $digit . $order_id;
		} else {
			return $order_id;
		}

	}

	/**
	 * Output for the order received page.
	 *
	 * @param int $order_id.
	 */
	public function thankyou_page( $order_id ) {
		if ( $this->instructions ) {
			echo wp_kses_post( wpautop( wptexturize( wp_kses_post( $this->instructions ) ) ) );
		}
		$this->show_va( $order_id );

	}

	/**
	 * Show VA number
	 */
	public function show_va( $order_id ) {
		?>
	  <table style="border: 2px solid #641E16">
			<tr id="channelbankbali">
				<td  style="background:#F9EBEA">A. NO. VA :: CHANNEL BANK BPD BALI</td>
				<td style="background:#F9EBEA"><strong><?php echo $this->makefive( $order_id ); ?></strong></td>
			</tr>
			<tr id="atmbanklain">
				<td  style="background:#F9EBEA">B. NO. VA :: ATM BANK LAIN</td>
				<td style="background:#F9EBEA"><strong><?php echo get_post_meta( $order_id, '_atm', true ); ?></strong></td>
			</tr>
<tr id="ebankingbanklain">
	<td  style="background:#F9EBEA">C. NO. VA :: E-BANKING BANK LAIN</td>
	<td style="background:#F9EBEA"><strong><?php echo get_post_meta( $order_id, '_atm', true ); ?></strong></td>
  </tr>
	  <tr id="sknrtgs">
		<td  style="background:#F9EBEA">D. NO. VA :: SKNBI / KLIRING / RTGS</td>
		<td style="background:#F9EBEA"><strong><?php echo get_post_meta( $order_id, '_rtgs', true ); ?></strong></td>
	  </tr>

	  </table>
		<?php
	}

	/**
	 * GET VA Status
	 *
	 * @param int $order_id
	 * @return string
	 */
	public function va_status( $order_id ) {

		global $woocommerce;
		$order = new WC_Order( $order_id );

		$args = array(
			'username' => $this->username,
			'password' => $this->password,
			'instansi' => $this->instansi,
			'noid'     => $this->makefive( $order->get_order_number() ),
		);

		// Connect to WSDL.
		$client   = new SoapClient( $this->api_soap_endpoint );
		$response = $client->__soapCall( 'ws_inquiry_tagihan', $args );
		$response = json_decode( $response );
		if ( '1' === $response->data[0]->sts_bayar ) {
			$this->complete_order( $order_id );
		}
		return $response->data[0]->sts_bayar;

	}

	/**
	 * Callback Handler
	 */
	public function complete_order( $order_id ) {
		global $woocommerce;
		$check = get_post_meta( $order_id, '_check_payment', true );
		$order = new WC_ORDER( $order_id );
		if ( '0' === $check ) {
			$order->add_order_note( __( 'Your payment have been received', 'woocommerce' ) );
			$order->payment_complete();
			wc_reduce_stock_levels( $order_id );
			update_post_meta( $order_id, '_check_payment', '1' );
		}

	}

	/**
	 * Check status
	 */

	public function bulk_check() {
		$args   = array(
			'status' => 'on-hold',
			'return' => 'ids',
		);
		$orders = wc_get_orders( $args );
		foreach ( $orders as $order_id ) {
			$this->va_status( $order_id );

		}

	}

	/**
	 * add instrctions and payment code in email
	 */
	public function email_instructions( $order, $sent_to_admin, $plain_text = false ) {
		if ( $this->instructions && ! $sent_to_admin && $this->id === $order->payment_method && $order->has_status( 'on-hold' ) ) {
			echo '<div style="text-align:center">';
			echo esc_html( wpautop( wptexturize( $this->instructions ) ) );
			echo '<p>' . $this->show_va( $order->get_order_number() ) . '</p>';
			echo '</div>';
		}
	}

	public function show_va_my_account( $order_id ) {
		$order_id = $_GET['view-order'];
		$method   = get_post_meta( $order_id, '_payment_method', true );
		if ( 'bpd-va' === $method ) {
			$this->show_va( $order_id );
		}
	}
}
