<?php
/**
 * Class WC_Gateway_BPD_VA_QRIS
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
/**
 * BPD VA QRIS
 *
 * @class WC_Gateway_BPD_VA_QRIS
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
		$this->method_description = 'Pembayaran dengan VA QRIS';

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
			$this->api_soap_endpoint = 'http://36.75.213.124:7070/ws_bpd_payment/interkoneksi/v1/ws_interkoneksi.php?wsdl';
			$this->api_qris_endpoint = 'http://36.75.213.124:7070/merchant-admin/rest/openapi/generateQrisPost';
			$this->api_qris_status   = 'http://36.75.213.124:7070/merchant-admin/rest/openapi/getTrxByQrString';
		} else {
			$this->username          = $this->get_option( 'username_production' );
			$this->password          = $this->get_option( 'password_production' );
			$this->merchant_id       = $this->get_option( 'merchant_id_production' );
			$this->merchant_key      = $this->get_option( 'merchant_key_production' );
			$this->api_soap_endpoint = 'http://36.75.213.124:7070/ws_bpd_payment/interkoneksi/v1/ws_interkoneksi.php?wsdl';
			$this->api_qris_endpoint = 'http://36.75.213.124:7070/merchant-admin/rest/openapi/generateQrisPost';
			$this->api_qris_status   = 'http://36.75.213.124:7070/merchant-admin/rest/openapi/getTrxByQrString';
		}

		// Actions.
		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
		add_action( 'admin_print_scripts-woocommerce_page_wc-settings', array( &$this, 'bpd_admin_scripts' ) );
		add_action( 'woocommerce_thankyou_' . $this->id, array( $this, 'thankyou_page' ) );
		// Callback.
		add_action( 'woocommerce_api_' . strtolower( get_class( $this ) ), array( $this, 'callback_handler' ) );

		add_action( 'woocommerce_admin_order_item_headers', array( $this, 'qris_on_admin' ) );
		add_action( 'woocommerce_order_details_after_order_table', array( $this, 'show_qris_my_account' ) );

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
			add_post_meta( $order_id, '_record_id', $response->data[0]->recordId, true );
			// generate qris here
			$qrises = $this->generate_qris( $order_id );
			$logger->log( 'response generate qris', wc_print_r( $qrises, true ) );
			$n_qris = 1;
			if ( sizeof( $qrises ) == 1 ) {
				add_post_meta( $order_id, '_nmid' . $n_qris, $qrises->nmid, true );
				add_post_meta( $order_id, '_merchant_name' . $n_qris, $qrises->merchantName, true );
				add_post_meta( $order_id, '_qris_string' . $n_qris, $qrises->qrValue, true );
				add_post_meta( $order_id, '_qris_expired' . $n_qris, $qrises->expiredDate, true );
				add_post_meta( $order_id, '_amount' . $n_qris, $qrises->amount, true );
				add_post_meta( $order_id, '_total_amount' . $n_qris, $qrises->totalAmount, true );
				add_post_meta( $order_id, '_n_qris', $n_qris, true );
			} else {
				foreach ( $qrises as $qris ) {
					add_post_meta( $order_id, '_nmid' . $n_qris, $qris->nmid, true );
					add_post_meta( $order_id, '_merchant_name' . $n_qris, $qris->merchantName, true );
					add_post_meta( $order_id, '_qris_string' . $n_qris, $qris->qrValue, true );
					add_post_meta( $order_id, '_qris_expired' . $n_qris, $qris->expiredDate, true );
					add_post_meta( $order_id, '_amount' . $n_qris, $qris->amount, true );
					add_post_meta( $order_id, '_total_amount' . $n_qris, $qris->totalAmount, true );
					$n_qris++;
				}
				add_post_meta( $order_id, '_n_qris', $n_qris - 1, true );

			}
			add_post_meta( $order_id, '_check_payment', '0', true );

			// Update order status.
			$order->update_status( 'on-hold', 'Awaiting payment via ' . $this->method_title );
			// Update order note with payment code.
			// $order->add_order_note( 'Your ' . $this->method_title . ' code is <b>' . $atm . ', ' . $kliring . '. ' . $rtgs . '</b>' );

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
	 * Generate QRIS
	 */
	public function generate_qris( $order_id ) {
		$variables = $this->merchant_id . $this->terminal . $this->instansi . $this->makefive( $order_id ) . $this->merchant_key;
		$hashing   = hash( 'sha256', $variables );

		$data   = array(
			'merchantPan'  => $this->merchant_id,
			'terminalUser' => $this->terminal,
			'productCode'  => $this->instansi,
			'hashcodeKey'  => $hashing,
			'billNumber'   => $this->makefive( $order_id ),
			'recordId'     => get_post_meta( $order_id, '_record_id', true ),
		);
		$data   = wp_json_encode( $data );
		$logger = wc_get_logger();
		$logger->log( 'vars', $variables );
		$logger->log( 'DATA TO GENERATE', wc_print_r( $data, true ) );

		$options = array(
			'body'        => $data,
			'headers'     => array(
				'Content-Type' => 'application/json',
			),
			'data_format' => 'body',
		);

		$response = wp_remote_post( $this->api_qris_endpoint, $options );
		$response = wp_remote_retrieve_body( $response );

		$data = json_decode( $response );
		$logger->log( 'QRIS RESPONSE', wc_print_r( $data, true ) );
		return $data;

	}

	/**
	 * Output for the order received page.
	 *
	 * @param int $order_id.
	 */
	public function thankyou_page( $order_id ) {

		echo '<div style="text-align:center">';
		if ( $this->instructions ) {
			echo wp_kses_post( wpautop( wptexturize( wp_kses_post( $this->instructions ) ) ) );
		}
		$this->show_qris( $order_id );
		echo '</div>';

	}

	/**
	 * Show QR Code
	 *
	 * @param int @order_id
	 * @return string
	 */
	public function show_qris( $order_id ) {
		global $woocommerce;
		$order  = new WC_Order( $order_id );
		$n_qris = get_post_meta( $order_id, '_n_qris', true );
		echo '<div style="display:flex;align-items: center;justify-content: center;text-align:center;margin-bottom:50px">';
		for ( $i = 1;$i <= $n_qris;$i++ ) {
			$merchant_name = get_post_meta( $order_id, '_merchant_name' . $i, true );
			$nmid          = get_post_meta( $order_id, '_nmid' . $i, true );
			$qris_string   = get_post_meta( $order_id, '_qris_string' . $i, true );
			$qris_expired  = get_post_meta( $order_id, '_qris_expired' . $i, true );
			$amount        = get_post_meta( $order_id, '_amount' . $i, true );
			$total_amount  = get_post_meta( $order_id, '_total_amount' . $i, true );
			$color         = 'green';
			$status_text   = 'Sudah Terbayar';
			$status        = $this->va_status( $order_id );
			if ( '0' === $status ) {
				$color       = 'red';
				$status_text = 'Belum Terbayar';
			}
			?>
			<div style="margin-right: 20px">
				<strong><?php echo $merchant_name; ?></strong><br/>
				<strong><?php echo $nmid; ?></strong><br/>
				<strong><?php echo $this->terminal; ?></strong><br/>
				<img src="https://chart.googleapis.com/chart?cht=qr&chs=250x250&chl=<?php echo $qris_string; ?>" style="margin:0 auto" /><br/>
				<?php echo wc_price( $amount ) . ' dari ' . wc_price( $total_amount ); ?><br />
				Expired: <?php echo $qris_expired; ?><br />
			</div>
			<?php

		}
		?>
	</div>
	Status: <span id="qris-status" style="padding:2px;background-color:<?php echo $color; ?>;color:white"><?php echo $status_text; ?></span>
				<div class="check-status-qris" style="margin-top:10px">
					<button id="check-qris-status" onClick="history.go(0);">Check Status</button>
				</div>
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
	 * GET QRIS Status
	 *
	 * @param int $order_id
	 * @return string
	 */
	// public function qris_status( $qris_string, $order_id ) {
	// $variables = $this->merchant_id . $this->terminal . $qris_string . $this->merchant_key;
	// $hashing   = hash( 'sha256', $variables );

	// $data   = array(
	// 'merchantPan'  => $this->merchant_id,
	// 'terminalUser' => $this->terminal,
	// 'qrValue'      => $qris_string,
	// 'hashcodeKey'  => $hashing,
	// );
	// $logger = wc_get_logger();
	// $logger->log( 'DATA TO GENERATE', wc_print_r( $data, true ) );
	// $data = wp_json_encode( $data );

	// $options  = array(
	// 'body'        => $data,
	// 'headers'     => array(
	// 'Content-Type' => 'application/json',
	// ),
	// 'data_format' => 'body',
	// );
	// $response = wp_remote_post( $this->api_qris_status, $options );
	// $response = wp_remote_retrieve_body( $response );

	// $data = json_decode( $response );
	// $logger->log( 'QRIS RESPONSE', wc_print_r( $data, true ) );
	// if ( 'Sudah Terbayar' === $data->status ) {
	// $this->complete_order( $order_id );
	// }
	// return $data->status;

	// }

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
	 * Put QRIS in order detail
	 */
	public function qris_on_admin() {
		$order_id = $_GET['post'];
		$method   = get_post_meta( $order_id, '_payment_method', true );
		if ( 'bpd-va-qris' === $method ) {
			echo '<br />';
			$this->show_qris( $order_id );
		}
	}

	public function show_qris_my_account( $order_id ) {
		$method = get_post_meta( $order_id, '_payment_method', true );
		if ( 'bpd-va-qris' === $method ) {
			$this->show_qris( $order_id );
		}
	}

	public function bulk_check() {
		$args       = array(
			'status' => 'on-hold',
			'return' => 'ids',
		);
			$orders = wc_get_orders( $args );
		foreach ( $orders as $order_id ) {
			$this->va_status( $order_id );

		}

	}
}
