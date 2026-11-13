<?php
/**
 * 카드 결제 수수료 (Card surcharge)
 *
 * Moneris 결제창(Moneris Checkout)에서 붙는 수수료는 사이트 주문에 기록되지 않고,
 * 플러그인이 주문 금액만 승인(capture)해서 실제로는 걷히지 않음.
 * → 수수료를 WooCommerce 주문에 "Card surcharge (2.4%)" 줄로 직접 넣어서
 *   결제 금액 · 인보이스 · 영수증 · 메일이 모두 같은 금액이 되게 함.
 *
 * 설정: 1AM Settings → Business & legal → Card surcharge (%) — 0 이면 끔
 * 대상: 결제 수단 ID 에 "moneris" 가 들어간 카드 결제 (필터 oneam_is_card_method)
 *
 * @package 1am-vivid
 */

defined( 'ABSPATH' ) || exit;

/** 수수료율 (%) */
function oneam_surcharge_pct() {
	$raw = str_replace( ',', '.', trim( (string) oneam_opt( 'oneam_card_surcharge' ) ) ); // "2,4" → 2.4
	if ( '' === $raw || false !== strpos( $raw, '-' ) || ! preg_match( '/\d+(?:\.\d+)?/', $raw, $m ) ) {
		return 0; // 비었거나 · 마이너스 · 숫자가 아니면 끔
	}
	return max( 0, min( 10, (float) $m[0] ) );
}

/** 이 결제 수단이 수수료 대상(카드)인지 */
function oneam_is_card_method( $id ) {
	$id = (string) $id;
	// 신용카드만 (Interac · 데빗 결제에는 수수료 금지)
	$card = '' !== $id && false !== stripos( $id, 'moneris' ) && false === stripos( $id, 'interac' ) && false === stripos( $id, 'debit' );
	return (bool) apply_filters( 'oneam_is_card_method', $card, $id );
}

/** 주문서 · 인보이스에 보이는 줄 이름: "Card surcharge (2.4%)" */
function oneam_surcharge_label() {
	return 'Card surcharge (' . rtrim( rtrim( number_format( oneam_surcharge_pct(), 2, '.', '' ), '0' ), '.' ) . '%)';
}

function oneam_is_surcharge_name( $name ) {
	return 0 === strpos( (string) $name, 'Card surcharge' );
}

/**
 * 결제 화면에서만 수수료 줄을 보여줌 (장바구니 · 미니 카트에는 안 보이게).
 * 결제 화면 안에서 오는 요청(Store API · 클래식 결제 ajax)은 주소가 /checkout 인 페이지에서 옴.
 */
function oneam_surcharge_on_checkout() {
	if ( function_exists( 'is_checkout' ) && is_checkout() ) {
		return true;
	}
	if ( defined( 'WOOCOMMERCE_CHECKOUT' ) && WOOCOMMERCE_CHECKOUT ) {
		return true;
	}
	$ref = isset( $_SERVER['HTTP_REFERER'] ) ? (string) wp_unslash( $_SERVER['HTTP_REFERER'] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
	$co  = function_exists( 'wc_get_checkout_url' ) ? (string) wp_parse_url( wc_get_checkout_url(), PHP_URL_PATH ) : '/checkout/';
	return '' !== $ref && '' !== $co && false !== strpos( (string) wp_parse_url( $ref, PHP_URL_PATH ), untrailingslashit( $co ) );
}

/* ---------------------------------------------------------------------------
 * 장바구니 · 결제 화면: 카드를 고르면 합계에 수수료 줄
 * ------------------------------------------------------------------------- */
add_action(
	'woocommerce_cart_calculate_fees',
	function ( $cart ) {
		$pct = oneam_surcharge_pct();
		if ( ! $pct || ! WC()->session || ! oneam_is_card_method( WC()->session->get( 'chosen_payment_method' ) ) || ! oneam_surcharge_on_checkout() ) {
			return;
		}
		// 상품 + 상품 세금 + 배송비 + 배송 세금 (= 수수료 전 합계)
		$base = (float) $cart->get_cart_contents_total() + (float) $cart->get_cart_contents_tax() + (float) $cart->get_shipping_total() + (float) $cart->get_shipping_tax();
		$fee = round( $base * $pct / 100, 2 );
		if ( $fee > 0 ) { // 아주 작은 금액(예: $0.05)은 수수료가 $0.00 이라 줄을 안 넣음
			$cart->add_fee( oneam_surcharge_label(), $fee, false );
		}
	}
);

// 블록 결제 화면: 결제 수단을 바꾸면 합계 다시 계산 (수수료 줄이 바로 보이게)
// 테마는 WooCommerce 다음에 불러와져서 woocommerce_blocks_loaded 가 이미 지나갔을 수 있음 → 바로 등록
function oneam_register_surcharge_callback() {
	static $done = false;
	if ( $done || ! function_exists( 'woocommerce_store_api_register_update_callback' ) ) {
		return;
	}
	$done = true;
	woocommerce_store_api_register_update_callback(
		array(
			'namespace' => 'oneam-surcharge',
			'callback'  => function ( $data ) {
				if ( WC()->session ) {
					WC()->session->set( 'chosen_payment_method', sanitize_text_field( (string) ( $data['method'] ?? '' ) ) );
				}
			},
		)
	);
}
if ( did_action( 'woocommerce_blocks_loaded' ) ) {
	oneam_register_surcharge_callback();
} else {
	add_action( 'woocommerce_blocks_loaded', 'oneam_register_surcharge_callback' );
}
add_action(
	'wp_enqueue_scripts',
	function () {
		if ( ! oneam_surcharge_pct() || ! function_exists( 'is_checkout' ) || ! is_checkout() || is_wc_endpoint_url( 'order-received' ) ) {
			return;
		}
		wp_register_script( 'oneam-surcharge', '', array( 'wp-data' ), ONEAM_VERSION, true );
		wp_enqueue_script( 'oneam-surcharge' );
		wp_add_inline_script(
			'oneam-surcharge',
			"(function(){if(!window.wp||!wp.data)return;var last=null,busy=false;
wp.data.subscribe(function(){var s=wp.data.select('wc/store/payment');if(!s||!s.getActivePaymentMethod)return;var m=s.getActivePaymentMethod();
if(!m||m===last||busy||!window.wc||!wc.blocksCheckout||!wc.blocksCheckout.extensionCartUpdate)return;last=m;busy=true;
Promise.resolve(wc.blocksCheckout.extensionCartUpdate({namespace:'oneam-surcharge',data:{method:m}})).catch(function(){}).then(function(){busy=false;});});})();"
		);
	}
);
// 클래식 결제 화면: 결제 수단을 바꾸면 다시 계산
add_action(
	'woocommerce_review_order_before_payment',
	function () {
		if ( oneam_surcharge_pct() ) {
			echo "<script>jQuery(function($){ $('form.checkout').on('change','input[name=\"payment_method\"]',function(){ $(document.body).trigger('update_checkout'); }); });</script>";
		}
	}
);

/* ---------------------------------------------------------------------------
 * 주문에 수수료 확정 (화면 계산과 상관없이 서버에서 한 번 더 맞춤)
 * ------------------------------------------------------------------------- */
function oneam_apply_order_surcharge( $order, $method ) {
	if ( ! $order instanceof WC_Order ) {
		return;
	}
	$changed = false;
	foreach ( $order->get_fees() as $id => $fee ) {
		if ( oneam_is_surcharge_name( $fee->get_name() ) ) {
			$order->remove_item( $id );
			$changed = true;
		}
	}
	$pct = oneam_surcharge_pct();
	if ( $changed ) {
		$order->calculate_totals( false );
	}
	if ( $pct && oneam_is_card_method( $method ) ) {
		$amount = round( (float) $order->get_total() * $pct / 100, 2 );
		if ( $amount > 0 ) {
			$fee = new WC_Order_Item_Fee();
			$fee->set_name( oneam_surcharge_label() );
			$fee->set_amount( $amount );
			$fee->set_total( $amount );
			$fee->set_tax_status( 'none' );
			$order->add_item( $fee );
			$order->calculate_totals( false );
			$changed = true;
		}
	}
	if ( $changed ) {
		$order->save();
	}
}

// 블록 결제: 주문이 만들어질 때 (결제 처리 바로 전)
add_action(
	'woocommerce_store_api_checkout_update_order_from_request',
	function ( $order, $request ) {
		oneam_apply_order_surcharge( $order, (string) $request['payment_method'] );
	},
	99,
	2
);
// 클래식 결제
add_action(
	'woocommerce_checkout_create_order',
	function ( $order, $data ) {
		oneam_apply_order_surcharge( $order, (string) ( $data['payment_method'] ?? '' ) );
	},
	99,
	2
);
// 관리자가 만든 주문의 결제 페이지 (Customer payment page)
add_action(
	'woocommerce_before_pay_action',
	function ( $order ) {
		// phpcs:ignore WordPress.Security.NonceVerification -- WooCommerce 가 이 직전에 nonce 확인
		$method = isset( $_POST['payment_method'] ) ? sanitize_text_field( wp_unslash( $_POST['payment_method'] ) ) : '';
		oneam_apply_order_surcharge( $order, $method );
	}
);

/* ---------------------------------------------------------------------------
 * 결제 화면 안내: "카드는 2.4% 수수료, e-Transfer 는 없음"
 * ------------------------------------------------------------------------- */
function oneam_surcharge_note() {
	return oneam_surcharge_pct() ? 'Credit card payments include a ' . rtrim( rtrim( number_format( oneam_surcharge_pct(), 2, '.', '' ), '0' ), '.' ) . '% card surcharge, shown in your order total and on your invoice. Interac e-Transfer has no surcharge.' : '';
}
add_filter(
	'woocommerce_gateway_description',
	function ( $desc, $id ) {
		return oneam_surcharge_pct() && oneam_is_card_method( $id ) ? trim( $desc . ' ' . oneam_surcharge_note() ) : $desc;
	},
	10,
	2
);
add_filter(
	'render_block',
	function ( $html, $block ) {
		if ( 'woocommerce/checkout-payment-block' !== ( $block['blockName'] ?? '' ) || ! oneam_surcharge_note() ) {
			return $html;
		}
		return $html . '<p class="oneam-surcharge-note">' . esc_html( oneam_surcharge_note() ) . '</p>';
	},
	10,
	2
);
add_action(
	'wp_head',
	function () {
		if ( function_exists( 'is_checkout' ) && is_checkout() ) {
			echo '<style>.oneam-surcharge-note{margin:12px 0 0;padding:10px 14px;border-radius:10px;background:rgba(138,63,252,.08);font-size:14px;line-height:1.5}</style>';
		}
	}
);

/* ---------------------------------------------------------------------------
 * Moneris 결제창(전체 화면)이 사이트 위쪽 경고줄 · 메뉴에 가려지지 않게
 * 결제창이 열리면 맨 위로 올리고, 그동안 메뉴는 결제창 뒤로 보냄
 * ------------------------------------------------------------------------- */
add_action(
	'wp_enqueue_scripts',
	function () {
		if ( ! function_exists( 'is_checkout' ) || ! ( is_checkout() || is_wc_endpoint_url( 'order-pay' ) ) ) {
			return;
		}
		wp_register_script( 'oneam-mco-front', '', array(), ONEAM_VERSION, true );
		wp_enqueue_script( 'oneam-mco-front' );
		wp_add_inline_script(
			'oneam-mco-front',
			"(function(){var t;function fix(){var on=false;document.querySelectorAll('[id*=\"moneris\" i],[class*=\"moneris\" i],iframe[src*=\"moneris\" i]').forEach(function(el){var s=getComputedStyle(el);if((s.position==='fixed'||s.position==='absolute')&&el.offsetWidth>window.innerWidth*0.5&&el.offsetHeight>window.innerHeight*0.5){if(el.style.getPropertyValue('z-index')!=='2147483000')el.style.setProperty('z-index','2147483000','important');on=true;}});document.documentElement.classList.toggle('oneam-mco-open',on);}
new MutationObserver(function(){clearTimeout(t);t=setTimeout(fix,60);}).observe(document.documentElement,{childList:true,subtree:true,attributes:true,attributeFilter:['style','class']});})();"
		);
		wp_add_inline_style( 'oneam-main', '.oneam-mco-open .site-header,.oneam-mco-open .topbar{z-index:1!important}' );
	}
);
