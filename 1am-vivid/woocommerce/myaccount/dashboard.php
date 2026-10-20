<?php
/**
 * 내 계정 첫 화면 (WooCommerce myaccount/dashboard.php 대체)
 *
 * 인사 · 도매 승인 상태 · 주 · 바로가기 카드 · 최근 주문
 *
 * @package 1am-vivid
 */

defined( 'ABSPATH' ) || exit;

$oneam_user  = wp_get_current_user();
$oneam_state = oneam_member_state();
$oneam_prov  = oneam_customer_province();
$oneam_store = get_user_meta( $oneam_user->ID, 'oneam_business_name', true ) ?: get_user_meta( $oneam_user->ID, 'billing_company', true );
$oneam_name  = $oneam_user->first_name ?: $oneam_user->display_name;
$oneam_cards = array(
	array( 'orders', 'cart', 'Orders', 'Track orders and invoices' ),
	array( 'edit-address', 'truck', 'Addresses', 'Billing and shipping' ),
	array( 'edit-account', 'user', 'Account details', 'Name, email, password' ),
);
$oneam_recent = function_exists( 'wc_get_orders' ) ? wc_get_orders(
	array(
		'customer' => $oneam_user->ID,
		'limit'    => 3,
		'orderby'  => 'date',
		'order'    => 'DESC',
	)
) : array();
?>
<p class="acct__hello">Hi, <?php echo esc_html( $oneam_name ); ?></p>
<p>Welcome to your 1AM retailer account<?php echo $oneam_store ? ' for <strong>' . esc_html( $oneam_store ) . '</strong>' : ''; ?>.</p>

<div class="acct__meta">
	<?php if ( 'approved' === $oneam_state ) : ?>
		<span class="acct__pill is-ok">Approved retailer</span>
	<?php else : ?>
		<span class="acct__pill is-wait">Application under review</span>
	<?php endif; ?>
	<?php if ( $oneam_prov ) : ?>
		<span class="acct__pill"><?php echo esc_html( oneam_provinces()[ $oneam_prov ] ?? $oneam_prov ); ?></span>
	<?php endif; ?>
	<span class="acct__pill"><?php echo esc_html( $oneam_user->user_email ); ?></span>
</div>

<div class="acct__grid">
	<a class="acct__card acct__card--shop" href="<?php echo esc_url( oneam_shop_url() ); ?>">
		<?php echo oneam_icon( 'arrow' ); // phpcs:ignore ?>
		<strong>Shop wholesale</strong>
		<span><?php echo 'approved' === $oneam_state ? 'Order cartons for your store' : 'Browse the lineup'; ?></span>
	</a>
	<?php foreach ( $oneam_cards as $c ) : ?>
		<a class="acct__card" href="<?php echo esc_url( wc_get_account_endpoint_url( $c[0] ) ); ?>">
			<?php echo oneam_icon( $c[1] ); // phpcs:ignore ?>
			<strong><?php echo esc_html( $c[2] ); ?></strong>
			<span><?php echo esc_html( $c[3] ); ?></span>
		</a>
	<?php endforeach; ?>
</div>

<?php if ( $oneam_recent ) : ?>
	<div class="acct__recent">
		<h3>Recent orders</h3>
		<table class="woocommerce-orders-table shop_table shop_table_responsive">
			<thead><tr><th>Order</th><th>Date</th><th>Status</th><th>Total</th></tr></thead>
			<tbody>
				<?php foreach ( $oneam_recent as $o ) : ?>
					<tr>
						<td data-title="Order"><a href="<?php echo esc_url( $o->get_view_order_url() ); ?>">#<?php echo esc_html( $o->get_order_number() ); ?></a></td>
						<td data-title="Date"><?php echo esc_html( wc_format_datetime( $o->get_date_created() ) ); ?></td>
						<td data-title="Status"><?php echo esc_html( wc_get_order_status_name( $o->get_status() ) ); ?></td>
						<td data-title="Total"><?php echo wp_kses_post( $o->get_formatted_order_total() ); ?></td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	</div>
<?php endif; ?>

<?php
do_action( 'woocommerce_account_dashboard' );
