<?php
/**
 * 관리자 주문 만들기 · 편집 화면 정리
 *
 * 1) Add item(s) → Add product(s): 검색하지 않아도 판매 중인 상품(맛별)이 전부 목록으로 보임.
 *    수량만 넣고 [Add] → 수량을 넣은 상품만 주문에 들어감. 위쪽 칸에 글자를 넣으면 목록이 좁혀짐.
 * 2) 상품을 넣을 때 화면에서 고른 고객 · 주를 같이 보냄 → 주문을 저장(Create)하기 전에도
 *    그 고객의 주 가격 · 등급 가격이 바로 들어감 (계산은 inc/tiers.php).
 * 3) PDF 플러그인의 "Send order email" 칸은 숨김 → "Message to customer" 칸의 "Email to send" 로 합침.
 *
 * @package 1am-vivid
 */

defined( 'ABSPATH' ) || exit;

/** 주문 화면인지 (HPOS · 예전 방식) */
function oneam_is_order_edit_screen() {
	$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
	if ( ! $screen ) {
		return false;
	}
	$id = function_exists( 'wc_get_page_screen_id' ) ? wc_get_page_screen_id( 'shop-order' ) : 'shop_order';
	return $screen->id === $id || 'shop_order' === $screen->id;
}

// 3) "Send order email" 칸 숨김
add_action(
	'add_meta_boxes',
	function () {
		$screen = function_exists( 'wc_get_page_screen_id' ) ? wc_get_page_screen_id( 'shop-order' ) : 'shop_order';
		remove_meta_box( 'wpo_wcpdf_send_emails', $screen, 'side' );
	},
	999
);

/** 주문에 넣을 수 있는 상품 목록 (변형 상품은 맛별로) */
function oneam_admin_order_product_list() {
	$out = array();
	$ids = wc_get_products(
		array(
			'status'  => 'publish',
			'limit'   => 200,
			'orderby' => 'menu_order',
			'order'   => 'ASC',
			'return'  => 'ids',
		)
	);
	foreach ( $ids as $pid ) {
		$p = wc_get_product( $pid );
		if ( ! $p ) {
			continue;
		}
		$kids = $p->is_type( 'variable' ) ? $p->get_children() : array( $pid );
		foreach ( $kids as $vid ) {
			$v = $p->is_type( 'variable' ) ? wc_get_product( $vid ) : $p;
			if ( ! $v || ( ! $v->is_purchasable() && '' === $v->get_regular_price() ) ) {
				continue;
			}
			$out[] = array(
				'id'    => $v->get_id(),
				'group' => $p->get_name(),
				'name'  => $p->is_type( 'variable' ) ? wc_get_formatted_variation( $v, true, false, false ) : $p->get_name(),
				'sku'   => (string) $v->get_sku(),
				'stock' => $v->is_in_stock() ? ( $v->managing_stock() ? (int) $v->get_stock_quantity() . ' in stock' : '' ) : 'Out of stock',
			);
		}
	}
	return $out;
}

add_action(
	'admin_footer',
	function () {
		if ( ! oneam_is_order_edit_screen() || ! current_user_can( 'edit_shop_orders' ) ) {
			return;
		}
		$list = oneam_admin_order_product_list();
		?>
		<style>
			.oneam-pl-filter{width:100%;margin:0 0 10px;padding:8px 10px;font-size:14px}
			.wc-backbone-modal-add-products .oneam-pl-wrap{max-height:56vh;overflow:auto;border:1px solid #dcdcde;border-radius:4px}
			.wc-backbone-modal-add-products .oneam-pl-wrap table{border:0}
			.oneam-pl-group td{background:#f6f7f7;font-weight:600;padding:8px 10px!important;text-align:left!important}
			.oneam-pl-row td{vertical-align:middle;padding:8px 10px!important;text-align:left}
			.oneam-pl-row .oneam-pl-meta{color:#646970;font-size:12px;margin-left:6px}
			.oneam-pl-row.is-out .oneam-pl-meta{color:#b32d2e}
			.oneam-pl-row input.quantity{width:80px}
			.oneam-pl-row.has-qty td{background:#f0f6fc}
			.oneam-pl-msg{color:#b32d2e;margin:8px 0 0;font-weight:600}
		</style>
		<script>
		(function ($) {
			var LIST = <?php echo wp_json_encode( $list ); ?>;
			function esc(s) { return $('<div>').text(s == null ? '' : String(s)).html(); }

			// 2) 상품 추가 요청에 화면의 고객 · 주를 같이 보냄
			$.ajaxPrefilter(function (o) {
				if (typeof o.data !== 'string' || o.data.indexOf('action=woocommerce_add_order_item') === -1) return;
				var cu = $('#customer_user').val() || '';
				var st = $('#_shipping_state').val() || $('#_billing_state').val() || '';
				o.data += '&oneam_customer=' + encodeURIComponent(cu) + '&oneam_state=' + encodeURIComponent(st);
			});

			// 1) Add products 창 → 전체 목록
			$(document.body).on('wc_backbone_modal_loaded', function (e, target) {
				if (target !== 'wc-modal-add-products' || !LIST.length) return;
				setTimeout(function () {
					var $m = $('.wc-backbone-modal-add-products');
					$m.find('.wc-product-search').each(function () { try { $(this).selectWoo('close'); } catch (err) {} });
					var $table = $m.find('table.widefat'), $body = $table.find('tbody');
					var html = '', last = null;
					LIST.forEach(function (p) {
						if (p.group !== last && p.group !== p.name) { html += '<tr class="oneam-pl-group"><td colspan="2">' + esc(p.group) + '</td></tr>'; }
						last = p.group;
						html += '<tr class="oneam-pl-row' + (p.stock === 'Out of stock' ? ' is-out' : '') + '" data-q="' + esc((p.group + ' ' + p.name + ' ' + p.sku).toLowerCase()) + '">'
							+ '<td><input type="hidden" name="item_id" value="' + p.id + '">' + esc(p.name)
							+ (p.sku ? '<span class="oneam-pl-meta">' + esc(p.sku) + '</span>' : '')
							+ (p.stock ? '<span class="oneam-pl-meta">' + esc(p.stock) + '</span>' : '') + '</td>'
							+ '<td><input type="number" step="1" min="0" max="9999" name="item_qty" class="quantity" placeholder="0" autocomplete="off"></td></tr>';
					});
					$body.html(html).removeAttr('data-row');
					$table.find('thead th:last').text('Quantity (cartons)');
					if (!$table.parent().hasClass('oneam-pl-wrap')) { $table.wrap('<div class="oneam-pl-wrap"></div>'); }
					$m.find('.oneam-pl-filter, .oneam-pl-msg').remove();
					$('<input type="search" class="oneam-pl-filter" placeholder="Type to filter (flavour, product, SKU)…">').insertBefore($m.find('.oneam-pl-wrap'));
					$('<p class="oneam-pl-msg" hidden>Enter a quantity for at least one product.</p>').insertAfter($m.find('.oneam-pl-wrap'));
					$m.find('.oneam-pl-filter').trigger('focus');
				}, 0);
			});
			$(document).on('input', '.oneam-pl-filter', function () {
				var q = $.trim(this.value).toLowerCase();
				$('.oneam-pl-row').each(function () { $(this).toggle(!q || this.getAttribute('data-q').indexOf(q) > -1); });
				$('.oneam-pl-group').toggle(!q);
			});
			$(document).on('input change', '.oneam-pl-row input.quantity', function () {
				$(this).closest('tr').toggleClass('has-qty', parseInt(this.value, 10) > 0);
			});

			// [Add] 누르기 직전: 수량 없는 줄은 빼기 (그대로 두면 WooCommerce 가 1개씩 넣음)
			function prune(e) {
				var $m = $('.wc-backbone-modal-add-products');
				if (!$m.find('.oneam-pl-row').length) return;
				var picked = 0;
				$m.find('.oneam-pl-row').each(function () {
					var q = parseInt($(this).find('input.quantity').val(), 10);
					if (q > 0) { picked++; } else { $(this).find('input').prop('disabled', true).attr('name', ''); }
				});
				if (!picked) {
					$m.find('.oneam-pl-row input').prop('disabled', false).filter('[type=hidden]').attr('name', 'item_id').end().filter('.quantity').attr('name', 'item_qty');
					$m.find('.oneam-pl-msg').prop('hidden', false);
					e.preventDefault(); e.stopImmediatePropagation();
					return;
				}
				$m.find('.oneam-pl-row').filter(function () { return !$(this).find('input[name="item_id"]').length; }).remove();
				$m.find('.oneam-pl-group').remove();
			}
			document.addEventListener('click', function (e) {
				if (e.target.closest && e.target.closest('.wc-backbone-modal-add-products #btn-ok')) prune(e);
			}, true);
			document.addEventListener('keydown', function (e) {
				if (e.key === 'Enter' && e.target.closest && e.target.closest('.wc-backbone-modal-add-products')) { e.preventDefault(); e.stopImmediatePropagation(); }
			}, true);
		})(jQuery);
		</script>
		<?php
	}
);
