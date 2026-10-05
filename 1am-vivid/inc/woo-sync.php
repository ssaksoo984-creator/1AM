<?php
/**
 * WooCommerce → 사이트 맛 목록 연결
 *
 * WooCommerce 에 옵션 상품(Variable product)을 만들고 맛을 옵션으로 추가하면
 * 메인 라인업, 상품 페이지 슬라이더/맛 목록에 자동으로 나옵니다.
 * - 맛 이름   = 옵션 값 (예: Grape Ice)
 * - 사진      = 옵션 이미지 (없으면 상품 대표 이미지)
 * - 설명      = 옵션 설명
 * - 상품 라인 = 상품 슬러그 (예: slim-hybrid → 1AM Lineup 의 같은 슬러그 페이지에 연결)
 * - 컬러/분류 = 옵션 편집 화면의 "1AM colour" 칸 (비우면 기본값 또는 자동 색)
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** 기본 맛 데이터에서 같은 이름의 컬러/분류 찾기 */
function oneam_default_flavor_by_slug( $slug ) {
	foreach ( oneam_default_flavors() as $f ) {
		if ( $f[1] === $slug ) {
			return $f;
		}
	}
	return null;
}

/** 기본값이 없는 새 맛은 이름으로 정해지는 비비드 컬러 */
function oneam_auto_colours( $slug ) {
	$palette = array(
		array( '#FF2E4D', '#FFB321' ),
		array( '#8A3FFC', '#16C75A' ),
		array( '#1E5BFF', '#00C2A8' ),
		array( '#FF5C8A', '#E8174B' ),
		array( '#12D16B', '#19C6E6' ),
		array( '#FFD60A', '#FF8A4D' ),
		array( '#B026FF', '#FF1464' ),
	);
	return $palette[ abs( crc32( $slug ) ) % count( $palette ) ];
}

/**
 * WooCommerce 옵션 상품의 옵션들을 맛 목록으로 변환.
 *
 * @return array[]
 */
function oneam_flavors_from_woo() {
	if ( ! function_exists( 'wc_get_products' ) ) {
		return array();
	}
	$out      = array();
	$products = wc_get_products(
		array(
			'type'    => 'variable',
			'status'  => 'publish',
			'limit'   => -1,
			'orderby' => 'menu_order',
			'order'   => 'ASC',
		)
	);
	foreach ( $products as $product ) {
		$line_post = get_page_by_path( $product->get_slug(), OBJECT, 'oneam_line' );
		$line_url  = $line_post ? get_permalink( $line_post ) : home_url( '/#products' );
		foreach ( $product->get_children() as $vid ) {
			$v = wc_get_product( $vid );
			if ( ! $v || 'publish' !== $v->get_status() ) {
				continue;
			}
			$attrs = $v->get_attributes();
			$name  = $attrs ? (string) reset( $attrs ) : '';
			if ( taxonomy_exists( (string) key( $attrs ) ) ) {
				$term = get_term_by( 'slug', $name, key( $attrs ) );
				$name = $term ? $term->name : $name;
			}
			if ( '' === $name ) {
				$name = $v->get_name();
			}
			$slug = sanitize_title( $name );
			$def  = oneam_default_flavor_by_slug( $slug );
			$auto = oneam_auto_colours( $slug );
			$img  = $v->get_image_id() ? wp_get_attachment_image_url( $v->get_image_id(), 'large' ) : '';
			if ( ! $img && $def ) {
				$img = oneam_asset( 'img/flavors/' . $slug . '.webp' );
			}
			if ( ! $img && $product->get_image_id() ) {
				$img = wp_get_attachment_image_url( $product->get_image_id(), 'large' );
			}
			$out[] = array(
				'name' => $name,
				'slug' => $slug,
				'cat'  => $v->get_meta( '_oneam_cat' ) ?: ( $def ? $def[2] : 'ice' ),
				'c1'   => $v->get_meta( '_oneam_c1' ) ?: ( $def ? $def[3] : $auto[0] ),
				'c2'   => $v->get_meta( '_oneam_c2' ) ?: ( $def ? $def[4] : $auto[1] ),
				'img'  => $img ?: oneam_asset( 'img/flavors/grape-ice.webp' ),
				'desc' => wp_strip_all_tags( $v->get_description() ) ?: ( $def ? $def[5] : '' ),
				'url'  => $line_url,
				'line' => $product->get_slug(),
			);
		}
	}
	return $out;
}

/* ---------------------------------------------------------------------------
 * 옵션(맛) 편집 화면에 "1AM colour" 칸 추가
 * ------------------------------------------------------------------------- */
add_action(
	'woocommerce_product_after_variable_attributes',
	function ( $loop, $variation_data, $variation ) {
		$v    = wc_get_product( $variation->ID );
		$cats = oneam_flavor_categories();
		$cat  = $v ? $v->get_meta( '_oneam_cat' ) : '';
		?>
		<div class="form-row form-row-full" style="display:flex;gap:16px;align-items:center;flex-wrap:wrap;border-top:1px solid #eee;padding-top:10px">
			<strong>1AM colour</strong>
			<label>Main <input type="color" name="oneam_c1[<?php echo (int) $loop; ?>]" value="<?php echo esc_attr( $v ? ( $v->get_meta( '_oneam_c1' ) ?: '#ffffff' ) : '#ffffff' ); ?>"></label>
			<label>Second <input type="color" name="oneam_c2[<?php echo (int) $loop; ?>]" value="<?php echo esc_attr( $v ? ( $v->get_meta( '_oneam_c2' ) ?: '#ffffff' ) : '#ffffff' ); ?>"></label>
			<label>Type
				<select name="oneam_cat[<?php echo (int) $loop; ?>]">
					<option value="">Auto</option>
					<?php foreach ( $cats as $k => $label ) : ?>
						<option value="<?php echo esc_attr( $k ); ?>" <?php selected( $cat, $k ); ?>><?php echo esc_html( $label ); ?></option>
					<?php endforeach; ?>
				</select>
			</label>
			<span class="description">White = automatic colour.</span>
		</div>
		<?php
	},
	10,
	3
);

add_action(
	'woocommerce_save_product_variation',
	function ( $variation_id, $i ) {
		// WooCommerce 가 nonce 와 권한을 이미 확인한 뒤 호출합니다.
		$v = wc_get_product( $variation_id );
		if ( ! $v ) {
			return;
		}
		foreach ( array( 'c1', 'c2' ) as $k ) {
			if ( isset( $_POST[ 'oneam_' . $k ][ $i ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
				$c = sanitize_hex_color( wp_unslash( $_POST[ 'oneam_' . $k ][ $i ] ) ); // phpcs:ignore WordPress.Security.NonceVerification
				$v->update_meta_data( '_oneam_' . $k, ( $c && '#ffffff' !== strtolower( $c ) ) ? $c : '' );
			}
		}
		if ( isset( $_POST['oneam_cat'][ $i ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			$c = sanitize_key( wp_unslash( $_POST['oneam_cat'][ $i ] ) ); // phpcs:ignore WordPress.Security.NonceVerification
			$v->update_meta_data( '_oneam_cat', array_key_exists( $c, oneam_flavor_categories() ) ? $c : '' );
		}
		$v->save_meta_data();
	},
	10,
	2
);
