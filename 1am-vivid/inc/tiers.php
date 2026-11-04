<?php
/**
 * 리테일러 등급 (VIP · Gold · Platinum …) — 등급마다 할인율 하나, 상품별 예외 가격
 *
 * - 등급 관리: Users → Retailer tiers (추가 · 수정 · 삭제, 아이콘 · 색 · 할인율)
 * - 매장에 등급 주기: 사용자 화면 맨 위 "1AM retailer account" 카드의 Tier
 * - 상품별 예외: 상품 화면 1AM product setup → ② Price → "Tier prices (optional)"
 *   (등급을 골라 그 상품만 고정 가격 또는 다른 할인율. 추가하지 않은 등급은 기본 할인율)
 * - 가격 계산: (주별 가격 또는 기본 가격) → 등급 할인. 고객은 자기 가격만 보고, 원래 가격은 줄 그어 표시
 *
 * @package 1am-vivid
 */

defined( 'ABSPATH' ) || exit;

/** 고를 수 있는 아이콘 */
function oneam_tier_icons() {
	return array(
		'crown'   => '<path d="M3 8l4.5 4L12 5l4.5 7L21 8l-2 11H5z"/>',
		'star'    => '<path d="M12 3l2.8 5.7 6.2.9-4.5 4.4 1.1 6.2L12 17.3l-5.6 2.9 1.1-6.2L3 9.6l6.2-.9z"/>',
		'diamond' => '<path d="M6 3h12l3 6-9 12L3 9z"/><path d="M3 9h18M9 3l3 18M15 3l-3 18"/>',
		'medal'   => '<circle cx="12" cy="15" r="5.5"/><path d="M8 3l4 6 4-6"/><path d="M12 12.5l.9 1.8 2 .3-1.4 1.4.3 2-1.8-.9-1.8.9.3-2-1.4-1.4 2-.3z"/>',
		'shield'  => '<path d="M12 3l8 3v6c0 5-3.5 8-8 9-4.5-1-8-4-8-9V6z"/><path d="M8.5 12l2.5 2.5 4.5-5"/>',
		'bolt'    => '<path d="M13 2L4 14h7l-1 8 9-12h-7z"/>',
		'heart'   => '<path d="M12 20.5s-8-5.2-8-10.8A4.6 4.6 0 0 1 12 7a4.6 4.6 0 0 1 8 2.7c0 5.6-8 10.8-8 10.8z"/>',
		'flame'   => '<path d="M12 3c3 4 6.5 6.2 6.5 11a6.5 6.5 0 0 1-13 0c0-3 2-4.5 2.3-7.2 2 1.2 3 3.2 3 5.2 1.3-2.8 1.5-6 1.2-9z"/>',
	);
}

function oneam_tier_icon_svg( $key, $size = 16 ) {
	$icons = oneam_tier_icons();
	$path  = $icons[ $key ] ?? $icons['star'];
	return '<svg class="tier-ico" viewBox="0 0 24 24" width="' . (int) $size . '" height="' . (int) $size . '" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $path . '</svg>';
}

/** 기본 등급 (처음 한 번 저장 — 이후에는 Users → Retailer tiers 에서 수정) */
function oneam_default_tiers() {
	return array(
		'vip'      => array( 'name' => 'VIP', 'discount' => 5, 'icon' => 'crown', 'color' => '#8A3FFC' ),
		'gold'     => array( 'name' => 'Gold', 'discount' => 7, 'icon' => 'star', 'color' => '#C8961E' ),
		'platinum' => array( 'name' => 'Platinum', 'discount' => 10, 'icon' => 'diamond', 'color' => '#5B6B82' ),
	);
}

function oneam_tiers() {
	$t = get_option( 'oneam_tiers', null );
	if ( null === $t ) {
		$t = oneam_default_tiers();
		update_option( 'oneam_tiers', $t, false );
	}
	return is_array( $t ) ? $t : array();
}

/** 매장의 등급 id ('' = 기본) — 삭제된 등급이면 기본 */
function oneam_user_tier( $user_id = 0 ) {
	$user_id = $user_id ?: get_current_user_id();
	if ( ! $user_id ) {
		return '';
	}
	$id = (string) get_user_meta( $user_id, 'oneam_tier', true );
	return isset( oneam_tiers()[ $id ] ) ? $id : '';
}

/** 등급 배지 HTML */
function oneam_tier_badge( $id, $with_discount = false ) {
	$t = oneam_tiers()[ $id ] ?? null;
	if ( ! $t ) {
		return '';
	}
	return sprintf(
		'<span class="tier-badge" style="--tc:%1$s">%2$s<span>%3$s%4$s</span></span>',
		esc_attr( $t['color'] ),
		oneam_tier_icon_svg( $t['icon'] ),
		esc_html( $t['name'] ),
		$with_discount ? ' · −' . esc_html( rtrim( rtrim( number_format( (float) $t['discount'], 2, '.', '' ), '0' ), '.' ) ) . '%' : ''
	);
}

/* ---------------------------------------------------------------------------
 * 가격 계산
 * ------------------------------------------------------------------------- */

/** 상품의 등급 예외: [ tier_id => [ 'mode' => 'fixed'|'pct', 'value' => float ] ] */
function oneam_product_tier_prices( $product ) {
	$parent = $product && $product->is_type( 'variation' ) ? wc_get_product( $product->get_parent_id() ) : $product;
	$v      = $parent ? $parent->get_meta( '_oneam_tier_prices' ) : array();
	return is_array( $v ) ? $v : array();
}

/** 지금 손님 등급의 가격 (등급이 없거나 승인 전이면 null) */
function oneam_tier_price( $product, $base ) {
	if ( '' === $base || null === $base || ! is_numeric( $base ) ) {
		return null;
	}
	$tier = oneam_user_tier();
	if ( '' === $tier || 'approved' !== oneam_member_state() ) {
		return null;
	}
	$rule = oneam_product_tier_prices( $product )[ $tier ] ?? null;
	if ( $rule && 'fixed' === $rule['mode'] && (float) $rule['value'] > 0 ) {
		$p = (float) $rule['value'];
	} else {
		$pct = $rule && 'pct' === $rule['mode'] ? (float) $rule['value'] : (float) ( oneam_tiers()[ $tier ]['discount'] ?? 0 );
		if ( $pct <= 0 ) {
			return null;
		}
		$p = (float) $base * ( 1 - min( $pct, 90 ) / 100 );
	}
	return (string) round( $p, 2 );
}

// 판매가(sale) 자리에 등급 가격 → WooCommerce 가 원래 가격에 줄을 긋고 등급 가격을 보여줌
$oneam_tier_sale = function ( $sale, $product ) {
	if ( is_admin() && ! wp_doing_ajax() ) {
		return $sale;
	}
	$t = oneam_tier_price( $product, $product->get_regular_price() );
	return null === $t ? $sale : $t;
};
$oneam_tier_now = function ( $price, $product ) {
	if ( is_admin() && ! wp_doing_ajax() ) {
		return $price;
	}
	$t = oneam_tier_price( $product, $product->get_regular_price() );
	return null === $t ? $price : $t;
};
foreach ( array( 'woocommerce_product_get_sale_price', 'woocommerce_product_variation_get_sale_price' ) as $oneam_h ) {
	add_filter( $oneam_h, $oneam_tier_sale, 30, 2 );
}
foreach ( array( 'woocommerce_product_get_price', 'woocommerce_product_variation_get_price' ) as $oneam_h ) {
	add_filter( $oneam_h, $oneam_tier_now, 30, 2 );
}
foreach ( array( 'woocommerce_variation_prices_price', 'woocommerce_variation_prices_sale_price' ) as $oneam_h ) {
	add_filter(
		$oneam_h,
		function ( $price, $variation ) {
			$t = oneam_tier_price( $variation, $variation->get_regular_price() );
			return null === $t ? $price : $t;
		},
		30,
		2
	);
}
add_filter(
	'woocommerce_get_variation_prices_hash',
	function ( $hash ) {
		$hash[] = 'tier-' . oneam_user_tier() . '-' . md5( wp_json_encode( oneam_tiers() ) );
		return $hash;
	}
);

// 상품 페이지 가격 아래: "Your VIP price"
add_filter(
	'woocommerce_get_price_html',
	function ( $html, $product ) {
		$tier = oneam_user_tier();
		if ( ! $tier || '' === $html || is_admin() || 'approved' !== oneam_member_state() || null === oneam_tier_price( $product, $product->get_regular_price() ?: $product->get_price() ) ) {
			return $html;
		}
		return $html . ' <span class="tier-price-note">' . oneam_tier_badge( $tier ) . ' price</span>';
	},
	40,
	2
);

// 주문에 등급 기록 (관리자 주문 화면 · 인보이스)
add_action(
	'woocommerce_checkout_create_order',
	function ( $order ) {
		$tier = oneam_user_tier( $order->get_customer_id() );
		if ( $tier ) {
			$t = oneam_tiers()[ $tier ];
			$order->update_meta_data( '_oneam_tier', $t['name'] . ' (−' . (float) $t['discount'] . '%)' );
		}
	}
);
add_action(
	'woocommerce_store_api_checkout_update_order_from_request',
	function ( $order ) {
		$tier = oneam_user_tier( $order->get_customer_id() );
		if ( $tier ) {
			$t = oneam_tiers()[ $tier ];
			$order->update_meta_data( '_oneam_tier', $t['name'] . ' (−' . (float) $t['discount'] . '%)' );
		}
	}
);
add_action(
	'woocommerce_admin_order_data_after_billing_address',
	function ( $order ) {
		if ( $order->get_meta( '_oneam_tier' ) ) {
			echo '<p><strong>Retailer tier:</strong> ' . esc_html( $order->get_meta( '_oneam_tier' ) ) . '</p>';
		}
	},
	20
);

/* ---------------------------------------------------------------------------
 * 관리: Users → Retailer tiers
 * ------------------------------------------------------------------------- */
add_action(
	'admin_menu',
	function () {
		add_users_page( 'Retailer tiers', 'Retailer tiers', 'edit_users', 'oneam-tiers', 'oneam_tiers_page' );
	}
);

add_action(
	'admin_post_oneam_save_tiers',
	function () {
		if ( ! current_user_can( 'edit_users' ) ) {
			wp_die( 'Not allowed.' );
		}
		check_admin_referer( 'oneam_save_tiers' );
		$rows = isset( $_POST['tier'] ) ? (array) wp_unslash( $_POST['tier'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		$out  = array();
		foreach ( $rows as $r ) {
			$name = isset( $r['name'] ) ? trim( sanitize_text_field( $r['name'] ) ) : '';
			if ( '' === $name ) {
				continue;
			}
			$id = isset( $r['id'] ) && '' !== $r['id'] ? sanitize_key( $r['id'] ) : sanitize_key( sanitize_title( $name ) );
			$id = $id ?: 'tier';
			$base = $id;
			$n    = 2;
			while ( isset( $out[ $id ] ) ) {
				$id = $base . '-' . $n++;
			}
			$icon        = isset( $r['icon'] ) ? sanitize_key( $r['icon'] ) : 'star';
			$out[ $id ] = array(
				'name'     => $name,
				'discount' => max( 0, min( 90, (float) ( $r['discount'] ?? 0 ) ) ),
				'icon'     => isset( oneam_tier_icons()[ $icon ] ) ? $icon : 'star',
				'color'    => sanitize_hex_color( $r['color'] ?? '' ) ?: '#8A3FFC',
			);
		}
		update_option( 'oneam_tiers', $out, false );
		wp_safe_redirect( admin_url( 'users.php?page=oneam-tiers&saved=1' ) );
		exit;
	}
);

function oneam_tier_row( $id, $t, $i ) {
	ob_start();
	?>
	<tr>
		<td class="tr-prev"><?php echo oneam_tier_badge( $id ?: 'preview' ) ?: '<span class="tier-badge" style="--tc:' . esc_attr( $t['color'] ) . '">' . oneam_tier_icon_svg( $t['icon'] ) . '<span>' . esc_html( $t['name'] ?: 'New tier' ) . '</span></span>'; // phpcs:ignore ?></td>
		<td>
			<input type="hidden" name="tier[<?php echo esc_attr( $i ); ?>][id]" value="<?php echo esc_attr( $id ); ?>">
			<input type="text" class="tr-name" name="tier[<?php echo esc_attr( $i ); ?>][name]" value="<?php echo esc_attr( $t['name'] ); ?>" placeholder="e.g. VIP">
		</td>
		<td><input type="number" class="tr-disc" step="0.5" min="0" max="90" name="tier[<?php echo esc_attr( $i ); ?>][discount]" value="<?php echo esc_attr( $t['discount'] ); ?>"> %</td>
		<td class="tr-icons">
			<?php foreach ( oneam_tier_icons() as $k => $svg ) : ?>
				<label title="<?php echo esc_attr( $k ); ?>"><input type="radio" name="tier[<?php echo esc_attr( $i ); ?>][icon]" value="<?php echo esc_attr( $k ); ?>" <?php checked( $t['icon'], $k ); ?>><span><?php echo oneam_tier_icon_svg( $k, 18 ); // phpcs:ignore ?></span></label>
			<?php endforeach; ?>
		</td>
		<td><input type="color" class="tr-color" name="tier[<?php echo esc_attr( $i ); ?>][color]" value="<?php echo esc_attr( $t['color'] ); ?>"></td>
		<td><button type="button" class="button-link tr-del" style="color:#b32d2e">Delete</button></td>
	</tr>
	<?php
	return ob_get_clean();
}

function oneam_tiers_page() {
	$tiers = oneam_tiers();
	$count = array();
	foreach ( get_users( array( 'meta_key' => 'oneam_tier', 'fields' => array( 'ID' ) ) ) as $u ) { // phpcs:ignore WordPress.DB.SlowDBQuery
		$id           = oneam_user_tier( $u->ID );
		$count[ $id ] = ( $count[ $id ] ?? 0 ) + 1;
	}
	?>
	<div class="wrap">
		<h1>Retailer tiers</h1>
		<?php if ( isset( $_GET['saved'] ) ) : // phpcs:ignore WordPress.Security.NonceVerification ?>
			<div class="notice notice-success is-dismissible"><p>Tiers saved.</p></div>
		<?php endif; ?>
		<p style="max-width:760px">Each tier gets one discount off the regular price (or the province price). Give a store its tier in <strong>Users → the store → 1AM retailer account → Tier</strong>. For an exception on one product, use <strong>1AM product setup → Price → Tier prices</strong>. Stores only see their own price.</p>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<input type="hidden" name="action" value="oneam_save_tiers">
			<?php wp_nonce_field( 'oneam_save_tiers' ); ?>
			<table class="widefat oneam-tiers">
				<thead><tr><th>Preview</th><th>Name</th><th>Discount</th><th>Icon</th><th>Colour</th><th></th></tr></thead>
				<tbody>
					<tr class="tr-basic"><td><span class="tier-badge tier-badge--basic"><span>Basic</span></span></td><td colspan="5">Stores without a tier pay the regular price.</td></tr>
					<?php
					$i = 0;
					foreach ( $tiers as $id => $t ) {
						echo oneam_tier_row( $id, $t, $i++ ); // phpcs:ignore
						if ( ! empty( $count[ $id ] ) ) {
							echo '<tr class="tr-count"><td></td><td colspan="5">' . (int) $count[ $id ] . ' store' . ( 1 === $count[ $id ] ? '' : 's' ) . ' in this tier</td></tr>';
						}
					}
					?>
				</tbody>
			</table>
			<template id="tr-tpl"><?php echo oneam_tier_row( '', array( 'name' => '', 'discount' => 5, 'icon' => 'medal', 'color' => '#1E5BFF' ), '__i__' ); // phpcs:ignore ?></template>
			<p><button type="button" class="button tr-add">+ Add tier</button></p>
			<p><button type="submit" class="button button-primary button-large">Save tiers</button></p>
			<p class="description">Deleting a tier moves its stores back to Basic.</p>
		</form>
	</div>
	<style>
		.oneam-tiers { max-width: 1100px; }
		.oneam-tiers td { vertical-align: middle; }
		.oneam-tiers .tr-name { width: 150px; }
		.oneam-tiers .tr-disc { width: 70px; }
		.tr-icons { display: flex; flex-wrap: wrap; gap: 4px; }
		.tr-icons label { cursor: pointer; }
		.tr-icons input { position: absolute; opacity: 0; }
		.tr-icons span { display: grid; place-items: center; width: 32px; height: 32px; border: 1px solid #dcdcde; border-radius: 8px; color: #50575e; }
		.tr-icons input:checked + span { border-color: #2271b1; background: #f0f6fc; color: #2271b1; box-shadow: 0 0 0 1px #2271b1; }
		.tr-icons input:focus-visible + span { outline: 2px solid #2271b1; }
		.tr-count td { padding-top: 0; color: #646970; font-size: 12px; }
		.tier-badge { display: inline-flex; align-items: center; gap: 5px; padding: 3px 10px 3px 7px; border-radius: 999px; background: color-mix(in srgb, var(--tc, #8A3FFC) 14%, #fff); color: var(--tc, #8A3FFC); font-weight: 700; font-size: 12px; line-height: 1.4; }
		.tier-badge--basic { background: #f0f0f1; color: #50575e; padding-left: 10px; }
	</style>
	<script>
	(function () {
		var table = document.querySelector('.oneam-tiers tbody'), n = 1000;
		function preview(tr) {
			var b = tr.querySelector('.tr-prev'); if (!b) return;
			var name = tr.querySelector('.tr-name').value || 'New tier', color = tr.querySelector('.tr-color').value, ic = tr.querySelector('.tr-icons input:checked');
			var svg = ic ? ic.nextElementSibling.innerHTML.replace(/width="18" height="18"/, 'width="16" height="16"') : '';
			b.innerHTML = '<span class="tier-badge" style="--tc:' + color + '">' + svg + '<span></span></span>';
			b.querySelector('.tier-badge > span').textContent = name;
		}
		document.querySelector('.tr-add').addEventListener('click', function () {
			table.insertAdjacentHTML('beforeend', document.getElementById('tr-tpl').innerHTML.replace(/__i__/g, String(n++)));
			var tr = table.lastElementChild; preview(tr); tr.querySelector('.tr-name').focus();
		});
		table.addEventListener('input', function (e) { var tr = e.target.closest('tr'); if (tr) preview(tr); });
		table.addEventListener('change', function (e) { var tr = e.target.closest('tr'); if (tr) preview(tr); });
		table.addEventListener('click', function (e) {
			if (!e.target.closest('.tr-del')) return;
			var tr = e.target.closest('tr'), nm = tr.querySelector('.tr-name').value;
			if (!nm || window.confirm('Delete the “' + nm + '” tier? Its stores move back to Basic when you save.')) {
				var next = tr.nextElementSibling; if (next && next.classList.contains('tr-count')) next.remove();
				tr.remove();
			}
		});
	})();
	</script>
	<?php
}

/* ---------------------------------------------------------------------------
 * 매장에 등급 주기 (사용자 카드) · 목록 표시
 * ------------------------------------------------------------------------- */
add_action(
	'oneam_wholesale_profile_rows',
	function ( $user ) {
		$cur = oneam_user_tier( $user->ID );
		?>
		<tr>
			<th><label for="oneam_tier">Tier</label></th>
			<td>
				<select name="oneam_tier" id="oneam_tier">
					<option value="">Basic (regular price)</option>
					<?php foreach ( oneam_tiers() as $id => $t ) : ?>
						<option value="<?php echo esc_attr( $id ); ?>" <?php selected( $cur, $id ); ?>><?php echo esc_html( $t['name'] . ' — ' . (float) $t['discount'] . '% off' ); ?></option>
					<?php endforeach; ?>
				</select>
				<a href="<?php echo esc_url( admin_url( 'users.php?page=oneam-tiers' ) ); ?>" style="display:inline-block;margin-top:4px">Manage tiers</a>
			</td>
		</tr>
		<?php
	},
	1
);
add_action(
	'oneam_wholesale_profile_save',
	function ( $user_id ) {
		// 프로필 저장 nonce 는 WordPress 가 확인
		if ( isset( $_POST['oneam_tier'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			$id = sanitize_key( wp_unslash( $_POST['oneam_tier'] ) ); // phpcs:ignore WordPress.Security.NonceVerification
			if ( '' === $id || isset( oneam_tiers()[ $id ] ) ) {
				update_user_meta( $user_id, 'oneam_tier', $id );
			}
		}
	}
);
add_filter(
	'manage_users_columns',
	function ( $cols ) {
		$cols['oneam_tier'] = 'Tier';
		return $cols;
	},
	20
);
add_filter(
	'manage_users_custom_column',
	function ( $out, $col, $user_id ) {
		if ( 'oneam_tier' !== $col ) {
			return $out;
		}
		$b = oneam_tier_badge( oneam_user_tier( $user_id ) );
		return $b ?: '<span style="color:#8c8f94">Basic</span>';
	},
	10,
	3
);
add_action(
	'admin_head',
	function () {
		echo '<style>.tier-badge{display:inline-flex;align-items:center;gap:5px;padding:3px 10px 3px 7px;border-radius:999px;background:color-mix(in srgb,var(--tc,#8A3FFC) 14%,#fff);color:var(--tc,#8A3FFC);font-weight:700;font-size:12px;line-height:1.4}</style>';
	}
);
