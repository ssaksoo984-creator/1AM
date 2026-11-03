<?php
/**
 * "Order shipped" 고객 메일: 발송되었습니다 · 아래 운송장 번호로 배송을 확인해 주세요
 * (WooCommerce → Settings → Emails 에서 켜고 끄기 · 문구 수정 가능)
 *
 * @package 1am-vivid
 */

defined( 'ABSPATH' ) || exit;

class OneAM_Email_Shipped extends WC_Email {

	public function __construct() {
		$this->id             = 'oneam_customer_shipped_order';
		$this->customer_email = true;
		$this->title          = 'Order shipped';
		$this->description    = 'Sent to the customer when an order is marked as shipped, with the carrier and tracking number.';
		$this->placeholders   = array(
			'{order_number}' => '',
			'{order_date}'   => '',
		);
		add_action( 'woocommerce_order_status_shipped_notification', array( $this, 'trigger' ), 10, 2 );
		add_action( 'oneam_send_shipped_email', array( $this, 'trigger' ), 10, 2 );
		parent::__construct();
	}

	public function get_default_subject() {
		return 'Your 1AM order #{order_number} has shipped';
	}

	public function get_default_heading() {
		return 'Your order has shipped';
	}

	public function get_default_additional_content() {
		return 'Please check the delivery status with the tracking number above. Thank you for ordering with 1AM.';
	}

	public function trigger( $order_id, $order = false ) {
		$this->setup_locale();
		if ( $order_id && ! is_a( $order, 'WC_Order' ) ) {
			$order = wc_get_order( $order_id );
		}
		if ( is_a( $order, 'WC_Order' ) ) {
			$this->object                         = $order;
			$this->recipient                      = $order->get_billing_email();
			$this->placeholders['{order_number}'] = $order->get_order_number();
			$this->placeholders['{order_date}']   = wc_format_datetime( $order->get_date_created() );
		}
		if ( $this->is_enabled() && $this->get_recipient() ) {
			$this->send( $this->get_recipient(), $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments() );
		}
		$this->restore_locale();
	}

	public function get_content_html() {
		ob_start();
		do_action( 'woocommerce_email_header', $this->get_heading(), $this );
		echo '<p>Hi ' . esc_html( $this->object->get_billing_first_name() ?: $this->object->get_billing_company() ) . ',</p>';
		echo '<p>Good news — your order #' . esc_html( $this->object->get_order_number() ) . ' has shipped. Please check the delivery status with the tracking number below.</p>';
		do_action( 'woocommerce_email_order_details', $this->object, false, false, $this );
		do_action( 'woocommerce_email_customer_details', $this->object, false, false, $this );
		if ( $this->get_additional_content() ) {
			echo wp_kses_post( wpautop( wptexturize( $this->get_additional_content() ) ) );
		}
		do_action( 'woocommerce_email_footer', $this );
		return ob_get_clean();
	}

	public function get_content_plain() {
		$t   = oneam_order_tracking( $this->object );
		$out = $this->get_heading() . "\n\n";
		$out .= 'Good news — your order #' . $this->object->get_order_number() . " has shipped. Please check the delivery status with the tracking number below.\n\n";
		if ( $t ) {
			$out .= $t['carrier'] . ': ' . $t['number'] . "\n" . ( $t['url'] ? $t['url'] . "\n" : '' ) . "\n";
		}
		$out .= $this->get_additional_content();
		return $out;
	}
}
