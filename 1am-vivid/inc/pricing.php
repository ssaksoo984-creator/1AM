<?php
/**
 * 주(Province)별 가격 · 판매 지역 · GST
 *
 * 상품 편집 > General 탭 "Province prices" 에 한 줄씩:  BC: 80 / SK: 80 / AB: 90
 * - 가격은 배송지(=판매 지역, 소비세 스탬프 지역) 주에 따라 자동 적용
 *   (배송지 → 청구지 → 가입 때 입력한 매장 주 순서)
 * - 목록에 없는 주는 주문 불가, 결제 화면에서도 판매하는 주만 고를 수 있음
 * - 단위(Unit, 기본 carton) 와 "+ GST" 가 가격 옆에 표시
 * - 주문 상품마다 "Province / excise stamp" 기록
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** "BC: 80" 줄 → array( 'BC' => 80.0 ) */
function oneam_parse_province_prices( $text ) {
	$out = array();
	foreach ( preg_split( '/[\r\n,]+/', (string) $text ) as $line ) {
		if ( preg_match( '/^\s*([A-Za-z]{2})\s*[:=]\s*\$?\s*([\d.]+)/', $line, $m ) ) {
			$out[ strtoupper( $m[1] ) ] = (float) $m[2];
		}
	}
	return $out;
}

/** 상품(또는 옵션의 부모 상품)의 주별 가격 */
function oneam_province_prices( $product ) {
	if ( ! $product ) {
		return array();
	}
	$parent = $product->is_type( 'variation' ) ? wc_get_product( $product->get_parent_id() ) : $product;
	return $parent ? oneam_parse_province_prices( $parent->get_meta( '_oneam_prices' ) ) : array();
}

function oneam_unit_label( $product ) {
	$parent = $product && $product->is_type( 'variation' ) ? wc_get_product( $product->get_parent_id() ) : $product;
	$unit   = $parent ? trim( (string) $parent->get_meta( '_oneam_unit' ) ) : '';
	return '' !== $unit ? $unit : 'carton';
}

/** 지금 손님의 주 (배송지 → 청구지 → 가입 때 매장 주) */
function oneam_customer_province() {
	$prov = '';
	if ( function_exists( 'WC' ) && WC()->customer ) {
		$prov = WC()->customer->get_shipping_state() ?: WC()->customer->get_billing_state();
	}
	if ( ! $prov && is_user_logged_in() ) {
		$prov = get_user_meta( get_current_user_id(), 'oneam_province', true );
	}
	return strtoupper( (string) $prov );
}

/** 이 상품의 지금 가격 (주별 가격이 없는 상품이면 null) */
function oneam_province_price( $product ) {
	$pp = oneam_province_prices( $product );
	if ( ! $pp ) {
		return null;
	}
	$prov = oneam_customer_province();
	if ( isset( $pp[ $prov ] ) ) {
		return $pp[ $prov ];
	}
	return min( $pp ); // 아직 주를 모를 때 (결제 화면에서 주를 고르면 다시 계산)
}

/* ---------------------------------------------------------------------------
 * 가격 적용
 * ------------------------------------------------------------------------- */
$oneam_price_filter = function ( $price, $product ) {
	$p = oneam_province_price( $product );
	return null === $p ? $price : $p;
};
foreach ( array( 'woocommerce_product_get_price', 'woocommerce_product_get_regular_price', 'woocommerce_product_variation_get_price', 'woocommerce_product_variation_get_regular_price' ) as $oneam_hook ) {
	add_filter( $oneam_hook, $oneam_price_filter, 20, 2 );
}
foreach ( array( 'woocommerce_variation_prices_price', 'woocommerce_variation_prices_regular_price' ) as $oneam_hook ) {
	add_filter(
		$oneam_hook,
		function ( $price, $variation ) {
			$p = oneam_province_price( $variation );
			return null === $p ? $price : $p;
		},
		20,
		2
	);
}
// 주마다 다른 가격이 캐시에 섞이지 않게
add_filter(
	'woocommerce_get_variation_prices_hash',
	function ( $hash ) {
		$hash[] = 'prov-' . oneam_customer_province();
		return $hash;
	}
);

/** 판매하지 않는 주(배송지가 정해진 경우)는 주문 불가 */
add_filter(
	'woocommerce_is_purchasable',
	function ( $ok, $product ) {
		$pp   = oneam_province_prices( $product );
		$prov = oneam_customer_province();
		if ( $ok && $pp && $prov && ! isset( $pp[ $prov ] ) ) {
			return false;
		}
		return $ok;
	},
	20,
	2
);
add_filter(
	'woocommerce_variation_is_purchasable',
	function ( $ok, $variation ) {
		$pp   = oneam_province_prices( $variation );
		$prov = oneam_customer_province();
		return ( $ok && $pp && $prov && ! isset( $pp[ $prov ] ) ) ? false : $ok;
	},
	20,
	2
);

/** 가격 옆에 "/ carton + GST" */
add_filter(
	'woocommerce_get_price_html',
	function ( $html, $product ) {
		if ( ! oneam_province_prices( $product ) || '' === $html ) {
			return $html;
		}
		return $html . ' <small class="oneam-unit">/ ' . esc_html( oneam_unit_label( $product ) ) . ' + GST</small>';
	},
	20,
	2
);

/** 결제 화면: 장바구니 상품을 판매하는 주만 선택 가능 */
add_filter(
	'woocommerce_states',
	function ( $states ) {
		if ( is_admin() || ! function_exists( 'WC' ) || ! WC()->cart || ! isset( $states['CA'] ) ) {
			return $states;
		}
		if ( ! ( function_exists( 'is_checkout' ) && ( is_checkout() || is_cart() ) ) && ! wp_doing_ajax() && ! ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
			return $states;
		}
		$allowed = null;
		foreach ( WC()->cart->get_cart() as $item ) {
			$pp = oneam_province_prices( $item['data'] );
			if ( $pp ) {
				$allowed = null === $allowed ? array_keys( $pp ) : array_intersect( $allowed, array_keys( $pp ) );
			}
		}
		if ( $allowed ) {
			$states['CA'] = array_intersect_key( $states['CA'], array_flip( $allowed ) );
		}
		return $states;
	}
);

/** 주문 상품마다 판매 지역(소비세 스탬프) 기록 */
add_action(
	'woocommerce_checkout_create_order_line_item',
	function ( $item, $key, $values, $order ) {
		if ( oneam_province_prices( $values['data'] ) ) {
			$prov = $order->get_shipping_state() ?: $order->get_billing_state();
			$item->add_meta_data( 'Province / excise stamp', $prov, true );
		}
	},
	10,
	4
);

/* ---------------------------------------------------------------------------
 * 상품 편집 화면: Province prices · Unit
 * ------------------------------------------------------------------------- */
add_action(
	'woocommerce_product_options_general_product_data',
	function () {
		echo '<div class="options_group oneam-prices">';
		woocommerce_wp_textarea_input(
			array(
				'id'          => '_oneam_prices',
				'label'       => '1AM province prices',
				'placeholder' => "BC: 80\nSK: 80\nAB: 90",
				'description' => 'One province per line (code: price before GST). Only these provinces can order, at these prices — this overrides the regular price. Leave empty to use the regular price everywhere.',
				'desc_tip'    => false,
				'rows'        => 4,
			)
		);
		woocommerce_wp_text_input(
			array(
				'id'          => '_oneam_unit',
				'label'       => 'Unit',
				'placeholder' => 'carton',
				'description' => 'Shown after the price, e.g. "$80.00 / carton + GST".',
			)
		);
		echo '</div>';
	},
	30
);
add_action(
	'woocommerce_admin_process_product_object',
	function ( $product ) {
		// phpcs:disable WordPress.Security.NonceVerification -- WooCommerce verifies the product save nonce.
		if ( isset( $_POST['_oneam_prices'] ) ) {
			$pp    = oneam_parse_province_prices( sanitize_textarea_field( wp_unslash( $_POST['_oneam_prices'] ) ) );
			$lines = array();
			foreach ( $pp as $code => $price ) {
				$lines[] = $code . ': ' . rtrim( rtrim( number_format( $price, 2, '.', '' ), '0' ), '.' );
			}
			$product->update_meta_data( '_oneam_prices', implode( "\n", $lines ) );
		}
		if ( isset( $_POST['_oneam_unit'] ) ) {
			$product->update_meta_data( '_oneam_unit', sanitize_text_field( wp_unslash( $_POST['_oneam_unit'] ) ) );
		}
		// phpcs:enable
	}
);

/** 한 번만: Slim HYBRID 에 기본 판매 지역 · 가격 (BC/SK 80, AB 90 per carton) */
add_action(
	'admin_init',
	function () {
		if ( '1' === get_option( 'oneam_prices_v1' ) || ! function_exists( 'wc_get_product' ) ) {
			return;
		}
		$post = get_page_by_path( 'slim-hybrid', OBJECT, 'product' );
		if ( $post ) {
			$p = wc_get_product( $post->ID );
			if ( $p && ! $p->get_meta( '_oneam_prices' ) ) {
				$p->update_meta_data( '_oneam_prices', "BC: 80\nSK: 80\nAB: 90" );
				$p->update_meta_data( '_oneam_unit', 'carton' );
				$p->save();
			}
		}
		update_option( 'oneam_prices_v1', '1' );
	}
);

/** 한 번만: 우커머스 맛(옵션)에 남아 있는 예전 기본 설명 → 새 맛 특징 (장바구니 · 주문 메일에도 반영) */
add_action(
	'admin_init',
	function () {
		if ( '1' === get_option( 'oneam_flavour_desc_v1' ) || ! function_exists( 'wc_get_products' ) ) {
			return;
		}
		foreach ( wc_get_products( array( 'type' => 'variable', 'limit' => -1, 'status' => array( 'publish', 'draft', 'private' ) ) ) as $product ) {
			foreach ( $product->get_children() as $vid ) {
				$v = wc_get_product( $vid );
				if ( ! $v ) {
					continue;
				}
				$desc = trim( wp_strip_all_tags( $v->get_description() ) );
				if ( '' !== $desc && ! in_array( $desc, oneam_old_flavor_texts(), true ) ) {
					continue;
				}
				$attrs = $v->get_attributes();
				$def   = oneam_default_flavor_by_slug( sanitize_title( $attrs ? (string) reset( $attrs ) : '' ) );
				if ( $def ) {
					$v->set_description( $def[5] );
					$v->save();
				}
			}
		}
		update_option( 'oneam_flavour_desc_v1', '1' );
	}
);

/* ---------------------------------------------------------------------------
 * GST 5% 한 번에 설정 (WooCommerce 화면 안내)
 * ------------------------------------------------------------------------- */
function oneam_has_gst() {
	global $wpdb;
	return (bool) $wpdb->get_var( "SELECT tax_rate_id FROM {$wpdb->prefix}woocommerce_tax_rates WHERE tax_rate_country = 'CA' LIMIT 1" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
}
add_action(
	'admin_post_oneam_setup_gst',
	function () {
		check_admin_referer( 'oneam_setup_gst' );
		if ( ! current_user_can( 'manage_woocommerce' ) || ! class_exists( 'WC_Tax' ) ) {
			wp_die( 'Not allowed.' );
		}
		update_option( 'woocommerce_calc_taxes', 'yes' );
		update_option( 'woocommerce_prices_include_tax', 'no' );
		update_option( 'woocommerce_tax_display_shop', 'excl' );
		update_option( 'woocommerce_tax_display_cart', 'excl' );
		update_option( 'woocommerce_tax_based_on', 'shipping' );
		if ( ! oneam_has_gst() ) {
			WC_Tax::_insert_tax_rate(
				array(
					'tax_rate_country'  => 'CA',
					'tax_rate_state'    => '',
					'tax_rate'          => '5.0000',
					'tax_rate_name'     => 'GST',
					'tax_rate_priority' => 1,
					'tax_rate_compound' => 0,
					'tax_rate_shipping' => 1,
					'tax_rate_order'    => 0,
					'tax_rate_class'    => '',
				)
			);
		}
		wp_safe_redirect( admin_url( 'admin.php?page=wc-settings&tab=tax&section=standard' ) );
		exit;
	}
);
add_action(
	'admin_notices',
	function () {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( ! $screen || ! current_user_can( 'manage_woocommerce' ) || ! class_exists( 'WC_Tax' ) ) {
			return;
		}
		if ( ! in_array( $screen->id, array( 'woocommerce_page_wc-settings', 'edit-product', 'product' ), true ) ) {
			return;
		}
		if ( 'yes' === get_option( 'woocommerce_calc_taxes' ) && oneam_has_gst() ) {
			return;
		}
		printf(
			'<div class="notice notice-info"><p><strong>1AM:</strong> Prices are before GST. Turn on taxes and add 5%% GST for Canada (prices entered without tax, tax based on the shipping address). <a class="button button-primary" href="%s">Set up 5%% GST</a></p></div>',
			esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=oneam_setup_gst' ), 'oneam_setup_gst' ) )
		);
	}
);

/** 판매 지역 (승인 회원이면 주별 가격까지) — 상품 정보에 표시 */
function oneam_regions_html( $regions, $product = null ) {
	$regions = array_filter( (array) $regions );
	if ( ! $regions ) {
		return '';
	}
	$pp   = $product ? oneam_province_prices( $product ) : array();
	$show = $pp && 'approved' === oneam_member_state();
	$you  = oneam_customer_province();
	$out  = '<ul class="pinfo__regions" aria-label="Available in">';
	foreach ( $regions as $code ) {
		$label = $code;
		if ( $show && isset( $pp[ $code ] ) ) {
			$label .= ' ' . wp_strip_all_tags( wc_price( $pp[ $code ] ) ) . ' / ' . oneam_unit_label( $product ) . ' + GST';
		}
		$out .= '<li' . ( $code === $you ? ' class="is-you"' : '' ) . '>' . esc_html( $label ) . '</li>';
	}
	return $out . '</ul>';
}

/* ---------------------------------------------------------------------------
 * 결제 수단 표시 (카드사 · Moneris 요구: 사용 가능한 카드 · 통화 · 보안 결제 안내)
 * ------------------------------------------------------------------------- */
function oneam_payment_badges() {
	$methods = array_filter( array_map( 'trim', explode( '·', (string) oneam_opt( 'oneam_payments' ) ) ) );
	$out     = '<div class="paybadges"><p class="paybadges__title">' . oneam_icon( 'check' ) . ' ' . ( oneam_opt( 'oneam_processor' ) ? 'Secure payment by ' . esc_html( oneam_opt( 'oneam_processor' ) ) : 'Payment' ) . ' · CAD</p><ul>';
	foreach ( $methods as $m ) {
		$out .= '<li>' . esc_html( $m ) . '</li>';
	}
	return $out . '</ul></div>';
}
// 블록 결제 · 장바구니 화면
add_filter(
	'render_block',
	function ( $html, $block ) {
		// 결제 블록 안쪽은 우커머스 스크립트가 다시 그리므로, 결제 · 장바구니 화면 전체 아래에 표시
		return in_array( $block['blockName'] ?? '', array( 'woocommerce/checkout', 'woocommerce/cart' ), true ) ? $html . oneam_payment_badges() : $html;
	},
	10,
	2
);
// 클래식 결제 화면
add_action( 'woocommerce_review_order_after_payment', function () { echo oneam_payment_badges(); } ); // phpcs:ignore

/* ---------------------------------------------------------------------------
 * Interac e-Transfer 결제 (지금은 e-Transfer 만, 카드는 Moneris 연결 후)
 * 우커머스 기본 "Direct bank transfer" 를 e-Transfer 로 설정 (블록 결제 화면 지원)
 * 받는 이메일: 사용자 정의하기 > 1AM Settings > Checkout confirmation > Interac e-Transfer email
 * ------------------------------------------------------------------------- */
function oneam_etransfer_email() {
	return oneam_opt( 'oneam_etransfer' ) ?: oneam_opt( 'oneam_email' );
}

function oneam_setup_etransfer() {
	if ( ! class_exists( 'WooCommerce' ) ) {
		return;
	}
	$email = oneam_etransfer_email();
	$bacs  = (array) get_option( 'woocommerce_bacs_settings', array() );
	update_option(
		'woocommerce_bacs_settings',
		array_merge(
			$bacs,
			array(
				'enabled'      => 'yes',
				'title'        => 'Interac e-Transfer',
				'description'  => "After you place your order, send an Interac e-Transfer for the order total to {$email}. Put your order number in the message. Your order ships once payment is received.",
				'instructions' => "Please send an Interac e-Transfer for the order total to {$email} and include your order number in the message. We process your order as soon as the payment arrives. Unpaid orders may be cancelled after 3 business days.",
			)
		)
	);
	update_option( 'woocommerce_bacs_accounts', array() ); // 은행 계좌 표는 표시하지 않음
	// 지금은 e-Transfer 만: 수표 · 착불 결제 끄기
	foreach ( array( 'cheque', 'cod' ) as $gw ) {
		$o = (array) get_option( "woocommerce_{$gw}_settings", array() );
		if ( ( $o['enabled'] ?? 'no' ) === 'yes' ) {
			$o['enabled'] = 'no';
			update_option( "woocommerce_{$gw}_settings", $o );
		}
	}
}
// 테마 업데이트 후 한 번, 그리고 사용자 정의하기에서 이메일을 바꾸면 다시 적용
add_action(
	'admin_init',
	function () {
		if ( ONEAM_VERSION !== get_option( 'oneam_etransfer_v' ) ) {
			oneam_setup_etransfer();
			if ( function_exists( 'oneam_setup_checkout_terms' ) ) {
				oneam_setup_checkout_terms();
			}
			update_option( 'oneam_etransfer_v', ONEAM_VERSION );
		}
	}
);
add_action( 'customize_save_after', 'oneam_setup_etransfer' );

/** 주문 완료 화면 · 주문 메일: 보낼 금액 · 이메일 · 주문번호를 정확히 */
function oneam_etransfer_box( $order, $plain = false ) {
	if ( ! $order || 'bacs' !== $order->get_payment_method() || $order->is_paid() ) {
		return;
	}
	$total = wp_strip_all_tags( wc_price( $order->get_total(), array( 'currency' => $order->get_currency() ) ) );
	$email = oneam_etransfer_email();
	$num   = $order->get_order_number();
	if ( $plain ) {
		echo "\nINTERAC E-TRANSFER\nSend: {$total}\nTo: {$email}\nMessage: Order #{$num}\nUnpaid orders may be cancelled after 3 business days.\n\n"; // phpcs:ignore
		return;
	}
	printf(
		'<div class="etransfer"><p class="etransfer__title">Pay by Interac e-Transfer</p><dl><div><dt>Send</dt><dd>%s</dd></div><div><dt>To</dt><dd>%s</dd></div><div><dt>Message</dt><dd>Order #%s</dd></div></dl><p class="etransfer__note">We process your order as soon as the payment arrives. Unpaid orders may be cancelled after 3 business days.</p></div>',
		esc_html( $total ),
		esc_html( $email ),
		esc_html( $num )
	);
}
add_action(
	'woocommerce_thankyou_bacs',
	function ( $order_id ) {
		oneam_etransfer_box( wc_get_order( $order_id ) );
	},
	5
);
add_action(
	'woocommerce_email_before_order_table',
	function ( $order, $sent_to_admin, $plain ) {
		if ( ! $sent_to_admin ) {
			oneam_etransfer_box( $order, $plain );
		}
	},
	5,
	3
);
