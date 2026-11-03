<?php
/**
 * 관리자 화면 정리
 *  - 인보이스 플러그인의 홍보 · 안내 배너는 플러그인 설정 화면에서만 보이게 (다른 화면을 덮지 않게)
 *  - 주문 화면: 쓰지 않는 칸(Custom Fields, Order attribution 광고) 숨김
 *
 * @package 1am-vivid
 */

defined( 'ABSPATH' ) || exit;

add_action(
	'admin_footer',
	function () {
		// phpcs:ignore WordPress.Security.NonceVerification
		if ( isset( $_GET['page'] ) && false !== strpos( sanitize_key( wp_unslash( $_GET['page'] ) ), 'wpo_wcpdf' ) ) {
			return;
		}
		?>
		<script>
		(function () {
			var words = ['PDF Invoices & Packing Slips for WooCommerce', 'PDF Invoices &amp; Packing Slips'];
			document.querySelectorAll('#wpbody-content .notice, #wpbody-content .updated, #wpbody-content .wpo-wcpdf-review-notice, #wpbody-content .wpo-wcpdf-install-notice').forEach(function (n) {
				if (n.closest('.inline') || n.classList.contains('inline')) return;
				var t = n.textContent || '';
				if (words.some(function (w) { return t.indexOf(w.replace('&amp;', '&')) > -1; })) n.style.display = 'none';
			});
		})();
		</script>
		<?php
	}
);

add_action(
	'add_meta_boxes',
	function () {
		$screen = function_exists( 'wc_get_page_screen_id' ) ? wc_get_page_screen_id( 'shop-order' ) : 'shop_order';
		remove_meta_box( 'postcustom', $screen, 'normal' );
		remove_meta_box( 'woocommerce-order-source-data', $screen, 'side' );
		remove_meta_box( 'order_custom', $screen, 'normal' );
	},
	99
);
