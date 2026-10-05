<?php
/**
 * 맛 데이터.
 *
 * WooCommerce 옵션 상품의 옵션(맛)을 사용합니다. (inc/woo-sync.php)
 * WooCommerce 에 상품이 없을 때만 테마에 포함된 15개 기본 맛을 보여줍니다.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** 카테고리 라벨 */
function oneam_flavor_categories() {
	return array(
		'ice'   => 'Ice',
		'fruit' => 'Fruity',
		'mint'  => 'Mint',
		'sweet' => 'Sweet',
	);
}

/** 테마에 포함된 기본 맛 데이터 (이미지: assets/img/flavors/{slug}.webp) */
function oneam_default_flavors() {
	return array(
		array( 'Grape Ice', 'grape-ice', 'fruit', '#8A3FFC', '#16C75A', 'Purple grape with a cool finish.' ),
		array( 'Blue Razz Ice', 'blue-razz-ice', 'ice', '#00C2A8', '#1E5BFF', 'Blue raspberry, lightly cooled.' ),
		array( 'Watermelon Ice', 'watermelon-ice', 'fruit', '#FF2E4D', '#2BD45A', 'Ripe watermelon with a cool finish.' ),
		array( 'Lemon Lime Ice', 'lemon-lime-ice', 'fruit', '#1FD65A', '#D7F21A', 'Lemon and lime, bright and crisp.' ),
		array( 'Raspberry Dragonfruit Ice', 'raspberry-dragonfruit-ice', 'fruit', '#B026FF', '#FF1464', 'Raspberry and dragonfruit, lightly cooled.' ),
		array( 'Miami Mint', 'miami-mint', 'mint', '#12D16B', '#19C6E6', 'Spearmint with a clean, cool finish.' ),
		array( 'Double Mint', 'double-mint', 'mint', '#2F6BFF', '#12D16B', 'Layered mint, extra cool.' ),
		array( 'Classic Ice', 'classic-ice', 'ice', '#FF2A2A', '#FF8A7A', 'Clean and cool. The original.' ),
		array( 'Red Classic Ice', 'red-classic-ice', 'ice', '#E0115F', '#3D5BFF', 'A red-berry take on the classic.' ),
		array( 'Milky Ice', 'milky-ice', 'sweet', '#1FB8E6', '#F58CC0', 'Smooth milk flavour, lightly cooled.' ),
		array( 'Cheesy Strawberry', 'cheesy-strawberry', 'sweet', '#FF3B5C', '#FFB321', 'Strawberry with a smooth, rich finish.' ),
		array( 'Juicy Peach Ice', 'juicy-peach-ice', 'fruit', '#FF8A4D', '#FFC857', 'Soft peach, lightly cooled.' ),
		array( 'Guava Ice', 'guava-ice', 'fruit', '#FF5C8A', '#E8174B', 'Pink guava, cool and smooth.' ),
		array( 'Banana Ice', 'banana-ice', 'sweet', '#FFD60A', '#FF9F1C', 'Ripe banana, lightly cooled.' ),
		array( 'Double Birch Ice', 'double-birch-ice', 'ice', '#F2B705', '#A0461F', 'Birch with a double cool finish.' ),
	);
}

/**
 * 모든 맛을 통일된 배열로 반환.
 *
 * @return array[] name, slug, cat, c1, c2, img, desc, url, line
 */
function oneam_get_flavors() {
	static $cache = null;
	if ( null !== $cache ) {
		return $cache;
	}

	// WooCommerce 옵션 상품의 맛(옵션)을 그대로 사용
	$flavors = function_exists( 'oneam_flavors_from_woo' ) ? oneam_flavors_from_woo() : array();

	// WooCommerce 에 아직 상품이 없으면 테마 기본 15종
	if ( empty( $flavors ) ) {
		foreach ( oneam_default_flavors() as $f ) {
			$flavors[] = array(
				'name' => $f[0],
				'slug' => $f[1],
				'cat'  => $f[2],
				'c1'   => $f[3],
				'c2'   => $f[4],
				'img'  => oneam_asset( 'img/flavors/' . $f[1] . '.webp' ),
				'desc' => $f[5],
				'url'  => home_url( '/#flavors' ),
				'line' => 'slim-hybrid',
			);
		}
	}

	$cache = $flavors;
	return $cache;
}

/** 맛 하나의 인라인 CSS 변수 */
function oneam_flavor_style( $f ) {
	return sprintf( '--c1:%s;--c2:%s;', esc_attr( $f['c1'] ), esc_attr( $f['c2'] ) );
}
