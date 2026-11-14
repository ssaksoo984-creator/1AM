<?php
/**
 * 파비콘 · 홈 화면 아이콘 · 링크 공유 이미지
 *
 * - 관리자가 사용자 정의하기 → Site Identity → Site Icon 을 올리면 그걸 씀 (WordPress 기본).
 * - 안 올렸으면 테마에 들어 있는 1AM 아이콘을 씀 (assets/img/icons).
 *   브라우저 탭 · 북마크 · 구글 검색 결과 아이콘 · 아이폰/안드로이드 홈 화면 아이콘 · /favicon.ico
 * - SEO 플러그인이 없으면 카톡 · 문자 · SNS 링크 미리보기용 og 태그도 넣음.
 *
 * @package 1am-vivid
 */

defined( 'ABSPATH' ) || exit;

/** 사이트 아이콘이 없으면 테마 아이콘 주소 (크기별) */
add_filter(
	'get_site_icon_url',
	function ( $url, $size ) {
		if ( $url ) {
			return $url;
		}
		$size = (int) $size;
		if ( $size <= 32 ) {
			$file = 'favicon-32.png';
		} elseif ( 180 === $size ) {
			$file = 'apple-touch-icon.png';
		} elseif ( $size <= 192 ) {
			$file = 'icon-192.png';
		} else {
			$file = 'icon-512.png';
		}
		return oneam_asset( 'img/icons/' . $file );
	},
	10,
	2
);

// 주소창에 직접 /favicon.ico 를 요청할 때 (구글 · 오래된 브라우저)
add_action(
	'do_favicon',
	function () {
		if ( get_option( 'site_icon' ) ) {
			return; // 관리자가 올린 아이콘 → WordPress 기본 처리
		}
		$file = get_template_directory() . '/assets/img/icons/favicon.ico';
		if ( is_readable( $file ) ) {
			header( 'Content-Type: image/x-icon' );
			header( 'Cache-Control: public, max-age=604800' );
			readfile( $file ); // phpcs:ignore WordPress.WP.AlternativeFunctions
			exit;
		}
	},
	1
);

// .ico · 테마 색 · 홈 화면 앱 정보
add_action(
	'wp_head',
	function () {
		if ( ! get_option( 'site_icon' ) ) {
			echo '<link rel="icon" href="' . esc_url( oneam_asset( 'img/icons/favicon.ico' ) ) . '" sizes="48x48">' . "\n";
		}
		echo '<meta name="theme-color" content="#0e0d12">' . "\n";
		echo '<link rel="manifest" href="' . esc_url( home_url( '/?oneam_manifest=1' ) ) . '">' . "\n";
	},
	2
);

// 홈 화면에 추가할 때 쓰는 앱 정보 (site.webmanifest)
add_action(
	'template_redirect',
	function () {
		if ( empty( $_GET['oneam_manifest'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			return;
		}
		$name = get_bloginfo( 'name' ) ?: '1AM';
		header( 'Content-Type: application/manifest+json; charset=utf-8' );
		echo wp_json_encode(
			array(
				'name'             => $name,
				'short_name'       => $name,
				'start_url'        => home_url( '/' ),
				'display'          => 'browser',
				'background_color' => '#0e0d12',
				'theme_color'      => '#0e0d12',
				'icons'            => array(
					array( 'src' => get_site_icon_url( 192 ), 'sizes' => '192x192', 'type' => 'image/png' ),
					array( 'src' => get_site_icon_url( 512 ), 'sizes' => '512x512', 'type' => 'image/png' ),
				),
			),
			JSON_UNESCAPED_SLASHES
		);
		exit;
	},
	0
);

/* ---------------------------------------------------------------------------
 * 링크 미리보기 (카톡 · 문자 · 페이스북 · 구글) — SEO 플러그인이 있으면 그쪽에 맡김
 * ------------------------------------------------------------------------- */
add_action(
	'wp_head',
	function () {
		if ( oneam_has_seo_plugin() || is_404() ) {
			return;
		}
		$title = wp_get_document_title();
		$desc  = function_exists( 'oneam_meta_description' ) ? oneam_meta_description() : '';
		$url   = is_singular() ? get_permalink() : ( is_front_page() ? home_url( '/' ) : '' );
		$img   = oneam_asset( 'img/icons/og-image.jpg' );
		$w     = 1200;
		$h     = 630;
		if ( is_singular() && has_post_thumbnail() ) {
			$src = wp_get_attachment_image_src( get_post_thumbnail_id(), 'large' );
			if ( $src ) {
				list( $img, $w, $h ) = $src;
			}
		}
		$tags = array(
			'og:type'        => is_singular( 'product' ) ? 'product' : 'website',
			'og:site_name'   => get_bloginfo( 'name' ) ?: '1AM',
			'og:title'       => $title,
			'og:description' => $desc,
			'og:url'         => $url,
			'og:image'       => $img,
			'og:image:width' => $w,
			'og:image:height' => $h,
		);
		foreach ( $tags as $k => $v ) {
			if ( '' !== (string) $v ) {
				echo '<meta property="' . esc_attr( $k ) . '" content="' . esc_attr( $v ) . '">' . "\n";
			}
		}
		echo '<meta name="twitter:card" content="summary_large_image">' . "\n";
	},
	3
);
