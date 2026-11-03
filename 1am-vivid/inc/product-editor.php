<?php
/**
 * 상품 등록을 쉽게: 상품 편집 화면 맨 위 "1AM product setup" 칸
 *
 * 쇼핑몰 상품 등록처럼 한 화면에서
 *   기본 정보 → 주별 가격 → 맛(옵션) 목록 → 스펙 → 상품 페이지 아코디언 문구
 * 를 쓰고 "Update" 하면, WooCommerce 의 속성(Attributes) · 옵션(Variations)은 테마가 알아서 만들고 지웁니다.
 * (Attributes 탭에서 만들고 Variations 탭에서 Generate 할 필요 없음)
 *
 * 저장 위치는 예전과 같아서 사이트 화면 · 장바구니 · 주문은 그대로 동작합니다.
 *   - 맛 하나 = WooCommerce 옵션(variation) 하나 (속성 이름 "Flavour")
 *   - 스펙 = 옵션이 아닌 속성 (예: E-liquid: 2ml)
 *   - 주별 가격 = _oneam_prices,  기본 가격 = 각 옵션의 Regular price
 *
 * @package 1am-vivid
 */

defined( 'ABSPATH' ) || exit;

/** 이 칸이 켜져 있는지 (General 탭의 같은 칸은 숨김) */
function oneam_pe_active() {
	return true;
}

/** 지금 상품에서 칸에 채울 값 읽기 */
function oneam_pe_data( $product ) {
	$data = array(
		'price'   => '',
		'flavors' => array(),
		'specs'   => array(),
	);
	if ( ! $product ) {
		return $data;
	}
	$flav_key = '';
	foreach ( $product->get_attributes() as $key => $attr ) {
		$label = wc_attribute_label( $attr->get_name() );
		$vals  = $attr->is_taxonomy() ? wc_get_product_terms( $product->get_id(), $attr->get_name(), array( 'fields' => 'names' ) ) : $attr->get_options();
		if ( $attr->get_variation() ) {
			$flav_key = $key;
			continue;
		}
		$data['specs'][] = array( $label, implode( ', ', $vals ) );
	}
	if ( $product->is_type( 'variable' ) ) {
		foreach ( $product->get_children() as $vid ) {
			$v = wc_get_product( $vid );
			if ( ! $v ) {
				continue;
			}
			$attrs = $v->get_attributes();
			$name  = $flav_key && isset( $attrs[ $flav_key ] ) ? $attrs[ $flav_key ] : (string) reset( $attrs );
			if ( $flav_key && taxonomy_exists( $flav_key ) ) {
				$term = get_term_by( 'slug', $name, $flav_key );
				$name = $term ? $term->name : $name;
			}
			if ( '' === $data['price'] && '' !== (string) $v->get_regular_price( 'edit' ) ) {
				$data['price'] = (string) $v->get_regular_price( 'edit' );
			}
			$data['flavors'][] = array(
				'vid'   => $vid,
				'name'  => $name,
				'desc'  => wp_strip_all_tags( $v->get_description( 'edit' ) ),
				'cat'   => (string) $v->get_meta( '_oneam_cat' ),
				'c1'    => (string) $v->get_meta( '_oneam_c1' ),
				'c2'    => (string) $v->get_meta( '_oneam_c2' ),
				'img'   => (int) $v->get_image_id( 'edit' ),
				'stock' => 'outofstock' !== $v->get_stock_status( 'edit' ),
			);
		}
	}
	return $data;
}

/** 칸 그리기 */
add_action(
	'add_meta_boxes_product',
	function () {
		add_meta_box( 'oneam-product-setup', '1AM product setup — fill this in, then click Update', 'oneam_pe_box', 'product', 'oneam_top', 'high' );
	}
);
// 상품 이름 바로 아래에 (큰 설명 편집기보다 위)
add_action(
	'edit_form_after_title',
	function ( $post ) {
		if ( 'product' === $post->post_type ) {
			echo '<div style="margin-top:16px">';
			do_meta_boxes( get_current_screen(), 'oneam_top', $post );
			echo '</div>';
		}
	}
);

// 이미지 고르기 · 드래그 순서 바꾸기에 필요한 스크립트
add_action(
	'admin_enqueue_scripts',
	function () {
		$screen = get_current_screen();
		if ( $screen && 'product' === $screen->id ) {
			wp_enqueue_media();
			wp_enqueue_script( 'jquery-ui-sortable' );
		}
	}
);

function oneam_pe_row( $f, $i, $cats ) {
	$img = $f['img'] ? wp_get_attachment_image_url( $f['img'], 'thumbnail' ) : '';
	ob_start();
	?>
	<tr class="pe-flav">
		<td class="pe-drag" title="Drag to reorder">⋮⋮</td>
		<td class="pe-img">
			<input type="hidden" name="oneam_pe_flav[<?php echo esc_attr( $i ); ?>][vid]" value="<?php echo esc_attr( $f['vid'] ); ?>">
			<input type="hidden" class="pe-img-id" name="oneam_pe_flav[<?php echo esc_attr( $i ); ?>][img]" value="<?php echo esc_attr( $f['img'] ?: '' ); ?>">
			<button type="button" class="pe-img-btn" title="Choose image"><?php echo $img ? '<img src="' . esc_url( $img ) . '" alt="">' : '<span>+ Image</span>'; ?></button>
		</td>
		<td><input type="text" class="pe-name" name="oneam_pe_flav[<?php echo esc_attr( $i ); ?>][name]" value="<?php echo esc_attr( $f['name'] ); ?>" placeholder="e.g. Grape Ice"></td>
		<td><input type="text" name="oneam_pe_flav[<?php echo esc_attr( $i ); ?>][desc]" value="<?php echo esc_attr( $f['desc'] ); ?>" placeholder="e.g. Juicy grape · Icy finish"></td>
		<td>
			<select name="oneam_pe_flav[<?php echo esc_attr( $i ); ?>][cat]">
				<option value="">Auto</option>
				<?php foreach ( $cats as $k => $l ) : ?>
					<option value="<?php echo esc_attr( $k ); ?>" <?php selected( $f['cat'], $k ); ?>><?php echo esc_html( $l ); ?></option>
				<?php endforeach; ?>
			</select>
		</td>
		<td class="pe-colors">
			<input type="color" name="oneam_pe_flav[<?php echo esc_attr( $i ); ?>][c1]" value="<?php echo esc_attr( $f['c1'] ?: '#ffffff' ); ?>" title="Main colour (white = automatic)">
			<input type="color" name="oneam_pe_flav[<?php echo esc_attr( $i ); ?>][c2]" value="<?php echo esc_attr( $f['c2'] ?: '#ffffff' ); ?>" title="Second colour (white = automatic)">
		</td>
		<td class="pe-center"><label class="pe-switch"><input type="checkbox" name="oneam_pe_flav[<?php echo esc_attr( $i ); ?>][stock]" value="1" <?php checked( $f['stock'] ); ?>><span>In stock</span></label></td>
		<td class="pe-center"><button type="button" class="pe-del" title="Remove this flavour">✕</button></td>
	</tr>
	<?php
	return ob_get_clean();
}

function oneam_pe_box( $post ) {
	$product = wc_get_product( $post->ID );
	$d       = oneam_pe_data( $product );
	$cats    = oneam_flavor_categories();
	$pp      = $product ? oneam_province_prices( $product ) : array();
	$meta    = function ( $k ) use ( $product ) {
		return $product ? (string) $product->get_meta( $k ) : '';
	};
	$blank   = array(
		'vid'   => '',
		'name'  => '',
		'desc'  => '',
		'cat'   => '',
		'c1'    => '',
		'c2'    => '',
		'img'   => 0,
		'stock' => true,
	);
	wp_nonce_field( 'oneam_pe_save', 'oneam_pe_nonce' );
	?>
	<div class="pe">
		<?php if ( 'publish' !== $post->post_status ) : ?>
			<p class="pe-warn"><strong>Not on the site yet.</strong> This product is a <em><?php echo esc_html( get_post_status_object( $post->post_status )->label ?? $post->post_status ); ?></em>. Click <strong>Publish</strong> (top right) when it is ready — copies made with “Duplicate” always start as drafts.</p>
		<?php endif; ?>

		<section class="pe-sec">
			<h3><span>1</span> Basics</h3>
			<div class="pe-grid">
				<label>Label on the home page<input type="text" name="_oneam_label" value="<?php echo esc_attr( $meta( '_oneam_label' ) ); ?>" placeholder="e.g. 2ml Disposable"></label>
				<label>Nicotine<input type="text" name="_oneam_nicotine" value="<?php echo esc_attr( $meta( '_oneam_nicotine' ) ); ?>" placeholder="e.g. 20 mg/mL"></label>
				<label>Sold by (unit)<input type="text" name="_oneam_unit" value="<?php echo esc_attr( $meta( '_oneam_unit' ) ); ?>" placeholder="carton"></label>
				<label>Pieces per unit<input type="number" min="0" name="_oneam_pack" value="<?php echo esc_attr( $meta( '_oneam_pack' ) ); ?>" placeholder="e.g. 10"></label>
				<label class="pe-colorlbl">Product colours <span><input type="color" name="_oneam_c1" value="<?php echo esc_attr( $meta( '_oneam_c1' ) ?: '#ffffff' ); ?>"> <input type="color" name="_oneam_c2" value="<?php echo esc_attr( $meta( '_oneam_c2' ) ?: '#ffffff' ); ?>"> <small>white = automatic</small></span></label>
				<?php $lu = (int) $meta( '_oneam_lineup_img' ); ?>
				<div class="pe-imgwrap pe-lineup"><span>Home line-up image <small>(tall, transparent PNG/WebP — empty = product image)</small></span>
					<input type="hidden" class="pe-img-id" name="_oneam_lineup_img" value="<?php echo esc_attr( $lu ?: '' ); ?>">
					<button type="button" class="pe-img-btn pe-img-btn--tall" title="Choose image"><?php echo $lu ? '<img src="' . esc_url( wp_get_attachment_image_url( $lu, 'medium' ) ) . '" alt="">' : '<span>+ Image</span>'; ?></button>
					<button type="button" class="button-link pe-img-clear"<?php echo $lu ? '' : ' hidden'; ?>>Remove</button>
				</div>
				<div class="pe-checks">
					<label class="pe-check"><input type="checkbox" name="oneam_pe_home" value="1" <?php checked( 'yes' !== $meta( '_oneam_hide_home' ) ); ?>> Show on the home line-up</label>
					<label class="pe-check"><input type="checkbox" name="oneam_pe_menu" value="1" <?php checked( 'yes' !== $meta( '_oneam_hide_menu' ) ); ?>> Show in the Products menu</label>
					<label class="pe-check"><input type="checkbox" name="_oneam_soon" value="yes" id="pe-soon" <?php checked( 'yes', $meta( '_oneam_soon' ) ); ?>> Coming soon — shows a “Launching soon” page, cannot be ordered</label>
				</div>
			</div>
			<?php $shape = $meta( '_oneam_shape' ) ?: 'slim'; ?>
			<div class="pe-soonlook" id="pe-soonlook"<?php echo 'yes' === $meta( '_oneam_soon' ) ? '' : ' hidden'; ?>>
				<div>
					<p class="pe-soonlook__title">Coming soon look <small>(used when there is no Home line-up image)</small></p>
					<p class="pe-help">Pick the silhouette shape. Its colours are the <em>Product colours</em> above.</p>
					<div class="pe-shapes">
						<?php foreach ( array( 'slim' => 'Slim device', 'max' => 'MAX device', 'refill' => 'Refill pod' ) as $k => $l ) : ?>
							<label><input type="radio" name="_oneam_shape" value="<?php echo esc_attr( $k ); ?>" <?php checked( $shape, $k ); ?>> <?php echo esc_html( $l ); ?></label>
						<?php endforeach; ?>
					</div>
				</div>
				<div class="pe-ghost" id="pe-ghost" data-shape="<?php echo esc_attr( $shape ); ?>" style="--c1:<?php echo esc_attr( $meta( '_oneam_c1' ) ?: '#8A3FFC' ); ?>;--c2:<?php echo esc_attr( $meta( '_oneam_c2' ) ?: '#16C75A' ); ?>">
					<span class="pe-ghost__cap"></span><span class="pe-ghost__body"><b><?php echo esc_html( get_the_title( $post ) ?: 'NEW' ); ?></b></span><span class="pe-ghost__base"></span>
				</div>
			</div>
		</section>

		<section class="pe-sec">
			<h3><span>2</span> Price</h3>
			<div class="pe-grid">
				<label>Price per unit (CAD, before GST)<input type="number" step="0.01" min="0" name="oneam_pe_price" value="<?php echo esc_attr( $d['price'] ); ?>" placeholder="e.g. 80"></label>
			</div>
			<p class="pe-help">Different price in some provinces? Add them below. Only provinces listed here can order this product. Leave the list empty to sell everywhere at the price above.</p>
			<table class="pe-table pe-pp">
				<thead><tr><th>Province</th><th>Price per unit</th><th></th></tr></thead>
				<tbody>
					<?php foreach ( $pp as $code => $price ) : ?>
						<tr>
							<td><select name="oneam_pe_pp_code[]"><?php foreach ( oneam_provinces() as $c => $l ) : ?><option value="<?php echo esc_attr( $c ); ?>" <?php selected( $code, $c ); ?>><?php echo esc_html( $l ); ?></option><?php endforeach; ?></select></td>
							<td><input type="number" step="0.01" min="0" name="oneam_pe_pp_price[]" value="<?php echo esc_attr( $price ); ?>"></td>
							<td class="pe-center"><button type="button" class="pe-del">✕</button></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
			<template id="pe-pp-tpl"><tr><td><select name="oneam_pe_pp_code[]"><?php foreach ( oneam_provinces() as $c => $l ) : ?><option value="<?php echo esc_attr( $c ); ?>"><?php echo esc_html( $l ); ?></option><?php endforeach; ?></select></td><td><input type="number" step="0.01" min="0" name="oneam_pe_pp_price[]"></td><td class="pe-center"><button type="button" class="pe-del">✕</button></td></tr></template>
			<button type="button" class="button pe-add-pp">+ Add province price</button>
		</section>

		<section class="pe-sec">
			<h3><span>3</span> Flavours <small id="pe-count"><?php echo count( $d['flavors'] ); ?></small></h3>
			<p class="pe-help">One row = one flavour customers can pick. Drag ⋮⋮ to change the order. Removing a row removes that flavour from the site when you click Update.</p>
			<div class="pe-scroll">
				<table class="pe-table pe-flavs">
					<thead><tr><th></th><th>Image</th><th>Flavour name</th><th>Taste notes</th><th>Type</th><th>Colours</th><th>Stock</th><th></th></tr></thead>
					<tbody>
						<?php
						foreach ( $d['flavors'] as $i => $f ) {
							echo oneam_pe_row( $f, $i, $cats ); // phpcs:ignore
						}
						?>
					</tbody>
				</table>
			</div>
			<template id="pe-flav-tpl"><?php echo oneam_pe_row( $blank, '__i__', $cats ); // phpcs:ignore ?></template>
			<button type="button" class="button button-primary pe-add-flav">+ Add flavour</button>
		</section>

		<section class="pe-sec">
			<h3><span>4</span> Specs</h3>
			<p class="pe-help">Shown in the spec boxes on the product page and the home line-up (e.g. E-liquid · 2ml, Battery · 400 mAh).</p>
			<table class="pe-table pe-specs">
				<thead><tr><th>Name</th><th>Value</th><th></th></tr></thead>
				<tbody>
					<?php foreach ( $d['specs'] as $s ) : ?>
						<tr><td><input type="text" name="oneam_pe_spec_k[]" value="<?php echo esc_attr( $s[0] ); ?>"></td><td><input type="text" name="oneam_pe_spec_v[]" value="<?php echo esc_attr( $s[1] ); ?>"></td><td class="pe-center"><button type="button" class="pe-del">✕</button></td></tr>
					<?php endforeach; ?>
				</tbody>
			</table>
			<template id="pe-spec-tpl"><tr><td><input type="text" name="oneam_pe_spec_k[]" placeholder="e.g. E-liquid"></td><td><input type="text" name="oneam_pe_spec_v[]" placeholder="e.g. 2ml"></td><td class="pe-center"><button type="button" class="pe-del">✕</button></td></tr></template>
			<button type="button" class="button pe-add-spec">+ Add spec</button>
		</section>

		<section class="pe-sec">
			<h3><span>5</span> Product page text</h3>
			<p class="pe-help">Where each part of the product page comes from:</p>
			<ul class="pe-map">
				<li><b>Short text under the name (home line-up)</b> → the <em>Product short description</em> box further down this page.</li>
				<li><b>“Description” accordion</b> → the big text editor at the top of this page.</li>
				<li><b>“Flavour profiles” accordion</b> → the <em>Taste notes</em> in step 3.</li>
				<li><b>The three accordions below</b> → write here for this product only, or leave empty to use the site-wide text (Appearance → Customize → 1AM Settings → Shop product page).</li>
			</ul>
			<?php
			$acc = array(
				'_oneam_acc_regions'    => array( '“Sales regions & excise stamps” accordion', 'oneam_shop_regions' ),
				'_oneam_acc_shipping'   => array( '“Shipping & returns” accordion', 'oneam_shop_shipping' ),
				'_oneam_acc_compliance' => array( '“Compliance & terms” accordion (one point per line)', 'oneam_shop_compliance' ),
			);
			foreach ( $acc as $k => $a ) :
				?>
				<label class="pe-block"><?php echo esc_html( $a[0] ); ?>
					<textarea name="<?php echo esc_attr( $k ); ?>" rows="3" placeholder="<?php echo esc_attr( "Site-wide text (used when empty):\n" . oneam_opt( $a[1] ) ); ?>"><?php echo esc_textarea( $meta( $k ) ); ?></textarea>
				</label>
			<?php endforeach; ?>
		</section>

		<p class="pe-save"><button type="submit" class="button button-primary button-large" name="<?php echo 'publish' === $post->post_status ? 'save' : 'publish'; ?>" value="<?php echo 'publish' === $post->post_status ? 'Update' : 'Publish'; ?>"><?php echo 'publish' === $post->post_status ? 'Save product' : 'Save &amp; publish'; ?></button></p>
		<p class="pe-foot">The main photo for the home line-up is the <em>Product image</em> box on the right. Click <strong>Update</strong> (or <strong>Publish</strong>) at the top right to save. Advanced users can still open the WooCommerce Attributes and Variations tabs: <a href="#" class="pe-adv">show advanced tabs</a>.</p>
	</div>

	<style>
		#oneam-product-setup .inside { margin: 0; padding: 0; }
		.pe { padding: 4px 16px 16px; }
		.pe-warn { margin: 12px 0 0; padding: 12px 14px; border-left: 4px solid #dba617; background: #fcf9e8; }
		.pe-sec { padding: 18px 0; border-bottom: 1px solid #e5e5e5; }
		.pe-sec h3 { display: flex; align-items: center; gap: 10px; margin: 0 0 12px; font-size: 15px; }
		.pe-sec h3 span { display: inline-grid; place-items: center; width: 24px; height: 24px; border-radius: 50%; background: #2271b1; color: #fff; font-size: 12px; }
		.pe-sec h3 small { padding: 1px 8px; border-radius: 99px; background: #f0f0f1; font-weight: 600; }
		.pe-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 12px 16px; align-items: start; }
		.pe-grid .pe-lineup { grid-row: span 2; }
		.pe-grid label, .pe-block { display: grid; gap: 4px; font-weight: 600; }
		.pe-grid input[type=text], .pe-grid input[type=number] { width: 100%; }
		.pe-block { margin-top: 12px; }
		.pe-block textarea { width: 100%; }
		.pe-colorlbl span { display: flex; align-items: center; gap: 6px; font-weight: 400; }
		.pe-check { display: flex !important; align-items: center; gap: 8px; font-weight: 400 !important; }
		.pe-checks { display: grid; gap: 6px; grid-column: 1 / -1; }
		.pe-soonlook { display: flex; flex-wrap: wrap; align-items: center; gap: 24px; margin-top: 14px; padding: 14px 16px; border-radius: 8px; background: #f6f7f7; }
		.pe-soonlook[hidden] { display: none; }
		.pe-soonlook__title { margin: 0 0 4px; font-weight: 600; }
		.pe-shapes { display: flex; flex-wrap: wrap; gap: 14px; }
		.pe-ghost { display: grid; grid-template-rows: 14% 1fr 7%; height: 170px; aspect-ratio: 300 / 1400; }
		.pe-ghost[data-shape=max] { aspect-ratio: 420 / 1400; }
		.pe-ghost[data-shape=refill] { aspect-ratio: 330 / 1400; grid-template-rows: 22% 1fr 0%; }
		.pe-ghost__cap { border-radius: 40% 40% 4px 4px; background: #e3e3e6; box-shadow: inset 0 0 0 1px #c9c9cf; }
		.pe-ghost__body { margin: 2px 0; display: grid; place-items: center; overflow: hidden; background: linear-gradient(180deg, var(--c1), var(--c2)); }
		.pe-ghost__body b { writing-mode: vertical-rl; transform: rotate(180deg); color: #fff; font-size: 10px; letter-spacing: .08em; text-transform: uppercase; white-space: nowrap; }
		.pe-ghost__base { border-radius: 0 0 3px 3px; background: linear-gradient(90deg, #8d8f93, #d8d9dc 45%, #7c7e82); }
		
		.pe-help { margin: 0 0 10px; color: #646970; }
		.pe-scroll { overflow-x: auto; }
		.pe-table { width: 100%; border-collapse: collapse; margin: 0 0 10px; }
		.pe-table th { padding: 6px 8px; text-align: left; font-size: 12px; color: #646970; border-bottom: 1px solid #dcdcde; }
		.pe-table td { padding: 6px 8px; border-bottom: 1px solid #f0f0f1; vertical-align: middle; }
		.pe-table input[type=text], .pe-table input[type=number], .pe-table select { width: 100%; min-width: 120px; }
		.pe-flavs td:nth-child(3) input { min-width: 150px; font-weight: 600; }
		.pe-flavs td:nth-child(4) input { min-width: 200px; }
		.pe-center { text-align: center; }
		.pe-drag { cursor: grab; color: #a7aaad; font-size: 16px; width: 18px; }
		.pe-img-btn { display: grid; place-items: center; width: 52px; height: 52px; padding: 0; border: 1px dashed #c3c4c7; border-radius: 8px; background: #f6f7f7; cursor: pointer; overflow: hidden; }
		.pe-img-btn img { width: 100%; height: 100%; object-fit: contain !important; }
		.pe-img-btn span { font-size: 11px; color: #2271b1; }
		.pe-colors { white-space: nowrap; }
		.pe-imgwrap { display: grid; gap: 6px; justify-items: start; font-weight: 600; }
		.pe-imgwrap small { font-weight: 400; color: #646970; }
		.pe-img-btn--tall { width: 90px; height: 130px; }
		.pe-colors input { width: 32px; height: 28px; padding: 0; border: 1px solid #dcdcde; }
		.pe-switch { display: inline-flex; align-items: center; gap: 4px; white-space: nowrap; }
		.pe-del { width: 28px; height: 28px; border: 0; border-radius: 6px; background: none; color: #b32d2e; cursor: pointer; }
		.pe-del:hover { background: #fcf0f1; }
		.pe-flav.is-placeholder { height: 64px; background: #f0f6fc; }
		.pe-map { margin: 0 0 4px 18px; list-style: disc; }
		.pe-foot { margin: 8px 0 0; color: #646970; }
		.pe-save { margin: 16px 0 0; }
		body:not(.oneam-pe-adv) #woocommerce-product-data .attribute_options,
		body:not(.oneam-pe-adv) #woocommerce-product-data .variations_options { display: none !important; }
	</style>
	<script>
	(function ($) {
		var box = document.getElementById('oneam-product-setup');
		if (!box) return;
		var n = 1000;
		function add(tplId, tbody) {
			var t = document.getElementById(tplId).innerHTML.replace(/__i__/g, String(n++));
			tbody.insertAdjacentHTML('beforeend', t);
			return tbody.lastElementChild;
		}
		function count() { document.getElementById('pe-count').textContent = box.querySelectorAll('.pe-flavs tbody tr').length; }
		box.addEventListener('click', function (e) {
			var t = e.target;
			if (t.closest('.pe-add-flav')) { var r = add('pe-flav-tpl', box.querySelector('.pe-flavs tbody')); r.querySelector('.pe-name').focus(); count(); }
			if (t.closest('.pe-add-pp')) add('pe-pp-tpl', box.querySelector('.pe-pp tbody'));
			if (t.closest('.pe-add-spec')) add('pe-spec-tpl', box.querySelector('.pe-specs tbody'));
			if (t.closest('.pe-del')) { var tr = t.closest('tr'); var nm = tr.querySelector('.pe-name'); if (!nm || !nm.value || window.confirm('Remove “' + nm.value + '”? It disappears from the site when you click Update.')) { tr.remove(); count(); } }
			if (t.closest('.pe-adv')) { e.preventDefault(); document.body.classList.toggle('oneam-pe-adv'); }
			if (t.closest('.pe-img-clear')) { var w = t.closest('.pe-imgwrap'); w.querySelector('.pe-img-id').value = ''; w.querySelector('.pe-img-btn').innerHTML = '<span>+ Image</span>'; t.hidden = true; }
			var ib = t.closest('.pe-img-btn');
			if (ib && window.wp && wp.media) {
				var frame = wp.media({ title: 'Choose flavour image', multiple: false, library: { type: 'image' } });
				frame.on('select', function () {
					var a = frame.state().get('selection').first().toJSON();
					var wrap = ib.closest('.pe-imgwrap, td');
					wrap.querySelector('.pe-img-id').value = a.id;
					var clr = wrap.querySelector('.pe-img-clear'); if (clr) clr.hidden = false;
					ib.innerHTML = '<img src="' + ((a.sizes && a.sizes.thumbnail) ? a.sizes.thumbnail.url : a.url) + '" alt="">';
				});
				frame.open();
			}
		});
		var soon = document.getElementById('pe-soon'), look = document.getElementById('pe-soonlook'), gh = document.getElementById('pe-ghost');
		if (soon && look) soon.addEventListener('change', function () { look.hidden = !soon.checked; });
		box.addEventListener('change', function (e) {
			if (!gh) return;
			if (e.target.name === '_oneam_shape') gh.dataset.shape = e.target.value;
			if (e.target.name === '_oneam_c1' && e.target.value.toLowerCase() !== '#ffffff') gh.style.setProperty('--c1', e.target.value);
			if (e.target.name === '_oneam_c2' && e.target.value.toLowerCase() !== '#ffffff') gh.style.setProperty('--c2', e.target.value);
		});
		if ($ && $.fn.sortable) $(box.querySelector('.pe-flavs tbody')).sortable({ handle: '.pe-drag', placeholder: 'pe-flav is-placeholder', axis: 'y' });
	})(window.jQuery);
	</script>
	<?php
}

// 주별 가격 표 → 예전 저장 형식(_oneam_prices)으로 (pricing.php 의 저장 코드가 그대로 저장)
add_action(
	'woocommerce_admin_process_product_object',
	function ( $product ) {
		// phpcs:disable WordPress.Security.NonceVerification -- WooCommerce verifies the product save nonce.
		if ( ! isset( $_POST['oneam_pe_nonce'] ) ) {
			return;
		}
		$codes  = isset( $_POST['oneam_pe_pp_code'] ) ? array_map( 'sanitize_key', (array) wp_unslash( $_POST['oneam_pe_pp_code'] ) ) : array();
		$prices = isset( $_POST['oneam_pe_pp_price'] ) ? (array) wp_unslash( $_POST['oneam_pe_pp_price'] ) : array();
		$lines  = array();
		foreach ( $codes as $i => $c ) {
			$p = isset( $prices[ $i ] ) ? (float) $prices[ $i ] : 0;
			if ( $c && $p > 0 ) {
				$lines[ strtoupper( $c ) ] = strtoupper( $c ) . ': ' . $p;
			}
		}
		$_POST['_oneam_prices'] = implode( "\n", $lines );
		$product->update_meta_data( '_oneam_lineup_img', isset( $_POST['_oneam_lineup_img'] ) ? absint( $_POST['_oneam_lineup_img'] ) ?: '' : '' );
		$product->update_meta_data( '_oneam_hide_home', empty( $_POST['oneam_pe_home'] ) ? 'yes' : '' );
		$product->update_meta_data( '_oneam_hide_menu', empty( $_POST['oneam_pe_menu'] ) ? 'yes' : '' );
		$shape = isset( $_POST['_oneam_shape'] ) ? sanitize_key( wp_unslash( $_POST['_oneam_shape'] ) ) : '';
		$product->update_meta_data( '_oneam_shape', in_array( $shape, array( 'slim', 'max', 'refill' ), true ) ? $shape : '' );
		foreach ( array( '_oneam_acc_regions', '_oneam_acc_shipping', '_oneam_acc_compliance' ) as $k ) {
			$product->update_meta_data( $k, isset( $_POST[ $k ] ) ? sanitize_textarea_field( wp_unslash( $_POST[ $k ] ) ) : '' );
		}
		// phpcs:enable
	},
	1
);

// 맛 표 · 스펙 → WooCommerce 속성 · 옵션 (WooCommerce 저장이 끝난 뒤)
add_action(
	'woocommerce_process_product_meta',
	function ( $post_id ) {
		if ( ! isset( $_POST['oneam_pe_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['oneam_pe_nonce'] ) ), 'oneam_pe_save' ) || ! current_user_can( 'edit_product', $post_id ) ) {
			return;
		}
		$rows = array();
		$seen = array();
		foreach ( (array) ( $_POST['oneam_pe_flav'] ?? array() ) as $r ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
			$name = isset( $r['name'] ) ? trim( sanitize_text_field( wp_unslash( $r['name'] ) ) ) : '';
			$name = str_replace( '|', '/', $name ); // WooCommerce 옵션 구분자
			if ( '' === $name || isset( $seen[ strtolower( $name ) ] ) ) {
				continue;
			}
			$seen[ strtolower( $name ) ] = true;
			$c1                          = isset( $r['c1'] ) ? sanitize_hex_color( wp_unslash( $r['c1'] ) ) : '';
			$c2                          = isset( $r['c2'] ) ? sanitize_hex_color( wp_unslash( $r['c2'] ) ) : '';
			$cat                         = isset( $r['cat'] ) ? sanitize_key( wp_unslash( $r['cat'] ) ) : '';
			$rows[]                      = array(
				'vid'   => isset( $r['vid'] ) ? absint( $r['vid'] ) : 0,
				'name'  => $name,
				'desc'  => isset( $r['desc'] ) ? sanitize_text_field( wp_unslash( $r['desc'] ) ) : '',
				'cat'   => array_key_exists( $cat, oneam_flavor_categories() ) ? $cat : '',
				'c1'    => ( $c1 && '#ffffff' !== strtolower( $c1 ) ) ? $c1 : '',
				'c2'    => ( $c2 && '#ffffff' !== strtolower( $c2 ) ) ? $c2 : '',
				'img'   => isset( $r['img'] ) ? absint( $r['img'] ) : 0,
				'stock' => ! empty( $r['stock'] ),
			);
		}
		$specs = array();
		$keys  = isset( $_POST['oneam_pe_spec_k'] ) ? (array) wp_unslash( $_POST['oneam_pe_spec_k'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		$vals  = isset( $_POST['oneam_pe_spec_v'] ) ? (array) wp_unslash( $_POST['oneam_pe_spec_v'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		foreach ( $keys as $i => $k ) {
			$k = trim( sanitize_text_field( $k ) );
			$v = isset( $vals[ $i ] ) ? trim( sanitize_text_field( $vals[ $i ] ) ) : '';
			if ( '' !== $k && '' !== $v && 'flavour' !== sanitize_title( $k ) ) {
				$specs[ $k ] = $v;
			}
		}
		$price = isset( $_POST['oneam_pe_price'] ) && '' !== $_POST['oneam_pe_price'] ? wc_format_decimal( wp_unslash( $_POST['oneam_pe_price'] ) ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput

		oneam_pe_sync( $post_id, $rows, $specs, $price );
	},
	50
);

/** 맛 · 스펙을 WooCommerce 상품에 반영 (없어진 맛의 옵션은 삭제) */
function oneam_pe_sync( $post_id, $rows, $specs, $price ) {
	$product = wc_get_product( $post_id );
	if ( ! $product ) {
		return;
	}
	// 맛이 있으면 옵션 상품(variable)으로
	if ( $rows && ! $product->is_type( 'variable' ) ) {
		wp_set_object_terms( $post_id, 'variable', 'product_type' );
		$product = new WC_Product_Variable( $post_id );
	}

	$attrs = array();
	$pos   = 0;
	foreach ( $specs as $k => $v ) {
		$a = new WC_Product_Attribute();
		$a->set_id( 0 );
		$a->set_name( $k );
		$a->set_options( array( $v ) );
		$a->set_visible( true );
		$a->set_variation( false );
		$a->set_position( $pos++ );
		$attrs[ sanitize_title( $k ) ] = $a;
	}
	if ( $rows ) {
		$a = new WC_Product_Attribute();
		$a->set_id( 0 );
		$a->set_name( 'Flavour' );
		$a->set_options( wp_list_pluck( $rows, 'name' ) );
		$a->set_visible( true );
		$a->set_variation( true );
		$a->set_position( $pos++ );
		$attrs['flavour'] = $a;
	}
	$product->set_attributes( $attrs );
	$product->save();

	if ( ! $product->is_type( 'variable' ) ) {
		return;
	}
	$keep = array();
	foreach ( $rows as $i => $r ) {
		$v = $r['vid'] ? wc_get_product( $r['vid'] ) : null;
		if ( ! $v || ! $v->is_type( 'variation' ) || (int) $v->get_parent_id() !== (int) $post_id ) {
			$v = new WC_Product_Variation();
			$v->set_parent_id( $post_id );
		}
		$v->set_attributes( array( 'flavour' => $r['name'] ) );
		$v->set_menu_order( $i );
		$v->set_status( 'publish' );
		$v->set_description( $r['desc'] );
		$v->set_image_id( $r['img'] ?: '' );
		$v->set_stock_status( $r['stock'] ? 'instock' : 'outofstock' );
		if ( '' !== $price ) {
			$v->set_regular_price( $price );
		}
		$v->update_meta_data( '_oneam_cat', $r['cat'] );
		$v->update_meta_data( '_oneam_c1', $r['c1'] );
		$v->update_meta_data( '_oneam_c2', $r['c2'] );
		$keep[] = $v->save();
	}
	foreach ( $product->get_children() as $child ) {
		if ( ! in_array( (int) $child, array_map( 'intval', $keep ), true ) ) {
			$c = wc_get_product( $child );
			if ( $c ) {
				$c->delete( true );
			}
		}
	}
	WC_Product_Variable::sync( $post_id );
	wc_delete_product_transients( $post_id );
}

// "Duplicate" 로 만든 사본: 목록에서도 눈에 띄게 (초안 · 사이트에 안 보임)
add_filter(
	'display_post_states',
	function ( $states, $post ) {
		if ( 'product' === $post->post_type && 'draft' === $post->post_status ) {
			$states['oneam_draft'] = 'Draft — not on the site yet';
		}
		return $states;
	},
	10,
	2
);
