<?php
/**
 * 외모 > 사용자 정의하기 > 1AM 설정
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function oneam_defaults() {
	return array(
		// 상단 경고문 (문구는 Health Canada 기준으로 최종 확인 필요)
		'oneam_topbar'        => 'WARNING: Vaping products contain nicotine, a highly addictive chemical.',
		'oneam_hero_kicker'   => 'Canada · Wholesale only · 19+',
		'oneam_hero_title'    => "Wholesale\nvape supply.",
		'oneam_marquee'       => 'WHOLESALE ONLY ✦ SLIM HYBRID 2ML ✦ 15 FLAVOURS ✦ MAX & REFILL COMING SOON ✦',
		'oneam_video_mp4'     => '',
		'oneam_video_youtube' => '',
		'oneam_video_poster'  => '',
		'oneam_video_title'   => 'See it in motion.',
		'oneam_about_title'   => 'A wholesale brand built for Canadian retailers.',
		'oneam_about_text'    => '1AM은 캐나다 소매점을 위한 도매 전용 베이핑 브랜드입니다. 검증된 제품만 공급하고, 승인된 거래처와만 거래합니다. (회사 소개 문구로 교체해 주세요)',
		'oneam_about_url'     => '/about-us/',
		'oneam_device_title'  => 'Slim outside. Loud inside.',
		'oneam_spec_1_num'    => '15',
		'oneam_spec_1_label'  => 'Flavours',
		'oneam_spec_2_num'    => '2',
		'oneam_spec_2_label'  => 'ml E-liquid',
		'oneam_spec_3_num'    => '3',
		'oneam_spec_3_label'  => 'Product lines',
		'oneam_cta_title'     => 'Stock 1AM.',
		'oneam_cta_text'      => '가입 신청 → 사업자 서류 확인 → 승인 후 도매가로 바로 주문하세요.',
		'oneam_signup_url'    => '/wholesale-signup/',
		'oneam_pending_url'   => '',
		'oneam_faq_url'       => '/faq/',
		'oneam_order_url'     => '/how-to-order/',
		'oneam_warning'       => 'WARNING: Vaping products contain nicotine, a highly addictive chemical. For adults 19+ only. 성인(19+) 전용.',
		'oneam_min_age'       => 19,
		'oneam_age_gate'      => true,
	);
}

function oneam_opt( $key ) {
	$d = oneam_defaults();
	$v = get_theme_mod( $key, isset( $d[ $key ] ) ? $d[ $key ] : '' );
	// 사이트 내부 경로("/faq/")는 전체 URL 로
	if ( is_string( $v ) && preg_match( '/_url$/', $key ) && 0 === strpos( $v, '/' ) ) {
		$v = home_url( $v );
	}
	return $v;
}

add_action(
	'customize_register',
	function ( $wp_customize ) {
		$wp_customize->add_panel( 'oneam', array( 'title' => '1AM 설정', 'priority' => 30 ) );

		$sections = array(
			'oneam_general' => array( '공통 / 경고문', array(
				'oneam_topbar'  => array( '상단 고정 경고문', 'textarea' ),
				'oneam_warning' => array( '푸터 경고 문구', 'textarea' ),
			) ),
			'oneam_hero'    => array( '메인 비주얼', array(
				'oneam_hero_kicker' => array( '상단 작은 문구', 'text' ),
				'oneam_hero_title'  => array( '타이틀 (줄바꿈 가능)', 'textarea' ),
				'oneam_marquee'     => array( '흐르는 띠 문구', 'text' ),
			) ),
			'oneam_video'   => array( '영상', array(
				'oneam_video_mp4'     => array( 'MP4 영상 (업로드 권장)', 'upload' ),
				'oneam_video_poster'  => array( '영상 대표 이미지', 'upload' ),
				'oneam_video_youtube' => array( '또는 YouTube 주소', 'url' ),
				'oneam_video_title'   => array( '영상 위 문구', 'text' ),
			) ),
			'oneam_about'   => array( '회사 소개', array(
				'oneam_about_title' => array( '타이틀', 'text' ),
				'oneam_about_text'  => array( '소개 문구', 'textarea' ),
				'oneam_about_url'   => array( 'About Us 링크', 'text' ),
			) ),
			'oneam_device'  => array( '디바이스 / 스펙', array(
				'oneam_device_title' => array( '타이틀', 'text' ),
				'oneam_spec_1_num'   => array( '스펙 1 숫자', 'text' ),
				'oneam_spec_1_label' => array( '스펙 1 라벨', 'text' ),
				'oneam_spec_2_num'   => array( '스펙 2 숫자', 'text' ),
				'oneam_spec_2_label' => array( '스펙 2 라벨', 'text' ),
				'oneam_spec_3_num'   => array( '스펙 3 숫자', 'text' ),
				'oneam_spec_3_label' => array( '스펙 3 라벨', 'text' ),
			) ),
			'oneam_cta'     => array( '도매 가입 / 링크', array(
				'oneam_cta_title'   => array( '마지막 섹션 타이틀', 'text' ),
				'oneam_cta_text'    => array( '마지막 섹션 문구', 'textarea' ),
				'oneam_signup_url'  => array( '도매 가입 페이지', 'text' ),
				'oneam_pending_url' => array( '승인 대기 안내 페이지 (비우면 내 계정)', 'text' ),
				'oneam_order_url'   => array( '구매 절차 페이지', 'text' ),
				'oneam_faq_url'     => array( 'FAQ 페이지', 'text' ),
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
		$wp_customize->add_control( 'oneam_age_gate', array( 'label' => '성인 인증 팝업 사용', 'section' => 'oneam_general', 'type' => 'checkbox' ) );

		$wp_customize->add_setting( 'oneam_min_age', array( 'default' => 19, 'sanitize_callback' => 'absint' ) );
		$wp_customize->add_control( 'oneam_min_age', array( 'label' => '최소 연령', 'section' => 'oneam_general', 'type' => 'number' ) );
	}
);
