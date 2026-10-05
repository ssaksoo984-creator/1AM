<?php
/**
 * WooCommerce → 사이트 맛 목록 연결
 *
 * WooCommerce 에 옵션 상품(Variable product)을 만들고 맛을 옵션으로 추가하면
 * 메인 라인업, 상품 페이지 슬라이더/맛 목록에 자동으로 나옵니다.
 * - 맛 이름   = 옵션 값 (예: Grape Ice)
 * - 사진      = 옵션 이미지 (없으면 상품 대표 이미지)
 * - 설명      = 옵션 설명
 * - 링크      = WooCommerce 상품 페이지
 * - 컬러/분류 = 옵션 편집 화면의 "1AM colour" 칸 (비우면 기본값 또는 자동 색)
 *
 * 상품 자체(메인 "상품 라인업")는 상품 편집 화면 General 탭의 "1AM" 칸으로 꾸밉니다.
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
		$line_url = get_permalink( $product->get_id() );
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

/* ---------------------------------------------------------------------------
 * 상품 → 메인 "상품 라인업"
 * ------------------------------------------------------------------------- */

/**
 * WooCommerce 상품을 라인업 형식으로 변환.
 * - 이름/설명 = 상품명 / 짧은 설명
 * - 라벨     = General 탭 "1AM label" (예: 2ml Disposable)
 * - 출시 예정 = General 탭 "Coming soon" 체크
 * - 스펙     = 옵션이 아닌 속성(Attributes, 예: E-liquid: 2ml) + 맛 개수
 * - 사진     = 대표 이미지 (출시 예정이고 사진이 없으면 실루엣)
 */
function oneam_products_from_woo() {
	if ( ! function_exists( 'wc_get_products' ) ) {
		return array();
	}
	$out = array();
	foreach ( wc_get_products( array( 'status' => 'publish', 'limit' => -1, 'orderby' => array( 'menu_order' => 'ASC', 'date' => 'ASC' ) ) ) as $product ) {
		$slug  = $product->get_slug();
		$soon  = 'yes' === $product->get_meta( '_oneam_soon' );
		$auto  = oneam_auto_colours( $slug );
		$specs = array();
		foreach ( $product->get_attributes() as $attr ) {
			if ( $attr->get_variation() || ! $attr->get_visible() ) {
				continue;
			}
			$specs[ wc_attribute_label( $attr->get_name() ) ] = implode( ', ', $attr->is_taxonomy() ? wc_get_product_terms( $product->get_id(), $attr->get_name(), array( 'fields' => 'names' ) ) : $attr->get_options() );
		}
		if ( $product->is_type( 'variable' ) && $product->get_children() ) {
			$specs['Flavours'] = (string) count( $product->get_children() );
		}
		$c1    = $product->get_meta( '_oneam_c1' );
		$c2    = $product->get_meta( '_oneam_c2' );
		$out[] = array(
			'name'   => $product->get_name(),
			'slug'   => $slug,
			'tag'    => $product->get_meta( '_oneam_label' ),
			'status' => $soon ? 'soon' : 'available',
			'c1'     => $c1 ?: $auto[0],
			'c2'     => $c2 ?: $auto[1],
			'img'    => $product->get_image_id() ? wp_get_attachment_image_url( $product->get_image_id(), 'large' ) : '',
			'desc'   => wp_strip_all_tags( $product->get_short_description() ),
			'specs'  => $specs,
			'url'    => get_permalink( $product->get_id() ),
		);
	}
	return $out;
}

/** 상품 편집 > General 탭에 "1AM" 칸 */
add_action(
	'woocommerce_product_options_general_product_data',
	function () {
		global $post;
		$p = wc_get_product( $post->ID );
		echo '<div class="options_group"><p style="margin:12px 12px 0;font-weight:600">1AM homepage</p>';
		woocommerce_wp_text_input(
			array(
				'id'          => '_oneam_label',
				'label'       => 'Label',
				'placeholder' => 'e.g. 2ml Disposable',
				'value'       => $p ? $p->get_meta( '_oneam_label' ) : '',
			)
		);
		woocommerce_wp_checkbox(
			array(
				'id'          => '_oneam_soon',
				'label'       => 'Coming soon',
				'description' => 'Show as "Coming soon" in the homepage line-up.',
				'value'       => $p ? $p->get_meta( '_oneam_soon' ) : '',
			)
		);
		foreach ( array( '_oneam_c1' => 'Main colour', '_oneam_c2' => 'Second colour' ) as $key => $label ) {
			$val = $p ? $p->get_meta( $key ) : '';
			printf(
				'<p class="form-field"><label for="%1$s">%2$s</label><input type="color" id="%1$s" name="%1$s" value="%3$s"> <span class="description">White = automatic colour.</span></p>',
				esc_attr( $key ),
				esc_html( $label ),
				esc_attr( $val ?: '#ffffff' )
			);
		}
		echo '</div>';
	}
);

add_action(
	'woocommerce_admin_process_product_object',
	function ( $product ) {
		// WooCommerce 가 nonce 와 권한을 이미 확인한 뒤 호출합니다.
		// phpcs:disable WordPress.Security.NonceVerification
		$product->update_meta_data( '_oneam_label', isset( $_POST['_oneam_label'] ) ? sanitize_text_field( wp_unslash( $_POST['_oneam_label'] ) ) : '' );
		$product->update_meta_data( '_oneam_soon', isset( $_POST['_oneam_soon'] ) ? 'yes' : 'no' );
		foreach ( array( '_oneam_c1', '_oneam_c2' ) as $key ) {
			$c = isset( $_POST[ $key ] ) ? sanitize_hex_color( wp_unslash( $_POST[ $key ] ) ) : '';
			$product->update_meta_data( $key, ( $c && '#ffffff' !== strtolower( $c ) ) ? $c : '' );
		}
		// phpcs:enable
	}
);

/* ---------------------------------------------------------------------------
 * 상품 페이지 (공개): 맛 슬라이더 → 상품 정보 → 맛 목록 → 가입 유도
 * 가격·장바구니는 승인 회원에게만
 * ------------------------------------------------------------------------- */

/** 승인 회원이 아니면 가격 숨김 + 구매 불가 */
add_filter(
	'woocommerce_get_price_html',
	function ( $html ) {
		return ( is_admin() || 'approved' === oneam_member_state() ) ? $html : '';
	}
);
add_filter(
	'woocommerce_is_purchasable',
	function ( $ok ) {
		return ( is_admin() || 'approved' === oneam_member_state() ) ? $ok : false;
	}
);

add_action(
	'wp',
	function () {
		if ( ! function_exists( 'is_product' ) || ! is_product() ) {
			return;
		}
		// 비회원·승인 대기: 장바구니 대신 가입/로그인 안내
		if ( 'approved' !== oneam_member_state() ) {
			remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_add_to_cart', 30 );
			add_action(
				'woocommerce_single_product_summary',
				function () {
					echo '<div class="pdp-gate"><p>Wholesale pricing is visible to approved retailers.</p>';
					oneam_member_cta();
					if ( 'guest' === oneam_member_state() ) {
						printf( ' <a class="link-arrow" href="%s">Log in <span>&rarr;</span></a>', esc_url( oneam_login_url() ) );
					}
					echo '</div>';
				},
				30
			);
		}
	}
);

/** 맛 슬라이더를 상품 페이지 맨 위(전체 폭)에, 맛 목록·가입 유도를 아래에 */
add_action(
	'woocommerce_before_main_content',
	function () {
		if ( ! is_product() ) {
			return;
		}
		$product = wc_get_product( get_queried_object_id() );
		if ( $product && $product->is_type( 'variable' ) && $product->get_children() ) {
			get_template_part(
				'template-parts/hero',
				null,
				array(
					'line'   => $product->get_slug(),
					'title'  => $product->get_name() . '.',
					'kicker' => $product->get_meta( '_oneam_label' ) ?: count( $product->get_children() ) . ' flavours',
				)
			);
		}
	},
	5
);
add_action(
	'woocommerce_after_main_content',
	function () {
		if ( ! is_product() ) {
			return;
		}
		$product = wc_get_product( get_queried_object_id() );
		if ( $product && $product->is_type( 'variable' ) ) {
			get_template_part( 'template-parts/flavors', null, array( 'line' => $product->get_slug(), 'title' => $product->get_name() ) );
		}
		get_template_part( 'template-parts/cta' );
	},
	20
);
