<?php
/**
 * 고객에게 이유 보내기 · 고객 답장
 *
 * 1) 도매 신청 검토: 관리자가 상태(검토 중 · 추가 정보 요청 · 승인 · 거절)와 이유를 골라 보내면
 *    메일 + 고객의 신청 상태 화면에 그대로 보이고, "추가 정보 요청"이면 고객이 화면에서 답장할 수 있음
 * 2) 주문: 관리자 주문 화면의 "Message to customer" 칸에서 이유를 골라(보류 · 취소 등) 보내고 상태도 함께 변경,
 *    고객은 내 계정 → 주문 보기에서 메시지를 보고 답장
 *
 * 이유 목록은 아래 함수에서 고치면 됩니다 (영문 그대로 고객에게 감).
 *
 * @package 1am-vivid
 */

defined( 'ABSPATH' ) || exit;

/* ---------------------------------------------------------------------------
 * 1) 도매 신청 검토
 * ------------------------------------------------------------------------- */

/** 신청 검토 — 상태별 이유 목록 */
function oneam_review_reasons() {
	return apply_filters(
		'oneam_review_reasons',
		array(
			'info'     => array(
				'Please send a copy of your provincial vape retail licence or business licence.',
				'Please confirm your 9-digit Business Number (BN).',
				'We could not find your store at the address provided. Please confirm the store address.',
				'Please send a photo of your storefront showing the store name.',
				'Please confirm the province where you will sell the products.',
			),
			'rejected' => array(
				'We could not verify your business information.',
				'The business does not appear to be a licensed vape retail store.',
				'We are not able to supply stores in your province at this time.',
				'An account already exists for this business.',
				'We did not receive the information we asked for.',
			),
			'approved' => array(
				'Welcome to 1AM. Your sales rep will be in touch shortly.',
			),
		)
	);
}

/** 신청 검토 기록 (누가 · 언제 · 상태 · 내용) */
function oneam_review_log( $user_id, $who, $status, $text ) {
	$log   = (array) get_user_meta( $user_id, 'oneam_review_log', true );
	$log[] = array(
		'time'   => current_time( 'mysql' ),
		'who'    => $who,
		'status' => $status,
		'text'   => (string) $text,
	);
	update_user_meta( $user_id, 'oneam_review_log', array_slice( array_filter( $log ), -30 ) );
	if ( 'staff' === $who ) {
		delete_user_meta( $user_id, 'oneam_review_replied' );
	}
}

function oneam_review_history( $user_id ) {
	return array_values( array_filter( (array) get_user_meta( $user_id, 'oneam_review_log', true ), 'is_array' ) );
}

/** 관리자가 보낸 가장 최근 메시지 (상태별) */
function oneam_review_last_message( $user_id, $status ) {
	foreach ( array_reverse( oneam_review_history( $user_id ) ) as $row ) {
		if ( 'staff' === $row['who'] && $status === $row['status'] && '' !== $row['text'] ) {
			return $row['text'];
		}
	}
	return '';
}

/** 관리자 사용자 화면: 검토 칸 (상태 · 이유 · 메일) */
function oneam_review_panel( $user ) {
	$status  = oneam_wholesale_status( $user->ID );
	$want    = isset( $_GET['oneam_to'] ) ? sanitize_key( wp_unslash( $_GET['oneam_to'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
	$current = in_array( $want, array( 'info', 'rejected' ), true ) ? $want : $status;
	$labels  = array(
		''         => 'Not a wholesale applicant',
		'pending'  => 'Pending — under review',
		'info'     => 'Need more information',
		'approved' => 'Approved',
		'rejected' => 'Rejected',
	);
	?>
	<div class="oneam-review" style="max-width:640px">
		<p style="margin-top:0"><label for="oneam_wholesale_status"><strong>Status</strong></label><br>
			<select name="oneam_wholesale_status" id="oneam_wholesale_status">
				<?php foreach ( $labels as $k => $l ) : ?>
					<option value="<?php echo esc_attr( $k ); ?>" <?php selected( $current, $k ); ?>><?php echo esc_html( $l ); ?></option>
				<?php endforeach; ?>
			</select>
		</p>
		<p><label for="oneam_review_reason"><strong>Reason</strong> <span class="description">(pick one, then edit the message below if needed)</span></label><br>
			<select id="oneam_review_reason" style="max-width:100%">
				<option value="">— Choose a reason —</option>
				<?php foreach ( oneam_review_reasons() as $st => $list ) : ?>
					<optgroup label="<?php echo esc_attr( $labels[ $st ] ?? $st ); ?>" data-status="<?php echo esc_attr( $st ); ?>">
						<?php foreach ( $list as $r ) : ?>
							<option value="<?php echo esc_attr( $r ); ?>"><?php echo esc_html( $r ); ?></option>
						<?php endforeach; ?>
					</optgroup>
				<?php endforeach; ?>
				<option value="__custom">Write my own…</option>
			</select>
		</p>
		<p><label for="oneam_review_message"><strong>Message to the applicant</strong></label><br>
			<textarea name="oneam_review_message" id="oneam_review_message" rows="4" class="large-text" placeholder="Shown in the email and on the applicant's status page."></textarea>
		</p>
		<p><label><input type="checkbox" name="oneam_review_send" value="1" checked> Email the applicant</label>
			<br><span class="description">Click <strong>Save &amp; send</strong> below. Approved and Rejected change what the store can see right away.</span></p>
		<?php $hist = oneam_review_history( $user->ID ); ?>
		<?php if ( $hist ) : ?>
			<p><strong>History</strong></p>
			<ol style="margin:0 0 0 18px">
				<?php foreach ( array_reverse( $hist ) as $row ) : ?>
					<li style="margin-bottom:8px">
						<span style="color:#646970"><?php echo esc_html( $row['time'] ); ?> ·</span>
						<strong><?php echo 'store' === $row['who'] ? 'Applicant replied' : esc_html( $labels[ $row['status'] ] ?? $row['status'] ); ?></strong>
						<?php if ( '' !== $row['text'] ) : ?>
							<br><span style="white-space:pre-wrap"><?php echo esc_html( $row['text'] ); ?></span>
						<?php endif; ?>
					</li>
				<?php endforeach; ?>
			</ol>
		<?php endif; ?>
	</div>
	<script>
	(function () {
		var st = document.getElementById('oneam_wholesale_status'), rs = document.getElementById('oneam_review_reason'), msg = document.getElementById('oneam_review_message');
		if (!st || !rs || !msg) return;
		function filter() {
			rs.querySelectorAll('optgroup').forEach(function (g) { g.hidden = g.dataset.status !== st.value; g.disabled = g.hidden; });
			rs.value = '';
		}
		st.addEventListener('change', filter); filter();
		rs.addEventListener('change', function () {
			if (rs.value === '__custom') { msg.value = ''; msg.focus(); return; }
			if (rs.value) msg.value = rs.value;
		});
		if (location.search.indexOf('oneam_to=') > -1) { document.getElementById('oneam-review').scrollIntoView(); st.focus(); }
	})();
	</script>
	<?php
}

// 신청자가 "추가 정보 요청"에 답장
add_action(
	'init',
	function () {
		if ( empty( $_POST['oneam_review_reply'] ) || ! is_user_logged_in() ) {
			return;
		}
		$uid = get_current_user_id();
		if ( ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) ), 'oneam_review_reply' ) ) {
			return;
		}
		$text = isset( $_POST['oneam_reply_text'] ) ? trim( sanitize_textarea_field( wp_unslash( $_POST['oneam_reply_text'] ) ) ) : '';
		$text = function_exists( 'mb_substr' ) ? mb_substr( $text, 0, 2000 ) : substr( $text, 0, 2000 );
		if ( '' !== $text && 'info' === oneam_wholesale_status( $uid ) ) {
			oneam_review_log( $uid, 'store', 'info', $text );
			update_user_meta( $uid, 'oneam_review_replied', current_time( 'mysql' ) );
			$biz = get_user_meta( $uid, 'oneam_business_name', true );
			oneam_mail(
				oneam_staff_emails(),
				'[1AM] Applicant replied: ' . $biz,
				"{$biz} replied to your request for more information:\n\n{$text}\n\nReview the application:\n" . admin_url( 'user-edit.php?user_id=' . $uid . '#oneam-review' )
			);
		}
		wp_safe_redirect( add_query_arg( 'replied', '' !== $text ? '1' : '0', oneam_signup_url() ) . '#apply' );
		exit;
	}
);

/* ---------------------------------------------------------------------------
 * 2) 주문: 고객에게 메시지 (이유) + 상태 변경, 고객 답장
 * ------------------------------------------------------------------------- */

/** 주문 메시지 — 상태별 이유 목록 ({email} = e-Transfer 받는 주소) */
function oneam_order_reasons() {
	return apply_filters(
		'oneam_order_reasons',
		array(
			'on-hold'    => array(
				'We have not received your payment yet. Please send an Interac e-Transfer for the order total to {email} with your order number in the message.',
				'The payment we received does not match the order total. Please contact us so we can sort it out.',
				'We need your Business Number (BN) before we can ship this order. Please reply with your 9-digit BN.',
				'An item in your order is temporarily out of stock. We will ship as soon as it arrives — reply if you would like to change your order.',
				'Please confirm the province where these products will be sold, so we can ship the correct excise stamp.',
			),
			'processing' => array(
				'Payment confirmed — thank you. We are preparing your shipment.',
			),
			'cancelled'  => array(
				'We did not receive payment in time, so this order has been cancelled.',
				'This order was cancelled at your request.',
				'We could not verify the store for this order.',
				'An item is no longer available. Any payment received will be refunded.',
			),
		)
	);
}

/** 관리자 주문 화면에 칸 추가 (HPOS · 예전 방식 모두) */
add_action(
	'add_meta_boxes',
	function () {
		$screen = function_exists( 'wc_get_page_screen_id' ) ? wc_get_page_screen_id( 'shop-order' ) : 'shop_order';
		add_meta_box( 'oneam-order-message', 'Message to customer', 'oneam_order_message_box', $screen, 'side', 'high' );
	}
);

function oneam_order_message_box( $post_or_order ) {
	$order = $post_or_order instanceof WC_Order ? $post_or_order : wc_get_order( $post_or_order->ID );
	if ( ! $order ) {
		return;
	}
	$statuses = array(
		''           => 'Keep current status',
		'on-hold'    => 'On hold',
		'processing' => 'Payment confirmed · Preparing shipment',
		'completed'  => 'Completed',
		'cancelled'  => 'Cancelled',
	);
	$email    = function_exists( 'oneam_etransfer_email' ) ? oneam_etransfer_email() : '';
	?>
	<p><label for="oneam_msg_status"><strong>Change status to</strong></label>
		<select name="oneam_msg_status" id="oneam_msg_status" style="width:100%">
			<?php foreach ( $statuses as $k => $l ) : ?>
				<option value="<?php echo esc_attr( $k ); ?>"><?php echo esc_html( $l ); ?></option>
			<?php endforeach; ?>
		</select>
	</p>
	<p><label for="oneam_msg_reason"><strong>Reason</strong></label>
		<select id="oneam_msg_reason" style="width:100%">
			<option value="">— Choose a reason —</option>
			<?php foreach ( oneam_order_reasons() as $st => $list ) : ?>
				<optgroup label="<?php echo esc_attr( $statuses[ $st ] ?? $st ); ?>" data-status="<?php echo esc_attr( $st ); ?>">
					<?php foreach ( $list as $r ) : ?>
						<?php $r = str_replace( '{email}', $email, $r ); ?>
						<option value="<?php echo esc_attr( $r ); ?>"><?php echo esc_html( wp_trim_words( $r, 9 ) ); ?></option>
					<?php endforeach; ?>
				</optgroup>
			<?php endforeach; ?>
			<option value="__custom">Write my own…</option>
		</select>
	</p>
	<p><label for="oneam_msg_text"><strong>Message</strong></label>
		<textarea name="oneam_msg_text" id="oneam_msg_text" rows="5" style="width:100%" placeholder="Emailed to the customer and shown in My account → Orders."></textarea>
	</p>
	<p><button type="submit" class="button button-primary" name="save" value="Update" style="width:100%">Save &amp; send to customer</button></p>
	<p class="description">The customer gets the message by email, sees it in My account → Orders, and can reply there. Replies appear in Order notes below.</p>
	<?php wp_nonce_field( 'oneam_order_msg', 'oneam_order_msg_nonce' ); ?>
	<script>
	(function () {
		var st = document.getElementById('oneam_msg_status'), rs = document.getElementById('oneam_msg_reason'), msg = document.getElementById('oneam_msg_text');
		if (!st || !rs || !msg) return;
		rs.addEventListener('change', function () {
			if (rs.value === '__custom') { msg.value = ''; msg.focus(); return; }
			if (!rs.value) return;
			msg.value = rs.value;
			var g = rs.options[rs.selectedIndex].parentNode;
			if (g && g.dataset && g.dataset.status) st.value = g.dataset.status;
		});
	})();
	</script>
	<?php
}

// 저장: WooCommerce 가 주문을 저장한 다음 (상태 변경이 있으면 이 칸이 우선)
add_action(
	'woocommerce_process_shop_order_meta',
	function ( $order_id ) {
		if ( ! isset( $_POST['oneam_order_msg_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['oneam_order_msg_nonce'] ) ), 'oneam_order_msg' ) || ! current_user_can( 'edit_shop_orders' ) ) {
			return;
		}
		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			return;
		}
		$to   = isset( $_POST['oneam_msg_status'] ) ? sanitize_key( wp_unslash( $_POST['oneam_msg_status'] ) ) : '';
		$text = isset( $_POST['oneam_msg_text'] ) ? trim( sanitize_textarea_field( wp_unslash( $_POST['oneam_msg_text'] ) ) ) : '';
		if ( '' !== $text ) {
			$order->add_order_note( $text, 1, true ); // 고객 메모 → WooCommerce 가 고객에게 메일
		}
		if ( $to && in_array( 'wc-' . $to, array_keys( wc_get_order_statuses() ), true ) && ! $order->has_status( $to ) ) {
			$order->update_status( $to, '' !== $text ? 'Status changed with a message to the customer.' : '' );
		}
	},
	60
);

// 고객 메모 메일 제목 · 머리말: "Update on your 1AM order #123"
add_filter(
	'woocommerce_email_subject_customer_note',
	function ( $subject, $order ) {
		return $order ? 'Update on your 1AM order #' . $order->get_order_number() : $subject;
	},
	10,
	2
);
add_filter(
	'woocommerce_email_heading_customer_note',
	function () {
		return 'An update on your order';
	}
);

/** 주문의 고객 답장 목록 */
function oneam_order_replies( $order ) {
	return array_values( array_filter( (array) $order->get_meta( '_oneam_replies' ), 'is_array' ) );
}

// 내 계정 → 주문 보기: 고객 답장 칸 (주문 상세 아래)
add_action(
	'woocommerce_view_order',
	function ( $order_id ) {
		$order = wc_get_order( $order_id );
		if ( ! $order || (int) $order->get_customer_id() !== get_current_user_id() ) {
			return;
		}
		$replies = oneam_order_replies( $order );
		?>
		<section class="order-reply" id="order-reply">
			<h2 class="order-reply__title">Questions about this order?</h2>
			<?php if ( isset( $_GET['replied'] ) ) : // phpcs:ignore WordPress.Security.NonceVerification ?>
				<p class="order-reply__flash">Thanks — we received your message and will get back to you within 1–2 business days.</p>
			<?php endif; ?>
			<?php if ( $replies ) : ?>
				<ul class="order-reply__list">
					<?php foreach ( $replies as $r ) : ?>
						<li><span><?php echo esc_html( date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $r['time'] ) ) ); ?> · You wrote</span><p><?php echo nl2br( esc_html( $r['text'] ) ); // phpcs:ignore ?></p></li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
			<form method="post" class="order-reply__form">
				<?php wp_nonce_field( 'oneam_order_reply_' . $order->get_id() ); ?>
				<input type="hidden" name="oneam_order_reply" value="<?php echo esc_attr( $order->get_id() ); ?>">
				<label for="oneam_order_reply_text">Send us a message about order #<?php echo esc_html( $order->get_order_number() ); ?></label>
				<textarea id="oneam_order_reply_text" name="oneam_order_reply_text" rows="4" maxlength="2000" required placeholder="e.g. Payment sent today from Maple Vape Ltd."></textarea>
				<button type="submit" class="btn btn--solid"><span>Send message &rarr;</span></button>
			</form>
		</section>
		<?php
	},
	20
);

add_action(
	'init',
	function () {
		if ( empty( $_POST['oneam_order_reply'] ) || ! is_user_logged_in() ) {
			return;
		}
		$order_id = absint( $_POST['oneam_order_reply'] );
		$order    = wc_get_order( $order_id );
		if ( ! $order || (int) $order->get_customer_id() !== get_current_user_id() || ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) ), 'oneam_order_reply_' . $order_id ) ) {
			return;
		}
		$text = isset( $_POST['oneam_order_reply_text'] ) ? trim( sanitize_textarea_field( wp_unslash( $_POST['oneam_order_reply_text'] ) ) ) : '';
		$text = function_exists( 'mb_substr' ) ? mb_substr( $text, 0, 2000 ) : substr( $text, 0, 2000 );
		if ( '' !== $text ) {
			$replies   = oneam_order_replies( $order );
			$replies[] = array(
				'time' => current_time( 'mysql' ),
				'text' => $text,
			);
			$order->update_meta_data( '_oneam_replies', array_slice( $replies, -30 ) );
			$order->add_order_note( 'Customer replied: ' . $text, 0, false ); // 관리자 주문 화면 메모에 보임
			$order->save();
			oneam_mail(
				oneam_staff_emails(),
				'[1AM] Customer message on order #' . $order->get_order_number(),
				$order->get_billing_company() . ' (' . $order->get_billing_email() . ") wrote about order #{$order->get_order_number()}:\n\n{$text}\n\nOpen the order:\n" . $order->get_edit_order_url()
			);
		}
		wp_safe_redirect( add_query_arg( 'replied', '1', $order->get_view_order_url() ) . '#order-reply' );
		exit;
	}
);
