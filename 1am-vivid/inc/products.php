<?php
/**
 * 상품 라인업 + 도매 회원 상태 + WooCommerce 연동.
 *
 * 상품은 WooCommerce 에서만 관리합니다. (WooCommerce 에 상품이 없을 때만 아래 기본 3종을 보여줌)
 * - 메인 "상품 라인업" = WooCommerce 상품 (순서: 상품 목록의 Menu order)
 * - 상품 페이지(공개)   = WooCommerce 상품 페이지 + 맛 슬라이더 / 맛 목록
 * - 쇼핑몰·장바구니·결제 = 승인 회원만
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * 기본 상품 3종.
 * status: available | soon
 */
function oneam_default_products() {
	return array(
		array(
			'name'    => 'Slim HYBRID',
			'slug'    => 'slim-hybrid',
			'tag'     => '2ml Disposable',
			'status'  => 'available',
			'c1'      => '#8A3FFC',
			'c2'      => '#16C75A',
			'img'     => oneam_asset( 'img/flavors/grape-ice.webp' ),
			'desc'    => 'A slim disposable with a crystal-clear body. 15 flavours, ready to stock.',
			'specs'   => array( 'E-liquid' => '2ml', 'Flavours' => '15', 'Type' => 'Disposable' ),
		),
		array(
			'name'    => 'HYBRID Max',
			'slug'    => 'hybrid-max',
			'tag'     => 'Large capacity',
			'status'  => 'soon',
			'c1'      => '#FF2E4D',
			'c2'      => '#FFB321',
			'img'     => '',
			'desc'    => 'A larger-capacity line. In development.',
			'specs'   => array( 'E-liquid' => 'TBA', 'Flavours' => 'TBA', 'Type' => 'Disposable' ),
		),
		array(
			'name'    => 'HYBRID Refill',
			'slug'    => 'hybrid-refill',
			'tag'     => 'Refillable pod',
			'status'  => 'soon',
			'c1'      => '#1E5BFF',
			'c2'      => '#00C2A8',
			'img'     => '',
			'desc'    => 'A refillable pod line. In development.',
			'specs'   => array( 'E-liquid' => 'TBA', 'Flavours' => 'TBA', 'Type' => 'Refill pod' ),
		),
	);
}

/** 모든 상품 (WooCommerce 우선, 없으면 기본 3종) */
function oneam_get_products() {
	static $cache = null;
	if ( null !== $cache ) {
		return $cache;
	}
	$out = function_exists( 'oneam_products_from_woo' ) ? oneam_products_from_woo() : array();
	if ( empty( $out ) ) {
		foreach ( oneam_default_products() as $d ) {
			$d['url'] = home_url( '/#products' );
			$out[]    = $d;
		}
	}
	$cache = apply_filters( 'oneam_products', $out );
	return $cache;
}

/** 제품 이미지 또는 "출시 예정" 실루엣 */
function oneam_product_visual( $p, $class = '' ) {
	if ( ! empty( $p['img'] ) ) {
		printf( '<img class="%s" src="%s" alt="%s" width="246" height="1400" loading="lazy">', esc_attr( $class ), esc_url( $p['img'] ), esc_attr( $p['name'] ) );
		return;
	}
	printf(
		'<div class="ghost %s ghost--%s" role="img" aria-label="%s"><span class="ghost__cap"></span><span class="ghost__body"><span class="ghost__txt">%s</span></span><span class="ghost__base"></span></div>',
		esc_attr( $class ),
		esc_attr( $p['slug'] ),
		esc_attr( $p['name'] . ' — coming soon' ),
		esc_html( $p['name'] )
	);
}

/* ---------------------------------------------------------------------------
 * 도매 회원 상태
 * ------------------------------------------------------------------------- */

/**
 * guest | pending | approved
 *
 * 기본: 사용자 메타 oneam_wholesale_status = approved 이거나 wholesale_customer 역할 / 관리자.
 * 가입 승인 플러그인을 쓰면 'oneam_member_state' 필터로 연결하세요.
 */
function oneam_member_state() {
	if ( ! function_exists( 'is_user_logged_in' ) || ! is_user_logged_in() ) {
		$state = 'guest';
	} else {
		$user  = wp_get_current_user();
		$meta  = get_user_meta( $user->ID, 'oneam_wholesale_status', true );
		$state = ( 'approved' === $meta || in_array( 'wholesale_customer', (array) $user->roles, true ) || user_can( $user, 'manage_woocommerce' ) || user_can( $user, 'manage_options' ) ) ? 'approved' : 'pending';
	}
	return apply_filters( 'oneam_member_state', $state );
}

function oneam_signup_url() {
	return oneam_opt( 'oneam_signup_url' ) ?: home_url( '/wholesale-signup/' );
}

function oneam_login_url() {
	if ( function_exists( 'wc_get_page_permalink' ) ) {
		return wc_get_page_permalink( 'myaccount' );
	}
	return wp_login_url();
}

function oneam_shop_url() {
	if ( function_exists( 'wc_get_page_permalink' ) ) {
		return wc_get_page_permalink( 'shop' );
	}
	return home_url( '/shop/' );
}

/**
 * 회원 상태에 따른 주 버튼 (견적서: 비회원 → 가입 / 승인 대기 → 안내 / 승인 완료 → 쇼핑몰)
 */
function oneam_member_cta( $size = '' ) {
	$state = oneam_member_state();
	$map   = array(
		'guest'    => array( oneam_signup_url(), 'Apply for wholesale' ),
		'pending'  => array( oneam_opt( 'oneam_pending_url' ) ?: oneam_signup_url(), 'Application under review' ),
		'approved' => array( oneam_shop_url(), 'Shop wholesale' ),
	);
	list( $url, $label ) = $map[ $state ];
	printf(
		'<a class="btn btn--solid %s" href="%s" data-magnetic data-state="%s"><span>%s &rarr;</span></a>',
		esc_attr( $size ),
		esc_url( $url ),
		esc_attr( $state ),
		esc_html( $label )
	);
}

/* ---------------------------------------------------------------------------
 * WooCommerce: 비공개 쇼핑몰
 * ------------------------------------------------------------------------- */
add_action(
	'after_setup_theme',
	function () {
		add_theme_support(
			'woocommerce',
			array(
				'thumbnail_image_width' => 400,
				'single_image_width'    => 800,
			)
		);
		add_theme_support( 'wc-product-gallery-zoom' );
		add_theme_support( 'wc-product-gallery-lightbox' );
		add_theme_support( 'wc-product-gallery-slider' );
	}
);

// 제품 사진이 세로로 긴 형태라 상점 썸네일을 정사각형으로 자르지 않음
add_filter(
	'woocommerce_get_image_size_thumbnail',
	function ( $size ) {
		$size['height'] = 0;
		$size['crop']   = 0;
		return $size;
	}
);

// 테마의 <main> 이 이미 있으므로 WooCommerce 기본 감싸개·사이드바는 쓰지 않음
add_action(
	'init',
	function () {
		remove_action( 'woocommerce_before_main_content', 'woocommerce_output_content_wrapper', 10 );
		remove_action( 'woocommerce_after_main_content', 'woocommerce_output_content_wrapper_end', 10 );
		remove_action( 'woocommerce_sidebar', 'woocommerce_get_sidebar', 10 );
	}
);
add_action( 'woocommerce_before_main_content', function () { echo '<div class="shop-wrap">'; }, 10 );
add_action( 'woocommerce_after_main_content', function () { echo '</div>'; }, 10 );

/** WooCommerce 페이지인지 */
function oneam_is_shop_area() {
	return function_exists( 'is_woocommerce' ) && ( is_woocommerce() || is_cart() || is_checkout() || is_account_page() );
}

/** 승인 회원이 아니면 상점/장바구니/결제 → 가입 또는 승인 안내로 이동 */
add_action(
	'template_redirect',
	function () {
		if ( ! function_exists( 'is_woocommerce' ) || ! apply_filters( 'oneam_private_shop', true ) ) {
			return;
		}
		// 상품 페이지는 공개 (가격·장바구니는 승인 회원에게만), 상점 목록·장바구니·결제는 비공개
		if ( ! ( is_shop() || is_product_taxonomy() || is_cart() || is_checkout() ) ) {
			return;
		}
		$state = oneam_member_state();
		if ( 'approved' === $state ) {
			return;
		}
		wp_safe_redirect( 'guest' === $state ? oneam_signup_url() : ( oneam_opt( 'oneam_pending_url' ) ?: oneam_signup_url() ) );
		exit;
	}
);

/** 비공개 쇼핑몰 영역은 검색 노출 제외 (상품 페이지는 노출) */
add_filter(
	'wp_robots',
	function ( $robots ) {
		if ( function_exists( 'is_woocommerce' ) && ( is_shop() || is_product_taxonomy() || is_cart() || is_checkout() || is_account_page() ) ) {
			$robots['noindex']  = true;
			$robots['nofollow'] = true;
		}
		return $robots;
	}
);
