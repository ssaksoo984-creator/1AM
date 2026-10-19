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
			'regions' => array( 'BC', 'AB', 'SK' ),
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
			// 테마가 제공하는 상세 페이지 (/products/slim-hybrid/). 출시 예정은 가입 페이지로.
			$d['url'] = 'soon' === $d['status'] ? oneam_signup_url() : oneam_product_page_url( $d['slug'] );
			$out[]    = $d;
		}
	}
	// 같은 이름(슬러그)의 페이지가 있으면 (Elementor 로 편집하는 상품 페이지) 그 페이지로 연결
	foreach ( $out as $i => $p ) {
		$page = 'soon' !== $p['status'] && function_exists( 'get_page_by_path' ) ? get_page_by_path( $p['slug'] ) : null;
		if ( $page && 'publish' === $page->post_status ) {
			$out[ $i ]['url'] = get_permalink( $page );
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

/**
 * 가입 페이지 찾기: 사용자 정의하기 주소 → 슬러그 wholesale-signup / wholesale-sign-up 등
 * → [oneam_wholesale_signup] 숏코드가 들어간 페이지
 */
function oneam_signup_page_id() {
	static $id = null;
	if ( null !== $id || ! function_exists( 'get_page_by_path' ) ) {
		return (int) $id;
	}
	$id   = 0;
	$path = trim( (string) wp_parse_url( (string) oneam_opt( 'oneam_signup_url' ), PHP_URL_PATH ), '/' );
	foreach ( array_filter( array( $path, 'wholesale-signup', 'wholesale-sign-up', 'wholesale-application', 'apply', 'signup', 'sign-up' ) ) as $slug ) {
		$page = get_page_by_path( $slug );
		if ( $page && 'publish' === $page->post_status ) {
			$id = $page->ID;
			return $id;
		}
	}
	$found = get_posts( array( 'post_type' => 'page', 'post_status' => 'publish', 's' => '[oneam_wholesale_signup', 'fields' => 'ids', 'numberposts' => 1 ) );
	$id    = $found ? (int) $found[0] : 0;
	return $id;
}

function oneam_signup_url() {
	$id = oneam_signup_page_id();
	if ( $id ) {
		return get_permalink( $id );
	}
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

/* ---------------------------------------------------------------------------
 * 로그인 상태 헤더 아이콘: 내 계정 · 장바구니(승인 회원) · 로그아웃
 * ------------------------------------------------------------------------- */
function oneam_icon( $name ) {
	$paths = array(
		'user'   => '<circle cx="12" cy="8" r="4"/><path d="M4 21c0-4.4 3.6-7 8-7s8 2.6 8 7"/>',
		'cart'   => '<path d="M3 4h2l2.4 11.2a2 2 0 0 0 2 1.6h7.7a2 2 0 0 0 2-1.5L21 8H6.2"/><circle cx="10" cy="20" r="1.4"/><circle cx="17" cy="20" r="1.4"/>',
		'logout' => '<path d="M9 4H6a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h3"/><path d="M16 17l5-5-5-5"/><path d="M21 12H9"/>',
		'mail'   => '<rect x="3" y="5" width="18" height="14" rx="2.5"/><path d="M4 7l8 6 8-6"/>',
		'arrow'  => '<path d="M5 12h14"/><path d="M13 6l6 6-6 6"/>',
		'check'  => '<rect x="3" y="3" width="18" height="18" rx="5"/><path d="M8 12.5l2.8 2.8L16.5 9"/>',
		'card'   => '<rect x="2.5" y="5" width="19" height="14" rx="2.5"/><path d="M2.5 10h19"/><path d="M6.5 15h4"/>',
		'truck'  => '<path d="M2.5 6.5h11v9h-11z"/><path d="M13.5 9.5h4l3 3.2v2.8h-7z"/><circle cx="6.5" cy="17.5" r="1.8"/><circle cx="17" cy="17.5" r="1.8"/>',
	);
	return '<svg class="icon icon--' . esc_attr( $name ) . '" viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $paths[ $name ] . '</svg>';
}

function oneam_cart_count() {
	return function_exists( 'WC' ) && WC()->cart ? (int) WC()->cart->get_cart_contents_count() : 0;
}

function oneam_header_icons( $state ) {
	$user = wp_get_current_user();
	echo '<div class="site-nav__icons">';
	printf(
		'<a class="site-nav__icon" href="%s" aria-label="My account" title="My account">%s<span class="site-nav__label">%s</span></a>',
		esc_url( oneam_login_url() ),
		oneam_icon( 'user' ), // phpcs:ignore WordPress.Security.EscapeOutput
		esc_html( $user->first_name ? $user->first_name : 'My account' )
	);
	if ( 'approved' === $state && function_exists( 'wc_get_cart_url' ) ) {
		$n = oneam_cart_count();
		printf(
			'<a class="site-nav__icon site-nav__cart" href="%s" aria-label="Cart" title="Cart">%s<span class="site-nav__count%s">%d</span></a>',
			esc_url( wc_get_cart_url() ),
			oneam_icon( 'cart' ), // phpcs:ignore WordPress.Security.EscapeOutput
			$n ? '' : ' is-empty',
			(int) $n
		);
	}
	printf(
		'<a class="site-nav__icon" href="%s" aria-label="Log out" title="Log out">%s</a>',
		esc_url( wp_logout_url( home_url( '/' ) ) ),
		oneam_icon( 'logout' ) // phpcs:ignore WordPress.Security.EscapeOutput
	);
	echo '</div>';
}

/** 장바구니에 담으면 헤더 숫자도 바로 바뀌게 */
add_filter(
	'woocommerce_add_to_cart_fragments',
	function ( $fragments ) {
		$n = oneam_cart_count();
		$fragments['span.site-nav__count'] = '<span class="site-nav__count' . ( $n ? '' : ' is-empty' ) . '">' . (int) $n . '</span>';
		return $fragments;
	}
);

/* ---------------------------------------------------------------------------
 * 테마 상품 상세 주소: /products/{slug}/
 * (WooCommerce 상품이 없어도 상세 페이지가 열림. 있으면 그 상품 페이지로 이동)
 * ------------------------------------------------------------------------- */
function oneam_product_page_url( $slug ) {
	return get_option( 'permalink_structure' ) ? home_url( '/products/' . $slug . '/' ) : add_query_arg( 'oneam_product', $slug, home_url( '/' ) );
}

function oneam_product_by_slug( $slug ) {
	foreach ( oneam_get_products() as $p ) {
		if ( $p['slug'] === $slug ) {
			return $p;
		}
	}
	foreach ( oneam_default_products() as $p ) {
		if ( $p['slug'] === $slug ) {
			$p['url'] = oneam_product_page_url( $slug );
			return $p;
		}
	}
	return null;
}

add_action(
	'init',
	function () {
		add_rewrite_rule( '^products/([^/]+)/?$', 'index.php?oneam_product=$matches[1]', 'top' );
		if ( '2' !== get_option( 'oneam_rewrite_version' ) ) {
			flush_rewrite_rules( false );
			update_option( 'oneam_rewrite_version', '2' );
		}
	}
);
add_filter(
	'query_vars',
	function ( $vars ) {
		$vars[] = 'oneam_product';
		return $vars;
	}
);
add_action(
	'template_redirect',
	function () {
		$slug = get_query_var( 'oneam_product' );
		if ( ! $slug ) {
			return;
		}
		$slug = sanitize_title( $slug );
		// WooCommerce 에 같은 상품이 발행돼 있으면 그 페이지가 정식 주소
		$page = get_page_by_path( $slug );
		if ( $page && 'publish' === $page->post_status ) {
			wp_safe_redirect( get_permalink( $page ), 301 );
			exit;
		}
		$found = post_type_exists( 'product' ) ? get_page_by_path( $slug, OBJECT, 'product' ) : null;
		if ( $found && 'publish' === $found->post_status ) {
			wp_safe_redirect( get_permalink( $found ), 301 );
			exit;
		}
		if ( ! oneam_product_by_slug( $slug ) ) {
			global $wp_query;
			$wp_query->set_404(); // 모르는 상품
			status_header( 404 );
			return;
		}
		global $wp_query;
		$wp_query->is_404  = false;
		$wp_query->is_home = false;
		status_header( 200 );
		add_filter(
			'document_title_parts',
			function ( $t ) use ( $slug ) {
				$p          = oneam_product_by_slug( $slug );
				$t['title'] = $p['name'];
				return $t;
			}
		);
		add_filter( 'template_include', function () { return locate_template( 'template-product.php' ); }, 99 );
	},
	1
);
