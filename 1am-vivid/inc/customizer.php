<?php
/**
 * 외모 > 사용자 정의하기 > 1AM 설정
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function oneam_defaults() {
	return array(
		'oneam_hero_kicker'   => 'Slim HYBRID · 15 Flavors',
		'oneam_hero_title'    => "It's always\n1AM somewhere.",
		'oneam_marquee'       => 'STAY UP LATE ✦ SLIM HYBRID ✦ 15 VIVID FLAVORS ✦ ICE COLD ✦',
		'oneam_device_title'  => 'Slim outside. Loud inside.',
		'oneam_cta_title'     => 'Find your 1AM.',
		'oneam_cta_url'       => '#',
		'oneam_warning'       => 'WARNING: This product contains nicotine. Nicotine is an addictive chemical. 본 제품은 성인 전용입니다.',
		'oneam_min_age'       => 19,
		'oneam_age_gate'      => true,
		'oneam_spec_1_num'    => '15',
		'oneam_spec_1_label'  => 'Flavors',
		'oneam_spec_2_num'    => '600',
		'oneam_spec_2_label'  => 'Puffs',
		'oneam_spec_3_num'    => '2',
		'oneam_spec_3_label'  => 'ml E-liquid',
	);
}

function oneam_opt( $key ) {
	$d = oneam_defaults();
	return get_theme_mod( $key, isset( $d[ $key ] ) ? $d[ $key ] : '' );
}

add_action(
	'customize_register',
	function ( $wp_customize ) {
		$wp_customize->add_section( 'oneam', array( 'title' => '1AM 설정', 'priority' => 30 ) );

		$text = array(
			'oneam_hero_kicker'  => '히어로 상단 문구',
			'oneam_hero_title'   => '히어로 타이틀 (줄바꿈 가능)',
			'oneam_marquee'      => '흐르는 띠 문구',
			'oneam_device_title' => '디바이스 섹션 타이틀',
			'oneam_spec_1_num'   => '스펙 1 숫자',
			'oneam_spec_1_label' => '스펙 1 라벨',
			'oneam_spec_2_num'   => '스펙 2 숫자',
			'oneam_spec_2_label' => '스펙 2 라벨',
			'oneam_spec_3_num'   => '스펙 3 숫자',
			'oneam_spec_3_label' => '스펙 3 라벨',
			'oneam_cta_title'    => 'CTA 타이틀',
			'oneam_cta_url'      => 'CTA 링크 (구매처)',
			'oneam_warning'      => '푸터 경고 문구',
		);
		$d    = oneam_defaults();

		foreach ( $text as $key => $label ) {
			$wp_customize->add_setting( $key, array( 'default' => $d[ $key ], 'sanitize_callback' => 'oneam_cta_url' === $key ? 'esc_url_raw' : 'sanitize_textarea_field' ) );
			$wp_customize->add_control( $key, array( 'label' => $label, 'section' => 'oneam', 'type' => in_array( $key, array( 'oneam_hero_title', 'oneam_warning' ), true ) ? 'textarea' : 'text' ) );
		}

		$wp_customize->add_setting( 'oneam_age_gate', array( 'default' => true, 'sanitize_callback' => 'wp_validate_boolean' ) );
		$wp_customize->add_control( 'oneam_age_gate', array( 'label' => '성인 인증 팝업 사용', 'section' => 'oneam', 'type' => 'checkbox' ) );

		$wp_customize->add_setting( 'oneam_min_age', array( 'default' => 19, 'sanitize_callback' => 'absint' ) );
		$wp_customize->add_control( 'oneam_min_age', array( 'label' => '최소 연령', 'section' => 'oneam', 'type' => 'number' ) );
	}
);
