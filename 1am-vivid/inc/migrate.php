<?php
/**
 * 1AM 컴플라이언스 업데이트 (테마를 올린 뒤 관리자 화면에 처음 들어갈 때 한 번 자동 실행)
 *
 * - 없는 정책 페이지 만들기 (Shipping Policy 404 해결)
 * - Terms · Return · Shipping · Privacy 페이지를 새 글로 교체 (예전 글은 Revisions 에 남음)
 * - FAQ 를 새 답변으로 교체 (카드 결제 문구 · 판매 지역 · 스탬프 · 배송 기간)
 * - 저장된 페이지 글 · Elementor 데이터 속 옛 문구 바꾸기 ("across Canada" 등)
 * - /privacy-2/ → /privacy-policy/ 주소 정리 (옛 주소는 301 로 이동)
 * 결과는 Pages 화면 위 알림으로 보여줌. 다시 실행: Pages 화면 알림의 "Run again".
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'ONEAM_COMPLIANCE_VERSION', '2026-10-compliance' );

/** 옛 문구 → 새 문구 (페이지 글 · Elementor 데이터) */
function oneam_migrate_replacements() {
	$regions = oneam_regions_phrase();
	$list    = array(
		'adult-only vape retailers across Canada'   => 'adult-only vape retailers in ' . $regions,
		'vape retailers across Canada'              => 'vape retailers in ' . $regions,
		'retailers across Canada'                   => 'retailers in ' . $regions,
		'across Canada'                             => 'in ' . $regions,
		'Retailers are responsible for selecting products with the appropriate excise stamps for their intended sales jurisdiction.' => "We ship only products stamped for your store's province.",
		'Choose products with the correct excise stamps for your province and confirm compliance at checkout.' => "Choose your store's province (we ship only products stamped for it) and confirm compliance at checkout.",
		'Choose products with the correct excise stamps for your retail sales jurisdiction.' => "We ship only products stamped for your store's province.",
		'typically arriving in 1–5 business days.'  => 'typically arriving ' . oneam_opt( 'oneam_transit_time' ) . ' after shipping.',
	);
	if ( ! oneam_opt( 'oneam_processor' ) ) {
		$list['We accept Interac e-Transfer, Visa, Mastercard, and American Express.'] = 'We accept Interac e-Transfer.';
		$list['We accept Interac e-Transfer, Visa, Mastercard and American Express.']  = 'We accept Interac e-Transfer.';
		$list['Interac e-Transfer · Visa · Mastercard · American Express']             = 'Interac e-Transfer';
		$list['Interac e-Transfer, Visa, Mastercard, and American Express']            = 'Interac e-Transfer';
		$list['Pay by Interac e-Transfer or card.']                                    = 'Pay by Interac e-Transfer.';
	}
	return $list;
}

/** 문자열 바꾸기 (Elementor JSON 의 – 같은 형태도 함께) */
function oneam_migrate_text( $text, $json = false ) {
	foreach ( oneam_migrate_replacements() as $old => $new ) {
		$text = str_replace( $old, $new, $text );
		if ( $json ) {
			$o = substr( wp_json_encode( $old ), 1, -1 );
			$n = substr( wp_json_encode( $new ), 1, -1 );
			$text = str_replace( $o, $n, $text );
		}
	}
	return $text;
}

/** 옛 주소 → 새 페이지 (301) */
function oneam_slug_redirects() {
	return (array) get_option( 'oneam_slug_redirects', array() );
}

function oneam_run_compliance_migration() {
	$report = array();
	update_option( 'oneam_policy_updated', wp_date( 'F j, Y' ) );
	if ( ! get_theme_mod( 'oneam_terms_effective' ) ) {
		set_theme_mod( 'oneam_terms_effective', wp_date( 'F j, Y' ) );
		$report[] = 'Terms effective date set to today (change it in 1AM Settings > Business & legal).';
	}

	// 1) Privacy 주소 정리: /privacy-2/ 등 → /privacy-policy/
	$privacy = get_page_by_path( 'privacy-policy' );
	$current = (int) get_option( 'wp_page_for_privacy_policy' );
	$old     = $current ? get_post( $current ) : null;
	if ( ! $old ) {
		$old = get_page_by_path( 'privacy-2' );
	}
	$redirects = oneam_slug_redirects();
	if ( $old && 'privacy-policy' !== $old->post_name ) {
		$redirects[ $old->post_name ] = 'privacy-policy';
		if ( ! $privacy ) {
			wp_update_post( array( 'ID' => $old->ID, 'post_name' => 'privacy-policy', 'post_status' => 'publish' ) );
			$report[] = 'Privacy Policy moved from /' . $old->post_name . '/ to /privacy-policy/ (the old address redirects).';
		} else {
			wp_update_post( array( 'ID' => $old->ID, 'post_status' => 'draft' ) );
			$report[] = 'Duplicate privacy page /' . $old->post_name . '/ set to draft; it now redirects to /privacy-policy/.';
		}
	}
	update_option( 'oneam_slug_redirects', $redirects, false );

	// 2) 없는 페이지 만들기 + 3) 정책 페이지 새 글
	foreach ( oneam_run_setup_pages( false, false ) as $line ) {
		if ( false !== strpos( $line, 'created' ) ) {
			$report[] = $line;
		}
	}
	foreach ( oneam_run_setup_pages( false, true ) as $line ) {
		$report[] = $line;
	}

	// 4) FAQ 새 답변
	$faq = get_page_by_path( 'faq' );
	if ( $faq ) {
		if ( 'builder' === get_post_meta( $faq->ID, '_elementor_edit_mode', true ) && function_exists( 'oneam_fill_page' ) ) {
			oneam_fill_page( $faq->ID, 'faq' );
		} else {
			$pages = oneam_setup_pages();
			wp_update_post( wp_slash( array( 'ID' => $faq->ID, 'post_content' => $pages['faq']['content'] ) ) );
		}
		$report[] = 'FAQ: answers updated (payment, provinces, excise stamps, shipping times).';
	}

	// 5) 저장된 글 · Elementor 데이터의 옛 문구
	$changed = array();
	$ids     = get_posts( array( 'post_type' => array( 'page', 'elementor_library' ), 'post_status' => array( 'publish', 'draft', 'private' ), 'numberposts' => -1, 'fields' => 'ids' ) );
	foreach ( $ids as $id ) {
		$post = get_post( $id );
		$new  = oneam_migrate_text( $post->post_content );
		if ( $new !== $post->post_content ) {
			wp_update_post( wp_slash( array( 'ID' => $id, 'post_content' => $new ) ) );
			$changed[ $id ] = $post->post_title;
		}
		$data = get_post_meta( $id, '_elementor_data', true );
		if ( is_string( $data ) && '' !== $data ) {
			$fixed = oneam_migrate_text( $data, true );
			if ( $fixed !== $data && null !== json_decode( $fixed ) ) {
				update_post_meta( $id, '_elementor_data', wp_slash( $fixed ) );
				delete_post_meta( $id, '_elementor_element_cache' );
				delete_post_meta( $id, '_elementor_css' );
				$changed[ $id ] = $post->post_title;
			}
		}
	}
	if ( $changed ) {
		$report[] = 'Old wording fixed on: ' . implode( ', ', array_unique( $changed ) ) . '.';
	}
	if ( class_exists( '\Elementor\Plugin' ) && isset( \Elementor\Plugin::$instance->files_manager ) ) {
		\Elementor\Plugin::$instance->files_manager->clear_cache();
	}

	// 6) 결제 화면 문구 · e-Transfer 안내
	if ( function_exists( 'oneam_setup_checkout_terms' ) ) {
		oneam_setup_checkout_terms();
	}
	if ( function_exists( 'oneam_setup_etransfer' ) ) {
		oneam_setup_etransfer();
	}
	if ( 'yes' === get_option( 'woocommerce_coming_soon' ) ) {
		$report[] = 'Note: WooCommerce "Coming soon" mode is on (WooCommerce > Settings > Site visibility). Product pages now use the 1AM layout; switch the store to "Live" when you launch.';
	}

	update_option( 'oneam_compliance_report', $report, false );
	update_option( 'oneam_compliance_done', ONEAM_COMPLIANCE_VERSION );
	return $report;
}

add_action(
	'admin_init',
	function () {
		if ( ONEAM_COMPLIANCE_VERSION === get_option( 'oneam_compliance_done' ) || ! current_user_can( 'manage_options' ) || wp_doing_ajax() ) {
			return;
		}
		oneam_run_compliance_migration();
	},
	30
);

add_action(
	'admin_post_oneam_compliance_rerun',
	function () {
		check_admin_referer( 'oneam_compliance_rerun' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Not allowed.' );
		}
		oneam_run_compliance_migration();
		delete_option( 'oneam_compliance_seen' );
		wp_safe_redirect( admin_url( 'edit.php?post_type=page' ) );
		exit;
	}
);

add_action(
	'admin_notices',
	function () {
		$report = get_option( 'oneam_compliance_report' );
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( ! $report || ! current_user_can( 'manage_options' ) || ! $screen || ! in_array( $screen->id, array( 'dashboard', 'edit-page' ), true ) ) {
			return;
		}
		$again = wp_nonce_url( admin_url( 'admin-post.php?action=oneam_compliance_rerun' ), 'oneam_compliance_rerun' );
		echo '<div class="notice notice-info"><p><strong>1AM compliance update applied.</strong> Previous page text is kept in each page’s Revisions.</p><ul style="list-style:disc;margin-left:20px">';
		foreach ( (array) $report as $line ) {
			echo '<li>' . esc_html( $line ) . '</li>';
		}
		echo '</ul><p>Values still to confirm with the owner (address, phone, GST number, dispatch time, payment and claim deadlines, Privacy Officer) are in <a href="' . esc_url( admin_url( 'customize.php?autofocus[section]=oneam_business' ) ) . '">Customize › 1AM Settings › Business &amp; legal</a>. After changing them, <a href="' . esc_url( $again ) . '">run the update again</a> to refresh the policy pages.</p></div>';
	}
);

// 옛 주소 → 새 주소 (예: /privacy-2/ → /privacy-policy/)
add_action(
	'template_redirect',
	function () {
		$map = oneam_slug_redirects();
		if ( ! $map ) {
			return;
		}
		$path = trim( (string) wp_parse_url( isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : '', PHP_URL_PATH ), '/' ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		$home = trim( (string) wp_parse_url( home_url(), PHP_URL_PATH ), '/' );
		if ( $home && 0 === strpos( $path, $home ) ) {
			$path = trim( substr( $path, strlen( $home ) ), '/' );
		}
		if ( isset( $map[ $path ] ) ) {
			$to = get_page_by_path( $map[ $path ] );
			if ( $to && 'publish' === $to->post_status ) {
				wp_safe_redirect( get_permalink( $to ), 301 );
				exit;
			}
		}
	},
	1
);
