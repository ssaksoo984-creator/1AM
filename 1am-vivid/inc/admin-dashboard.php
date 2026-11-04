<?php
/**
 * 관리자 첫 화면(Dashboard) 맨 위 "1AM — today" 칸
 * 지금 해야 할 일을 숫자로: 가입 신청 · 입금 대기 · 발송할 주문 · 발송된 주문 · 고객 메시지 · 상품 · 메일 상태
 *
 * @package 1am-vivid
 */

defined( 'ABSPATH' ) || exit;

/** 주문 목록 주소 (상태별) — HPOS · 예전 방식 모두 */
function oneam_orders_url( $status = '' ) {
	$hpos = class_exists( '\Automattic\WooCommerce\Utilities\OrderUtil' ) && \Automattic\WooCommerce\Utilities\OrderUtil::custom_orders_table_usage_is_enabled();
	if ( $hpos ) {
		return admin_url( 'admin.php?page=wc-orders' . ( $status ? '&status=wc-' . $status : '' ) );
	}
	return admin_url( 'edit.php?post_type=shop_order' . ( $status ? '&post_status=wc-' . $status : '' ) );
}

function oneam_count_orders( $args ) {
	return count( wc_get_orders( array_merge( array( 'limit' => -1, 'return' => 'ids' ), $args ) ) );
}

add_action(
	'wp_dashboard_setup',
	function () {
		if ( ! current_user_can( 'edit_shop_orders' ) || ! function_exists( 'wc_get_orders' ) ) {
			return;
		}
		wp_add_dashboard_widget( 'oneam_today', '1AM — today', 'oneam_dashboard_widget' );
		// 맨 위로
		global $wp_meta_boxes;
		$normal = $wp_meta_boxes['dashboard']['normal']['core'] ?? array();
		if ( isset( $normal['oneam_today'] ) ) {
			$wp_meta_boxes['dashboard']['normal']['core'] = array( 'oneam_today' => $normal['oneam_today'] ) + $normal; // phpcs:ignore WordPress.WP.GlobalVariablesOverride
		}
	}
);

function oneam_dashboard_widget() {
	$apps     = function_exists( 'oneam_waiting_applications' ) ? oneam_waiting_applications() : 0;
	$hold     = oneam_count_orders( array( 'status' => 'on-hold' ) );
	$ship     = oneam_count_orders( array( 'status' => 'processing' ) );
	$shipped  = oneam_count_orders( array( 'status' => 'shipped' ) );
	// 안 읽은 고객 답장: 최근에 바뀐 주문 중에서 (저장 방식과 상관없이 정확하게)
	$replies = array_values(
		array_filter(
			wc_get_orders( array( 'limit' => 60, 'orderby' => 'modified', 'order' => 'DESC' ) ),
			function ( $o ) {
				return (bool) $o->get_meta( '_oneam_reply_unread' );
			}
		)
	);
	$products = wp_count_posts( 'product' );
	$soon     = count( wc_get_products( array( 'status' => 'publish', 'limit' => -1, 'return' => 'ids', 'meta_key' => '_oneam_soon', 'meta_value' => 'yes' ) ) ); // phpcs:ignore WordPress.DB.SlowDBQuery
	$mail_err = get_option( 'oneam_mail_error' );
	$days     = function_exists( 'oneam_autocomplete_days' ) ? oneam_autocomplete_days() : 7;

	$tiles = array(
		array( $apps, 'Retailer applications', 'waiting for review', admin_url( 'users.php?oneam_wholesale=pending' ), 'Review', $apps ? 'hot' : '' ),
		array( $hold, 'Awaiting payment', 'On hold — check your e-Transfers', oneam_orders_url( 'on-hold' ), 'Open', $hold ? 'warm' : '' ),
		array( $ship, 'Ready to ship', 'Payment confirmed — add tracking', oneam_orders_url( 'processing' ), 'Ship', $ship ? 'hot' : '' ),
		array( $shipped, 'Shipped', $days ? 'Completes automatically after ' . $days . ' days' : 'On the way', oneam_orders_url( 'shipped' ), 'View', '' ),
		array( count( $replies ), 'Customer messages', 'Unread replies on orders', $replies ? $replies[0]->get_edit_order_url() : oneam_orders_url(), 'Read', $replies ? 'hot' : '' ),
		array( (int) ( $products->publish ?? 0 ), 'Products on the site', $soon . ' coming soon · ' . (int) ( $products->draft ?? 0 ) . ' draft', admin_url( 'edit.php?post_type=product' ), 'Manage', '' ),
	);
	?>
	<div class="oneam-today">
		<?php if ( $mail_err ) : ?>
			<p class="oneam-today__alert">Email problem: <?php echo esc_html( $mail_err['message'] ); ?> (<?php echo esc_html( $mail_err['time'] ); ?>). Check <em>WP Mail SMTP → Tools → Email Test</em>.</p>
		<?php endif; ?>
		<div class="oneam-today__grid">
			<?php foreach ( $tiles as $t ) : ?>
				<a class="oneam-today__tile <?php echo $t[5] ? 'is-' . esc_attr( $t[5] ) : ''; ?>" href="<?php echo esc_url( $t[3] ); ?>">
					<span class="oneam-today__num"><?php echo (int) $t[0]; ?></span>
					<span class="oneam-today__label"><?php echo esc_html( $t[1] ); ?></span>
					<span class="oneam-today__sub"><?php echo esc_html( $t[2] ); ?></span>
					<span class="oneam-today__go"><?php echo esc_html( $t[4] ); ?> →</span>
				</a>
			<?php endforeach; ?>
		</div>
		<?php if ( $replies ) : ?>
			<p class="oneam-today__h">Unread customer messages</p>
			<ul class="oneam-today__list">
				<?php foreach ( $replies as $o ) : ?>
					<?php
					$rs   = function_exists( 'oneam_order_replies' ) ? oneam_order_replies( $o ) : array();
					$last = $rs ? $rs[ count( $rs ) - 1 ] : null;
					?>
					<li><a href="<?php echo esc_url( $o->get_edit_order_url() ); ?>">#<?php echo esc_html( $o->get_order_number() ); ?> · <?php echo esc_html( $o->get_billing_company() ?: $o->get_billing_first_name() ); ?></a> — <?php echo esc_html( $last ? wp_trim_words( $last['text'], 14 ) : '' ); ?></li>
				<?php endforeach; ?>
			</ul>
		<?php endif; ?>
		<p class="oneam-today__links">
			<a class="button button-primary" href="<?php echo esc_url( admin_url( 'post-new.php?post_type=product' ) ); ?>">+ Add product</a>
			<a class="button" href="<?php echo esc_url( oneam_orders_url() ); ?>">All orders</a>
			<a class="button" href="<?php echo esc_url( admin_url( 'users.php' ) ); ?>">Retailers</a>
			<a class="button" href="<?php echo esc_url( admin_url( 'customize.php?autofocus[panel]=oneam' ) ); ?>">1AM Settings</a>
			<a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=wc-settings&tab=email' ) ); ?>">Emails</a>
		</p>
	</div>
	<style>
		#oneam_today .inside { margin: 0; padding: 12px 14px 14px; }
		.oneam-today__alert { margin: 0 0 12px; padding: 10px 12px; border-left: 4px solid #d63638; background: #fcf0f1; }
		.oneam-today__grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap: 10px; }
		.oneam-today__tile { display: grid; gap: 2px; padding: 14px 14px 12px; border: 1px solid #dcdcde; border-radius: 10px; background: #fff; color: #1d2327; text-decoration: none; transition: border-color .15s, box-shadow .15s; }
		.oneam-today__tile:hover { border-color: #2271b1; box-shadow: 0 2px 8px rgba(0,0,0,.06); color: #1d2327; }
		.oneam-today__tile.is-hot { border-color: #8a3ffc; background: #f6f0ff; }
		.oneam-today__tile.is-warm { border-color: #dba617; background: #fcf9e8; }
		.oneam-today__num { font-size: 30px; font-weight: 700; line-height: 1.1; }
		.oneam-today__label { font-weight: 600; }
		.oneam-today__sub { font-size: 12px; color: #646970; }
		.oneam-today__go { margin-top: 6px; font-size: 12px; font-weight: 600; color: #2271b1; }
		.oneam-today__h { margin: 14px 0 6px; font-weight: 600; }
		.oneam-today__list { margin: 0 0 0 16px; list-style: disc; }
		.oneam-today__links { display: flex; flex-wrap: wrap; gap: 6px; margin: 14px 0 0; }
	</style>
	<?php
}

// 고객 답장 "안 읽음" 표시: 답장이 오면 표시, 관리자가 그 주문을 열면 지움
add_action(
	'add_meta_boxes',
	function () {
		// phpcs:disable WordPress.Security.NonceVerification
		$id = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : ( isset( $_GET['post'] ) ? absint( $_GET['post'] ) : 0 );
		// phpcs:enable
		if ( ! $id ) {
			return;
		}
		$order = wc_get_order( $id );
		if ( $order && $order->get_meta( '_oneam_reply_unread' ) ) {
			$order->delete_meta_data( '_oneam_reply_unread' );
			$order->save_meta_data();
		}
	},
	1
);
