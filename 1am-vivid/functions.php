<?php
/**
 * 1AM Vivid theme functions.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'ONEAM_VERSION', '1.0.0' );

function oneam_asset( $path ) {
	return get_template_directory_uri() . '/assets/' . ltrim( $path, '/' );
}

require get_template_directory() . '/inc/flavors.php';
require get_template_directory() . '/inc/customizer.php';

add_action(
	'after_setup_theme',
	function () {
		add_theme_support( 'title-tag' );
		add_theme_support( 'post-thumbnails' );
		add_theme_support( 'custom-logo', array( 'flex-width' => true, 'flex-height' => true ) );
		add_theme_support( 'html5', array( 'search-form', 'gallery', 'caption', 'style', 'script' ) );
		register_nav_menus(
			array(
				'primary' => '메인 메뉴 (전체화면 메뉴)',
				'footer'  => '푸터 메뉴',
			)
		);
	}
);

add_action(
	'wp_enqueue_scripts',
	function () {
		wp_enqueue_style( 'oneam-main', oneam_asset( 'css/main.css' ), array(), ONEAM_VERSION );

		wp_enqueue_script( 'gsap', oneam_asset( 'vendor/gsap.min.js' ), array(), '3.15.0', true );
		wp_enqueue_script( 'gsap-scrolltrigger', oneam_asset( 'vendor/ScrollTrigger.min.js' ), array( 'gsap' ), '3.15.0', true );
		wp_enqueue_script( 'lenis', oneam_asset( 'vendor/lenis.min.js' ), array(), '1.3.26', true );
		wp_enqueue_script( 'oneam-main', oneam_asset( 'js/main.js' ), array( 'gsap', 'gsap-scrolltrigger', 'lenis' ), ONEAM_VERSION, true );

		wp_localize_script(
			'oneam-main',
			'ONEAM',
			array(
				'ageGate' => (bool) get_theme_mod( 'oneam_age_gate', true ),
				'minAge'  => (int) get_theme_mod( 'oneam_min_age', 19 ),
			)
		);
	}
);

/** 폰트 미리 불러오기 */
add_action(
	'wp_head',
	function () {
		foreach ( array( 'archivo-var-latin', 'instrument-serif-italic-latin' ) as $font ) {
			printf( '<link rel="preload" href="%s" as="font" type="font/woff2" crossorigin>' . "\n", esc_url( oneam_asset( 'fonts/' . $font . '.woff2' ) ) );
		}
	},
	1
);

/** 로고 출력 (커스텀 로고가 있으면 우선) */
function oneam_logo( $variant = 'black', $class = '' ) {
	$src = oneam_asset( 'img/logo-' . $variant . '.png' );
	if ( 'black' === $variant && has_custom_logo() ) {
		$src = wp_get_attachment_image_url( get_theme_mod( 'custom_logo' ), 'full' );
	}
	printf( '<img class="%s" src="%s" alt="%s" width="700" height="355">', esc_attr( $class ), esc_url( $src ), esc_attr( get_bloginfo( 'name' ) ?: '1AM' ) );
}

/** 메뉴가 없을 때 기본 링크 */
function oneam_fallback_menu() {
	$links = array(
		'#flavors' => 'Flavors',
		'#lab'     => 'Flavor Lab',
		'#device'  => 'Device',
		'#find'    => 'Where to buy',
	);
	echo '<ul>';
	foreach ( $links as $href => $label ) {
		printf( '<li><a href="%s">%s</a></li>', esc_url( home_url( '/' ) . $href ), esc_html( $label ) );
	}
	echo '</ul>';
}

/** 글자 단위로 쪼개서 애니메이션용 span 으로 감싸기 (단어는 줄바꿈되지 않도록 .w 로 묶음) */
function oneam_split( $text ) {
	$out = '';
	$i   = 0;
	foreach ( preg_split( '/\s+/u', trim( $text ) ) as $word ) {
		$out .= '<span class="w">';
		foreach ( preg_split( '//u', $word, -1, PREG_SPLIT_NO_EMPTY ) as $ch ) {
			$out .= sprintf( '<span class="ch" style="--i:%d">%s</span>', $i++, esc_html( $ch ) );
		}
		$out .= '</span> ';
	}
	return trim( $out );
}
