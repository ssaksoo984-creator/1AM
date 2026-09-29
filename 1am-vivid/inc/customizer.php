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
		'oneam_min_age'       => 19,
		'oneam_age_gate'      => true,
	);
}

function oneam_opt( $key ) {
	$d = oneam_defaults();
	$v = get_theme_mod( $key, isset( $d[ $key ] ) ? $d[ $key ] : '' );
	// Site-relative paths ("/faq/") become full URLs.
	if ( is_string( $v ) && preg_match( '/_url$/', $key ) && 0 === strpos( $v, '/' ) ) {
		$v = home_url( $v );
	}
	return $v;
}

add_action(
	'customize_register',
	function ( $wp_customize ) {
		$wp_customize->add_panel( 'oneam', array( 'title' => '1AM Settings', 'priority' => 30 ) );

		$sections = array(
			'oneam_general' => array( 'General / Warnings', array(
				'oneam_topbar'  => array( 'Top warning bar', 'textarea' ),
				'oneam_warning' => array( 'Footer warning', 'textarea' ),
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
				$sanitize = in_array( $ctl[1], array( 'upload', 'url' ), true ) ? 'esc_url_raw' : 'sanitize_textarea_field';
				$wp_customize->add_setting( $key, array( 'default' => $d[ $key ], 'sanitize_callback' => $sanitize ) );
				if ( 'upload' === $ctl[1] ) {
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
