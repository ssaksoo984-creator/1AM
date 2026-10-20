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
		'fruit-ice'   => 'Fruit Ice',
		'mint'        => 'Mint',
		'classic-ice' => 'Classic Ice',
	);
}

/** 테마에 포함된 기본 맛 데이터 (이미지: assets/img/flavors/{slug}.webp). 설명 = 맛 특징 4가지 */
function oneam_default_flavors() {
	return array(
		array( 'Grape Ice', 'grape-ice', 'fruit-ice', '#8A3FFC', '#16C75A', 'Rich red grape · Juicy & sweet · Icy cooling · Classic refreshment' ),
		array( 'Blue Razz Ice', 'blue-razz-ice', 'fruit-ice', '#00C2A8', '#1E5BFF', 'Sweet & sour · Tangy raspberry · Extra frosty · Bold punch' ),
		array( 'Watermelon Ice', 'watermelon-ice', 'fruit-ice', '#FF2E4D', '#2BD45A', 'Fresh watermelon juice · Bright & sweet · Summer cooling · Hydrating vibe' ),
		array( 'Lemon Lime Ice', 'lemon-lime-ice', 'classic-ice', '#1FD65A', '#D7F21A', 'Zesty lemon citrus · Tangy lime twist · Super refreshing · Crisp icy' ),
		array( 'Raspberry Dragonfruit Ice', 'raspberry-dragonfruit-ice', 'fruit-ice', '#B026FF', '#FF1464', 'Exotic dragonfruit · Tangy raspberry · Sweet & fruity · Chilled ice finish' ),
		array( 'Miami Mint', 'miami-mint', 'mint', '#12D16B', '#19C6E6', 'Smooth spearmint · Citrus touch · Gentle cooling · All-day refresh' ),
		array( 'Double Mint', 'double-mint', 'mint', '#2F6BFF', '#12D16B', 'Pure spearmint · Zero sweetness · Extra frosty · Clean mouthfeel' ),
		array( 'Classic Ice', 'classic-ice', 'classic-ice', '#FF2A2A', '#FF8A7A', 'Crisp frosted blast · Pure refreshing chill · Clean smooth finish · Timeless icy cool' ),
		array( 'Red Classic Ice', 'red-classic-ice', 'classic-ice', '#E0115F', '#3D5BFF', 'Juicy red apple · Sweet & crisp notes · Chilly ice finish · Refreshing fruit blend' ),
		array( 'Milky Ice', 'milky-ice', 'classic-ice', '#1FB8E6', '#F58CC0', 'Creamy milk base · Subtle sweetness · Smooth & silky texture · Refreshing ice coolness' ),
		array( 'Cheesy Strawberry', 'cheesy-strawberry', 'fruit-ice', '#FF3B5C', '#FFB321', 'Rich creamy feeling · Sweet fresh strawberry · Smooth blend · Indulgent flavour profile' ),
		array( 'Juicy Peach Ice', 'juicy-peach-ice', 'fruit-ice', '#FF8A4D', '#FFC857', 'Ripe peach · Sweet & luscious · Crisp ice finish · Fragrant aroma' ),
		array( 'Guava Ice', 'guava-ice', 'fruit-ice', '#FF5C8A', '#E8174B', 'Exotic tropical · Soft sweetness · Mild cooling · Smooth aromatic' ),
		array( 'Banana Ice', 'banana-ice', 'fruit-ice', '#FFD60A', '#FF9F1C', 'Sweet creamy banana · Smooth tropical blend · Crisp frost · Icy refreshment' ),
		array( 'Double Birch Ice', 'double-birch-ice', 'classic-ice', '#F2B705', '#A0461F', 'Woody sweetness · Deep birch sap · Crisp chilly · Clean finish' ),
	);
}

/** 예전 기본 설명 (WooCommerce 에 이 문구가 그대로 남아 있으면 새 설명으로 교체) */
function oneam_old_flavor_texts() {
	return array(
		'Purple grape with a cool finish.', 'Blue raspberry, lightly cooled.', 'Ripe watermelon with a cool finish.',
		'Lemon and lime, bright and crisp.', 'Raspberry and dragonfruit, lightly cooled.', 'Spearmint with a clean, cool finish.',
		'Layered mint, extra cool.', 'Clean and cool. The original.', 'A red-berry take on the classic.',
		'Smooth milk flavour, lightly cooled.', 'Strawberry with a smooth, rich finish.', 'Soft peach, lightly cooled.',
		'Pink guava, cool and smooth.', 'Ripe banana, lightly cooled.', 'Birch with a double cool finish.',
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
