<?php
/**
 * 1AM 컴플라이언스 업데이트 (테마를 올린 뒤 관리자 화면에 처음 들어갈 때 한 번 자동 실행)
 *
 * - 없는 정책 페이지 만들기 (Shipping Policy 404 해결)
 * - Terms · Return · Shipping · Privacy 페이지를 새 글로 교체 (예전 글은 Revisions 에 남음)
 * - FAQ 를 새 답변으로 교체 (카드 결제 문구 · 판매 지역 · 스탬프 · 배송 기간)
 * - 저장된 페이지 글 · Elementor 데이터 속 옛 문구 바꾸기 ("across Canada" 등)
 * - /privacy-2/ → /privacy-policy/ 주소 정리 (옛 주소는 301 로 이동)
 * 결과는 Pages 화면 위 알림으로 보여줌. 다시 실행: Pages 화면 알림의 "Run again".
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'ONEAM_COMPLIANCE_VERSION', '2026-10-24-client-c' );

/** 옛 문구 → 새 문구 (페이지 글 · Elementor 데이터) */
function oneam_migrate_replacements() {
	$regions = oneam_regions_phrase();
	$brand   = '1AM is a Canadian vape brand serving licensed retailers across Canada. We do not sell directly to consumers.';
	$list    = array(
		// 대표님 요청 (2026-10-20): "wholesale only brand" 표현 없애기 · Apply for wholesale → Become a Retailer
		'1AM is a wholesale-only vape brand for Canadian retail stores. We supply proven products and work only with approved retail partners.' => $brand,
		'1AM is a vape brand built for adult-only vape retailers in ' . $regions . '.' => '1AM is a Canadian vape brand serving licensed retailers across Canada.',
		'1AM is a vape brand built for adult-only vape retailers across Canada.' => '1AM is a Canadian vape brand serving licensed retailers across Canada.',
		'A wholesale brand built for *Canadian retailers.*' => 'Built for *licensed retailers.*',
		'A wholesale-only brand for approved Canadian retailers.' => 'A Canadian vape brand serving licensed retailers. We do not sell directly to consumers.',
		'Slim HYBRID · 15 flavours · Wholesale only' => 'Slim HYBRID · 15 flavours · For licensed retailers',
		'Apply for a wholesale account' => 'Become a retailer',
		'Apply for wholesale'           => 'Become a Retailer',
		'Apply for Wholesale'           => 'Become a Retailer',
		'Wholesale Sign up'             => 'Become a Retailer',
		'Wholesale Sign Up'             => 'Become a Retailer',
		'Wholesale Signup'              => 'Become a Retailer',
		'<strong>Wholesale only.</strong>' => '<strong>Licensed retailers only.</strong>',
		'Wholesale only — available to approved Canadian retailers.' => 'For licensed retailers only — we do not sell directly to consumers.',
		'"title":"Wholesale only"'      => '"title":"Licensed retailers only"',
		// 대표님 요청 (2026-10-24)
		'Run an adult-only vape store in ' . $regions . '?' => 'Own or operate a vape store?',
		'Run an adult-only vape store in Canada?'           => 'Own or operate a vape store?',
		'Become a retailer. Once your business is verified, you can see pricing and order online.' => 'Apply for a 1AM retailer account. Once your business is verified and approved, you can view pricing and place orders online.',
		'Apply for a wholesale account. Once your business is verified, you can see pricing and order online.' => 'Apply for a 1AM retailer account. Once your business is verified and approved, you can view pricing and place orders online.',
		'before it can see pricing or place orders.' => 'before it can view pricing or place orders.',
		'Products are supplied for sale in line with Canadian excise stamp and provincial requirements.' => 'Our products are supplied in accordance with applicable Canadian excise stamping requirements and provincial regulations.',
		'Submit the retail account application with your business information and documents confirming an authorized adult-only vape store.' => 'Complete the retailer account application to get started.',
		'"title":"Get verified"' => '"title":"Get approved"',
		'Our team reviews your application, usually within 1–2 business days, and emails you once your account is approved.' => 'Most applications are reviewed within 1–2 business days. We’ll email you once your account is approved.',
		'Log in to see wholesale pricing. Choose products with the correct excise stamps for your province and confirm compliance at checkout.' => 'Log in to access wholesale pricing and place orders. Ensure each product has the correct excise stamp for its intended province of sale.',
		"Log in to see wholesale pricing. Choose your store's province (we ship only products stamped for it) and confirm compliance at checkout." => 'Log in to access wholesale pricing and place orders. Ensure each product has the correct excise stamp for its intended province of sale.',
		'Apply, get verified, and order at wholesale prices as soon as you are approved.' => 'Apply, get approved, and begin placing orders.',
		"We ship only products stamped for your store's province." => 'All products carry the correct excise stamp for their intended province of sale.',
		'Choose products with the correct excise stamps for your retail sales jurisdiction.' => 'All products carry the correct excise stamp for their intended province of sale.',
		'Unsure which excise stamp you need? Ask us before ordering.' => 'Have questions or need assistance? Feel free to contact us at ' . oneam_opt( 'oneam_email' ) . '.',
		'Stores in more than one province? Ask us to add them to your account.' => 'Have questions or need assistance? Feel free to contact us at ' . oneam_opt( 'oneam_email' ) . '.',
		'Damaged or incorrect items: send your order number and photos.' => 'We typically respond within 1–2 business days.',
		"We typically respond within 1–2 business days.\n" . oneam_opt( 'oneam_email' ) => 'We typically respond within 1–2 business days.',
		'Minimum order quantities may vary by product.' => function_exists( 'oneam_min_order_line' ) && oneam_min_order_line() ? oneam_min_order_line() : 'Minimum order quantities may vary by product.',
		'HYBRID Max'    => '20ml MAX',
		'HYBRID Refill' => '2+20ml MAX',
		'authorized adult-only vape' => 'authorized vape',
		'an adult-only vape' => 'a vape',
		'adult-only vape' => 'vape',
		'Run an vape store' => 'Own or operate a vape store',
		'an vape' => 'a vape',
		'Retailers are responsible for selecting products with the appropriate excise stamps for their intended sales jurisdiction.' => 'All products carry the correct excise stamp for their intended province of sale.',
		'Choose products with the correct excise stamps for your province and confirm compliance at checkout.' => "Choose your store's province (we ship only products stamped for it) and confirm compliance at checkout.",
		'Choose products with the correct excise stamps for your retail sales jurisdiction.' => 'All products carry the correct excise stamp for their intended province of sale.',
		'typically arriving in 1–5 business days.'  => 'typically arriving ' . oneam_opt( 'oneam_transit_time' ) . ' after shipping.',
	);
	if ( ! oneam_opt( 'oneam_processor' ) ) {
		$list['We accept Interac e-Transfer, Visa, Mastercard, and American Express.'] = 'We accept Interac e-Transfer.';
		$list['We accept Interac e-Transfer, Visa, Mastercard and American Express.']  = 'We accept Interac e-Transfer.';
		$list['Interac e-Transfer · Visa · Mastercard · American Express']             = 'Interac e-Transfer';
		$list['Interac e-Transfer, Visa, Mastercard, and American Express']            = 'Interac e-Transfer';
		$list['Pay by Interac e-Transfer or card.']                                    = 'Pay by Interac e-Transfer.';
	}
	return $list;
}

/** 문자열 바꾸기 (Elementor JSON 의 – 같은 형태도 함께) */
function oneam_migrate_text( $text, $json = false ) {
	foreach ( oneam_migrate_replacements() as $old => $new ) {
		$text = str_replace( $old, $new, $text );
		if ( $json ) {
			// Elementor 데이터: 특수문자가 \u2013 처럼 저장된 경우와 그대로 저장된 경우 둘 다
			foreach ( array( 0, JSON_UNESCAPED_UNICODE ) as $flag ) {
				$o    = substr( wp_json_encode( $old, $flag ), 1, -1 );
				$n    = substr( wp_json_encode( $new, $flag ), 1, -1 );
				$text = str_replace( $o, $n, $text );
			}
		}
	}
	return $text;
}

/** 옛 주소 → 새 페이지 (301) */
function oneam_slug_redirects() {
	return (array) get_option( 'oneam_slug_redirects', array() );
}

/** 대표님이 준 회사 정보 (한 번만 — 이후 사용자 정의하기에서 바꾼 값은 덮어쓰지 않음) */
function oneam_apply_client_info() {
	if ( get_option( 'oneam_client_info_v' ) ) {
		return array();
	}
	$report = array();
	$mods   = array(
		'oneam_company'        => 'VANSKAI TRADING LTD.',
		'oneam_gst_number'     => '767692510 RT0001',
		'oneam_licence'        => '52-VAL-288',
		'oneam_dispatch_time'  => '1–3 business days',
		'oneam_shipping_rates' => 'Shipping is free.',
		'oneam_address'        => '', // 주거지 주소라 사이트에 표시하지 않음 (면허 번호로 대신)
		'oneam_phone'          => '',
		'oneam_signup_url'     => '/become-a-retailer/',
	);
	foreach ( $mods as $k => $v ) {
		set_theme_mod( $k, $v );
	}
	foreach ( array( 'oneam_about_text', 'oneam_about_title', 'oneam_intro_text' ) as $k ) {
		$cur = get_theme_mod( $k );
		if ( is_string( $cur ) && $cur !== oneam_migrate_text( $cur ) ) {
			set_theme_mod( $k, oneam_migrate_text( $cur ) );
		}
	}
	$report[] = 'Company info set: VANSKAI TRADING LTD., licence 52-VAL-288, GST 767692510 RT0001, ships 1–3 business days after payment, free shipping. Address and phone hidden.';

	// 가입 페이지 → Become a Retailer (/become-a-retailer/, 옛 주소는 301)
	$sid = function_exists( 'oneam_signup_page_id' ) ? oneam_signup_page_id() : 0;
	if ( $sid ) {
		$old       = get_post_field( 'post_name', $sid );
		$redirects = oneam_slug_redirects();
		if ( 'become-a-retailer' !== $old ) {
			$redirects[ $old ] = 'become-a-retailer';
		}
		$redirects['wholesale-signup']  = 'become-a-retailer';
		$redirects['wholesale-sign-up'] = 'become-a-retailer';
		unset( $redirects['become-a-retailer'] );
		update_option( 'oneam_slug_redirects', $redirects, false );
		wp_update_post( array( 'ID' => $sid, 'post_title' => 'Become a Retailer', 'post_name' => 'become-a-retailer' ) );
		$report[] = 'Sign-up page renamed to “Become a Retailer” (/become-a-retailer/); /' . $old . '/ redirects there.';
	}

	// 맛 분류: Lemon Lime Ice · Double Birch Ice → Classic Ice
	if ( function_exists( 'wc_get_products' ) ) {
		foreach ( wc_get_products( array( 'type' => 'variation', 'limit' => -1, 'status' => array( 'publish', 'private' ) ) ) as $v ) {
			$name = strtolower( implode( ' ', $v->get_attributes() ) );
			if ( ( false !== strpos( $name, 'lemon lime' ) || false !== strpos( $name, 'double birch' ) ) && $v->get_meta( '_oneam_cat' ) && 'classic-ice' !== $v->get_meta( '_oneam_cat' ) ) {
				$v->update_meta_data( '_oneam_cat', 'classic-ice' );
				$v->save();
			}
		}
		// Slim HYBRID: 1 carton = 10 pcs
		$slim = get_page_by_path( 'slim-hybrid', OBJECT, 'product' );
		$p    = $slim ? wc_get_product( $slim->ID ) : null;
		if ( $p && ! $p->get_meta( '_oneam_pack' ) ) {
			$p->update_meta_data( '_oneam_pack', 10 );
			$p->save();
			$report[] = 'Slim HYBRID: 10 pcs per carton (change per product: Products › Edit › Pieces per carton).';
		}
	}

	// 무료 배송: 배송 방법이 없는 배송 구역에 Free shipping 추가
	if ( class_exists( 'WC_Shipping_Zones' ) ) {
		foreach ( WC_Shipping_Zones::get_zones() as $z ) {
			if ( empty( $z['shipping_methods'] ) ) {
				$zone = new WC_Shipping_Zone( $z['id'] );
				$zone->add_shipping_method( 'free_shipping' );
				$report[] = 'Free shipping added to shipping zone “' . $z['zone_name'] . '”.';
			}
		}
	}
	update_option( 'oneam_client_info_v', '2026-10-20' );
	return $report;
}

/** 대표님 피드백 2차 (2026-10-24) — 한 번만 */
function oneam_apply_client_round2() {
	if ( '2026-10-24' === get_option( 'oneam_client_round2' ) ) {
		return array();
	}
	$report = array();
	set_theme_mod( 'oneam_etransfer', '' ); // e-Transfer 도 손님에게 보이는 sales@1amvape.com 으로
	set_theme_mod( 'oneam_min_order', '1000' );
	set_theme_mod( 'oneam_shipping_rates', 'Free ground shipping on orders of $1,000 CAD or more before tax.' );
	foreach ( array( 'oneam_cta_text', 'oneam_shop_regions', 'oneam_shop_compliance', 'oneam_about_text' ) as $k ) {
		$cur = get_theme_mod( $k );
		if ( is_string( $cur ) && $cur !== oneam_migrate_text( $cur ) ) {
			set_theme_mod( $k, oneam_migrate_text( $cur ) );
		}
	}
	$report[] = 'Customer-facing email set to ' . oneam_opt( 'oneam_email' ) . ' (e-Transfer too). Minimum online order $1,000; free shipping from $1,000.';
	if ( function_exists( 'oneam_setup_gst_rates' ) && oneam_setup_gst_rates() ) {
		$report[] = 'Taxes switched on: GST 5% is now added at checkout (WooCommerce › Settings › Tax).';
	}
	// 출시 예정 제품 이름
	foreach ( array( 'hybrid-max' => '20ml MAX', 'hybrid-refill' => '2+20ml MAX' ) as $slug => $name ) {
		$post = get_page_by_path( $slug, OBJECT, 'product' );
		if ( $post && $post->post_title !== $name ) {
			wp_update_post( array( 'ID' => $post->ID, 'post_title' => $name ) );
			$report[] = 'Product renamed: ' . $name . '.';
		}
	}
	// 가입 때 넣은 BN → 결제 화면 BN 칸에 미리 채움
	foreach ( get_users( array( 'meta_key' => 'oneam_business_number', 'fields' => 'ID' ) ) as $uid ) { // phpcs:ignore WordPress.DB.SlowDBQuery
		$bn = get_user_meta( $uid, 'oneam_business_number', true );
		if ( $bn && ! get_user_meta( $uid, '_wc_other/oneam/business-number', true ) ) {
			update_user_meta( $uid, '_wc_other/oneam/business-number', strtoupper( preg_replace( '/[\s-]/', '', $bn ) ) );
		}
	}
	update_option( 'oneam_client_round2', '2026-10-24' );
	return $report;
}

function oneam_run_compliance_migration() {
	$report = array_merge( oneam_apply_client_info(), oneam_apply_client_round2() );
	update_option( 'oneam_policy_updated', wp_date( 'F j, Y' ) );
	if ( ! get_theme_mod( 'oneam_terms_effective' ) ) {
		set_theme_mod( 'oneam_terms_effective', wp_date( 'F j, Y' ) );
		$report[] = 'Terms effective date set to today (change it in 1AM Settings > Business & legal).';
	}

	// 1) Privacy 주소 정리: /privacy-2/ 등 → /privacy-policy/
	$privacy = get_page_by_path( 'privacy-policy' );
	$current = (int) get_option( 'wp_page_for_privacy_policy' );
	$old     = $current ? get_post( $current ) : null;
	if ( ! $old ) {
		$old = get_page_by_path( 'privacy-2' );
	}
	$redirects = oneam_slug_redirects();
	if ( $old && 'privacy-policy' !== $old->post_name ) {
		$redirects[ $old->post_name ] = 'privacy-policy';
		if ( ! $privacy ) {
			wp_update_post( array( 'ID' => $old->ID, 'post_name' => 'privacy-policy', 'post_status' => 'publish' ) );
			$report[] = 'Privacy Policy moved from /' . $old->post_name . '/ to /privacy-policy/ (the old address redirects).';
		} else {
			wp_update_post( array( 'ID' => $old->ID, 'post_status' => 'draft' ) );
			$report[] = 'Duplicate privacy page /' . $old->post_name . '/ set to draft; it now redirects to /privacy-policy/.';
		}
	}
	update_option( 'oneam_slug_redirects', $redirects, false );

	// 2) 없는 페이지 만들기 + 3) 정책 페이지 새 글
	foreach ( oneam_run_setup_pages( false, false ) as $line ) {
		if ( false !== strpos( $line, 'created' ) ) {
			$report[] = $line;
		}
	}
	foreach ( oneam_run_setup_pages( false, true ) as $line ) {
		$report[] = $line;
	}

	// 4) FAQ 새 답변
	$faq = get_page_by_path( 'faq' );
	if ( $faq ) {
		if ( 'builder' === get_post_meta( $faq->ID, '_elementor_edit_mode', true ) && function_exists( 'oneam_fill_page' ) ) {
			oneam_fill_page( $faq->ID, 'faq' );
		} else {
			$pages = oneam_setup_pages();
			wp_update_post( wp_slash( array( 'ID' => $faq->ID, 'post_content' => $pages['faq']['content'] ) ) );
		}
		$report[] = 'FAQ: answers updated (payment, provinces, excise stamps, shipping times).';
	}

	// 5) 저장된 글 · Elementor 데이터의 옛 문구
	$changed = array();
	$ids     = get_posts( array( 'post_type' => array( 'page', 'elementor_library', 'product' ), 'post_status' => array( 'publish', 'draft', 'private' ), 'numberposts' => -1, 'fields' => 'ids' ) );
	foreach ( $ids as $id ) {
		$post = get_post( $id );
		$new  = oneam_migrate_text( $post->post_content );
		$exc  = oneam_migrate_text( $post->post_excerpt );
		if ( $new !== $post->post_content || $exc !== $post->post_excerpt ) {
			wp_update_post( wp_slash( array( 'ID' => $id, 'post_content' => $new, 'post_excerpt' => $exc ) ) );
			$changed[ $id ] = $post->post_title;
		}
		$data = get_post_meta( $id, '_elementor_data', true );
		if ( is_string( $data ) && '' !== $data ) {
			$fixed = oneam_migrate_text( $data, true );
			if ( $fixed !== $data && null !== json_decode( $fixed ) ) {
				update_post_meta( $id, '_elementor_data', wp_slash( $fixed ) );
				delete_post_meta( $id, '_elementor_element_cache' );
				delete_post_meta( $id, '_elementor_css' );
				$changed[ $id ] = $post->post_title;
			}
		}
	}
	if ( $changed ) {
		$report[] = 'Old wording fixed on: ' . implode( ', ', array_unique( $changed ) ) . '.';
	}
	if ( class_exists( '\Elementor\Plugin' ) && isset( \Elementor\Plugin::$instance->files_manager ) ) {
		\Elementor\Plugin::$instance->files_manager->clear_cache();
	}

	// 6) 결제 화면 문구 · e-Transfer 안내
	if ( function_exists( 'oneam_setup_checkout_terms' ) ) {
		oneam_setup_checkout_terms();
	}
	if ( function_exists( 'oneam_setup_etransfer' ) ) {
		oneam_setup_etransfer();
	}
	if ( 'yes' === get_option( 'woocommerce_coming_soon' ) ) {
		$report[] = 'Note: WooCommerce "Coming soon" mode is on (WooCommerce > Settings > Site visibility). Product pages now use the 1AM layout; switch the store to "Live" when you launch.';
	}

	update_option( 'oneam_compliance_report', $report, false );
	update_option( 'oneam_compliance_done', ONEAM_COMPLIANCE_VERSION );
	return $report;
}

add_action(
	'admin_init',
	function () {
		if ( ONEAM_COMPLIANCE_VERSION === get_option( 'oneam_compliance_done' ) || ! current_user_can( 'manage_options' ) || wp_doing_ajax() ) {
			return;
		}
		oneam_run_compliance_migration();
	},
	30
);

add_action(
	'admin_post_oneam_compliance_rerun',
	function () {
		check_admin_referer( 'oneam_compliance_rerun' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Not allowed.' );
		}
		oneam_run_compliance_migration();
		delete_option( 'oneam_compliance_seen' );
		wp_safe_redirect( admin_url( 'edit.php?post_type=page' ) );
		exit;
	}
);

add_action(
	'admin_notices',
	function () {
		$report = get_option( 'oneam_compliance_report' );
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( ! $report || ! current_user_can( 'manage_options' ) || ! $screen || ! in_array( $screen->id, array( 'dashboard', 'edit-page' ), true ) ) {
			return;
		}
		$again = wp_nonce_url( admin_url( 'admin-post.php?action=oneam_compliance_rerun' ), 'oneam_compliance_rerun' );
		echo '<div class="notice notice-info"><p><strong>1AM compliance update applied.</strong> Previous page text is kept in each page’s Revisions.</p><ul style="list-style:disc;margin-left:20px">';
		foreach ( (array) $report as $line ) {
			echo '<li>' . esc_html( $line ) . '</li>';
		}
		echo '</ul><p>Values still to confirm with the owner are in <a href="' . esc_url( admin_url( 'customize.php?autofocus[section]=oneam_business' ) ) . '">Customize › 1AM Settings › Business &amp; legal</a>. After changing them, <a href="' . esc_url( $again ) . '">run the update again</a> to refresh the policy pages.</p></div>';
	}
);

// 옛 주소 → 새 주소 (예: /privacy-2/ → /privacy-policy/)
add_action(
	'template_redirect',
	function () {
		$map = oneam_slug_redirects();
		if ( ! $map ) {
			return;
		}
		$path = trim( (string) wp_parse_url( isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : '', PHP_URL_PATH ), '/' ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		$home = trim( (string) wp_parse_url( home_url(), PHP_URL_PATH ), '/' );
		if ( $home && 0 === strpos( $path, $home ) ) {
			$path = trim( substr( $path, strlen( $home ) ), '/' );
		}
		if ( isset( $map[ $path ] ) ) {
			$to = get_page_by_path( $map[ $path ] );
			if ( $to && 'publish' === $to->post_status ) {
				wp_safe_redirect( get_permalink( $to ), 301 );
				exit;
			}
		}
	},
	1
);

// 지금 사이트에서 돌아가는 테마 버전 표시 (관리자 대시보드 · 페이지 소스) — 업로드가 제대로 됐는지 확인용
add_action(
	'admin_notices',
	function () {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( $screen && 'dashboard' === $screen->id && current_user_can( 'manage_options' ) ) {
			echo '<div class="notice notice-success"><p><strong>1AM Vivid theme v' . esc_html( ONEAM_VERSION ) . '</strong> is active. If the site still looks old, clear the cache (GoDaddy: "Flush Cache" in the top admin bar or in Managed WordPress settings) and reload with Cmd/Ctrl + Shift + R.</p></div>';
		}
	}
);
add_action( 'wp_head', function () { echo "<!-- 1AM Vivid v" . esc_html( ONEAM_VERSION ) . " -->\n"; }, 0 );
