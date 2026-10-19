<?php
/**
 * 모든 1AM 페이지를 Elementor 로 편집할 수 있게
 * - 페이지를 Elementor 로 처음(빈 상태로) 열면 그 페이지의 1AM 섹션과 글이 미리 채워져 열림
 * - Pages 화면의 "Build all pages in Elementor" 버튼: 1AM 페이지를 한 번에 만들고 채움
 *   (이미 Elementor 로 내용을 만든 페이지는 건드리지 않음)
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function oneam_el_rows( $items ) {
	return array_map(
		function ( $r ) {
			return array_merge( array( '_id' => oneam_el_id() ), $r );
		},
		$items
	);
}

/** 기존 글(블록) → HTML. 비어 있으면 1AM 기본 글 */
function oneam_el_page_html( $slug ) {
	$page = get_page_by_path( $slug );
	$raw  = $page ? $page->post_content : '';
	if ( '' === trim( wp_strip_all_tags( $raw ) ) ) {
		$defs = oneam_setup_pages();
		$raw  = isset( $defs[ $slug ] ) ? $defs[ $slug ]['content'] : '';
	}
	return function_exists( 'do_blocks' ) ? do_blocks( $raw ) : $raw;
}

/** 페이지별 구성: slug => title, hide_title(컬러 제목 숨김), sections */
function oneam_el_page_defs() {
	$faq    = array_map( function ( $q ) { return array( 'q' => $q[0], 'a' => $q[1] ); }, oneam_faq_items() );
	$signup = oneam_signup_page_id() ? get_post_field( 'post_name', oneam_signup_page_id() ) : 'wholesale-signup';
	return array(
		'about-us'      => array( 'About', true, array( array( 'story' ), array( 'make' ), array( 'benefits' ), array( 'work' ) ) ),
		'slim-hybrid'   => array(
			'Slim HYBRID',
			true,
			array(
				array( 'hero', array( 'line' => 'slim-hybrid', 'title' => "Slim\nHYBRID." ) ),
				array( 'pinfo', array( 'slug' => 'slim-hybrid' ) ),
				array( 'flavors', array( 'line' => 'slim-hybrid', 'title' => 'Slim HYBRID' ) ),
				array( 'cta' ),
			),
		),
		'how-to-order'  => array( 'How to Order', false, array( array( 'steps' ), array( 'orderinfo' ), array( 'faq' ), array( 'cta' ) ) ),
		'faq'           => array( 'FAQ', false, array( array( 'faq', array( 'items' => oneam_el_rows( $faq ) ) ), array( 'work' ) ) ),
		'terms'         => array( 'Terms & Conditions', false, array( array( 'core:text-editor', array( 'editor' => oneam_el_page_html( 'terms' ) ) ) ) ),
		'return-policy' => array( 'Return Policy', false, array( array( 'core:text-editor', array( 'editor' => oneam_el_page_html( 'return-policy' ) ) ) ) ),
		$signup         => array(
			'Wholesale Sign up',
			false,
			array(
				array( 'core:text-editor', array( 'editor' => '<p>Apply for a 1AM retail account. We review every application within 1–2 business days and email you once your account is approved.</p>' ) ),
				array( 'signup' ),
			),
		),
	);
}

/** 한 페이지 채우기 */
function oneam_fill_page( $id, $slug ) {
	$defs = oneam_el_page_defs();
	if ( ! isset( $defs[ $slug ] ) ) {
		return false;
	}
	list( , $hide, $sections ) = $defs[ $slug ];
	$data = array();
	foreach ( $sections as $sec ) {
		$data[] = oneam_el_section( $sec[0], isset( $sec[1] ) ? $sec[1] : array() );
	}
	update_post_meta( $id, '_elementor_edit_mode', 'builder' );
	update_post_meta( $id, '_elementor_template_type', 'wp-page' );
	update_post_meta( $id, '_wp_page_template', 'default' );
	if ( defined( 'ELEMENTOR_VERSION' ) ) {
		update_post_meta( $id, '_elementor_version', ELEMENTOR_VERSION );
	}
	$ps = get_post_meta( $id, '_elementor_page_settings', true );
	$ps = is_array( $ps ) ? $ps : array();
	if ( $hide ) {
		$ps['hide_title'] = 'yes';
	}
	update_post_meta( $id, '_elementor_page_settings', $ps );
	update_post_meta( $id, '_elementor_data', wp_slash( wp_json_encode( $data ) ) );
	delete_post_meta( $id, '_elementor_css' );
	return true;
}

/** 빈 1AM 페이지를 Elementor 로 열면 섹션이 채워진 채로 열림 */
add_action(
	'admin_action_elementor',
	function () {
		$id = isset( $_GET['post'] ) ? absint( $_GET['post'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification
		if ( ! $id || ! current_user_can( 'edit_post', $id ) || 'page' !== get_post_type( $id ) || ! oneam_el_is_empty( $id ) || $id === oneam_home_page_id() ) {
			return;
		}
		oneam_fill_page( $id, get_post_field( 'post_name', $id ) );
	},
	1
);

/** 버튼: 모든 1AM 페이지를 Elementor 로 */
add_action(
	'admin_post_oneam_build_pages',
	function () {
		check_admin_referer( 'oneam_build_pages' );
		if ( ! current_user_can( 'publish_pages' ) || ! did_action( 'elementor/loaded' ) ) {
			wp_die( 'Not allowed.' );
		}
		$report = array();
		$home   = oneam_home_page_id( true );
		if ( oneam_el_is_empty( $home ) || ! oneam_is_builder_page( $home ) ) {
			oneam_fill_home( $home );
			$report[] = 'Home: built';
		} else {
			$report[] = 'Home: already built in Elementor (left as is)';
		}
		foreach ( oneam_el_page_defs() as $slug => $def ) {
			$page = get_page_by_path( $slug );
			$id   = $page ? $page->ID : wp_insert_post( array( 'post_type' => 'page', 'post_status' => 'publish', 'post_title' => $def[0], 'post_name' => $slug ) );
			if ( 'builder' === get_post_meta( $id, '_elementor_edit_mode', true ) && ! oneam_el_is_empty( $id ) ) {
				$report[] = $def[0] . ': already built in Elementor (left as is)';
				continue;
			}
			oneam_fill_page( $id, $slug );
			$report[] = $def[0] . ( $page ? ': built' : ': created and built' );
		}
		if ( class_exists( '\Elementor\Plugin' ) && isset( \Elementor\Plugin::$instance->files_manager ) ) {
			\Elementor\Plugin::$instance->files_manager->clear_cache();
		}
		set_transient( 'oneam_setup_report', $report, 120 );
		wp_safe_redirect( admin_url( 'edit.php?post_type=page' ) );
		exit;
	}
);

add_action(
	'admin_notices',
	function () {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( ! $screen || 'edit-page' !== $screen->id || ! did_action( 'elementor/loaded' ) || ! current_user_can( 'publish_pages' ) ) {
			return;
		}
		printf(
			'<div class="notice notice-info"><p><strong>1AM Elementor:</strong> Make every 1AM page editable in Elementor — Home, About, Slim HYBRID, How to Order, FAQ, Terms, Return Policy and Wholesale Sign up — with their sections and text already in place. Pages you already built in Elementor are not touched. <a class="button button-primary" href="%s">Build all pages in Elementor</a></p></div>',
			esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=oneam_build_pages' ), 'oneam_build_pages' ) )
		);
	}
);
