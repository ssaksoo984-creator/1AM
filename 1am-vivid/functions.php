<?php
/**
 * 1AM Vivid theme functions.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'ONEAM_VERSION', '1.17.3' );

function oneam_asset( $path ) {
	return get_template_directory_uri() . '/assets/' . ltrim( $path, '/' );
}

require get_template_directory() . '/inc/flavors.php';
require get_template_directory() . '/inc/customizer.php';
require get_template_directory() . '/inc/products.php';
require get_template_directory() . '/inc/woo-sync.php';
require get_template_directory() . '/inc/pricing.php';
require get_template_directory() . '/inc/editor.php';
require get_template_directory() . '/inc/wholesale.php';
require get_template_directory() . '/inc/elementor.php';
require get_template_directory() . '/inc/policies.php';
require get_template_directory() . '/inc/setup-pages.php';
require get_template_directory() . '/inc/elementor-pages.php';

add_action(
	'after_setup_theme',
	function () {
		add_theme_support( 'title-tag' );
		add_theme_support( 'post-thumbnails' );
		add_theme_support( 'custom-logo', array( 'flex-width' => true, 'flex-height' => true ) );
		add_theme_support( 'html5', array( 'search-form', 'gallery', 'caption', 'style', 'script' ) );
		register_nav_menus(
			array(
				'primary' => 'Header menu',
				'footer'  => 'Footer menu',
				'legal'   => 'Footer policies menu',
			)
		);
	}
);

add_action(
	'wp_enqueue_scripts',
	function () {
		wp_enqueue_style( 'oneam-main', oneam_asset( 'css/main.css' ), array(), ONEAM_VERSION );

		// 쇼핑몰(장바구니·결제·내 계정)에서는 부드러운 스크롤·커서를 끄고 가볍게
		$shop = oneam_is_shop_area();
		if ( $shop ) {
			wp_enqueue_style( 'oneam-woo', oneam_asset( 'css/woo.css' ), array( 'oneam-main' ), ONEAM_VERSION );
		}

		wp_enqueue_script( 'gsap', oneam_asset( 'vendor/gsap.min.js' ), array(), '3.15.0', true );
		wp_enqueue_script( 'gsap-scrolltrigger', oneam_asset( 'vendor/ScrollTrigger.min.js' ), array( 'gsap' ), '3.15.0', true );
		$deps = array( 'gsap', 'gsap-scrolltrigger' );
		if ( ! $shop ) {
			wp_enqueue_script( 'lenis', oneam_asset( 'vendor/lenis.min.js' ), array(), '1.3.26', true );
			$deps[] = 'lenis';
		}
		wp_enqueue_script( 'oneam-main', oneam_asset( 'js/main.js' ), $deps, ONEAM_VERSION, true );

		wp_enqueue_script( 'oneam-popup', oneam_asset( 'js/popup.js' ), array(), ONEAM_VERSION, true );
		if ( function_exists( 'is_product' ) && is_product() ) {
			wp_enqueue_script( 'oneam-shop', oneam_asset( 'js/shop.js' ), array( 'jquery', 'oneam-popup' ), ONEAM_VERSION, true );
		}
		if ( function_exists( 'is_account_page' ) && is_account_page() && is_user_logged_in() ) {
			wp_enqueue_script( 'oneam-account', oneam_asset( 'js/account.js' ), array(), ONEAM_VERSION, true );
		}
		wp_localize_script(
			'oneam-popup',
			'ONEAM_POP',
			array(
				'addUrl'    => home_url( '/' ),
				'ajax'      => admin_url( 'admin-ajax.php' ),
				'fragments' => class_exists( 'WC_AJAX' ) ? WC_AJAX::get_endpoint( 'get_refreshed_fragments' ) : '',
			)
		);
		wp_localize_script(
			'oneam-main',
			'ONEAM',
			array(
				'ageGate' => (bool) get_theme_mod( 'oneam_age_gate', true ),
				'minAge'  => (int) get_theme_mod( 'oneam_min_age', 19 ),
				'lite'    => $shop,
				'builder' => function_exists( 'oneam_is_builder_preview' ) && oneam_is_builder_preview(),
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

/**
 * 기본 메뉴 구조 (견적서 기준). 외모 > 메뉴에서 만들면 그 메뉴가 우선합니다.
 * Products(1·2·3) / About / How to Order / FAQ  (+ Log in / Apply buttons)
 */
function oneam_menu_items() {
	$products = array();
	foreach ( oneam_get_products() as $p ) {
		$products[] = array( $p['name'] . ( 'soon' === $p['status'] ? ' (coming soon)' : '' ), $p['url'] );
	}
	return array(
		array( 'Products', home_url( '/#products' ), $products ),
		array( 'About', oneam_opt( 'oneam_about_url' ) ),
		array( 'How to Order', oneam_opt( 'oneam_order_url' ) ),
		array( 'FAQ', oneam_opt( 'oneam_faq_url' ) ),
	);
}

function oneam_render_menu( $items ) {
	echo '<ul>';
	foreach ( $items as $it ) {
		echo '<li>';
		printf( '<a href="%s">%s</a>', esc_url( $it[1] ), esc_html( $it[0] ) );
		if ( ! empty( $it[2] ) ) {
			oneam_render_menu( $it[2] );
		}
		echo '</li>';
	}
	echo '</ul>';
}

/** 메인 메뉴 기본값 */
function oneam_fallback_menu() {
	oneam_render_menu( oneam_menu_items() );
}

/** 푸터 메뉴 기본값 (1단계만) */
function oneam_fallback_footer_menu() {
	$flat = array();
	foreach ( oneam_menu_items() as $it ) {
		$flat[] = array( $it[0], $it[1] );
	}
	oneam_render_menu( $flat );
}

/** 정책 메뉴 기본값 */
function oneam_fallback_legal_menu() {
	oneam_render_menu(
		array(
			array( 'Terms & Conditions', home_url( '/terms/' ) ),
			array( 'Vaping Product Compliance', home_url( '/terms/#vape-compliance' ) ),
			array( 'Privacy Policy', function_exists( 'get_privacy_policy_url' ) && get_privacy_policy_url() ? get_privacy_policy_url() : home_url( '/privacy-policy/' ) ),
			array( 'Shipping Policy', home_url( '/shipping-policy/' ) ),
			array( 'Return Policy', home_url( '/return-policy/' ) ),
		)
	);
}

/**
 * Escape text and turn *words* into <em>words</em> (serif italic accent in headlines).
 */
function oneam_rich( $text ) {
	return nl2br( preg_replace( '/\*(.+?)\*/u', '<em>$1</em>', esc_html( $text ) ) );
}

/** 여러 줄 제목 → 줄마다 리빌 애니메이션용 span */
function oneam_lines( $text ) {
	$out = '';
	foreach ( preg_split( '/\r?\n/', trim( (string) $text ) ) as $part ) {
		if ( '' !== trim( $part ) ) {
			$out .= '<span class="line"><span>' . oneam_rich( trim( $part ) ) . '</span></span>';
		}
	}
	return $out;
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
