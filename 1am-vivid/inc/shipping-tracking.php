<?php
/**
 * 배송 흐름 (네이버식):
 *   On hold (입금 대기) → Payment confirmed · Preparing shipment (= Processing) → Shipped (송장 입력) → 며칠 뒤 자동 Completed
 *
 * - Processing 이름을 "Payment confirmed · Preparing shipment" 로, 고객 메일도 "결제 확인 · 배송 준비 중" 내용으로
 * - 새 상태 "Shipped": 주문 화면 "Shipping & tracking" 칸에서 택배사 · 운송장 번호를 넣고 [Mark as shipped]
 *   → 고객에게 "발송되었습니다 · 아래 운송장 번호로 조회" 메일 (조회 링크 포함), 내 계정 주문에도 조회 링크
 * - 발송 후 N일(1AM Settings → Business & legal, 기본 7일)이 지나면 자동으로 Completed (완료 메일 · 인보이스)
 *
 * @package 1am-vivid
 */

defined( 'ABSPATH' ) || exit;

/** 택배사 → 조회 주소 ({n} = 운송장 번호) */
function oneam_carriers() {
	return apply_filters(
		'oneam_carriers',
		array(
			'purolator'   => array( 'Purolator', 'https://www.purolator.com/en/shipping/tracker?pin={n}' ),
			'ups'         => array( 'UPS', 'https://www.ups.com/track?loc=en_CA&tracknum={n}' ),
			'canpar'      => array( 'Canpar', 'https://www.canpar.com/en/tracking/track.htm?barcode={n}' ),
			'canada-post' => array( 'Canada Post', 'https://www.canadapost-postescanada.ca/track-reperage/en#/search?searchFor={n}' ),
			'other'       => array( 'Other', '' ),
			'in-person'   => array( 'In-person delivery', '' ),
		)
	);
}

/** 직접 배달(운송장 없음)인지 */
function oneam_is_in_person( $order ) {
	return $order && 'in-person' === (string) $order->get_meta( '_oneam_carrier' );
}

/** 주문의 택배 정보: [택배사 이름, 운송장 번호, 조회 주소, 직접 배달 여부] (없으면 null) */
function oneam_order_tracking( $order ) {
	if ( oneam_is_in_person( $order ) ) {
		return array(
			'carrier'   => 'In-person delivery',
			'number'    => '',
			'url'       => '',
			'in_person' => true,
		);
	}
	$num = $order ? trim( (string) $order->get_meta( '_oneam_tracking' ) ) : '';
	if ( '' === $num ) {
		return null;
	}
	$key = (string) $order->get_meta( '_oneam_carrier' );
	$c   = oneam_carriers()[ $key ] ?? array( ucfirst( $key ?: 'Carrier' ), '' );
	return array(
		'carrier' => $c[0],
		'number'  => $num,
		'url'       => $c[1] ? str_replace( '{n}', rawurlencode( $num ), $c[1] ) : '',
		'in_person' => false,
	);
}

/** 자동 완료까지 일수 (0 = 자동 완료 안 함) */
function oneam_autocomplete_days() {
	$d = oneam_opt( 'oneam_autocomplete_days' );
	return '' === (string) $d ? 7 : max( 0, (int) $d );
}

/* ---------------------------------------------------------------------------
 * 상태: Processing 이름 바꾸기 + Shipped 추가
 * ------------------------------------------------------------------------- */
add_action(
	'init',
	function () {
		register_post_status(
			'wc-shipped',
			array(
				'label'                     => 'Shipped',
				'public'                    => false,
				'exclude_from_search'       => false,
				'show_in_admin_all_list'    => true,
				'show_in_admin_status_list' => true,
				/* translators: %s: number of orders */
				'label_count'               => _n_noop( 'Shipped <span class="count">(%s)</span>', 'Shipped <span class="count">(%s)</span>' ),
			)
		);
	}
);
add_filter(
	'wc_order_statuses',
	function ( $statuses ) {
		$out = array();
		foreach ( $statuses as $k => $label ) {
			$out[ $k ] = 'wc-processing' === $k ? 'Payment confirmed · Preparing shipment' : $label;
			if ( 'wc-processing' === $k ) {
				$out['wc-shipped'] = 'Shipped';
			}
		}
		return $out;
	}
);
// 결제가 끝난 상태로 취급 (매출 · 보고서 · 재주문 등)
add_filter(
	'woocommerce_order_is_paid_statuses',
	function ( $s ) {
		$s[] = 'shipped';
		return $s;
	}
);
add_filter(
	'woocommerce_reports_order_statuses',
	function ( $s ) {
		if ( is_array( $s ) && ! in_array( 'shipped', $s, true ) ) {
			$s[] = 'shipped';
		}
		return $s;
	}
);
// 주문 목록: 여러 주문 한 번에 "Shipped" 로 (운송장은 각 주문에서 입력)
foreach ( array( 'bulk_actions-edit-shop_order', 'bulk_actions-woocommerce_page_wc-orders' ) as $oneam_bulk ) {
	add_filter(
		$oneam_bulk,
		function ( $actions ) {
			$actions['mark_shipped'] = 'Change status to shipped';
			if ( isset( $actions['mark_processing'] ) ) {
				$actions['mark_processing'] = 'Change status to payment confirmed';
			}
			return $actions;
		}
	);
}
// 관리자 목록의 상태 색
add_action(
	'admin_head',
	function () {
		echo '<style>.order-status.status-shipped{background:#e3ecff;color:#1e3a8a}.order-status.status-processing{background:#e7f6ec;color:#14532d}</style>';
	}
);

/* ---------------------------------------------------------------------------
 * 주문 화면: "Shipping & tracking" 칸
 * ------------------------------------------------------------------------- */
add_action(
	'add_meta_boxes',
	function () {
		$screen = function_exists( 'wc_get_page_screen_id' ) ? wc_get_page_screen_id( 'shop-order' ) : 'shop_order';
		add_meta_box( 'oneam-tracking', 'Tracking number (after payment)', 'oneam_tracking_box', $screen, 'side', 'high' );
	},
	5
);

function oneam_tracking_box( $post_or_order ) {
	$order = $post_or_order instanceof WC_Order ? $post_or_order : wc_get_order( $post_or_order->ID );
	if ( ! $order ) {
		return;
	}
	$ready = $order->has_status( array( 'processing', 'shipped', 'completed' ) );
	?>
	<p class="oneam-track-wait"<?php echo $ready ? ' hidden' : ''; ?>>The tracking number is entered after payment is confirmed: change the status to <strong>Payment confirmed · Preparing shipment</strong> and click Update.<br><br><em>To charge a shipping fee</em>, use <strong>Add item(s) → Add shipping</strong> in the Items box (under the order details) — that works before payment.</p>
	<div class="oneam-track-form"<?php echo $ready ? '' : ' hidden'; ?>>
	<?php
	$t       = oneam_order_tracking( $order );
	$carrier = (string) $order->get_meta( '_oneam_carrier' ) ?: 'purolator';
	$shipped = (string) $order->get_meta( '_oneam_shipped_at' );
	$days    = oneam_autocomplete_days();
	wp_nonce_field( 'oneam_tracking', 'oneam_tracking_nonce' );
	?>
	<p><label for="oneam_carrier"><strong>Carrier</strong></label>
		<select name="oneam_carrier" id="oneam_carrier" style="width:100%">
			<?php foreach ( oneam_carriers() as $k => $c ) : ?>
				<option value="<?php echo esc_attr( $k ); ?>" <?php selected( $carrier, $k ); ?>><?php echo esc_html( $c[0] ); ?></option>
			<?php endforeach; ?>
		</select>
	</p>
	<p class="oneam-track-num"><label for="oneam_tracking"><strong>Tracking number</strong></label>
		<input type="text" name="oneam_tracking" id="oneam_tracking" value="<?php echo esc_attr( $order->get_meta( '_oneam_tracking' ) ); ?>" style="width:100%" placeholder="e.g. 329012345678">
	</p>
	<?php if ( $t && $t['url'] ) : ?>
		<p><a href="<?php echo esc_url( $t['url'] ); ?>" target="_blank" rel="noopener">Track this shipment ↗</a></p>
	<?php endif; ?>
	<?php if ( $order->has_status( array( 'shipped', 'completed' ) ) && $shipped ) : ?>
		<p class="description">Shipped on <?php echo esc_html( $shipped ); ?>.<?php echo $order->has_status( 'shipped' ) && $days ? ' Completes automatically ' . (int) $days . ' days after shipping.' : ''; ?></p>
		<p><button type="submit" class="button" name="oneam_tracking_resend" value="1" style="width:100%">Save &amp; email <?php echo oneam_is_in_person( $order ) ? 'delivery notice' : 'tracking'; ?> again</button></p>
		<?php if ( oneam_is_in_person( $order ) && $order->has_status( 'shipped' ) ) : ?>
			<p><button type="submit" class="button button-primary" name="oneam_mark_delivered" value="1" style="width:100%">Delivered — mark as completed</button></p>
		<?php endif; ?>
	<?php else : ?>
		<p><button type="submit" class="button button-primary" name="oneam_mark_shipped" value="1" style="width:100%"><span class="oneam-lbl-ship">Mark as shipped &amp; email customer</span><span class="oneam-lbl-person">Mark as out for delivery &amp; email customer</span></button></p>
		<p class="oneam-track-person"><button type="submit" class="button" name="oneam_mark_delivered" value="1" style="width:100%">Delivered — mark as completed</button></p>
		<p class="description oneam-lbl-ship">Changes the status to <strong>Shipped</strong> and emails the customer the tracking number and link.<?php echo $days ? ' The order completes automatically ' . (int) $days . ' days later (completed email + invoice).' : ''; ?></p>
		<p class="description oneam-lbl-person">No tracking number needed. <strong>Out for delivery</strong> = status Shipped, the customer gets an email that you will deliver in person<?php echo $days ? ' (completes automatically ' . (int) $days . ' days later)' : ''; ?>. <strong>Delivered</strong> = status Completed right away (completed email + invoice).</p>
	<?php endif; ?>
	<style>#oneam-tracking .oneam-lbl-person,#oneam-tracking .oneam-track-person{display:none}#oneam-tracking.is-person .oneam-lbl-ship,#oneam-tracking.is-person .oneam-track-num{display:none}#oneam-tracking.is-person .oneam-lbl-person{display:inline}#oneam-tracking.is-person p.oneam-lbl-person,#oneam-tracking.is-person .oneam-track-person{display:block}</style>
	</div>
	<script>
	document.addEventListener('DOMContentLoaded', function () {
		// 상태를 "Payment confirmed" 나 "Shipped" 로 고르면 바로 보이게 (상태 칸은 이 칸보다 나중에 그려지므로 페이지가 다 뜬 뒤)
		var car = document.getElementById('oneam_carrier'), wrap = document.getElementById('oneam-tracking');
		function person() { if (car && wrap) wrap.classList.toggle('is-person', car.value === 'in-person'); }
		if (car) { car.addEventListener('change', person); person(); }
		var sel = document.getElementById('order_status');
		var box = document.querySelector('#oneam-tracking .oneam-track-form'), wait = document.querySelector('#oneam-tracking .oneam-track-wait');
		if (!sel || !box || !wait) return;
		var initial = box.hidden;
		function sync() {
			var on = !initial || ['wc-processing', 'wc-shipped', 'wc-completed'].indexOf(sel.value) > -1;
			box.hidden = !on; wait.hidden = on;
		}
		(window.jQuery ? window.jQuery(sel).on('change', sync) : sel.addEventListener('change', sync));
	});
	</script>
	<?php
}

add_action(
	'woocommerce_process_shop_order_meta',
	function ( $order_id ) {
		if ( ! isset( $_POST['oneam_tracking_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['oneam_tracking_nonce'] ) ), 'oneam_tracking' ) ) {
			return;
		}
		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			return;
		}
		$carrier = isset( $_POST['oneam_carrier'] ) ? sanitize_key( wp_unslash( $_POST['oneam_carrier'] ) ) : '';
		$num     = isset( $_POST['oneam_tracking'] ) ? trim( sanitize_text_field( wp_unslash( $_POST['oneam_tracking'] ) ) ) : '';
		$order->update_meta_data( '_oneam_carrier', array_key_exists( $carrier, oneam_carriers() ) ? $carrier : 'other' );
		$order->update_meta_data( '_oneam_tracking', $num );
		$order->save_meta_data();
		$person = 'in-person' === $carrier;
		// 상태 목록에서 "Shipped" 를 골랐는데 운송장 번호가 없으면 상태는 그대로 (직접 배달은 번호 없이 가능)
		if ( isset( $_POST['order_status'] ) && 'wc-shipped' === $_POST['order_status'] && '' === $num && ! $person && ! $order->has_status( 'shipped' ) ) {
			$_POST['order_status'] = 'wc-' . $order->get_status();
			WC_Admin_Meta_Boxes::add_error( 'Enter the tracking number before marking the order as shipped (or choose In-person delivery).' );
		}
		// [Mark as shipped] 버튼: WooCommerce 가 상태를 저장할 때 Shipped 로 (같은 저장에서 한 번만 메일)
		if ( ! empty( $_POST['oneam_mark_shipped'] ) && ( '' !== $num || $person ) ) {
			$_POST['order_status'] = 'wc-shipped';
		}
		// [Delivered] 버튼 (직접 배달): 바로 Completed
		if ( ! empty( $_POST['oneam_mark_delivered'] ) && $person ) {
			$_POST['order_status'] = 'wc-completed';
		}
	},
	5
);
add_action(
	'woocommerce_process_shop_order_meta',
	function ( $order_id ) {
		if ( ! isset( $_POST['oneam_tracking_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['oneam_tracking_nonce'] ) ), 'oneam_tracking' ) || ! current_user_can( 'edit_shop_orders' ) ) {
			return;
		}
		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			return;
		}
		$carrier = isset( $_POST['oneam_carrier'] ) ? sanitize_key( wp_unslash( $_POST['oneam_carrier'] ) ) : '';
		$num     = isset( $_POST['oneam_tracking'] ) ? trim( sanitize_text_field( wp_unslash( $_POST['oneam_tracking'] ) ) ) : '';
		$order->update_meta_data( '_oneam_carrier', array_key_exists( $carrier, oneam_carriers() ) ? $carrier : 'other' );
		$order->update_meta_data( '_oneam_tracking', $num );
		$order->save();
		$person = 'in-person' === $carrier;
		if ( ! empty( $_POST['oneam_mark_delivered'] ) && $person ) {
			if ( ! $order->has_status( 'completed' ) ) {
				$order->update_status( 'completed' );
			}
			$order->add_order_note( 'Delivered in person.' );
		} elseif ( ! empty( $_POST['oneam_mark_shipped'] ) ) {
			if ( '' === $num && ! $person ) {
				WC_Admin_Meta_Boxes::add_error( 'Enter the tracking number before marking the order as shipped (or choose In-person delivery).' );
				return;
			}
			if ( ! $order->has_status( 'shipped' ) ) {
				$order->update_status( 'shipped' );
			}
			$order->add_order_note( $person ? 'Out for in-person delivery.' : 'Shipped with ' . ( oneam_carriers()[ $carrier ][0] ?? 'carrier' ) . ' ' . $num . '.' );
		} elseif ( ! empty( $_POST['oneam_tracking_resend'] ) && ( '' !== $num || $person ) ) {
			do_action( 'oneam_send_shipped_email', $order_id, $order );
			$order->add_order_note( $person ? 'Delivery email sent again (in person).' : 'Tracking email sent again: ' . $num );
		}
	},
	70
);

// 발송 시각 기록 (자동 완료 계산용)
add_action(
	'woocommerce_order_status_shipped',
	function ( $order_id, $order ) {
		$order->update_meta_data( '_oneam_shipped_at', current_time( 'mysql' ) );
		$order->save_meta_data();
	},
	5,
	2
);

/* ---------------------------------------------------------------------------
 * 자동 완료: 발송 후 N일
 * ------------------------------------------------------------------------- */
add_action(
	'init',
	function () {
		if ( ! wp_next_scheduled( 'oneam_autocomplete_shipped' ) ) {
			wp_schedule_event( time() + HOUR_IN_SECONDS, 'twicedaily', 'oneam_autocomplete_shipped' );
		}
	}
);
add_action( 'oneam_autocomplete_shipped', 'oneam_run_autocomplete' );
function oneam_run_autocomplete() {
	$days = oneam_autocomplete_days();
	if ( ! $days || ! function_exists( 'wc_get_orders' ) ) {
		return 0;
	}
	$done = 0;
	foreach ( wc_get_orders( array( 'status' => 'shipped', 'limit' => 100 ) ) as $order ) {
		$at = strtotime( (string) $order->get_meta( '_oneam_shipped_at' ) );
		if ( $at && ( current_time( 'timestamp' ) - $at ) >= $days * DAY_IN_SECONDS ) { // phpcs:ignore WordPress.DateTime.CurrentTimeTimestamp
			$order->update_status( 'completed', 'Completed automatically ' . $days . ' days after shipping.' );
			++$done;
		}
	}
	return $done;
}

/* ---------------------------------------------------------------------------
 * 메일
 * ------------------------------------------------------------------------- */

// "Shipped" 메일 (WooCommerce → Settings → Emails 에 "Order shipped" 로 보임)
add_filter(
	'woocommerce_email_classes',
	function ( $emails ) {
		if ( ! class_exists( 'OneAM_Email_Shipped' ) ) {
			require_once __DIR__ . '/email-shipped.php';
		}
		$emails['OneAM_Email_Shipped'] = new OneAM_Email_Shipped();
		return $emails;
	}
);
add_filter(
	'woocommerce_email_actions',
	function ( $actions ) {
		$actions[] = 'woocommerce_order_status_shipped';
		return $actions;
	}
);

// 결제 확인 (Processing) 메일: "Payment confirmed — preparing your shipment"
add_filter(
	'woocommerce_email_subject_customer_processing_order',
	function ( $subject, $order ) {
		return $order ? 'Payment confirmed — we’re preparing your 1AM order #' . $order->get_order_number() : $subject;
	},
	10,
	2
);
add_filter(
	'woocommerce_email_heading_customer_processing_order',
	function () {
		return 'Payment confirmed';
	}
);
add_filter(
	'woocommerce_email_additional_content_customer_processing_order',
	function () {
		return "We have received your payment and are now preparing your shipment.\nWe will email you the tracking number as soon as your order ships.";
	}
);

// 완료 (Completed) 메일: 배송 조회 정보도 다시
add_filter(
	'woocommerce_email_subject_customer_completed_order',
	function ( $subject, $order ) {
		return $order ? 'Your 1AM order #' . $order->get_order_number() . ' is complete' : $subject;
	},
	10,
	2
);
add_filter(
	'woocommerce_email_heading_customer_completed_order',
	function () {
		return 'Thank you for your order';
	}
);
add_filter(
	'woocommerce_email_additional_content_customer_completed_order',
	function () {
		return 'Your order is complete. Your invoice is attached (or available in My account → Orders). Thank you for choosing 1AM.';
	}
);

// Processing 메일 첫 문장: "is now being processed" → 결제 확인 · 배송 준비
add_filter(
	'gettext_woocommerce',
	function ( $t, $text ) {
		if ( false !== strpos( $text, 'is now being processed' ) ) {
			return false !== strpos( $text, '%s' )
				? 'Just to let you know — we have received your payment for order #%s and are preparing your shipment:'
				: 'Just to let you know — we have received your payment and are preparing your shipment.';
		}
		return $t;
	},
	10,
	2
);

// 고객 · 관리자 메일 본문의 주문표 위에 택배 정보 (Shipped · Completed)
add_action(
	'woocommerce_email_before_order_table',
	function ( $order, $sent_to_admin, $plain ) {
		$t = oneam_order_tracking( $order );
		if ( ! $t || ! $order->has_status( array( 'shipped', 'completed' ) ) ) {
			return;
		}
		if ( $t['in_person'] ) {
			$msg = $order->has_status( 'completed' ) ? 'Delivered in person by the 1AM team.' : 'Our team will deliver this order to your store in person. No tracking number is needed.';
			if ( $plain ) {
				echo "\nDELIVERY\n" . $msg . "\n\n"; // phpcs:ignore
				return;
			}
			echo '<div style="margin:0 0 24px;padding:16px 18px;border:1px solid #e5e5e5;border-radius:10px"><p style="margin:0 0 4px;font-size:12px;text-transform:uppercase;letter-spacing:.08em;color:#666">Delivery</p><p style="margin:0;font-size:16px"><strong>In-person delivery</strong> · ' . esc_html( $msg ) . '</p></div>';
			return;
		}
		if ( $plain ) {
			echo "\nTRACKING\n{$t['carrier']}: {$t['number']}\n" . ( $t['url'] ? $t['url'] . "\n" : '' ) . "\n"; // phpcs:ignore
			return;
		}
		echo '<div style="margin:0 0 24px;padding:16px 18px;border:1px solid #e5e5e5;border-radius:10px">';
		echo '<p style="margin:0 0 4px;font-size:12px;text-transform:uppercase;letter-spacing:.08em;color:#666">Tracking</p>';
		echo '<p style="margin:0;font-size:16px"><strong>' . esc_html( $t['carrier'] ) . '</strong> · ' . esc_html( $t['number'] ) . '</p>';
		if ( $t['url'] ) {
			echo '<p style="margin:10px 0 0"><a href="' . esc_url( $t['url'] ) . '" style="display:inline-block;padding:10px 18px;border-radius:999px;background:#0d0b12;color:#fff;text-decoration:none;font-weight:700">Track your delivery →</a></p>';
		}
		echo '</div>';
	},
	8,
	3
);

/* ---------------------------------------------------------------------------
 * 고객 화면: 주문 목록 · 주문 보기 · 인보이스
 * ------------------------------------------------------------------------- */
add_action(
	'woocommerce_order_details_before_order_table',
	function ( $order ) {
		$t = oneam_order_tracking( $order );
		if ( ! $t || ! $order->has_status( array( 'shipped', 'completed' ) ) ) {
			return;
		}
		if ( $t['in_person'] ) {
			echo '<div class="order-track"><span class="order-track__label">Delivery</span><strong>' . ( $order->has_status( 'completed' ) ? 'Delivered in person' : 'In-person delivery by our team' ) . '</strong></div>';
			return;
		}
		echo '<div class="order-track"><span class="order-track__label">Shipped with ' . esc_html( $t['carrier'] ) . '</span><strong>' . esc_html( $t['number'] ) . '</strong>';
		if ( $t['url'] ) {
			echo '<a class="btn btn--solid btn--sm" href="' . esc_url( $t['url'] ) . '" target="_blank" rel="noopener"><span>Track delivery &rarr;</span></a>';
		}
		echo '</div>';
	}
);
add_filter(
	'woocommerce_my_account_my_orders_actions',
	function ( $actions, $order ) {
		$t = oneam_order_tracking( $order );
		if ( $t && $t['url'] && $order->has_status( array( 'shipped', 'completed' ) ) ) {
			$actions['oneam_track'] = array(
				'url'  => $t['url'],
				'name' => 'Track',
			);
		}
		return $actions;
	},
	10,
	2
);
