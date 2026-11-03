<?php
/**
 * 도매 가입 신청 · 승인 관리
 *
 * 사이트:  "wholesale-signup" 페이지(또는 [oneam_wholesale_signup] 숏코드)에 가입 신청 폼
 *          → 계정 생성(승인 대기) + 사업자 서류 업로드 + 관리자에게 알림 메일
 * 관리자:  Users 화면의 "Wholesale" 열 / "Pending wholesale" 필터 / Approve · Reject 버튼
 *          사용자 프로필 화면에서 사업자 정보 · 서류 확인 · 상태 변경
 *          승인 · 거절하면 신청자에게 자동 메일
 *
 * 상태 값(사용자 메타 oneam_wholesale_status): pending | approved | rejected
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** 캐나다 주 */
function oneam_provinces() {
	return array(
		'AB' => 'Alberta', 'BC' => 'British Columbia', 'MB' => 'Manitoba', 'NB' => 'New Brunswick',
		'NL' => 'Newfoundland and Labrador', 'NS' => 'Nova Scotia', 'NT' => 'Northwest Territories',
		'NU' => 'Nunavut', 'ON' => 'Ontario', 'PE' => 'Prince Edward Island', 'QC' => 'Quebec',
		'SK' => 'Saskatchewan', 'YT' => 'Yukon',
	);
}

/** 신청서 항목 (키 => 라벨) */
function oneam_wholesale_fields() {
	return array(
		'oneam_business_name'   => 'Business name',
		'oneam_contact_name'    => 'Contact name',
		'oneam_phone'           => 'Phone',
		'oneam_store_address'   => 'Store address',
		'oneam_city'            => 'City',
		'oneam_province'        => 'Province',
		'oneam_postcode'        => 'Postal code',
	);
}

/** 선택 항목 (빈칸 가능) */
function oneam_wholesale_optional_fields() {
	return array(
		'oneam_business_number' => 'Business number (BN)',
		'oneam_pst_number'      => 'PST number (BC/SK, for resale exemption)',
	);
}

/** 사업자번호(BN) 형식: 9자리 (뒤에 RT0001 같은 프로그램 번호가 붙어도 됨) */
function oneam_valid_bn( $bn ) {
	return (bool) preg_match( '/^\d{9}([A-Z]{2}\d{4})?$/i', preg_replace( '/[\s-]/', '', (string) $bn ) );
}

/** 형식 검사: BN 9자리 · 우편번호 A1A 1A1 · 전화 10자리. 문제가 있으면 오류 코드 */
function oneam_validate_application( $data ) {
	if ( '' !== trim( (string) ( $data['oneam_business_number'] ?? '' ) ) && ! oneam_valid_bn( $data['oneam_business_number'] ) ) {
		return 'bn'; // 가입 때 BN 은 선택 — 넣었다면 형식만 확인 (결제할 때는 필수)
	}
	if ( ! preg_match( '/^[ABCEGHJ-NPRSTVXY]\d[ABCEGHJ-NPRSTV-Z][ -]?\d[ABCEGHJ-NPRSTV-Z]\d$/i', trim( $data['oneam_postcode'] ) ) ) {
		return 'postcode';
	}
	$phone = preg_replace( '/\D/', '', $data['oneam_phone'] );
	if ( 11 === strlen( $phone ) && '1' === $phone[0] ) {
		$phone = substr( $phone, 1 );
	}
	return 10 === strlen( $phone ) ? '' : 'phone';
}

/** 메일 보내기: 답장은 영업 메일로, 서명은 유통사 */
function oneam_mail( $to, $subject, $body ) {
	$email = oneam_opt( 'oneam_email' );
	$sign  = "\n\n—\n1AM · Distributed by " . oneam_opt( 'oneam_company' ) . "\n" . $email;
	$head  = $email ? array( 'Reply-To: 1AM <' . $email . '>' ) : array();
	return wp_mail( $to, $subject, $body . $sign, $head );
}

/** 새 신청 알림 받을 주소: 영업 메일 + 사이트 관리자 메일 */
function oneam_staff_emails() {
	return array_values( array_unique( array_filter( array( oneam_opt( 'oneam_email' ), get_option( 'admin_email' ) ), 'is_email' ) ) );
}

function oneam_wholesale_status( $user_id ) {
	$s = get_user_meta( $user_id, 'oneam_wholesale_status', true );
	return $s ? $s : '';
}

/* ---------------------------------------------------------------------------
 * 서류 보관 폴더 (웹에서 직접 열 수 없게)
 * ------------------------------------------------------------------------- */
function oneam_docs_dir() {
	// 가능하면 웹 폴더 바깥(사이트 폴더 한 단계 위)에 저장 — 웹 주소로는 절대 열 수 없음
	$outside = dirname( untrailingslashit( ABSPATH ) ) . '/oneam-private-docs';
	if ( is_dir( $outside ) || ( is_writable( dirname( $outside ) ) && wp_mkdir_p( $outside ) ) ) {
		return $outside;
	}
	return oneam_docs_dir_public();
}

/** 예전 보관 위치 (wp-content/uploads 안, .htaccess 로 차단 · 파일 이름은 무작위) */
function oneam_docs_dir_public() {
	$up  = wp_upload_dir();
	$dir = trailingslashit( $up['basedir'] ) . 'oneam-wholesale-docs';
	if ( ! is_dir( $dir ) ) {
		wp_mkdir_p( $dir );
	}
	if ( ! file_exists( $dir . '/.htaccess' ) ) {
		file_put_contents( $dir . '/.htaccess', "Require all denied\nDeny from all\n" ); // phpcs:ignore WordPress.WP.AlternativeFunctions
	}
	if ( ! file_exists( $dir . '/index.php' ) ) {
		file_put_contents( $dir . '/index.php', '<?php // Silence.' ); // phpcs:ignore WordPress.WP.AlternativeFunctions
	}
	return $dir;
}

/* ---------------------------------------------------------------------------
 * 가입 신청 처리
 * ------------------------------------------------------------------------- */
add_action(
	'init',
	function () {
		if ( empty( $_POST['oneam_wholesale_apply'] ) ) {
			return;
		}
		$back = wp_get_referer() ? wp_get_referer() : oneam_signup_url();
		$fail = function ( $code ) use ( $back ) {
			// 입력한 내용은 그대로 돌려줌 (비밀번호 제외) — 하나 틀렸다고 전부 다시 쓰지 않게
			$keep = array();
			foreach ( array_merge( array_keys( oneam_wholesale_fields() ), array_keys( oneam_wholesale_optional_fields() ), array( 'oneam_email', 'oneam_agree', 'oneam_marketing' ) ) as $k ) {
				if ( isset( $_POST[ $k ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
					$keep[ $k ] = sanitize_text_field( wp_unslash( $_POST[ $k ] ) ); // phpcs:ignore WordPress.Security.NonceVerification
				}
			}
			$token = wp_generate_password( 12, false );
			set_transient( 'oneam_apply_' . $token, $keep, 30 * MINUTE_IN_SECONDS );
			wp_safe_redirect( add_query_arg( array( 'apply_error' => $code, 'apply_data' => $token ), remove_query_arg( array( 'apply_error', 'apply_data', 'applied' ), $back ) ) . '#apply' );
			exit;
		};

		if ( ! isset( $_POST['oneam_apply_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['oneam_apply_nonce'] ) ), 'oneam_apply' ) ) {
			$fail( 'expired' );
		}
		// 스팸 봇 함정 (사람에게는 보이지 않는 칸)
		if ( ! empty( $_POST['oneam_website'] ) ) {
			$fail( 'invalid' );
		}

		$data = array();
		foreach ( oneam_wholesale_fields() as $key => $label ) {
			$data[ $key ] = isset( $_POST[ $key ] ) ? sanitize_text_field( wp_unslash( $_POST[ $key ] ) ) : '';
			if ( '' === $data[ $key ] ) {
				$fail( 'missing' );
			}
		}
		if ( ! isset( oneam_provinces()[ $data['oneam_province'] ] ) ) {
			$fail( 'missing' ); // 가입은 캐나다 모든 주 가능 (주문은 판매 중인 주만)
		}
		foreach ( oneam_wholesale_optional_fields() as $key => $label ) {
			$data[ $key ] = isset( $_POST[ $key ] ) ? sanitize_text_field( wp_unslash( $_POST[ $key ] ) ) : '';
		}
		$bad = oneam_validate_application( $data );
		if ( $bad ) {
			$fail( $bad );
		}
		$data['oneam_postcode'] = strtoupper( preg_replace( '/^(\w{3})[ -]?(\w{3})$/', '$1 $2', trim( $data['oneam_postcode'] ) ) );
		if ( ! in_array( $data['oneam_province'], array( 'BC', 'SK', 'MB' ), true ) ) {
			$data['oneam_pst_number'] = ''; // PST 재판매 면세번호가 있는 주만
		}
		$email    = isset( $_POST['oneam_email'] ) ? sanitize_email( wp_unslash( $_POST['oneam_email'] ) ) : '';
		$password = isset( $_POST['oneam_password'] ) ? (string) wp_unslash( $_POST['oneam_password'] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		if ( ! is_email( $email ) ) {
			$fail( 'email' );
		}
		if ( email_exists( $email ) ) {
			$fail( 'exists' );
		}
		if ( strlen( $password ) < 8 ) {
			$fail( 'password' );
		}
		if ( empty( $_POST['oneam_agree'] ) ) {
			$fail( 'agree' );
		}

		// 계정 생성 (WooCommerce 고객, 승인 대기)
		// 이메일이 곧 아이디 (로그인할 때 이메일만 입력)
		$login = sanitize_user( strtolower( $email ), true );
		if ( '' === $login || username_exists( $login ) ) {
			$fail( 'exists' );
		}
		$user_id = wp_insert_user(
			array(
				'user_login'   => $login,
				'user_email'   => $email,
				'user_pass'    => $password,
				'display_name' => $data['oneam_business_name'],
				'first_name'   => $data['oneam_contact_name'],
				'role'         => get_role( 'customer' ) ? 'customer' : 'subscriber',
			)
		);
		if ( is_wp_error( $user_id ) ) {
			$fail( 'invalid' );
		}
		foreach ( $data as $key => $value ) {
			update_user_meta( $user_id, $key, $value );
		}
		update_user_meta( $user_id, 'oneam_wholesale_status', 'pending' );
		if ( oneam_verify_required() ) {
			update_user_meta( $user_id, 'oneam_email_verified', '0' );
			oneam_send_verify_email( $user_id );
		}
		update_user_meta( $user_id, 'oneam_applied_at', current_time( 'mysql' ) );
		update_user_meta( $user_id, 'oneam_terms_accepted', current_time( 'mysql' ) );
		update_user_meta( $user_id, 'oneam_terms_version', function_exists( 'oneam_terms_version' ) ? oneam_terms_version() : '' );
		// CASL: 마케팅 메일은 직접 체크한 경우에만 (기본 해제), 동의 시각 기록
		if ( ! empty( $_POST['oneam_marketing'] ) ) {
			update_user_meta( $user_id, 'oneam_marketing_consent', current_time( 'mysql' ) );
		}
		// WooCommerce 청구지 정보도 미리 채워둠
		update_user_meta( $user_id, 'billing_company', $data['oneam_business_name'] );
		update_user_meta( $user_id, 'billing_phone', $data['oneam_phone'] );
		update_user_meta( $user_id, 'billing_address_1', $data['oneam_store_address'] );
		update_user_meta( $user_id, 'billing_city', $data['oneam_city'] );
		update_user_meta( $user_id, 'billing_state', $data['oneam_province'] );
		update_user_meta( $user_id, 'billing_postcode', $data['oneam_postcode'] );
		update_user_meta( $user_id, 'billing_country', 'CA' );
		update_user_meta( $user_id, 'billing_email', $email );

		// 가입 때 넣은 BN 은 결제 화면의 Business Number 칸에 미리 채워짐 (내 계정에서도 수정 가능)
		if ( $data['oneam_business_number'] ) {
			update_user_meta( $user_id, '_wc_other/oneam/business-number', preg_replace( '/[\s-]/', '', $data['oneam_business_number'] ) );
		}

		// 알림 메일
		$admin_url = admin_url( 'users.php?oneam_wholesale=pending' );
		oneam_mail(
			oneam_staff_emails(),
			'[1AM] New retailer application: ' . $data['oneam_business_name'],
			"A new retail account application is waiting for verification.\n\nBusiness: {$data['oneam_business_name']}\nBusiness number: " . ( $data['oneam_business_number'] ?: '— (not provided yet; required at first checkout)' ) . "\nStore: {$data['oneam_store_address']}, {$data['oneam_city']}, {$data['oneam_province']} {$data['oneam_postcode']}\nPST number: " . ( $data['oneam_pst_number'] ?: '—' ) . "\nContact: {$data['oneam_contact_name']}\nEmail: {$email}\nPhone: {$data['oneam_phone']}\n\nReview the store and approve or reject here:\n{$admin_url}"
		);
		oneam_mail(
			$email,
			'We received your 1AM retail account application',
			"Hi {$data['oneam_contact_name']},\n\nThank you for applying for a 1AM retail account. 1AM products are sold exclusively to verified and approved vape retailers in " . oneam_regions_phrase() . ".\n\nOur team will review your business information, usually within 1–2 business days. We may contact you if additional documentation is needed. You will receive an email as soon as your account is approved.\n\nQuestions? Just reply to this email."
		);

		// 바로 로그인 → 승인 대기 안내
		wp_set_current_user( $user_id );
		wp_set_auth_cookie( $user_id );
		wp_safe_redirect( add_query_arg( 'applied', '1', remove_query_arg( 'apply_error', $back ) ) . '#apply' );
		exit;
	}
);

/* ---------------------------------------------------------------------------
 * 가입 신청 폼
 * ------------------------------------------------------------------------- */
function oneam_wholesale_form() {
	ob_start();
	echo '<div class="apply" id="apply">';

	// 관리자는 방문자에게 보이는 폼을 미리보기로 확인 (제출은 막음 — 제출하면 새 계정으로 로그인되므로)
	$preview = current_user_can( 'edit_users' );
	if ( $preview ) {
		echo '<div class="apply__note"><strong>Admin preview</strong><p>You are logged in as an admin, so this is a preview of the form visitors see. Log out (or use a private window) to test a real application. Review applications in <a href="' . esc_url( admin_url( 'users.php?oneam_wholesale=pending' ) ) . '">Users &rarr; Pending wholesale</a>.</p></div>';
	}

	if ( is_user_logged_in() && ! $preview ) {
		$status = oneam_wholesale_status( get_current_user_id() );
		$mail = oneam_opt( 'oneam_email' );
		if ( 'approved' === $status || 'approved' === oneam_member_state() ) {
			$card = array( 'ok', 'check', 'Your account is approved', 'You can view wholesale pricing and place orders online.', array( array( oneam_shop_url(), 'Shop wholesale', 'solid' ), array( wc_get_page_permalink( 'myaccount' ), 'My account', 'ghost' ) ), false );
		} elseif ( 'rejected' === $status ) {
			$card = array( 'bad', 'mail', 'Your application was not approved', 'If you think this is a mistake, contact us at ' . $mail . ' and we will take another look.', array( array( 'mailto:' . $mail, 'Contact us', 'solid' ) ), false );
		} elseif ( 'info' === $status ) {
			$card = array( 'info', 'mail', 'We need a little more information', 'To finish reviewing your application, please answer the request below.', array(), false );
		} else {
			$card = array( 'wait', 'clock', 'Application received', 'Thanks for applying. We are reviewing your store information now.', array( array( home_url( '/#products' ), 'Browse products', 'solid' ), array( wc_get_page_permalink( 'myaccount' ), 'My account', 'ghost' ) ), true );
		}
		$uid        = get_current_user_id();
		$unverified = 'wait' === $card[0] && ! oneam_email_verified( $uid );
		if ( $unverified ) {
			$card[1] = 'mail';
			$card[2] = 'Confirm your email';
			$card[3] = 'We sent a confirmation link to ' . wp_get_current_user()->user_email . '. Click it to continue — we review your application once your email is confirmed.';
		}
		?>
		<div class="apply-status apply-status--<?php echo esc_attr( $card[0] ); ?>">
			<span class="apply-status__icon"><?php echo oneam_icon( $card[1] ); // phpcs:ignore ?></span>
			<h2 class="apply-status__title"><?php echo esc_html( $card[2] ); ?></h2>
			<p class="apply-status__text"><?php echo esc_html( $card[3] ); ?></p>
			<?php $reason = function_exists( 'oneam_review_last_message' ) && in_array( $status, array( 'rejected', 'info' ), true ) ? oneam_review_last_message( $uid, $status ) : ''; ?>
			<?php if ( $reason ) : ?>
				<div class="apply-status__reason"><span><?php echo 'info' === $status ? 'What we need' : 'Reason'; ?></span><p><?php echo nl2br( esc_html( $reason ) ); // phpcs:ignore ?></p></div>
			<?php endif; ?>
			<?php if ( 'info' === $status ) : ?>
				<?php if ( isset( $_GET['replied'] ) && '1' === $_GET['replied'] ) : // phpcs:ignore WordPress.Security.NonceVerification ?>
					<p class="apply-status__flash is-ok">Thanks — we received your reply and will continue the review.</p>
				<?php endif; ?>
				<form method="post" class="apply-status__reply">
					<?php wp_nonce_field( 'oneam_review_reply' ); ?>
					<input type="hidden" name="oneam_review_reply" value="1">
					<label for="oneam_reply_text">Your reply</label>
					<textarea id="oneam_reply_text" name="oneam_reply_text" rows="4" maxlength="2000" required placeholder="Type your answer here. You can also reply to our email with attachments."></textarea>
					<button type="submit" class="btn btn--solid"><span>Send reply &rarr;</span></button>
				</form>
			<?php endif; ?>
			<?php if ( $card[5] ) : ?>
				<?php if ( isset( $_GET['email_verified'] ) ) : // phpcs:ignore WordPress.Security.NonceVerification ?>
					<p class="apply-status__flash <?php echo '1' === $_GET['email_verified'] ? 'is-ok' : 'is-bad'; // phpcs:ignore ?>"><?php echo '1' === $_GET['email_verified'] ? 'Thanks — your email is confirmed.' : 'That link has expired or was already used. Send a new one below.'; // phpcs:ignore ?></p>
				<?php elseif ( isset( $_GET['verify_sent'] ) ) : // phpcs:ignore WordPress.Security.NonceVerification ?>
					<p class="apply-status__flash is-ok"><?php echo '1' === $_GET['verify_sent'] ? 'We sent a new confirmation link. Please check your inbox (and spam folder).' : 'We just sent you a link. Please wait a minute before asking for another one, and check your spam folder.'; // phpcs:ignore ?></p>
				<?php endif; ?>
				<ol class="apply-status__steps">
					<li class="is-done"><strong>Application sent</strong><span>Your store details are with our team.</span></li>
					<?php if ( oneam_verify_required() || get_user_meta( $uid, 'oneam_email_verified', true ) ) : ?>
						<li class="<?php echo $unverified ? 'is-now' : 'is-done'; ?>"><strong>Confirm your email</strong><span><?php echo $unverified ? 'Click the link we emailed you.' : 'Email confirmed.'; ?></span>
							<?php if ( $unverified ) : ?>
								<form method="post" class="apply-status__resend"><?php wp_nonce_field( 'oneam_resend_verify' ); ?><input type="hidden" name="oneam_resend_verify" value="1"><button type="submit">Resend the email</button></form>
							<?php endif; ?>
						</li>
					<?php endif; ?>
					<li class="<?php echo $unverified ? '' : 'is-now'; ?>"><strong>Review</strong><span>Usually within 1–2 business days.</span></li>
					<li><strong>Approved</strong><span>We email you, then you can view pricing and order online. Your Business Number (BN) is needed at your first checkout.</span></li>
				</ol>
			<?php endif; ?>
			<div class="apply-status__btns">
				<?php foreach ( $card[4] as $b ) : ?>
					<a class="btn btn--<?php echo esc_attr( $b[2] ); ?>" href="<?php echo esc_url( $b[0] ); ?>"><span><?php echo esc_html( $b[1] ); ?> &rarr;</span></a>
				<?php endforeach; ?>
			</div>
			<p class="apply-status__help">Questions? <?php echo esc_html( $mail ); ?></p>
		</div>
		<?php
		echo '</div>';
		return ob_get_clean();
	}

	$errors = array(
		'expired'  => 'The form expired. Please try again.',
		'missing'  => 'Please fill in every field.',
		'region'   => 'We currently supply stores in ' . oneam_regions_phrase() . ' only. Stores in other provinces and territories are not supported yet.',
		'bn'       => 'Please enter your 9-digit Business Number (BN), e.g. 123456789.',
		'postcode' => 'Please enter a valid Canadian postal code, e.g. V6B 1A1.',
		'phone'    => 'Please enter a 10-digit phone number, e.g. 604 555 0100.',
		'email'    => 'Please enter a valid email address.',
		'exists'   => 'An account with this email already exists. Please log in instead.',
		'password' => 'Your password must be at least 8 characters.',
		'agree'    => 'Please confirm your business is an authorized vape retailer and accept the Terms & Conditions.',
		'file'     => 'Please upload your business document as a PDF, JPG or PNG under 10 MB.',
		'invalid'  => 'Something went wrong. Please try again.',
	);
	$err = isset( $_GET['apply_error'] ) ? sanitize_key( wp_unslash( $_GET['apply_error'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
	$tok = isset( $_GET['apply_data'] ) ? sanitize_key( wp_unslash( $_GET['apply_data'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
	$old = $tok ? (array) get_transient( 'oneam_apply_' . $tok ) : array();
	$v   = function ( $k ) use ( $old ) {
		return isset( $old[ $k ] ) ? (string) $old[ $k ] : '';
	};
	// 오류 코드 → 빨갛게 표시할 칸
	$err_field = array(
		'bn'       => 'oneam_business_number',
		'postcode' => 'oneam_postcode',
		'phone'    => 'oneam_phone',
		'email'    => 'oneam_email',
		'exists'   => 'oneam_email',
		'password' => 'oneam_password',
		'agree'    => 'oneam_agree',
	);
	if ( 'missing' === $err ) {
		foreach ( oneam_wholesale_fields() as $k => $label ) {
			if ( '' === $v( $k ) ) {
				$err_field['missing'] = $k;
				break;
			}
		}
	}
	$bad_field = $err_field[ $err ] ?? '';
	$bad_attr  = function ( $k ) use ( $bad_field ) {
		return $k === $bad_field ? ' aria-invalid="true"' : '';
	};
	if ( $err && isset( $errors[ $err ] ) ) {
		printf( '<div class="apply__note is-bad" role="alert" data-field="%s">%s</div>', esc_attr( $bad_field ), esc_html( $errors[ $err ] ) );
	}
	?>
	<form class="apply__form" method="post" novalidate>
		<?php wp_nonce_field( 'oneam_apply', 'oneam_apply_nonce' ); ?>
		<input type="hidden" name="oneam_wholesale_apply" value="1">
		<div class="apply__hp" aria-hidden="true" hidden><label>Leave this field empty <input type="text" name="oneam_website" tabindex="-1" autocomplete="off"></label></div>

		<fieldset>
			<legend>Business</legend>
			<p><label for="oneam_business_name">Business name</label><input id="oneam_business_name" name="oneam_business_name" required autocomplete="organization" value="<?php echo esc_attr( $v( 'oneam_business_name' ) ); ?>"<?php echo $bad_attr( 'oneam_business_name' ); // phpcs:ignore ?>></p>
			<p><label for="oneam_business_number">Business number (BN) <small>(optional now — required before your first order)</small></label><input id="oneam_business_number" name="oneam_business_number" inputmode="numeric" placeholder="123456789" value="<?php echo esc_attr( $v( 'oneam_business_number' ) ); ?>"<?php echo $bad_attr( 'oneam_business_number' ); // phpcs:ignore ?>></p>
			<p class="is-wide"><label for="oneam_store_address">Store address</label><input id="oneam_store_address" name="oneam_store_address" required autocomplete="street-address" value="<?php echo esc_attr( $v( 'oneam_store_address' ) ); ?>"<?php echo $bad_attr( 'oneam_store_address' ); // phpcs:ignore ?>></p>
			<p><label for="oneam_city">City</label><input id="oneam_city" name="oneam_city" required autocomplete="address-level2" value="<?php echo esc_attr( $v( 'oneam_city' ) ); ?>"<?php echo $bad_attr( 'oneam_city' ); // phpcs:ignore ?>></p>
			<p><label for="oneam_province">Province</label><select id="oneam_province" name="oneam_province" required<?php echo $bad_attr( 'oneam_province' ); // phpcs:ignore ?>>
					<option value="">Choose…</option>
					<?php foreach ( oneam_provinces() as $code => $label ) : ?>
						<option value="<?php echo esc_attr( $code ); ?>"<?php selected( $v( 'oneam_province' ), $code ); ?>><?php echo esc_html( $label ); ?></option>
					<?php endforeach; ?>
				</select></p>
			<p><label for="oneam_postcode">Postal code</label><input id="oneam_postcode" name="oneam_postcode" required autocomplete="postal-code" placeholder="V6B 1A1" value="<?php echo esc_attr( $v( 'oneam_postcode' ) ); ?>"<?php echo $bad_attr( 'oneam_postcode' ); // phpcs:ignore ?>></p>
			<p class="apply__pst"><label for="oneam_pst_number">PST number <small>(optional — BC/SK, for resale exemption)</small></label><input id="oneam_pst_number" name="oneam_pst_number" autocomplete="off" value="<?php echo esc_attr( $v( 'oneam_pst_number' ) ); ?>"></p>
		</fieldset>

		<fieldset>
			<legend>Contact &amp; login</legend>
			<p><label for="oneam_contact_name">Contact name</label><input id="oneam_contact_name" name="oneam_contact_name" required autocomplete="name" value="<?php echo esc_attr( $v( 'oneam_contact_name' ) ); ?>"<?php echo $bad_attr( 'oneam_contact_name' ); // phpcs:ignore ?>></p>
			<p><label for="oneam_phone">Phone</label><input id="oneam_phone" name="oneam_phone" type="tel" inputmode="tel" required autocomplete="tel" placeholder="604 555 0100" value="<?php echo esc_attr( $v( 'oneam_phone' ) ); ?>"<?php echo $bad_attr( 'oneam_phone' ); // phpcs:ignore ?>></p>
			<p><label for="oneam_email">Email</label><input id="oneam_email" name="oneam_email" type="email" required autocomplete="email" value="<?php echo esc_attr( $v( 'oneam_email' ) ); ?>"<?php echo $bad_attr( 'oneam_email' ); // phpcs:ignore ?>></p>
			<p><label for="oneam_password">Password <small>(8+ characters)</small></label><span class="apply__pw"><input id="oneam_password" name="oneam_password" type="password" minlength="8" required autocomplete="new-password"<?php echo $bad_attr( 'oneam_password' ); // phpcs:ignore ?>><button type="button" class="apply__eye" aria-label="Show password" aria-pressed="false"><?php echo oneam_icon( 'eye' ); // phpcs:ignore ?></button></span></p>
		</fieldset>

		<fieldset>
			<legend>Confirm</legend>
			<p class="is-wide apply__check"><label><input type="checkbox" id="oneam_agree" name="oneam_agree" value="1" required<?php checked( '1', $v( 'oneam_agree' ) ); ?><?php echo $bad_attr( 'oneam_agree' ); // phpcs:ignore ?>> <span>I confirm my business is an authorized vape retail store in Canada, legally permitted to purchase and resell vaping products, that the information provided is accurate, and that I am <?php echo (int) oneam_opt( 'oneam_min_age' ); ?> or older. I agree to the <a href="<?php echo esc_url( home_url( '/terms/' ) ); ?>" target="_blank">Terms &amp; Conditions</a>, including <a href="<?php echo esc_url( home_url( '/terms/#vape-compliance' ) ); ?>" target="_blank">Vaping Product Compliance</a>.</span></label></p>
			<p class="is-wide apply__check"><label><input type="checkbox" id="oneam_marketing" name="oneam_marketing" value="1"<?php checked( '1', $v( 'oneam_marketing' ) ); ?>> <span>Optional: email me about new products and offers. I can unsubscribe at any time.</span></label></p>
		</fieldset>

		<div class="apply__actions">
			<button class="btn btn--solid btn--xl" type="submit"<?php disabled( $preview ); ?>><span>Submit application &rarr;</span></button>
			<p class="apply__login">Already have a retailer account? <a href="<?php echo esc_url( oneam_login_url() ); ?>">Log in &rarr;</a></p>
		</div>
	</form>
	<script>
	(function () {
		var form = document.querySelector('.apply__form');
		if (!form) return;
		var p = document.getElementById('oneam_province'), box = document.querySelector('.apply__pst');
		function sync() { if (!p || !box) return; box.hidden = p.value !== '' && ['BC', 'SK', 'MB'].indexOf(p.value) < 0; if (box.hidden) box.querySelector('input').value = ''; }
		if (p) { p.addEventListener('change', sync); sync(); }

		// 비밀번호 보기 (눈 아이콘)
		var eye = form.querySelector('.apply__eye'), pw = document.getElementById('oneam_password');
		var icons = { on: <?php echo wp_json_encode( oneam_icon( 'eye' ) ); ?>, off: <?php echo wp_json_encode( oneam_icon( 'eyeoff' ) ); ?> };
		if (eye && pw) eye.addEventListener('click', function () {
			var show = pw.type === 'password';
			pw.type = show ? 'text' : 'password';
			eye.innerHTML = show ? icons.off : icons.on;
			eye.setAttribute('aria-pressed', show ? 'true' : 'false');
			eye.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
			pw.focus();
		});

		// 칸마다 바로 확인: 틀린 칸만 빨갛게 + 아래에 이유 (입력한 내용은 그대로)
		var rules = {
			oneam_business_name: function (v) { return v ? '' : 'Enter your business name.'; },
			oneam_business_number: function (v) { return !v || /^\d{9}([A-Z]{2}\d{4})?$/i.test(v.replace(/[\s-]/g, '')) ? '' : 'Enter the 9-digit Business Number, e.g. 123456789 — or leave it blank for now.'; },
			oneam_store_address: function (v) { return v ? '' : 'Enter the store address.'; },
			oneam_city: function (v) { return v ? '' : 'Enter the city.'; },
			oneam_province: function (v) { return v ? '' : 'Choose the province.'; },
			oneam_postcode: function (v) { return /^[ABCEGHJ-NPRSTVXY]\d[ABCEGHJ-NPRSTV-Z][ -]?\d[ABCEGHJ-NPRSTV-Z]\d$/i.test(v) ? '' : 'Enter a Canadian postal code, e.g. V6B 1A1.'; },
			oneam_contact_name: function (v) { return v ? '' : 'Enter a contact name.'; },
			oneam_phone: function (v) { var d = v.replace(/\D/g, ''); if (d.length === 11 && d[0] === '1') d = d.slice(1); return d.length === 10 ? '' : 'Enter a 10-digit phone number, e.g. 604 555 0100.'; },
			oneam_email: function (v) { return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(v) ? '' : 'Enter a valid email address.'; },
			oneam_password: function (v) { return v.length >= 8 ? '' : 'Use at least 8 characters.'; },
			oneam_agree: function (v, el) { return el.checked ? '' : 'Please tick this box to continue.'; }
		};
		function holder(el) { return el.closest('p'); }
		function show(el, msg) {
			var h = holder(el), m = h.querySelector('.apply__err');
			el.setAttribute('aria-invalid', msg ? 'true' : 'false');
			h.classList.toggle('is-bad', !!msg);
			if (msg) {
				if (!m) { m = document.createElement('span'); m.className = 'apply__err'; m.id = el.id + '-err'; h.appendChild(m); el.setAttribute('aria-describedby', m.id); }
				m.textContent = msg;
			} else if (m) { m.remove(); el.removeAttribute('aria-describedby'); }
		}
		function check(el) { var r = rules[el.id]; if (!r) return ''; var msg = r(el.value.trim(), el); show(el, msg); return msg; }
		Object.keys(rules).forEach(function (id) {
			var el = document.getElementById(id); if (!el) return;
			el.addEventListener('blur', function () { if (el.value || el.getAttribute('aria-invalid') === 'true') check(el); });
			el.addEventListener(el.type === 'checkbox' || el.tagName === 'SELECT' ? 'change' : 'input', function () { if (el.getAttribute('aria-invalid') === 'true') check(el); });
		});
		form.addEventListener('submit', function (e) {
			var first = null;
			Object.keys(rules).forEach(function (id) { var el = document.getElementById(id); if (el && check(el) && !first) first = el; });
			if (first) {
				e.preventDefault();
				first.scrollIntoView({ behavior: 'smooth', block: 'center' });
				first.focus({ preventScroll: true });
			}
		});
		// 서버에서 돌아온 오류: 그 칸에 바로 표시
		var note = document.querySelector('.apply__note.is-bad[data-field]');
		if (note && note.dataset.field) {
			var el = document.getElementById(note.dataset.field);
			if (el) { show(el, note.textContent); setTimeout(function () { el.scrollIntoView({ block: 'center' }); el.focus({ preventScroll: true }); }, 60); }
		}
	})();
	</script>
	<?php
	echo '</div>';
	return ob_get_clean();
}
add_shortcode( 'oneam_wholesale_signup', 'oneam_wholesale_form' );

/* ---------------------------------------------------------------------------
 * 가입 이메일 인증 (설정: 1AM Settings → Business & legal → "Require email confirmation")
 * 메일 연동 후에 켜세요. 꺼져 있으면 아무 영향 없음. 예전 계정은 인증된 것으로 봄.
 * ------------------------------------------------------------------------- */
function oneam_verify_required() {
	return (bool) get_theme_mod( 'oneam_verify_email', false );
}

/** 이메일 인증을 마쳤는지 (인증이 필요 없던 계정 · 예전 계정은 true) */
function oneam_email_verified( $user_id ) {
	return '0' !== (string) get_user_meta( $user_id, 'oneam_email_verified', true );
}

/** 인증 링크 메일 보내기 (링크는 7일 동안 유효) */
function oneam_send_verify_email( $user_id ) {
	$user = get_userdata( $user_id );
	if ( ! $user ) {
		return false;
	}
	$token = wp_generate_password( 32, false );
	update_user_meta( $user_id, 'oneam_verify_hash', hash_hmac( 'sha256', $token, wp_salt( 'auth' ) ) );
	update_user_meta( $user_id, 'oneam_verify_sent', time() );
	$link = add_query_arg( 'oneam_verify', $user_id . '.' . $token, home_url( '/' ) );
	$name = get_user_meta( $user_id, 'oneam_contact_name', true ) ?: $user->first_name;
	return oneam_mail(
		$user->user_email,
		'Confirm your email for your 1AM retailer account',
		"Hi {$name},\n\nPlease confirm your email address to complete your 1AM retail account application:\n\n{$link}\n\nThis link works for 7 days. If you did not apply for a 1AM account, you can ignore this email."
	);
}

// 메일의 링크를 눌렀을 때
add_action(
	'init',
	function () {
		if ( empty( $_GET['oneam_verify'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			return;
		}
		list( $uid, $token ) = array_pad( explode( '.', sanitize_text_field( wp_unslash( $_GET['oneam_verify'] ) ), 2 ), 2, '' ); // phpcs:ignore WordPress.Security.NonceVerification
		$uid  = (int) $uid;
		$hash = (string) get_user_meta( $uid, 'oneam_verify_hash', true );
		$sent = (int) get_user_meta( $uid, 'oneam_verify_sent', true );
		$ok   = $uid && $hash && $token && hash_equals( $hash, hash_hmac( 'sha256', $token, wp_salt( 'auth' ) ) ) && ( time() - $sent ) < 7 * DAY_IN_SECONDS;
		if ( $ok ) {
			update_user_meta( $uid, 'oneam_email_verified', current_time( 'mysql' ) );
			delete_user_meta( $uid, 'oneam_verify_hash' );
		}
		wp_safe_redirect( add_query_arg( 'email_verified', $ok || oneam_email_verified( $uid ) ? '1' : '0', oneam_signup_url() ) . '#apply' );
		exit;
	}
);

// "인증 메일 다시 보내기" (1분에 한 번)
add_action(
	'init',
	function () {
		if ( empty( $_POST['oneam_resend_verify'] ) || ! is_user_logged_in() ) {
			return;
		}
		$uid  = get_current_user_id();
		$sent = 'wait';
		if ( isset( $_POST['_wpnonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) ), 'oneam_resend_verify' ) && ! oneam_email_verified( $uid ) && time() - (int) get_user_meta( $uid, 'oneam_verify_sent', true ) > MINUTE_IN_SECONDS ) {
			oneam_send_verify_email( $uid );
			$sent = '1';
		}
		wp_safe_redirect( add_query_arg( 'verify_sent', $sent, oneam_signup_url() ) . '#apply' );
		exit;
	}
);

/** 로그인 칸 이름: "Username or email" → "Email address" (이메일이 곧 아이디) */
add_filter(
	'gettext',
	function ( $t, $text, $domain ) {
		if ( is_admin() && ! wp_doing_ajax() ) {
			return $t;
		}
		$map = array(
			'Username or email address' => 'Email address',
			'Username or Email Address' => 'Email address',
			'Username or email'         => 'Email address',
		);
		return isset( $map[ $text ] ) && in_array( $domain, array( 'woocommerce', 'default' ), true ) ? $map[ $text ] : $t;
	},
	10,
	3
);

/** 이미 신청한 사람에게는 가입 페이지 안내 문장(“Apply for…”) 대신 상태 카드만 */
add_filter(
	'body_class',
	function ( $classes ) {
		if ( is_user_logged_in() && is_page() && get_queried_object_id() === oneam_signup_page_id() && ! current_user_can( 'edit_users' ) ) {
			$classes[] = 'oneam-applied';
		}
		return $classes;
	}
);

/** 가입 페이지 본문에 숏코드가 없으면 자동으로 폼을 붙임 */
add_filter(
	'the_content',
	function ( $content ) {
		if ( ! is_page() || ! in_the_loop() || has_shortcode( $content, 'oneam_wholesale_signup' ) || false !== strpos( $content, 'class="apply"' ) ) {
			return $content;
		}
		if ( get_the_ID() && get_the_ID() === oneam_signup_page_id() ) {
			$content .= oneam_wholesale_form();
		}
		return $content;
	},
	20
);

/** 내 계정 화면 상단에 승인 상태 표시 */
add_action(
	'woocommerce_account_content',
	function () {
		$status = oneam_wholesale_status( get_current_user_id() );
		if ( 'pending' === $status ) {
			echo '<div class="apply__note"><strong>Retailer application under review.</strong><p>We will email you once your account is approved.</p></div>';
		} elseif ( 'rejected' === $status ) {
			echo '<div class="apply__note is-bad"><strong>Your retailer application was not approved.</strong><p><a href="' . esc_url( oneam_signup_url() ) . '">See the reason</a></p></div>';
		} elseif ( 'info' === $status ) {
			echo '<div class="apply__note"><strong>We need a little more information to approve your account.</strong><p><a href="' . esc_url( oneam_signup_url() ) . '">See what we need and reply &rarr;</a></p></div>';
		}
	},
	5
);

/** 내 계정 주소: 위 제목 설명과 겹치는 문장은 빼고, 버튼은 "Edit" / "Add" 로 짧게 */
add_filter( 'woocommerce_my_account_my_address_description', '__return_empty_string' );
add_filter(
	'gettext_woocommerce',
	function ( $t, $text ) {
		if ( is_admin() && ! wp_doing_ajax() ) {
			return $t;
		}
		if ( 'Edit %s' === $text ) {
			return 'Edit';
		}
		if ( 'Add %s' === $text ) {
			return 'Add';
		}
		return $t;
	},
	10,
	2
);

/** 계정 정보 수정: 성(Last name)은 선택 — 이름 하나만 있어도 저장되게 */
add_filter(
	'woocommerce_save_account_details_required_fields',
	function ( $fields ) {
		unset( $fields['account_last_name'] );
		return $fields;
	}
);

/**
 * 계정 정보 수정: BN 이 비어 있어도 저장되게 (BN 은 결제할 때만 필수).
 * Woo 가 이름 · 비밀번호를 먼저 저장한 뒤 "BN is required" 오류를 남기면 성공 안내가 안 나오므로, 그 오류만 지웁니다.
 */
add_action(
	'woocommerce_save_account_details',
	function () {
		$keep = array();
		$drop = false;
		foreach ( wc_get_notices( 'error' ) as $n ) {
			$msg = is_array( $n ) ? ( $n['notice'] ?? '' ) : (string) $n;
			if ( false !== stripos( wp_strip_all_tags( $msg ), 'Business Number (BN) is required' ) ) {
				$drop = true;
				continue;
			}
			$keep[] = $n;
		}
		if ( $drop ) {
			$all          = wc_get_notices();
			$all['error'] = $keep;
			if ( ! $keep ) {
				unset( $all['error'] );
			}
			wc_set_notices( $all );
		}
	},
	20
);

/** 비밀번호 바꾸기: 가입 때처럼 8자 이상이면 되게 (아주 약한 비밀번호만 막음 — 저장 버튼이 이유 없이 잠기지 않게) */
add_filter(
	'woocommerce_min_password_strength',
	function () {
		return 1;
	}
);

/** 내 계정: 탭마다 내용 맨 위에 작은 제목 + 한 줄 설명 */
add_action(
	'woocommerce_account_content',
	function () {
		global $wp;
		$ep    = WC()->query->get_current_endpoint();
		$value = $ep && isset( $wp->query_vars[ $ep ] ) ? $wp->query_vars[ $ep ] : '';
		$heads = array(
			''             => array( 'Overview', 'Your retailer account at a glance.' ),
			'orders'       => array( 'Orders', 'Track your orders and download invoices.' ),
			'edit-address' => array( 'Addresses', 'These addresses are used by default at checkout.' ),
			'edit-account' => array( 'Account details', 'Your name, email, Business Number (BN) and password.' ),
		);
		if ( 'edit-address' === $ep && $value ) {
			$head = array( 'billing' === $value ? 'Billing address' : 'Shipping address', 'Update the address and save.' );
		} elseif ( isset( $heads[ $ep ] ) ) {
			$head = $heads[ $ep ];
		} else {
			$head = array( WC()->query->get_endpoint_title( $ep ), '' );
		}
		echo '<header class="acct__section-head"><h2 class="acct__title">' . esc_html( wp_strip_all_tags( $head[0] ) ) . '</h2>';
		if ( $head[1] ) {
			echo '<p class="acct__lead">' . esc_html( $head[1] ) . '</p>';
		}
		echo '</header>';
	},
	1
);

/* ---------------------------------------------------------------------------
 * 관리자: Users 화면
 * ------------------------------------------------------------------------- */

/**
 * 승인 / 거절 / 추가 정보 요청 (메일 발송 포함)
 * $message: 관리자가 고른 이유 · 직접 쓴 내용 — 메일과 고객 상태 화면에 그대로 보임
 */
function oneam_set_wholesale_status( $user_id, $status, $message = '', $send = true ) {
	$old     = oneam_wholesale_status( $user_id );
	$message = trim( (string) $message );
	update_user_meta( $user_id, 'oneam_wholesale_status', $status );
	if ( $old === $status && '' === $message ) {
		return;
	}
	if ( function_exists( 'oneam_review_log' ) ) {
		oneam_review_log( $user_id, 'staff', $status, $message );
	}
	$user = get_userdata( $user_id );
	if ( ! $user || ! $send ) {
		return;
	}
	$name = get_user_meta( $user_id, 'oneam_contact_name', true ) ?: $user->display_name;
	$note = '' !== $message ? "\n\n" . $message : '';
	if ( 'approved' === $status ) {
		oneam_mail(
			$user->user_email,
			'Your 1AM retail account is approved',
			"Hi {$name},\n\nGood news — your business has been verified and your 1AM retail account is approved. Log in to view wholesale pricing and place your first order:\n" . oneam_login_url() . $note . "\n\nAll products carry the correct excise stamp for their intended province of sale: choose the province where the products will be sold on the product page or at checkout. Your Business Number (BN) is required at checkout.\n\nOrders are subject to our Terms & Conditions, including Vaping Product Compliance:\n" . home_url( '/terms/#vape-compliance' ) . "\n\nWelcome aboard."
		);
	} elseif ( 'rejected' === $status ) {
		oneam_mail(
			$user->user_email,
			'Your 1AM retail account application',
			"Hi {$name},\n\nThank you for your interest in 1AM. Unfortunately we are unable to approve your retail account at this time." . ( '' !== $message ? "\n\nReason: " . $message : '' ) . "\n\n1AM products are sold only to verified, authorized vape retailers. If you believe this is a mistake or can provide more information, please reply to this email."
		);
	} elseif ( 'info' === $status ) {
		oneam_mail(
			$user->user_email,
			'We need a little more information for your 1AM application',
			"Hi {$name},\n\nThanks for applying for a 1AM retail account. To finish reviewing your application, we need the following:" . $note . "\n\nYou can reply to this email, or log in and answer here:\n" . oneam_signup_url() . "\n\nThank you."
		);
	}
}

/** 상태 배지 */
function oneam_status_badge( $status ) {
	$map = array(
		'pending'  => array( 'Pending', '#b26a00', '#fff4e0' ),
		'approved' => array( 'Approved', '#0a7a3d', '#e3f8ec' ),
		'rejected' => array( 'Rejected', '#b3261e', '#fde7e7' ),
		'info'     => array( 'Needs info', '#1e5bff', '#e8eeff' ),
	);
	if ( ! isset( $map[ $status ] ) ) {
		return '—';
	}
	return sprintf( '<span style="display:inline-block;padding:2px 10px;border-radius:999px;font-weight:600;color:%2$s;background:%3$s">%1$s</span>', esc_html( $map[ $status ][0] ), esc_attr( $map[ $status ][1] ), esc_attr( $map[ $status ][2] ) );
}

add_filter(
	'manage_users_columns',
	function ( $cols ) {
		$cols['oneam_wholesale'] = 'Wholesale';
		$cols['oneam_business']  = 'Business';
		return $cols;
	}
);
add_filter(
	'manage_users_custom_column',
	function ( $out, $col, $user_id ) {
		if ( 'oneam_wholesale' === $col ) {
			$mail = '0' === (string) get_user_meta( $user_id, 'oneam_email_verified', true ) ? '<br><small style="color:#b26a00">Email not confirmed</small>' : ( get_user_meta( $user_id, 'oneam_email_verified', true ) ? '<br><small style="color:#0a7a3d">Email confirmed</small>' : '' );
			$reply = 'info' === oneam_wholesale_status( $user_id ) && get_user_meta( $user_id, 'oneam_review_replied', true ) ? '<br><small style="color:#1e5bff;font-weight:600">Applicant replied</small>' : '';
			return oneam_status_badge( oneam_wholesale_status( $user_id ) ) . $mail . $reply;
		}
		if ( 'oneam_business' === $col ) {
			$b = get_user_meta( $user_id, 'oneam_business_name', true );
			$p = get_user_meta( $user_id, 'oneam_province', true );
			return $b ? esc_html( $b . ( $p ? ' · ' . $p : '' ) ) : '—';
		}
		return $out;
	},
	10,
	3
);

/** Approve / Reject 링크 */
add_filter(
	'user_row_actions',
	function ( $actions, $user ) {
		if ( ! current_user_can( 'edit_user', $user->ID ) || ! oneam_wholesale_status( $user->ID ) ) {
			return $actions;
		}
		$status = oneam_wholesale_status( $user->ID );
		$link   = function ( $to, $label ) use ( $user ) {
			$url = wp_nonce_url( admin_url( 'admin-post.php?action=oneam_wholesale&user=' . $user->ID . '&to=' . $to ), 'oneam_wholesale_' . $user->ID );
			return sprintf( '<a href="%s">%s</a>', esc_url( $url ), esc_html( $label ) );
		};
		if ( 'approved' !== $status ) {
			$actions['oneam_approve'] = $link( 'approved', 'Approve' );
		}
		// 거절 · 추가 정보 요청은 이유를 쓰도록 사용자 화면의 검토 칸으로
		$review = admin_url( 'user-edit.php?user_id=' . $user->ID . '#oneam-review' );
		if ( 'rejected' !== $status ) {
			$actions['oneam_reject'] = sprintf( '<a href="%s">Reject with reason</a>', esc_url( add_query_arg( 'oneam_to', 'rejected', $review ) ) );
		}
		if ( 'approved' !== $status ) {
			$actions['oneam_info'] = sprintf( '<a href="%s">Ask for info</a>', esc_url( add_query_arg( 'oneam_to', 'info', $review ) ) );
		}
		if ( get_user_meta( $user->ID, 'oneam_document', true ) ) {
			$actions['oneam_doc'] = sprintf( '<a href="%s" target="_blank">View document</a>', esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=oneam_doc&user=' . $user->ID ), 'oneam_doc_' . $user->ID ) ) );
		}
		return $actions;
	},
	10,
	2
);

add_action(
	'admin_post_oneam_wholesale',
	function () {
		$uid = isset( $_GET['user'] ) ? absint( $_GET['user'] ) : 0;
		$to  = isset( $_GET['to'] ) ? sanitize_key( wp_unslash( $_GET['to'] ) ) : '';
		check_admin_referer( 'oneam_wholesale_' . $uid );
		if ( ! current_user_can( 'edit_user', $uid ) || ! in_array( $to, array( 'approved', 'rejected', 'pending', 'info' ), true ) ) {
			wp_die( 'Not allowed.' );
		}
		oneam_set_wholesale_status( $uid, $to );
		wp_safe_redirect( add_query_arg( 'oneam_updated', $to, wp_get_referer() ? wp_get_referer() : admin_url( 'users.php' ) ) );
		exit;
	}
);

/** 서류 보기 (관리자만) */
add_action(
	'admin_post_oneam_doc',
	function () {
		$uid = isset( $_GET['user'] ) ? absint( $_GET['user'] ) : 0;
		check_admin_referer( 'oneam_doc_' . $uid );
		if ( ! current_user_can( 'edit_user', $uid ) ) {
			wp_die( 'Not allowed.' );
		}
		$name = basename( (string) get_user_meta( $uid, 'oneam_document', true ) );
		$path = oneam_docs_dir() . '/' . $name;
		if ( $name && ! is_file( $path ) ) {
			$path = oneam_docs_dir_public() . '/' . $name; // 예전 위치에 있는 서류
		}
		if ( ! $name || ! is_file( $path ) ) {
			wp_die( 'Document not found.' );
		}
		$type = wp_check_filetype( $path );
		nocache_headers();
		header( 'Content-Type: ' . ( $type['type'] ? $type['type'] : 'application/octet-stream' ) );
		header( 'Content-Disposition: inline; filename="' . $name . '"' );
		header( 'Content-Length: ' . filesize( $path ) );
		readfile( $path ); // phpcs:ignore WordPress.WP.AlternativeFunctions
		exit;
	}
);

/** "Pending wholesale (n)" 필터 */
add_filter(
	'views_users',
	function ( $views ) {
		$count = count( get_users( array( 'meta_key' => 'oneam_wholesale_status', 'meta_value' => array( 'pending', 'info' ), 'meta_compare' => 'IN', 'fields' => 'ID' ) ) ); // phpcs:ignore WordPress.DB.SlowDBQuery
		$cur   = isset( $_GET['oneam_wholesale'] ) && 'pending' === $_GET['oneam_wholesale']; // phpcs:ignore WordPress.Security.NonceVerification
		$views['oneam_pending'] = sprintf( '<a href="%s"%s>Pending wholesale <span class="count">(%d)</span></a>', esc_url( admin_url( 'users.php?oneam_wholesale=pending' ) ), $cur ? ' class="current" aria-current="page"' : '', $count );
		return $views;
	}
);
add_action(
	'pre_get_users',
	function ( $q ) {
		if ( is_admin() && isset( $_GET['oneam_wholesale'] ) && 'pending' === $_GET['oneam_wholesale'] ) { // phpcs:ignore WordPress.Security.NonceVerification
			$q->set( 'meta_key', 'oneam_wholesale_status' );
			$q->set( 'meta_value', array( 'pending', 'info' ) ); // 검토 중 + 추가 정보 요청
			$q->set( 'meta_compare', 'IN' );
		}
	}
);

/** 관리자 메뉴 Users 옆에 대기 건수 배지 */
add_action(
	'admin_menu',
	function () {
		global $menu;
		$count = count( get_users( array( 'meta_key' => 'oneam_wholesale_status', 'meta_value' => 'pending', 'fields' => 'ID' ) ) ) // phpcs:ignore WordPress.DB.SlowDBQuery
			+ count( get_users( array( 'meta_key' => 'oneam_review_replied', 'meta_compare' => 'EXISTS', 'fields' => 'ID' ) ) ); // 답장한 신청자 phpcs:ignore WordPress.DB.SlowDBQuery
		if ( ! $count ) {
			return;
		}
		foreach ( $menu as $i => $item ) {
			if ( 'users.php' === $item[2] ) {
				$menu[ $i ][0] .= ' <span class="awaiting-mod"><span class="pending-count">' . (int) $count . '</span></span>'; // phpcs:ignore WordPress.WP.GlobalVariablesOverride
			}
		}
	},
	99
);

/** 처리 완료 알림 */
add_action(
	'admin_notices',
	function () {
		if ( isset( $_GET['oneam_updated'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			$to = sanitize_key( wp_unslash( $_GET['oneam_updated'] ) ); // phpcs:ignore WordPress.Security.NonceVerification
			printf( '<div class="notice notice-success is-dismissible"><p>Wholesale status updated: <strong>%s</strong>. The applicant has been emailed.</p></div>', esc_html( ucfirst( $to ) ) );
		}
	}
);

/** 사용자 프로필 화면: 사업자 정보 · 서류 · 상태 */
function oneam_wholesale_profile( $user ) {
	if ( ! current_user_can( 'edit_users' ) ) {
		return;
	}
	$status = oneam_wholesale_status( $user->ID );
	?>
	<h2 id="oneam-review">1AM wholesale</h2>
	<table class="form-table" role="presentation">
		<tr><th>Review</th><td><?php oneam_review_panel( $user ); ?></td></tr>
		<?php foreach ( oneam_wholesale_fields() + oneam_wholesale_optional_fields() as $key => $label ) : ?>
			<tr><th><?php echo esc_html( $label ); ?></th><td><input type="text" class="regular-text" name="<?php echo esc_attr( $key ); ?>" value="<?php echo esc_attr( get_user_meta( $user->ID, $key, true ) ); ?>"></td></tr>
		<?php endforeach; ?>
		<?php do_action( 'oneam_wholesale_profile_rows', $user ); ?>
		<tr><th>Marketing emails</th><td><?php echo esc_html( get_user_meta( $user->ID, 'oneam_marketing_consent', true ) ? 'Opted in on ' . get_user_meta( $user->ID, 'oneam_marketing_consent', true ) : 'No consent — do not send marketing emails' ); ?></td></tr>
		<tr><th>Document</th><td>
			<?php if ( get_user_meta( $user->ID, 'oneam_document', true ) ) : ?>
				<a class="button" target="_blank" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=oneam_doc&user=' . $user->ID ), 'oneam_doc_' . $user->ID ) ); ?>">View uploaded document</a>
			<?php else : ?>
				—
			<?php endif; ?>
		</td></tr>
		<tr><th>Applied</th><td><?php echo esc_html( get_user_meta( $user->ID, 'oneam_applied_at', true ) ?: '—' ); ?></td></tr>
	</table>
	<?php
}
add_action( 'show_user_profile', 'oneam_wholesale_profile' );
add_action( 'edit_user_profile', 'oneam_wholesale_profile' );

function oneam_wholesale_profile_save( $user_id ) {
	if ( ! current_user_can( 'edit_users' ) ) {
		return;
	}
	// 프로필 저장은 WordPress 가 nonce 를 확인합니다 (update-user_{id}).
	// phpcs:disable WordPress.Security.NonceVerification
	foreach ( array_keys( oneam_wholesale_fields() + oneam_wholesale_optional_fields() ) as $key ) {
		if ( isset( $_POST[ $key ] ) ) {
			update_user_meta( $user_id, $key, sanitize_text_field( wp_unslash( $_POST[ $key ] ) ) );
		}
	}
	do_action( 'oneam_wholesale_profile_save', $user_id );
	if ( isset( $_POST['oneam_wholesale_status'] ) ) {
		$to   = sanitize_key( wp_unslash( $_POST['oneam_wholesale_status'] ) );
		$msg  = isset( $_POST['oneam_review_message'] ) ? sanitize_textarea_field( wp_unslash( $_POST['oneam_review_message'] ) ) : '';
		$send = ! empty( $_POST['oneam_review_send'] );
		if ( '' === $to ) {
			delete_user_meta( $user_id, 'oneam_wholesale_status' );
		} elseif ( in_array( $to, array( 'pending', 'info', 'approved', 'rejected' ), true ) ) {
			oneam_set_wholesale_status( $user_id, $to, $msg, $send );
		}
	}
	// phpcs:enable
}
add_action( 'personal_options_update', 'oneam_wholesale_profile_save' );
add_action( 'edit_user_profile_update', 'oneam_wholesale_profile_save' );

/* ---------------------------------------------------------------------------
 * 결제 전 필수 확인: Excise Stamp & Regulatory Compliance Confirmation
 * 블록 결제 화면(우커머스 기본) · 클래식 결제 화면 모두. 체크해야만 주문 가능.
 * 문구: 사용자 정의하기 > 1AM Settings > Checkout confirmation
 * ------------------------------------------------------------------------- */
function oneam_confirm_label() {
	return trim( oneam_opt( 'oneam_confirm_title' ) . ' — ' . oneam_opt( 'oneam_confirm_text' ), ' —' );
}

// 블록 결제 화면: 필수 체크박스 (주문 정보 영역, 약관 동의 · 결제 버튼 위)
add_action(
	'woocommerce_init',
	function () {
		if ( ! function_exists( 'woocommerce_register_additional_checkout_field' ) ) {
			return;
		}
		// 사업자번호(BN): 결제할 때 필수. 계정에 저장되어 다음 주문부터 자동으로 채워지고, 내 계정 > Account details 에서 수정 가능
		woocommerce_register_additional_checkout_field(
			array(
				'id'                => 'oneam/business-number',
				'label'             => 'Business Number (BN)',
				'location'          => 'contact',
				'type'              => 'text',
				'required'          => true,
				'attributes'        => array(
					'autocomplete' => 'off',
					'title'        => '9-digit Business Number, e.g. 123456789',
				),
				'sanitize_callback' => function ( $v ) {
					return strtoupper( preg_replace( '/[\s-]/', '', (string) $v ) );
				},
				'validate_callback' => function ( $v ) {
					if ( ! oneam_valid_bn( $v ) ) {
						return new WP_Error( 'oneam_bn', 'Please enter your 9-digit Business Number (BN), e.g. 123456789.' );
					}
				},
			)
		);
		woocommerce_register_additional_checkout_field(
			array(
				'id'            => 'oneam/excise-confirmation',
				'label'         => oneam_confirm_label(),
				'location'      => 'order',
				'type'          => 'checkbox',
				'required'      => true,
				'error_message' => oneam_opt( 'oneam_confirm_error' ),
			)
		);
	}
);

// 클래식 결제 화면
add_action(
	'woocommerce_review_order_before_submit',
	function () {
		?>
		<div class="form-row validate-required oneam-ack">
			<p class="oneam-ack__title"><strong><?php echo esc_html( oneam_opt( 'oneam_confirm_title' ) ); ?></strong></p>
			<label class="woocommerce-form__label woocommerce-form__label-for-checkbox checkbox">
				<input type="checkbox" class="woocommerce-form__input woocommerce-form__input-checkbox input-checkbox" name="oneam_compliance_ack" value="1">
				<span><?php echo esc_html( oneam_opt( 'oneam_confirm_text' ) ); ?></span>&nbsp;<abbr class="required" title="required">*</abbr>
			</label>
		</div>
		<?php
	},
	5
);
add_action(
	'woocommerce_checkout_process',
	function () {
		if ( empty( $_POST['oneam_compliance_ack'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			wc_add_notice( oneam_opt( 'oneam_confirm_error' ), 'error' );
		}
	}
);
add_action(
	'woocommerce_checkout_create_order',
	function ( $order ) {
		if ( ! empty( $_POST['oneam_compliance_ack'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			$order->update_meta_data( '_oneam_compliance_ack', current_time( 'mysql' ) );
		}
	}
);
// 클래식 결제 화면의 약관 동의 문구
add_filter(
	'woocommerce_get_terms_and_conditions_checkbox_text',
	function () {
		return oneam_opt( 'oneam_terms_text' );
	}
);

// 결제 때 넣은 BN 을 계정 정보(관리자 화면)에도 반영 · 약관 버전 기록
add_action(
	'woocommerce_store_api_checkout_order_processed',
	function ( $order ) {
		$uid = $order->get_customer_id();
		$bn  = $order->get_meta( '_wc_other/oneam/business-number' );
		if ( $uid && $bn ) {
			update_user_meta( $uid, 'oneam_business_number', $bn );
		}
		if ( function_exists( 'oneam_terms_version' ) ) {
			$order->update_meta_data( '_oneam_terms_version', oneam_terms_version() );
			$order->save();
		}
	}
);
// 관리자가 사용자 화면에서 BN 을 고치면 결제 칸에도 같은 값
add_action(
	'oneam_wholesale_profile_save',
	function ( $user_id ) {
		$bn = get_user_meta( $user_id, 'oneam_business_number', true );
		if ( $bn ) {
			update_user_meta( $user_id, '_wc_other/oneam/business-number', strtoupper( preg_replace( '/[\s-]/', '', $bn ) ) );
		}
	}
);
