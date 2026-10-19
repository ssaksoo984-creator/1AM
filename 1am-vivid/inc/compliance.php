<?php
/**
 * 1AM 컴플라이언스 · 보안
 *
 * - 판매 주(BC · AB · SK 등): 1AM Settings > Business & legal > Sales provinces 한 곳에서 관리
 * - 주별 소비세 스탬프(예: AB = Alberta, BC/SK = Federal) · 계정 매장 주와 맞지 않으면 담기/결제 차단
 * - 승인 전(비로그인 포함)에는 가격이 어디에도 나가지 않게: 화면 · Store API · 옵션 AJAX · 구조화 데이터
 * - 공개 users API 차단 · 작성자 페이지 끄기 · generator 메타 제거 · 이미지 alt · 검색 설명
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* ---------------------------------------------------------------------------
 * 판매 주
 * ------------------------------------------------------------------------- */
function oneam_sales_regions() {
	$all  = oneam_provinces();
	$list = array();
	foreach ( preg_split( '/[\s,;]+/', strtoupper( (string) oneam_opt( 'oneam_sales_regions' ) ) ) as $code ) {
		if ( isset( $all[ $code ] ) ) {
			$list[] = $code;
		}
	}
	return $list ? array_values( array_unique( $list ) ) : array( 'BC', 'AB', 'SK' );
}

/** "British Columbia, Alberta and Saskatchewan" */
function oneam_regions_phrase( $codes = null ) {
	$names = array_map(
		function ( $c ) {
			return oneam_provinces()[ $c ] ?? $c;
		},
		null === $codes ? oneam_sales_regions() : $codes
	);
	if ( count( $names ) < 2 ) {
		return implode( '', $names );
	}
	$last = array_pop( $names );
	return implode( ', ', $names ) . ' and ' . $last;
}

/* ---------------------------------------------------------------------------
 * 소비세 스탬프
 * ------------------------------------------------------------------------- */
function oneam_stamp_map() {
	$out = array();
	foreach ( preg_split( '/\r?\n/', (string) oneam_opt( 'oneam_stamp_map' ) ) as $line ) {
		if ( preg_match( '/^\s*([A-Za-z]{2})\s*[:=]\s*(.+?)\s*$/', $line, $m ) ) {
			$out[ strtoupper( $m[1] ) ] = $m[2];
		}
	}
	return $out;
}

/** 이 주에서 팔 수 있는 스탬프 이름 (예: "Alberta", "Federal") */
function oneam_stamp_for( $prov ) {
	$map = oneam_stamp_map();
	return $map[ strtoupper( (string) $prov ) ] ?? '';
}

/** "Alberta excise stamp" / "Federal excise stamp" */
function oneam_stamp_label( $prov ) {
	$s = oneam_stamp_for( $prov );
	return $s ? $s . ' excise stamp' : strtoupper( (string) $prov ) . ' excise stamp';
}

/* ---------------------------------------------------------------------------
 * 계정의 매장 주 (가입한 주 + 관리자가 추가한 주)
 * ------------------------------------------------------------------------- */
function oneam_account_provinces( $user_id = 0 ) {
	$user_id = $user_id ?: get_current_user_id();
	if ( ! $user_id ) {
		return array();
	}
	if ( user_can( $user_id, 'manage_woocommerce' ) || user_can( $user_id, 'manage_options' ) ) {
		return oneam_sales_regions(); // 관리자 테스트 주문
	}
	$list = array( strtoupper( (string) get_user_meta( $user_id, 'oneam_province', true ) ) );
	foreach ( preg_split( '/[\s,;]+/', strtoupper( (string) get_user_meta( $user_id, 'oneam_extra_provinces', true ) ) ) as $c ) {
		$list[] = $c;
	}
	return array_values( array_intersect( array_unique( array_filter( $list ) ), oneam_sales_regions() ) );
}

/** 이 손님이 이 주로 주문할 수 있는지 */
function oneam_account_can_ship_to( $prov, $user_id = 0 ) {
	$mine = oneam_account_provinces( $user_id );
	return ! $mine || in_array( strtoupper( (string) $prov ), $mine, true ); // 주 정보가 없는 옛 계정은 막지 않음
}

function oneam_stamp_mismatch_message( $prov ) {
	$mine = oneam_account_provinces();
	return sprintf(
		'We ship only products stamped for your store\'s province. Your account is registered for %1$s, so we can\'t ship %2$s-stamped products to %3$s. If you also operate a store in %3$s, contact us at %4$s to add it to your account.',
		$mine ? oneam_regions_phrase( $mine ) : 'another province',
		oneam_stamp_for( $prov ) ?: strtoupper( $prov ),
		oneam_provinces()[ strtoupper( $prov ) ] ?? $prov,
		oneam_opt( 'oneam_email' )
	);
}

// 장바구니 담기: 지금 고른 주가 계정 매장 주가 아니면 막음
add_filter(
	'woocommerce_add_to_cart_validation',
	function ( $ok, $product_id ) {
		if ( ! $ok || ! is_user_logged_in() ) {
			return $ok;
		}
		$prov = oneam_customer_province();
		if ( $prov && oneam_province_prices( wc_get_product( $product_id ) ) && ! oneam_account_can_ship_to( $prov ) ) {
			wc_add_notice( oneam_stamp_mismatch_message( $prov ), 'error' );
			return false;
		}
		return $ok;
	},
	10,
	2
);

// 블록 결제 화면: 배송 주가 계정 매장 주가 아니면 주문 불가
add_action(
	'woocommerce_store_api_cart_errors',
	function ( $errors, $cart ) {
		if ( ! is_user_logged_in() || ! WC()->customer ) {
			return;
		}
		$prov = strtoupper( WC()->customer->get_shipping_state() ?: WC()->customer->get_billing_state() );
		if ( $prov && ! oneam_account_can_ship_to( $prov ) ) {
			$errors->add( 'oneam_stamp_province', oneam_stamp_mismatch_message( $prov ) );
		}
	},
	10,
	2
);

// 클래식 결제 화면
add_action(
	'woocommerce_after_checkout_validation',
	function ( $data, $errors ) {
		$prov = strtoupper( ( $data['ship_to_different_address'] ?? false ) ? ( $data['shipping_state'] ?? '' ) : ( $data['billing_state'] ?? '' ) );
		if ( $prov && ! oneam_account_can_ship_to( $prov ) ) {
			$errors->add( 'oneam_stamp_province', oneam_stamp_mismatch_message( $prov ) );
		}
	},
	10,
	2
);

// 관리자 사용자 화면: 추가 매장 주
add_action(
	'oneam_wholesale_profile_rows',
	function ( $user ) {
		?>
		<tr><th>Additional store provinces</th><td>
			<input type="text" class="regular-text" name="oneam_extra_provinces" value="<?php echo esc_attr( get_user_meta( $user->ID, 'oneam_extra_provinces', true ) ); ?>" placeholder="e.g. AB">
			<p class="description">Province codes, comma separated. Add one only after verifying the retailer's licensed store there — orders can ship (with that province's excise stamp) only to the account's store provinces.</p>
		</td></tr>
		<?php
	}
);
add_action(
	'oneam_wholesale_profile_save',
	function ( $user_id ) {
		if ( isset( $_POST['oneam_extra_provinces'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			$codes = array_intersect( preg_split( '/[\s,;]+/', strtoupper( sanitize_text_field( wp_unslash( $_POST['oneam_extra_provinces'] ) ) ) ), oneam_sales_regions() ); // phpcs:ignore WordPress.Security.NonceVerification
			update_user_meta( $user_id, 'oneam_extra_provinces', implode( ', ', array_unique( $codes ) ) );
		}
	}
);

/* ---------------------------------------------------------------------------
 * 승인 전에는 가격 비공개
 * ------------------------------------------------------------------------- */
function oneam_can_see_prices() {
	if ( is_admin() && ! wp_doing_ajax() ) {
		return true;
	}
	return 'approved' === oneam_member_state();
}

// 화면 가격 · 장바구니 담기
add_filter( 'woocommerce_get_price_html', function ( $html ) { return oneam_can_see_prices() ? $html : ''; }, 99 );
add_filter( 'woocommerce_variable_price_html', function ( $html ) { return oneam_can_see_prices() ? $html : ''; }, 99 );
add_filter( 'woocommerce_is_purchasable', function ( $ok ) { return oneam_can_see_prices() ? $ok : false; }, 99 );
add_filter( 'woocommerce_variation_is_purchasable', function ( $ok ) { return oneam_can_see_prices() ? $ok : false; }, 99 );

// 옵션 정보 (?wc-ajax=get_variation, 상품 페이지 옵션 JSON)
add_filter(
	'woocommerce_available_variation',
	function ( $data ) {
		if ( ! oneam_can_see_prices() ) {
			foreach ( array( 'price_html', 'display_price', 'display_regular_price' ) as $k ) {
				unset( $data[ $k ] );
			}
		}
		return $data;
	},
	99
);

// 구조화 데이터(JSON-LD)의 가격
add_filter(
	'woocommerce_structured_data_product',
	function ( $markup ) {
		if ( ! oneam_can_see_prices() ) {
			unset( $markup['offers'] );
		}
		return $markup;
	},
	99
);

// Store API (/wp-json/wc/store/...): 가격 필드 제거
function oneam_strip_prices( $data ) {
	if ( ! is_array( $data ) ) {
		return $data;
	}
	foreach ( array( 'prices', 'price_html', 'price_range', 'display_price' ) as $k ) {
		unset( $data[ $k ] );
	}
	foreach ( $data as $k => $v ) {
		if ( is_array( $v ) ) {
			$data[ $k ] = oneam_strip_prices( $v );
		}
	}
	return $data;
}
add_filter(
	'rest_post_dispatch',
	function ( $response, $server, $request ) {
		$route = $request instanceof WP_REST_Request ? $request->get_route() : '';
		if ( 0 !== strpos( $route, '/wc/store' ) || oneam_can_see_prices() || ! ( $response instanceof WP_REST_Response ) ) {
			return $response;
		}
		$response->set_data( oneam_strip_prices( $response->get_data() ) );
		return $response;
	},
	99,
	3
);

/* ---------------------------------------------------------------------------
 * WooCommerce "Coming soon"(스토어 페이지만) 기본 화면 대신 테마 화면
 * (승인 전 방문자에게는 테마가 이미 가격 · 담기를 숨김)
 * ------------------------------------------------------------------------- */
add_filter(
	'woocommerce_coming_soon_exclude',
	function ( $exclude ) {
		if ( 'yes' !== get_option( 'woocommerce_store_pages_only' ) ) {
			return $exclude; // 사이트 전체 "Coming soon" 은 그대로 둠
		}
		// 상품 페이지는 테마 화면, 승인된 매장은 상점 · 장바구니 · 결제 · 내 계정 모두 이용 가능
		// 약관 · 정책 페이지는 누구나 볼 수 있어야 함 (결제사 심사) — WooCommerce 는 Terms 페이지도 스토어 페이지로 취급
		$policy = is_page() && ( in_array( get_post_field( 'post_name', get_queried_object_id() ), array( 'terms', 'return-policy', 'shipping-policy', 'privacy-policy' ), true ) || (int) get_queried_object_id() === (int) get_option( 'woocommerce_terms_page_id' ) );
		if ( $policy || ( function_exists( 'is_product' ) && is_product() ) || 'approved' === oneam_member_state() ) {
			return true;
		}
		return $exclude;
	}
);

/* ---------------------------------------------------------------------------
 * 보안: 공개 users API · 작성자 페이지 · 버전 노출
 * ------------------------------------------------------------------------- */
add_filter(
	'rest_endpoints',
	function ( $endpoints ) {
		if ( ! is_user_logged_in() ) {
			foreach ( array_keys( $endpoints ) as $route ) {
				if ( 0 === strpos( $route, '/wp/v2/users' ) ) {
					unset( $endpoints[ $route ] );
				}
			}
		}
		return $endpoints;
	}
);
add_action(
	'template_redirect',
	function () {
		if ( is_author() || ( isset( $_GET['author'] ) && ! is_admin() ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			wp_safe_redirect( home_url( '/' ), 301 );
			exit;
		}
	},
	0
);
add_filter( 'author_link', function () { return home_url( '/' ); } );
add_filter( 'wp_sitemaps_add_provider', function ( $provider, $name ) { return 'users' === $name ? false : $provider; }, 10, 2 );
add_filter( 'oembed_response_data', function ( $data ) { unset( $data['author_name'], $data['author_url'] ); return $data; } );

// <meta name="generator"> (WordPress · WooCommerce · Elementor 등) 제거
remove_action( 'wp_head', 'wp_generator' );
add_filter( 'the_generator', '__return_empty_string' );
add_action( 'wp_head', function () { ob_start(); }, -9999 );
add_action(
	'wp_head',
	function () {
		$html = ob_get_clean();
		echo preg_replace( '/<meta\s+name=["\']generator["\'][^>]*>\s*/i', '', (string) $html ); // phpcs:ignore WordPress.Security.EscapeOutput
	},
	99999
);

/* ---------------------------------------------------------------------------
 * 접근성: alt 없는 이미지 → alt="" (장식용). 미디어 라이브러리의 대체 텍스트가 있으면 그 값.
 * ------------------------------------------------------------------------- */
add_filter(
	'wp_get_attachment_image_attributes',
	function ( $attr ) {
		if ( ! isset( $attr['alt'] ) ) {
			$attr['alt'] = '';
		}
		return $attr;
	}
);
add_filter(
	'wp_content_img_tag',
	function ( $img ) {
		return preg_match( '/\salt=/i', $img ) ? $img : preg_replace( '/<img\b/i', '<img alt=""', $img, 1 );
	}
);

/* ---------------------------------------------------------------------------
 * 검색: 페이지별 설명 · 제목 (SEO 플러그인이 있으면 그쪽에 맡김)
 * ------------------------------------------------------------------------- */
function oneam_has_seo_plugin() {
	return defined( 'WPSEO_VERSION' ) || defined( 'RANK_MATH_VERSION' ) || defined( 'AIOSEO_VERSION' ) || defined( 'SEOPRESS_VERSION' ) || defined( 'THE_SEO_FRAMEWORK_VERSION' );
}

function oneam_meta_description() {
	$regions = oneam_regions_phrase();
	if ( is_front_page() ) {
		return oneam_opt( 'oneam_meta_desc' ) ?: "1AM is a wholesale-only vape brand for verified adult-only vape retailers in {$regions}. Apply for a retail account to see pricing and order.";
	}
	$slug  = is_singular() ? get_post_field( 'post_name', get_queried_object_id() ) : '';
	$fixed = array(
		'about-us'         => "About 1AM: a wholesale-only vape brand distributed by " . oneam_opt( 'oneam_company' ) . " to approved adult-only vape retailers in {$regions}.",
		'faq'              => 'Answers for retailers about 1AM wholesale accounts, pricing, excise stamps, payment, shipping and returns.',
		'how-to-order'     => 'How to open a 1AM retail account and place a wholesale order: apply, get verified, order online and pay by Interac e-Transfer.',
		'terms'            => '1AM wholesale terms of sale and vaping product compliance terms for approved retailers.',
		'shipping-policy'  => "1AM shipping policy: where we ship ({$regions}), carriers, dispatch and delivery times.",
		'return-policy'    => '1AM return policy: final sale, and how to report damaged, defective or incorrect items.',
		'privacy-policy'   => '1AM privacy policy: how we collect, use and protect business and personal information under PIPEDA and BC PIPA.',
		'wholesale-signup' => "Apply for a 1AM wholesale account. For verified adult-only vape retailers in {$regions}.",
	);
	if ( isset( $fixed[ $slug ] ) ) {
		return $fixed[ $slug ];
	}
	if ( function_exists( 'is_product' ) && is_product() ) {
		$p = wc_get_product( get_queried_object_id() );
		$d = $p ? wp_strip_all_tags( $p->get_short_description() ?: $p->get_description() ) : '';
		return trim( wp_trim_words( $d, 28, '…' ) . " Wholesale only, for approved retailers in {$regions}." );
	}
	if ( is_singular() ) {
		$d = get_the_excerpt( get_queried_object_id() );
		return $d ? wp_trim_words( wp_strip_all_tags( $d ), 28, '…' ) : '';
	}
	return '';
}

add_action(
	'wp_head',
	function () {
		if ( oneam_has_seo_plugin() ) {
			return;
		}
		$d = oneam_meta_description();
		if ( $d ) {
			echo '<meta name="description" content="' . esc_attr( $d ) . '">' . "\n";
		}
	},
	1
);

// 제목: "페이지 – 1AM", 홈은 "1AM – 사이트 설명(없으면 Wholesale vape brand …)"
add_filter(
	'document_title_parts',
	function ( $parts ) {
		if ( oneam_has_seo_plugin() ) {
			return $parts;
		}
		if ( is_front_page() ) {
			$parts['tagline'] = get_bloginfo( 'description' ) ?: 'Wholesale vape brand for Canadian retailers';
		}
		return $parts;
	}
);
add_filter( 'document_title_separator', function () { return '–'; } );

/* ---------------------------------------------------------------------------
 * 경고 문구: 끝에 "— Health Canada" (이미 있으면 그대로)
 * ------------------------------------------------------------------------- */
function oneam_health_warning( $key = 'oneam_topbar' ) {
	$t = trim( (string) oneam_opt( $key ) );
	if ( '' === $t || false !== stripos( $t, 'Health Canada' ) ) {
		return $t;
	}
	// 푸터처럼 뒤에 다른 문장(For adults 19+ only.)이 붙어 있으면 경고 문장 바로 뒤에 출처
	if ( preg_match( '/^(.*?chemical\.)(.*)$/is', $t, $m ) ) {
		return trim( $m[1] . ' — Health Canada' . ( trim( $m[2] ) ? ' · ' . trim( $m[2] ) : '' ) );
	}
	return $t . ' — Health Canada';
}

/* ---------------------------------------------------------------------------
 * 회사 정보 · GST · PST (주문 · 메일 · 인보이스)
 * ------------------------------------------------------------------------- */
function oneam_gst_line() {
	$gst = trim( (string) oneam_opt( 'oneam_gst_number' ) );
	return $gst ? 'GST/HST Reg. No. ' . $gst : '';
}

function oneam_payment_terms() {
	$days = (int) oneam_opt( 'oneam_pay_due_days' );
	return 'Interac e-Transfer due within ' . ( $days ?: 2 ) . ' business days of the order date. Unpaid orders are cancelled automatically.';
}

// 주문에 매장 PST 번호 저장 (블록 · 클래식 결제)
function oneam_order_add_business_meta( $order ) {
	$uid = $order->get_customer_id();
	if ( ! $uid ) {
		return;
	}
	$pst = get_user_meta( $uid, 'oneam_pst_number', true );
	$prov = strtoupper( $order->get_shipping_state() ?: $order->get_billing_state() );
	if ( $pst && 'AB' !== $prov ) {
		$order->update_meta_data( '_oneam_pst_number', $pst );
	}
	$order->update_meta_data( '_oneam_excise_stamp', oneam_stamp_label( $prov ) );
}
add_action( 'woocommerce_checkout_create_order', 'oneam_order_add_business_meta', 20 );
add_action( 'woocommerce_store_api_checkout_update_order_meta', 'oneam_order_add_business_meta', 20 );

// 관리자 주문 화면
add_action(
	'woocommerce_admin_order_data_after_billing_address',
	function ( $order ) {
		$pst = $order->get_meta( '_oneam_pst_number' );
		echo '<p><strong>PST exemption no.:</strong> ' . esc_html( $pst ?: '—' ) . '<br><strong>Excise stamp:</strong> ' . esc_html( $order->get_meta( '_oneam_excise_stamp' ) ?: '—' ) . '</p>';
	}
);

// 주문 메일: PST 번호 · 결제 조건
add_filter(
	'woocommerce_email_order_meta_fields',
	function ( $fields, $sent_to_admin, $order ) {
		if ( $order->get_meta( '_oneam_pst_number' ) ) {
			$fields['oneam_pst'] = array( 'label' => 'PST exemption no.', 'value' => $order->get_meta( '_oneam_pst_number' ) );
		}
		if ( $order->get_meta( '_oneam_excise_stamp' ) ) {
			$fields['oneam_stamp'] = array( 'label' => 'Excise stamp', 'value' => $order->get_meta( '_oneam_excise_stamp' ) );
		}
		if ( 'bacs' === $order->get_payment_method() ) {
			$fields['oneam_terms'] = array( 'label' => 'Payment terms', 'value' => oneam_payment_terms() );
		}
		return $fields;
	},
	10,
	3
);

// 메일 아래: 판매자 법인명 · 주소 · GST 번호
add_filter(
	'woocommerce_email_footer_text',
	function ( $text ) {
		$lines = array_filter( array( oneam_opt( 'oneam_company' ), str_replace( "\n", ', ', trim( (string) oneam_opt( 'oneam_address' ) ) ), oneam_opt( 'oneam_phone' ), oneam_gst_line() ) );
		return trim( $text . "\n" . implode( ' · ', $lines ) );
	}
);

// PDF Invoices & Packing Slips (WP Overnight) 플러그인을 쓰면 인보이스에 자동 표시
add_action(
	'wpo_wcpdf_after_shop_address',
	function ( $type ) {
		if ( 'invoice' === $type && oneam_gst_line() ) {
			echo '<div class="oneam-gst">' . esc_html( oneam_gst_line() ) . '</div>';
		}
	}
);
add_action(
	'wpo_wcpdf_after_order_data',
	function ( $type, $order ) {
		if ( 'invoice' !== $type || ! $order ) {
			return;
		}
		if ( $order->get_meta( '_oneam_pst_number' ) ) {
			echo '<tr class="oneam-pst"><th>PST exemption no.:</th><td>' . esc_html( $order->get_meta( '_oneam_pst_number' ) ) . '</td></tr>';
		}
		if ( $order->get_meta( '_oneam_excise_stamp' ) ) {
			echo '<tr class="oneam-stamp"><th>Excise stamp:</th><td>' . esc_html( $order->get_meta( '_oneam_excise_stamp' ) ) . '</td></tr>';
		}
		echo '<tr class="oneam-terms"><th>Payment terms:</th><td>' . esc_html( oneam_payment_terms() ) . '</td></tr>';
	},
	10,
	2
);

/** 출고 · 배송 소요일 한 문장 (출고 = 결제 후 발송까지, 배송 = 발송 후 도착까지) */
function oneam_shipping_time_sentence() {
	return 'Orders ship within ' . oneam_opt( 'oneam_dispatch_time' ) . ' of payment. Delivery typically takes ' . oneam_opt( 'oneam_transit_time' ) . ' after shipping, to stores in ' . oneam_regions_phrase() . '.';
}

/** 배송비 한 문장 */
function oneam_shipping_rates_sentence() {
	return trim( (string) oneam_opt( 'oneam_shipping_rates' ) ) ?: 'Shipping charges, if any, are shown at checkout before you pay.';
}

/* ---------------------------------------------------------------------------
 * 미입금 e-Transfer 주문 자동 취소 (약관 5조: 주문 후 N영업일)
 * ------------------------------------------------------------------------- */
function oneam_unpaid_note() {
	$days = (int) oneam_opt( 'oneam_pay_due_days' ) ?: 2;
	return 'Please pay within ' . $days . ' business ' . ( 1 === $days ? 'day' : 'days' ) . '. Unpaid orders are cancelled automatically after that.';
}

/** $from 시각 기준 N영업일(토·일 제외) 뒤 */
function oneam_add_business_days( $from, $days ) {
	$t = $from;
	while ( $days > 0 ) {
		$t += DAY_IN_SECONDS;
		if ( (int) wp_date( 'N', $t ) < 6 ) {
			--$days;
		}
	}
	return $t;
}

add_action(
	'init',
	function () {
		if ( ! wp_next_scheduled( 'oneam_cancel_unpaid' ) ) {
			wp_schedule_event( time() + HOUR_IN_SECONDS, 'hourly', 'oneam_cancel_unpaid' );
		}
	}
);
add_action(
	'oneam_cancel_unpaid',
	function () {
		if ( ! function_exists( 'wc_get_orders' ) ) {
			return;
		}
		$days   = (int) oneam_opt( 'oneam_pay_due_days' ) ?: 2;
		$orders = wc_get_orders(
			array(
				'status'         => array( 'on-hold', 'pending' ),
				'payment_method' => 'bacs',
				'date_created'   => '<' . ( time() - DAY_IN_SECONDS ),
				'limit'          => 50,
			)
		);
		foreach ( $orders as $order ) {
			$created = $order->get_date_created();
			if ( $created && time() > oneam_add_business_days( $created->getTimestamp(), $days ) ) {
				$order->update_status( 'cancelled', sprintf( 'Unpaid: Interac e-Transfer not received within %d business days.', $days ) );
			}
		}
	}
);

/** 주문이 확정된 뒤(주소가 최종 저장된 뒤) 상품마다 주 · 스탬프를 다시 기록 */
function oneam_finalize_order_stamps( $order ) {
	$order = is_numeric( $order ) ? wc_get_order( $order ) : $order;
	if ( ! $order ) {
		return;
	}
	$prov = strtoupper( $order->get_shipping_state() ?: $order->get_billing_state() );
	foreach ( $order->get_items() as $item ) {
		$product = $item->get_product();
		if ( $product && oneam_province_prices( $product ) ) {
			$item->update_meta_data( 'Province / excise stamp', $prov );
			$item->update_meta_data( 'Excise stamp', oneam_stamp_label( $prov ) );
			$item->save();
		}
	}
	$order->update_meta_data( '_oneam_excise_stamp', oneam_stamp_label( $prov ) );
	if ( 'AB' === $prov ) {
		$order->delete_meta_data( '_oneam_pst_number' );
	}
	$order->save();
}
add_action( 'woocommerce_store_api_checkout_order_processed', 'oneam_finalize_order_stamps' );
add_action( 'woocommerce_checkout_order_processed', 'oneam_finalize_order_stamps', 20 );
