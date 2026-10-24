<?php
/**
 * Appearance > Customize > 1AM Settings
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function oneam_defaults() {
	return array(
		// Confirm the final warning wording against Health Canada requirements.
		'oneam_topbar'        => 'WARNING: Vaping products contain nicotine, a highly addictive chemical.',
		'oneam_topbar_bg'     => '#0d0b12',
		'oneam_topbar_fg'     => '#ffffff',
		'oneam_intro_title'   => 'Stocked for *retail.*',
		'oneam_intro_text'    => 'Slim HYBRID · 15 flavours · For licensed retailers',
		'oneam_video_mp4'     => '',
		'oneam_video_youtube' => '',
		'oneam_video_poster'  => '',
		'oneam_about_title'   => 'Built for *licensed retailers.*',
		'oneam_about_text'    => '1AM is a Canadian vape brand serving licensed retailers across Canada. We do not sell directly to consumers.',
		'oneam_about_url'     => '/about-us/',
		'oneam_device_title'  => "Slim outside.\n*Loud inside.*",
		'oneam_spec_1_num'    => '15',
		'oneam_spec_1_label'  => 'Flavours',
		'oneam_spec_2_num'    => '2',
		'oneam_spec_2_label'  => 'ml E-liquid',
		'oneam_spec_3_num'    => '3',
		'oneam_spec_3_label'  => 'Product lines',
		'oneam_cta_title'     => 'Stock *1AM.*',
		'oneam_cta_text'      => 'Apply, get approved, and begin placing orders.',
		'oneam_signup_url'    => '/become-a-retailer/',
		'oneam_pending_url'   => '',
		'oneam_faq_url'       => '/faq/',
		'oneam_order_url'     => '/how-to-order/',
		'oneam_warning'       => 'WARNING: Vaping products contain nicotine, a highly addictive chemical. For adults 19+ only.',
		'oneam_company'       => 'VANSKAI TRADING LTD.',
		'oneam_email'         => 'sales@1amvape.com',
		'oneam_phone'         => '',
		'oneam_address'       => '',
		'oneam_payments'      => 'Interac e-Transfer',
		'oneam_processor'     => '',
		'oneam_etransfer'     => '',
		'oneam_shop_img_bg'   => '#ffffff',
		'oneam_shop_regions'    => "All products carry the correct excise stamp for their intended province of sale. Your price and excise stamp follow the store province you choose on the product page or at checkout.\nOrders ship only to the licensed store locations registered on your account. Operating stores in more than one province? Contact us to add them.",
		'oneam_shop_shipping'   => "Ships by Purolator Ground, UPS Ground or Canpar Ground.\nAll orders are final sale, except as required by law. For damaged, defective or incorrect items, send your order number and photos and we will help.",
		'oneam_shop_compliance' => "Sold only to verified, authorized vape retailers — never to consumers.\nRetail sale must follow federal, provincial and local rules, including minimum age (BC 19+, AB 18+, SK 18+), product, display and advertising restrictions.\nDo not alter or remove packaging, health warnings, regulatory markings or excise stamps.\nProduct reviews and testimonials are not published, in line with federal restrictions on promoting vaping products.",
		'oneam_confirm_title'   => 'Excise Stamp & Regulatory Compliance Confirmation',
		'oneam_confirm_text'    => 'I confirm that all vaping products in this order will be sold only in jurisdictions where their respective excise stamps are valid, and that my business will comply with all applicable federal, provincial, and territorial vaping regulations.',
		'oneam_confirm_error'   => 'Please confirm the Excise Stamp & Regulatory Compliance statement before placing your order.',
		'oneam_terms_text'      => 'I have read and agree to the Terms & Conditions, Vaping Product Compliance, Privacy Policy, Shipping Policy and Return Policy.',
		// 사업 · 법적 정보 (🔶 대표님 확정 전까지 기본값 — 1AM Settings > Business & legal 에서 수정)
		'oneam_sales_regions'   => 'BC, AB, SK',
		'oneam_stamp_map'       => "BC: Federal\nSK: Federal\nAB: Alberta",
		'oneam_gst_number'      => '767692510 RT0001',
		'oneam_licence'         => '52-VAL-288',
		'oneam_min_order'       => '1000',
		'oneam_dispatch_time'   => '1–3 business days',
		'oneam_transit_time'    => '1–5 business days',
		'oneam_shipping_rates'  => 'Free ground shipping on orders of $1,000 CAD or more before tax.',
		'oneam_pay_due_days'    => '2',
		'oneam_claim_days'      => '3',
		'oneam_defect_days'     => '30',
		'oneam_risk_transfer'   => 'Risk of loss passes to you when the order is delivered to your store.',
		'oneam_return_shipping' => 'We cover return shipping for items that were damaged in transit, defective or sent in error. We do not accept returns for any other reason.',
		'oneam_terms_effective' => '',
		'oneam_privacy_officer' => 'Privacy Officer',
		'oneam_privacy_email'   => '',
		'oneam_retention'       => 'We keep account and application records while your account is open and for 2 years after it is closed. Order, invoice and tax records are kept for at least 6 years, as required by Canadian tax law.',
		'oneam_hosting'         => 'Our website is hosted by GoDaddy. Your information may be stored or processed on servers located outside Canada, including in the United States, where it may be subject to the laws of that country.',
		'oneam_meta_desc'       => '',
		'oneam_min_age'       => 19,
		'oneam_age_gate'      => true,
	);
}

function oneam_opt( $key ) {
	$d = oneam_defaults();
	$v = get_theme_mod( $key, isset( $d[ $key ] ) ? $d[ $key ] : '' );
	// Elementor 위젯에서 입력한 값이 있으면 그 값이 우선
	$v = oneam_ctx( $key, $v );
	// Site-relative paths ("/faq/") become full URLs.
	if ( is_string( $v ) && preg_match( '/_url$/', $key ) && 0 === strpos( $v, '/' ) ) {
		$v = home_url( $v );
	}
	return $v;
}

/**
 * 섹션별 덮어쓰기 값 (Elementor 위젯 설정). 비어 있으면 기본값.
 */
function oneam_ctx( $key, $default = '' ) {
	if ( isset( $GLOBALS['oneam_ctx'][ $key ] ) ) {
		$v = $GLOBALS['oneam_ctx'][ $key ];
		if ( ( is_string( $v ) && '' !== trim( $v ) ) || ( is_array( $v ) && $v ) ) {
			return $v;
		}
	}
	return $default;
}

/** 섹션 출력 (덮어쓰기 값과 함께) */
function oneam_render_part( $part, $ctx = array(), $args = array() ) {
	$prev               = isset( $GLOBALS['oneam_ctx'] ) ? $GLOBALS['oneam_ctx'] : array();
	$GLOBALS['oneam_ctx'] = $ctx;
	get_template_part( 'template-parts/' . $part, null, $args );
	$GLOBALS['oneam_ctx'] = $prev;
}

add_action(
	'customize_register',
	function ( $wp_customize ) {
		$wp_customize->add_panel( 'oneam', array( 'title' => '1AM Settings', 'priority' => 30 ) );

		$sections = array(
			'oneam_business' => array( 'Business & legal', array(
				'oneam_sales_regions'   => array( 'Sales provinces (codes, comma separated, e.g. BC, AB, SK) — used by sign-up, checkout and policies', 'text' ),
				'oneam_stamp_map'       => array( 'Excise stamp by province (one per line, e.g. AB: Alberta)', 'textarea' ),
				'oneam_gst_number'      => array( 'GST registration number (e.g. 123456789 RT0001) — footer, terms, invoices, emails', 'text' ),
				'oneam_licence'         => array( 'Vaping product licence no. (shown instead of the address)', 'text' ),
				'oneam_min_order'       => array( 'Minimum order amount in CAD before GST (empty = no minimum)', 'text' ),
				'oneam_dispatch_time'   => array( 'Dispatch time — how long until an order ships after payment (e.g. 1–2 business days)', 'text' ),
				'oneam_transit_time'    => array( 'Delivery time after shipping (e.g. 1–5 business days)', 'text' ),
				'oneam_shipping_rates'  => array( 'Shipping charges (one sentence; empty = "shown at checkout before you pay")', 'textarea' ),
				'oneam_pay_due_days'    => array( 'e-Transfer payment due within (business days after the order)', 'text' ),
				'oneam_claim_days'      => array( 'Damaged / incorrect item claims within (business days after delivery)', 'text' ),
				'oneam_defect_days'     => array( 'Defect claims within (days after delivery)', 'text' ),
				'oneam_risk_transfer'   => array( 'Risk of loss sentence', 'textarea' ),
				'oneam_return_shipping' => array( 'Who pays return shipping (one sentence)', 'textarea' ),
				'oneam_terms_effective' => array( 'Terms effective date (e.g. October 20, 2026)', 'text' ),
				'oneam_privacy_officer' => array( 'Privacy Officer — name and title', 'text' ),
				'oneam_privacy_email'   => array( 'Privacy Officer — email (empty = contact email)', 'text' ),
				'oneam_retention'       => array( 'Privacy — how long records are kept', 'textarea' ),
				'oneam_hosting'         => array( 'Privacy — where data is stored (hosting)', 'textarea' ),
				'oneam_meta_desc'       => array( 'Search description for the home page (empty = automatic)', 'textarea' ),
			) ),
			'oneam_checkout' => array( 'Checkout confirmation', array(
				'oneam_etransfer'     => array( 'Interac e-Transfer email (customers send payment here)', 'text' ),
				'oneam_confirm_title' => array( 'Required confirmation — title', 'text' ),
				'oneam_confirm_text'  => array( 'Required confirmation — checkbox text', 'textarea' ),
				'oneam_confirm_error' => array( 'Message if not ticked', 'text' ),
				'oneam_terms_text'    => array( 'Terms agreement text (above Place order)', 'textarea' ),
			) ),
			'oneam_shop'    => array( 'Shop product page', array(
				'oneam_shop_img_bg'     => array( 'Product image background', 'color' ),
				'oneam_shop_regions'    => array( 'Sales regions & excise stamps (one paragraph per line)', 'textarea' ),
				'oneam_shop_shipping'   => array( 'Shipping & returns (one paragraph per line)', 'textarea' ),
				'oneam_shop_compliance' => array( 'Compliance & terms (one point per line)', 'textarea' ),
			) ),
			'oneam_general' => array( 'General / Warnings', array(
				'oneam_topbar'    => array( 'Top warning bar — text', 'textarea' ),
				'oneam_topbar_bg' => array( 'Top warning bar — background colour', 'color' ),
				'oneam_topbar_fg' => array( 'Top warning bar — text colour', 'color' ),
				'oneam_warning' => array( 'Footer warning', 'textarea' ),
				'oneam_company' => array( 'Distributor (company name)', 'text' ),
				'oneam_email'   => array( 'Contact / sales email (also receives new applications)', 'text' ),
				'oneam_phone'   => array( 'Customer service phone (required by card networks)', 'text' ),
				'oneam_address' => array( 'Business address (required by card networks)', 'textarea' ),
				'oneam_payments'  => array( 'Accepted payment methods (e.g. Interac e-Transfer — add cards when Moneris is live)', 'text' ),
				'oneam_processor' => array( 'Card processor, e.g. Moneris (leave empty until card payments are live)', 'text' ),
			) ),
			'oneam_intro'   => array( 'Home video', array(
				'oneam_video_mp4'     => array( 'MP4 video (recommended)', 'upload' ),
				'oneam_video_poster'  => array( 'Poster image', 'upload' ),
				'oneam_video_youtube' => array( 'Or YouTube URL', 'url' ),
				'oneam_intro_title'   => array( 'Headline (one line, *word* = italic)', 'text' ),
				'oneam_intro_text'    => array( 'Supporting text (short)', 'text' ),
			) ),
			'oneam_about'   => array( 'About', array(
				'oneam_about_title' => array( 'Headline (*word* = italic)', 'text' ),
				'oneam_about_text'  => array( 'Text', 'textarea' ),
				'oneam_about_url'   => array( 'About Us link', 'text' ),
			) ),
			'oneam_device'  => array( 'Device / Specs', array(
				'oneam_device_title' => array( 'Headline (line break = new line, *word* = italic)', 'textarea' ),
				'oneam_spec_1_num'   => array( 'Spec 1 number', 'text' ),
				'oneam_spec_1_label' => array( 'Spec 1 label', 'text' ),
				'oneam_spec_2_num'   => array( 'Spec 2 number', 'text' ),
				'oneam_spec_2_label' => array( 'Spec 2 label', 'text' ),
				'oneam_spec_3_num'   => array( 'Spec 3 number', 'text' ),
				'oneam_spec_3_label' => array( 'Spec 3 label', 'text' ),
			) ),
			'oneam_cta'     => array( 'Wholesale / Links', array(
				'oneam_cta_title'   => array( 'Closing headline (*word* = italic)', 'text' ),
				'oneam_cta_text'    => array( 'Closing text', 'textarea' ),
				'oneam_signup_url'  => array( 'Become a Retailer page', 'text' ),
				'oneam_pending_url' => array( 'Pending approval page (blank = My account)', 'text' ),
				'oneam_order_url'   => array( 'How to Order page', 'text' ),
				'oneam_faq_url'     => array( 'FAQ page', 'text' ),
			) ),
		);

		$d = oneam_defaults();
		$p = 10;
		foreach ( $sections as $sid => $sec ) {
			$wp_customize->add_section( $sid, array( 'title' => $sec[0], 'panel' => 'oneam', 'priority' => $p++ ) );
			foreach ( $sec[1] as $key => $ctl ) {
				$sanitize = in_array( $ctl[1], array( 'upload', 'url' ), true ) ? 'esc_url_raw' : ( 'color' === $ctl[1] ? 'sanitize_hex_color' : 'sanitize_textarea_field' );
				$wp_customize->add_setting( $key, array( 'default' => $d[ $key ], 'sanitize_callback' => $sanitize ) );
				if ( 'color' === $ctl[1] ) {
					$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, $key, array( 'label' => $ctl[0], 'section' => $sid ) ) );
				} elseif ( 'upload' === $ctl[1] ) {
					$wp_customize->add_control( new WP_Customize_Upload_Control( $wp_customize, $key, array( 'label' => $ctl[0], 'section' => $sid ) ) );
				} else {
					$wp_customize->add_control( $key, array( 'label' => $ctl[0], 'section' => $sid, 'type' => $ctl[1] ) );
				}
			}
		}

		$wp_customize->add_setting( 'oneam_restrict_provinces', array( 'default' => false, 'sanitize_callback' => 'wp_validate_boolean' ) );
		$wp_customize->add_control( 'oneam_restrict_provinces', array( 'label' => 'Only allow orders to the store provinces registered on each account (off = any sales province can be chosen)', 'section' => 'oneam_business', 'type' => 'checkbox' ) );

		$wp_customize->add_setting( 'oneam_age_gate', array( 'default' => true, 'sanitize_callback' => 'wp_validate_boolean' ) );
		$wp_customize->add_control( 'oneam_age_gate', array( 'label' => 'Show age verification', 'section' => 'oneam_general', 'type' => 'checkbox' ) );

		$wp_customize->add_setting( 'oneam_min_age', array( 'default' => 19, 'sanitize_callback' => 'absint' ) );
		$wp_customize->add_control( 'oneam_min_age', array( 'label' => 'Minimum age', 'section' => 'oneam_general', 'type' => 'number' ) );
	}
);

/** 관리자 바에 "Warning bar" 바로가기 → 사용자 정의하기의 경고 바 문구 · 색 */
add_action(
	'admin_bar_menu',
	function ( $bar ) {
		if ( ! current_user_can( 'customize' ) ) {
			return;
		}
		$bar->add_node(
			array(
				'id'    => 'oneam-warning-bar',
				'title' => 'Warning bar',
				'href'  => admin_url( 'customize.php?autofocus[section]=oneam_general&url=' . rawurlencode( home_url( add_query_arg( array() ) ) ) ),
			)
		);
	},
	90
);
