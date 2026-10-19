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
			// 예전 기본 설명이 그대로면 새 맛 특징으로
			$desc = trim( wp_strip_all_tags( $v->get_description() ) );
			if ( ( '' === $desc || in_array( $desc, oneam_old_flavor_texts(), true ) ) && $def ) {
				$desc = $def[5];
			}
			$out[] = array(
				'name' => $name,
				'slug' => $slug,
				'cat'  => array_key_exists( (string) $v->get_meta( '_oneam_cat' ), oneam_flavor_categories() ) ? $v->get_meta( '_oneam_cat' ) : ( $def ? $def[2] : 'fruit-ice' ),
				'c1'   => $v->get_meta( '_oneam_c1' ) ?: ( $def ? $def[3] : $auto[0] ),
				'c2'   => $v->get_meta( '_oneam_c2' ) ?: ( $def ? $def[4] : $auto[1] ),
				'img'  => $img ?: oneam_asset( 'img/flavors/grape-ice.webp' ),
				'desc' => $desc,
				'url'  => $line_url,
				'line' => $product->get_slug(),
				// 팝업에서 바로 장바구니에 담기용
				'pid'  => $product->get_id(),
				'vid'  => $vid,
				'attr' => $v->get_variation_attributes(),
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
			'url'     => get_permalink( $product->get_id() ),
			'regions' => function_exists( 'oneam_province_prices' ) ? array_keys( oneam_province_prices( $product ) ) : array(),
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
		// 상세 정보는 woocommerce/content-single-product.php 에서 직접 출력 (가격·장바구니는 승인 회원만)
		remove_action( 'woocommerce_before_main_content', 'woocommerce_breadcrumb', 20 );
	}
);

/** 맛 슬라이더를 상품 페이지 맨 위(전체 폭)에, 맛 목록·가입 유도를 아래에 */
/** 맛 목록 (line 이 '' 이면 전체) */
function oneam_flavours_for_line( $line ) {
	return array_values( array_filter( oneam_get_flavors(), function ( $f ) use ( $line ) { return '' === $line || $f['line'] === $line; } ) );
}

/**
 * 상품 페이지에 보여줄 맛 묶음.
 * 옵션(맛)이 있는 상품 → 그 상품의 맛 / 옵션 없는 일반 상품 → 전체 맛 / 출시 예정 상품 → 없음(false)
 */
function oneam_product_flavour_line( $product ) {
	if ( 'yes' === $product->get_meta( '_oneam_soon' ) ) {
		return false;
	}
	if ( oneam_flavours_for_line( $product->get_slug() ) ) {
		return $product->get_slug();
	}
	return oneam_get_flavors() ? '' : false;
}

/*
 * 상품 페이지(쇼핑몰)는 woocommerce/content-single-product.php 의 쇼핑 화면만 보여줌.
 * 브랜드 소개(맛 슬라이더 · 맛 목록 · 팝업)는 소개 페이지(/slim-hybrid/)에.
 */

/** 맛 선택 시 우커머스가 보여주는 설명도 예전 기본 문구면 새 맛 특징으로 */
add_filter(
	'woocommerce_available_variation',
	function ( $data, $product, $variation ) {
		$desc = trim( wp_strip_all_tags( $variation->get_description() ) );
		if ( '' === $desc || in_array( $desc, oneam_old_flavor_texts(), true ) ) {
			$attrs = $variation->get_attributes();
			$name  = $attrs ? (string) reset( $attrs ) : '';
			$def   = oneam_default_flavor_by_slug( sanitize_title( $name ) );
			if ( $def ) {
				$data['variation_description'] = wpautop( $def[5] );
			}
		}
		return $data;
	},
	10,
	3
);

/* ---------------------------------------------------------------------------
 * 맛 팝업 (맛 목록에서 카드를 누르면)
 * 왼쪽 제품 이미지 · 오른쪽 이름 · 분류 · 맛 특징 · 판매 지역/가격 · 장바구니 또는 가입 안내
 * ------------------------------------------------------------------------- */
function oneam_flavour_popup_data( $f, $cats ) {
	$d = array(
		'name'  => $f['name'],
		'cat'   => isset( $cats[ $f['cat'] ] ) ? $cats[ $f['cat'] ] : '',
		'notes' => array_values( array_filter( array_map( 'trim', explode( '·', (string) $f['desc'] ) ) ) ),
		'img'   => $f['img'],
		'c1'    => $f['c1'],
		'c2'    => $f['c2'],
		'url'   => $f['url'],
	);
	$product = ! empty( $f['pid'] ) && function_exists( 'wc_get_product' ) ? wc_get_product( $f['pid'] ) : null;
	if ( $product ) {
		$pp           = function_exists( 'oneam_province_prices' ) ? oneam_province_prices( $product ) : array();
		$d['line']    = $product->get_name();
		$d['regions'] = array_keys( $pp );
		if ( 'approved' === oneam_member_state() ) {
			$v          = wc_get_product( $f['vid'] );
			$d['pid']   = (int) $f['pid'];
			$d['vid']   = (int) $f['vid'];
			$d['attr']  = $f['attr'];
			$d['price'] = $v ? wp_strip_all_tags( wc_price( wc_get_price_to_display( $v ) ) ) : '';
			$d['unit']  = function_exists( 'oneam_unit_label' ) ? oneam_unit_label( $product ) : 'unit';
			$d['ok']    = $v ? $v->is_purchasable() && $v->is_in_stock() : false;
			$d['prov']  = function_exists( 'oneam_customer_province' ) ? oneam_customer_province() : '';
		}
	} else {
		$def          = function_exists( 'oneam_product_by_slug' ) ? oneam_product_by_slug( $f['line'] ) : null;
		$d['line']    = $def ? $def['name'] : '';
		$d['regions'] = $def && ! empty( $def['regions'] ) ? $def['regions'] : array();
	}
	return $d;
}

function oneam_flavour_popup() {
	static $done = false;
	if ( $done ) {
		return;
	}
	$done  = true;
	$state = oneam_member_state();
	?>
	<div class="fpop" id="fpop" hidden aria-hidden="true">
		<div class="fpop__backdrop" data-close></div>
		<div class="fpop__box" role="dialog" aria-modal="true" aria-labelledby="fpop-name">
			<button type="button" class="fpop__close" data-close aria-label="Close">&times;</button>
			<div class="fpop__media">
				<span class="fpop__glow" aria-hidden="true"></span>
				<img class="fpop__img" src="" alt="">
				<button type="button" class="fpop__nav fpop__nav--prev" data-step="-1" aria-label="Previous flavour">&larr;</button>
				<button type="button" class="fpop__nav fpop__nav--next" data-step="1" aria-label="Next flavour">&rarr;</button>
			</div>
			<div class="fpop__body">
				<p class="fpop__meta"><span class="fpop__cat"></span><span class="fpop__line"></span></p>
				<h2 class="fpop__name" id="fpop-name"></h2>
				<ul class="fpop__notes"></ul>
				<p class="fpop__regions"></p>
				<?php if ( 'approved' === $state && function_exists( 'WC' ) ) : ?>
					<form class="fpop__buy" method="post">
						<p class="fpop__price"></p>
						<div class="fpop__row">
							<label class="fpop__qty"><span>Qty</span><input type="number" name="quantity" value="1" min="1" step="1" inputmode="numeric"></label>
							<button type="submit" class="btn btn--solid fpop__add"><span>Add to cart</span></button>
						</div>
						<p class="fpop__msg" role="status"></p>
						<a class="link-arrow fpop__cart" href="<?php echo esc_url( wc_get_cart_url() ); ?>" hidden>View cart <span>&rarr;</span></a>
					</form>
				<?php else : ?>
					<div class="fpop__gate">
						<p><?php echo 'pending' === $state ? 'Your application is under review. Pricing and ordering open once you are approved.' : 'Wholesale pricing and ordering are available to approved retailers.'; ?></p>
						<div class="fpop__row">
							<?php oneam_member_cta(); ?>
							<?php if ( 'guest' === $state ) : ?>
								<a class="link-arrow" href="<?php echo esc_url( oneam_login_url() ); ?>">Log in <span>&rarr;</span></a>
							<?php endif; ?>
						</div>
					</div>
				<?php endif; ?>
			</div>
		</div>
	</div>
	<?php
}

/* ---------------------------------------------------------------------------
 * 리뷰 끄기 (연방 규정상 베이핑 제품 홍보에 후기 · 추천 사용 불가)
 * ------------------------------------------------------------------------- */
add_filter( 'pre_option_woocommerce_enable_reviews', function () { return 'no'; } );
add_filter(
	'comments_open',
	function ( $open, $post_id ) {
		return 'product' === get_post_type( $post_id ) ? false : $open;
	},
	10,
	2
);
add_filter(
	'woocommerce_product_tabs',
	function ( $tabs ) {
		unset( $tabs['reviews'] );
		return $tabs;
	},
	98
);
