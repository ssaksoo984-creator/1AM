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
		'oneam_intro_text'    => 'Slim HYBRID · 15 flavours · Wholesale only',
		'oneam_video_mp4'     => '',
		'oneam_video_youtube' => '',
		'oneam_video_poster'  => '',
		'oneam_about_title'   => 'A wholesale brand built for *Canadian retailers.*',
		'oneam_about_text'    => '1AM is a wholesale-only vape brand for Canadian retail stores. We supply proven products and work only with approved retail partners.',
		'oneam_about_url'     => '/about-us/',
		'oneam_device_title'  => "Slim outside.\n*Loud inside.*",
		'oneam_spec_1_num'    => '15',
		'oneam_spec_1_label'  => 'Flavours',
		'oneam_spec_2_num'    => '2',
		'oneam_spec_2_label'  => 'ml E-liquid',
		'oneam_spec_3_num'    => '3',
		'oneam_spec_3_label'  => 'Product lines',
		'oneam_cta_title'     => 'Stock *1AM.*',
		'oneam_cta_text'      => 'Apply, get verified, and order at wholesale prices as soon as you are approved.',
		'oneam_signup_url'    => '/wholesale-signup/',
		'oneam_pending_url'   => '',
		'oneam_faq_url'       => '/faq/',
		'oneam_order_url'     => '/how-to-order/',
		'oneam_warning'       => 'WARNING: Vaping products contain nicotine, a highly addictive chemical. For adults 19+ only.',
		'oneam_company'       => 'VanSkai Trading Ltd.',
		'oneam_email'         => 'sales@1amvape.com',
		'oneam_shop_regions'    => "Each carton carries the provincial excise stamp for its sales jurisdiction. Prices and stamps follow your shipping province, which you choose at checkout.\nProducts may only be resold in the province shown on their excise stamp. Unsure which stamp you need? Contact us before ordering.",
		'oneam_shop_shipping'   => "Ships by Purolator Ground, UPS Ground or Canpar Ground. Estimated delivery is 1–5 business days after your order ships.\nAll orders are final sale, except as required by law. For damaged, defective or incorrect items, send your order number and photos and we will help.",
		'oneam_shop_compliance' => "Sold only to verified, authorized adult-only vape retailers in Canada — never to consumers.\nRetail sale must follow federal, provincial and local rules, including minimum age (BC 19+, AB 18+, SK 18+), product, display and advertising restrictions.\nDo not alter or remove packaging, health warnings, regulatory markings or excise stamps.\nProduct reviews and testimonials are not published, in line with federal restrictions on promoting vaping products.",
		'oneam_confirm_title'   => 'Excise Stamp & Regulatory Compliance Confirmation',
		'oneam_confirm_text'    => 'I confirm that all vaping products in this order will be sold only in jurisdictions where their respective excise stamps are valid, and that my business will comply with all applicable federal, provincial, and territorial vaping regulations.',
		'oneam_confirm_error'   => 'Please confirm the Excise Stamp & Regulatory Compliance statement before placing your order.',
		'oneam_terms_text'      => 'By placing this order, you agree to our Terms & Conditions, including Vaping Product Compliance, our Shipping Policy and Return Policy.',
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
			'oneam_checkout' => array( 'Checkout confirmation', array(
				'oneam_confirm_title' => array( 'Required confirmation — title', 'text' ),
				'oneam_confirm_text'  => array( 'Required confirmation — checkbox text', 'textarea' ),
				'oneam_confirm_error' => array( 'Message if not ticked', 'text' ),
				'oneam_terms_text'    => array( 'Terms agreement text (above Place order)', 'textarea' ),
			) ),
			'oneam_shop'    => array( 'Shop product page', array(
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
				'oneam_signup_url'  => array( 'Wholesale sign-up page', 'text' ),
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
