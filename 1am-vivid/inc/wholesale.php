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
		'oneam_business_number' => 'Business number (BN)',
		'oneam_store_address'   => 'Store address',
		'oneam_city'            => 'City',
		'oneam_province'        => 'Province',
		'oneam_postcode'        => 'Postal code',
	);
}

function oneam_wholesale_status( $user_id ) {
	$s = get_user_meta( $user_id, 'oneam_wholesale_status', true );
	return $s ? $s : '';
}

/* ---------------------------------------------------------------------------
 * 서류 보관 폴더 (웹에서 직접 열 수 없게)
 * ------------------------------------------------------------------------- */
function oneam_docs_dir() {
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
			wp_safe_redirect( add_query_arg( 'apply_error', $code, remove_query_arg( array( 'apply_error', 'applied' ), $back ) ) . '#apply' );
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
		if ( ! array_key_exists( $data['oneam_province'], oneam_provinces() ) ) {
			$fail( 'missing' );
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

		// 서류 (PDF / JPG / PNG, 10MB 이하)
		if ( empty( $_FILES['oneam_document']['name'] ) || UPLOAD_ERR_OK !== (int) $_FILES['oneam_document']['error'] ) {
			$fail( 'file' );
		}
		$file  = $_FILES['oneam_document']; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		$types = array( 'pdf' => 'application/pdf', 'jpg|jpeg' => 'image/jpeg', 'png' => 'image/png' );
		$check = wp_check_filetype_and_ext( $file['tmp_name'], $file['name'], $types );
		if ( empty( $check['ext'] ) || (int) $file['size'] > 10 * MB_IN_BYTES ) {
			$fail( 'file' );
		}

		// 계정 생성 (WooCommerce 고객, 승인 대기)
		$login   = sanitize_user( current( explode( '@', $email ) ) . '-' . wp_generate_password( 4, false ), true );
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
		update_user_meta( $user_id, 'oneam_applied_at', current_time( 'mysql' ) );
		// WooCommerce 청구지 정보도 미리 채워둠
		update_user_meta( $user_id, 'billing_company', $data['oneam_business_name'] );
		update_user_meta( $user_id, 'billing_phone', $data['oneam_phone'] );
		update_user_meta( $user_id, 'billing_address_1', $data['oneam_store_address'] );
		update_user_meta( $user_id, 'billing_city', $data['oneam_city'] );
		update_user_meta( $user_id, 'billing_state', $data['oneam_province'] );
		update_user_meta( $user_id, 'billing_postcode', $data['oneam_postcode'] );
		update_user_meta( $user_id, 'billing_country', 'CA' );
		update_user_meta( $user_id, 'billing_email', $email );

		$name = 'user' . $user_id . '-' . wp_generate_password( 16, false ) . '.' . $check['ext'];
		$dest = oneam_docs_dir() . '/' . $name;
		if ( move_uploaded_file( $file['tmp_name'], $dest ) ) {
			update_user_meta( $user_id, 'oneam_document', $name );
		}

		// 알림 메일
		$admin_url = admin_url( 'users.php?oneam_wholesale=pending' );
		wp_mail(
			get_option( 'admin_email' ),
			'[1AM] New wholesale application: ' . $data['oneam_business_name'],
			"A new wholesale application is waiting for review.\n\nBusiness: {$data['oneam_business_name']}\nContact: {$data['oneam_contact_name']}\nEmail: {$email}\nPhone: {$data['oneam_phone']}\nProvince: {$data['oneam_province']}\n\nReview it here: {$admin_url}"
		);
		wp_mail(
			$email,
			'We received your 1AM wholesale application',
			"Hi {$data['oneam_contact_name']},\n\nThanks for applying for a 1AM wholesale account. We will review your documents and email you once your account is approved.\n\n1AM"
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
		if ( 'approved' === $status || 'approved' === oneam_member_state() ) {
			printf( '<div class="apply__note is-ok"><strong>Your account is approved.</strong><p>You can order at wholesale prices.</p><a class="btn btn--solid" href="%s"><span>Shop wholesale &rarr;</span></a></div>', esc_url( oneam_shop_url() ) );
		} elseif ( 'rejected' === $status ) {
			echo '<div class="apply__note is-bad"><strong>Your application was not approved.</strong><p>Please contact us if you think this is a mistake.</p></div>';
		} else {
			echo '<div class="apply__note"><strong>Application received — under review.</strong><p>We review applications within 1–2 business days and will email you once your account is approved.</p></div>';
		}
		echo '</div>';
		return ob_get_clean();
	}

	$errors = array(
		'expired'  => 'The form expired. Please try again.',
		'missing'  => 'Please fill in every field.',
		'email'    => 'Please enter a valid email address.',
		'exists'   => 'An account with this email already exists. Please log in instead.',
		'password' => 'Your password must be at least 8 characters.',
		'agree'    => 'Please confirm that you are a licensed retailer and 19 or older.',
		'file'     => 'Please upload your business document as a PDF, JPG or PNG under 10 MB.',
		'invalid'  => 'Something went wrong. Please try again.',
	);
	$err = isset( $_GET['apply_error'] ) ? sanitize_key( wp_unslash( $_GET['apply_error'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
	if ( $err && isset( $errors[ $err ] ) ) {
		printf( '<div class="apply__note is-bad" role="alert">%s</div>', esc_html( $errors[ $err ] ) );
	}
	?>
	<form class="apply__form" method="post" enctype="multipart/form-data">
		<?php wp_nonce_field( 'oneam_apply', 'oneam_apply_nonce' ); ?>
		<input type="hidden" name="oneam_wholesale_apply" value="1">
		<p class="apply__hp" aria-hidden="true"><label>Website <input type="text" name="oneam_website" tabindex="-1" autocomplete="off"></label></p>

		<fieldset>
			<legend>Business</legend>
			<p><label for="oneam_business_name">Business name</label><input id="oneam_business_name" name="oneam_business_name" required autocomplete="organization"></p>
			<p><label for="oneam_business_number">Business number (BN)</label><input id="oneam_business_number" name="oneam_business_number" required></p>
			<p class="is-wide"><label for="oneam_store_address">Store address</label><input id="oneam_store_address" name="oneam_store_address" required autocomplete="street-address"></p>
			<p><label for="oneam_city">City</label><input id="oneam_city" name="oneam_city" required autocomplete="address-level2"></p>
			<p><label for="oneam_province">Province</label>
				<select id="oneam_province" name="oneam_province" required>
					<option value="">Choose…</option>
					<?php foreach ( oneam_provinces() as $code => $label ) : ?>
						<option value="<?php echo esc_attr( $code ); ?>"><?php echo esc_html( $label ); ?></option>
					<?php endforeach; ?>
				</select></p>
			<p><label for="oneam_postcode">Postal code</label><input id="oneam_postcode" name="oneam_postcode" required autocomplete="postal-code"></p>
		</fieldset>

		<fieldset>
			<legend>Contact &amp; login</legend>
			<p><label for="oneam_contact_name">Contact name</label><input id="oneam_contact_name" name="oneam_contact_name" required autocomplete="name"></p>
			<p><label for="oneam_phone">Phone</label><input id="oneam_phone" name="oneam_phone" type="tel" required autocomplete="tel"></p>
			<p><label for="oneam_email">Email</label><input id="oneam_email" name="oneam_email" type="email" required autocomplete="email"></p>
			<p><label for="oneam_password">Password (8+ characters)</label><input id="oneam_password" name="oneam_password" type="password" minlength="8" required autocomplete="new-password"></p>
		</fieldset>

		<fieldset>
			<legend>Documents</legend>
			<p class="is-wide"><label for="oneam_document">Business licence or registration (PDF, JPG or PNG, max 10 MB)</label><input id="oneam_document" name="oneam_document" type="file" accept=".pdf,.jpg,.jpeg,.png" required></p>
			<p class="is-wide apply__check"><label><input type="checkbox" name="oneam_agree" value="1" required> I confirm this is a licensed retail business in Canada and I am 19 or older.</label></p>
		</fieldset>

		<button class="btn btn--solid btn--xl" type="submit"<?php disabled( $preview ); ?>><span>Submit application &rarr;</span></button>
		<p class="apply__login">Already applied? <a href="<?php echo esc_url( oneam_login_url() ); ?>">Log in</a></p>
	</form>
	<?php
	echo '</div>';
	return ob_get_clean();
}
add_shortcode( 'oneam_wholesale_signup', 'oneam_wholesale_form' );

/** 가입 페이지 본문에 숏코드가 없으면 자동으로 폼을 붙임 */
add_filter(
	'the_content',
	function ( $content ) {
		if ( ! is_page() || ! in_the_loop() || has_shortcode( $content, 'oneam_wholesale_signup' ) ) {
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
			echo '<div class="apply__note"><strong>Wholesale application under review.</strong><p>We will email you once your account is approved.</p></div>';
		} elseif ( 'rejected' === $status ) {
			echo '<div class="apply__note is-bad"><strong>Your wholesale application was not approved.</strong></div>';
		}
	},
	5
);

/* ---------------------------------------------------------------------------
 * 관리자: Users 화면
 * ------------------------------------------------------------------------- */

/** 승인 / 거절 (메일 발송 포함) */
function oneam_set_wholesale_status( $user_id, $status ) {
	$old = oneam_wholesale_status( $user_id );
	update_user_meta( $user_id, 'oneam_wholesale_status', $status );
	if ( $old === $status ) {
		return;
	}
	$user = get_userdata( $user_id );
	if ( ! $user ) {
		return;
	}
	$name = get_user_meta( $user_id, 'oneam_contact_name', true ) ?: $user->display_name;
	if ( 'approved' === $status ) {
		wp_mail(
			$user->user_email,
			'Your 1AM wholesale account is approved',
			"Hi {$name},\n\nGood news — your 1AM wholesale account is approved. Log in to see wholesale pricing and place your first order:\n" . oneam_login_url() . "\n\n1AM"
		);
	} elseif ( 'rejected' === $status ) {
		wp_mail(
			$user->user_email,
			'Your 1AM wholesale application',
			"Hi {$name},\n\nThank you for applying. Unfortunately we can't approve your wholesale account at this time. Reply to this email if you have questions.\n\n1AM"
		);
	}
}

/** 상태 배지 */
function oneam_status_badge( $status ) {
	$map = array(
		'pending'  => array( 'Pending', '#b26a00', '#fff4e0' ),
		'approved' => array( 'Approved', '#0a7a3d', '#e3f8ec' ),
		'rejected' => array( 'Rejected', '#b3261e', '#fde7e7' ),
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
			return oneam_status_badge( oneam_wholesale_status( $user_id ) );
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
		if ( 'rejected' !== $status ) {
			$actions['oneam_reject'] = $link( 'rejected', 'Reject' );
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
		if ( ! current_user_can( 'edit_user', $uid ) || ! in_array( $to, array( 'approved', 'rejected', 'pending' ), true ) ) {
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
		$count = count( get_users( array( 'meta_key' => 'oneam_wholesale_status', 'meta_value' => 'pending', 'fields' => 'ID' ) ) ); // phpcs:ignore WordPress.DB.SlowDBQuery
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
			$q->set( 'meta_value', 'pending' );
		}
	}
);

/** 관리자 메뉴 Users 옆에 대기 건수 배지 */
add_action(
	'admin_menu',
	function () {
		global $menu;
		$count = count( get_users( array( 'meta_key' => 'oneam_wholesale_status', 'meta_value' => 'pending', 'fields' => 'ID' ) ) ); // phpcs:ignore WordPress.DB.SlowDBQuery
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
	<h2>1AM wholesale</h2>
	<table class="form-table" role="presentation">
		<tr><th>Status</th><td>
			<select name="oneam_wholesale_status">
				<option value="" <?php selected( $status, '' ); ?>>Not a wholesale applicant</option>
				<option value="pending" <?php selected( $status, 'pending' ); ?>>Pending</option>
				<option value="approved" <?php selected( $status, 'approved' ); ?>>Approved</option>
				<option value="rejected" <?php selected( $status, 'rejected' ); ?>>Rejected</option>
			</select>
			<p class="description">Changing to Approved or Rejected emails the applicant.</p>
		</td></tr>
		<?php foreach ( oneam_wholesale_fields() as $key => $label ) : ?>
			<tr><th><?php echo esc_html( $label ); ?></th><td><input type="text" class="regular-text" name="<?php echo esc_attr( $key ); ?>" value="<?php echo esc_attr( get_user_meta( $user->ID, $key, true ) ); ?>"></td></tr>
		<?php endforeach; ?>
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
	foreach ( array_keys( oneam_wholesale_fields() ) as $key ) {
		if ( isset( $_POST[ $key ] ) ) {
			update_user_meta( $user_id, $key, sanitize_text_field( wp_unslash( $_POST[ $key ] ) ) );
		}
	}
	if ( isset( $_POST['oneam_wholesale_status'] ) ) {
		$to = sanitize_key( wp_unslash( $_POST['oneam_wholesale_status'] ) );
		if ( '' === $to ) {
			delete_user_meta( $user_id, 'oneam_wholesale_status' );
		} elseif ( in_array( $to, array( 'pending', 'approved', 'rejected' ), true ) ) {
			oneam_set_wholesale_status( $user_id, $to );
		}
	}
	// phpcs:enable
}
add_action( 'personal_options_update', 'oneam_wholesale_profile_save' );
add_action( 'edit_user_profile_update', 'oneam_wholesale_profile_save' );
