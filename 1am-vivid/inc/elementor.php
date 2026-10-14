<?php
/**
 * Elementor 연동
 *
 * - Elementor 편집기 왼쪽 위젯 목록에 "1AM" 카테고리: 영상 인트로, 맛 라인업, 상품 라인업, 디바이스,
 *   도매 가입 유도, 장점 카드, 구매 절차, FAQ, 맛 목록, 맛 슬라이더, 도매 가입 폼
 *   → 끌어다 놓고, 순서 바꾸고, 지우고, 오른쪽 패널에서 글자·이미지·영상 수정
 * - 상품·맛은 WooCommerce 에서 자동으로 읽어옴 (위젯에서 따로 입력하지 않음)
 * - 홈페이지를 Elementor 로 만들면 그 내용이, 아니면 테마 기본 홈 구성이 나옴
 * - 관리자 화면 버튼 한 번으로 지금의 홈 구성을 Elementor 페이지로 만들어 줌
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** 이 페이지를 Elementor 로 만들었는지 */
function oneam_is_builder_page( $post_id = 0 ) {
	$post_id = $post_id ? $post_id : get_the_ID();
	return $post_id && 'builder' === get_post_meta( $post_id, '_elementor_edit_mode', true ) && did_action( 'elementor/loaded' );
}

/** Elementor 편집 화면(미리보기) 안인지 */
function oneam_is_builder_preview() {
	return isset( $_GET['elementor-preview'] ) // phpcs:ignore WordPress.Security.NonceVerification
		|| ( did_action( 'elementor/loaded' ) && class_exists( '\Elementor\Plugin' ) && isset( \Elementor\Plugin::$instance->preview ) && \Elementor\Plugin::$instance->preview->is_preview_mode() );
}

/** 편집 화면에서는 애니메이션 대신 완성된 모습을 그대로 보여줌 */
add_filter(
	'body_class',
	function ( $classes ) {
		if ( oneam_is_builder_preview() ) {
			$classes[] = 'is-builder';
		}
		return $classes;
	}
);

/* ---------------------------------------------------------------------------
 * 위젯 목록
 * ------------------------------------------------------------------------- */
function oneam_elementor_widgets() {
	$italic = 'Wrap a word in *stars* for the serif italic accent.';
	return array(
		'film'     => array(
			'title'    => 'Video intro',
			'icon'     => 'eicon-youtube',
			'part'     => 'film',
			'note'     => 'Full-screen brand video with a one-line caption and the wholesale button. Leave the video empty to use the one set in Customize › 1AM Settings.',
			'controls' => array(
				'oneam_intro_title'   => array( 'label' => 'Headline', 'type' => 'text', 'default' => oneam_opt( 'oneam_intro_title' ), 'description' => $italic ),
				'oneam_intro_text'    => array( 'label' => 'Caption', 'type' => 'text', 'default' => oneam_opt( 'oneam_intro_text' ) ),
				'oneam_video_mp4'     => array( 'label' => 'Video (MP4)', 'type' => 'video' ),
				'oneam_video_youtube' => array( 'label' => 'or YouTube link', 'type' => 'text' ),
				'oneam_video_poster'  => array( 'label' => 'Poster image', 'type' => 'image' ),
			),
		),
		'about'    => array(
			'title'    => 'Flavour line-up + About',
			'icon'     => 'eicon-slider-push',
			'part'     => 'about',
			'note'     => 'The devices come from your WooCommerce product variations (flavours) automatically.',
			'controls' => array(
				'eyebrow'           => array( 'label' => 'Small label', 'type' => 'text', 'default' => 'About 1AM' ),
				'oneam_about_title' => array( 'label' => 'Title', 'type' => 'textarea', 'default' => oneam_opt( 'oneam_about_title' ), 'description' => $italic ),
				'oneam_about_text'  => array( 'label' => 'Text', 'type' => 'textarea', 'default' => oneam_opt( 'oneam_about_text' ) ),
				'button'            => array( 'label' => 'Button text', 'type' => 'text', 'default' => 'About' ),
				'oneam_about_url'   => array( 'label' => 'Button link', 'type' => 'url', 'default' => '/about-us/' ),
			),
		),
		'lineup'   => array(
			'title'    => 'Product line-up (horizontal scroll)',
			'icon'     => 'eicon-posts-carousel',
			'part'     => 'lineup',
			'note'     => 'Shows your WooCommerce products. Edit names, images, label and colours in Products.',
			'controls' => array(
				'eyebrow' => array( 'label' => 'Small label', 'type' => 'text', 'default' => 'Product lineup' ),
			),
		),
		'device'   => array(
			'title'    => 'Device + specs',
			'icon'     => 'eicon-device-mobile',
			'part'     => 'device',
			'controls' => array(
				'oneam_device_title' => array( 'label' => 'Title', 'type' => 'textarea', 'default' => oneam_opt( 'oneam_device_title' ), 'description' => 'One line per row. ' . $italic ),
				'callouts'           => array(
					'label'   => 'Callouts',
					'type'    => 'repeater',
					'fields'  => array(
						'title' => array( 'label' => 'Title', 'type' => 'text' ),
						'text'  => array( 'label' => 'Text', 'type' => 'textarea' ),
					),
					'default' => array(
						array( 'title' => 'Crystal shell', 'text' => 'A clear crystal body shows the colour core inside.' ),
						array( 'title' => 'Gradient core', 'text' => 'Each flavour has its own gradient.' ),
						array( 'title' => 'Slim HYBRID', 'text' => 'A slim body that sits easily in the hand.' ),
						array( 'title' => 'Soft mouthpiece', 'text' => 'A smooth, rounded mouthpiece.' ),
					),
				),
				'specs'              => array(
					'label'   => 'Numbers',
					'type'    => 'repeater',
					'fields'  => array(
						'num'   => array( 'label' => 'Number', 'type' => 'text' ),
						'label' => array( 'label' => 'Label', 'type' => 'text' ),
					),
					'default' => array(
						array( 'num' => oneam_opt( 'oneam_spec_1_num' ), 'label' => oneam_opt( 'oneam_spec_1_label' ) ),
						array( 'num' => oneam_opt( 'oneam_spec_2_num' ), 'label' => oneam_opt( 'oneam_spec_2_label' ) ),
						array( 'num' => oneam_opt( 'oneam_spec_3_num' ), 'label' => oneam_opt( 'oneam_spec_3_label' ) ),
					),
				),
			),
		),
		'cta'      => array(
			'title'    => 'Wholesale call to action',
			'icon'     => 'eicon-call-to-action',
			'part'     => 'cta',
			'note'     => 'The button changes by visitor: Apply / Application under review / Shop wholesale.',
			'controls' => array(
				'oneam_cta_title' => array( 'label' => 'Title', 'type' => 'text', 'default' => oneam_opt( 'oneam_cta_title' ), 'description' => $italic ),
				'oneam_cta_text'  => array( 'label' => 'Text', 'type' => 'textarea', 'default' => oneam_opt( 'oneam_cta_text' ) ),
				'note'            => array( 'label' => 'Small print', 'type' => 'text', 'default' => 'Retailers only · ' . (int) oneam_opt( 'oneam_min_age' ) . '+ · Canada' ),
			),
		),
		'benefits' => array(
			'title'    => 'Benefit cards',
			'icon'     => 'eicon-gallery-grid',
			'part'     => 'benefits',
			'controls' => array(
				'eyebrow' => array( 'label' => 'Small label', 'type' => 'text', 'default' => 'For retailers' ),
				'title'   => array( 'label' => 'Title', 'type' => 'textarea', 'default' => "Why stock\n*1AM*?", 'description' => 'One line per row. ' . $italic ),
				'items'   => array(
					'label'   => 'Cards',
					'type'    => 'repeater',
					'fields'  => array(
						'title' => array( 'label' => 'Title', 'type' => 'text' ),
						'text'  => array( 'label' => 'Text', 'type' => 'textarea' ),
						'c1'    => array( 'label' => 'Colour 1', 'type' => 'color' ),
						'c2'    => array( 'label' => 'Colour 2', 'type' => 'color' ),
					),
					'default' => array(
						array( 'title' => 'Wholesale pricing', 'text' => 'Trade pricing for approved accounts, visible as soon as you log in.', 'c1' => '#FF2E4D', 'c2' => '#FFB321' ),
						array( 'title' => 'Approved retailers only', 'text' => 'We only work with stores whose business documents we have verified.', 'c1' => '#8A3FFC', 'c2' => '#1E5BFF' ),
						array( 'title' => 'Ships where it\'s allowed', 'text' => 'Orders ship only to provinces where our products can be sold.', 'c1' => '#00C2A8', 'c2' => '#16C75A' ),
						array( 'title' => 'Clear product info', 'text' => 'Specs, flavours and product details in one place.', 'c1' => '#FFD60A', 'c2' => '#FF8A4D' ),
					),
				),
			),
		),
		'steps'    => array(
			'title'    => 'How to order steps',
			'icon'     => 'eicon-number-field',
			'part'     => 'steps',
			'controls' => array(
				'eyebrow' => array( 'label' => 'Small label', 'type' => 'text', 'default' => 'How to order' ),
				'title'   => array( 'label' => 'Title', 'type' => 'textarea', 'default' => "Three steps\n*to your first* order", 'description' => 'One line per row. ' . $italic ),
				'items'   => array(
					'label'   => 'Steps',
					'type'    => 'repeater',
					'fields'  => array(
						'title' => array( 'label' => 'Title', 'type' => 'text' ),
						'text'  => array( 'label' => 'Text', 'type' => 'textarea' ),
					),
					'default' => array(
						array( 'title' => 'Apply', 'text' => 'Submit the wholesale application with your business documents (business registration and retail licence).' ),
						array( 'title' => 'Get approved', 'text' => 'Our team reviews your documents and emails you once your account is approved.' ),
						array( 'title' => 'Order', 'text' => 'Log in to the private shop, see wholesale pricing and place your order.' ),
					),
				),
			),
		),
		'faq'      => array(
			'title'    => 'FAQ',
			'icon'     => 'eicon-accordion',
			'part'     => 'faq',
			'controls' => array(
				'eyebrow' => array( 'label' => 'Small label', 'type' => 'text', 'default' => 'FAQ' ),
				'title'   => array( 'label' => 'Title', 'type' => 'textarea', 'default' => "Questions,\n*answered.*", 'description' => 'One line per row. ' . $italic ),
				'items'   => array(
					'label'   => 'Questions',
					'type'    => 'repeater',
					'fields'  => array(
						'q' => array( 'label' => 'Question', 'type' => 'text' ),
						'a' => array( 'label' => 'Answer', 'type' => 'textarea' ),
					),
					'default' => array(
						array( 'q' => 'Who can buy from 1AM?', 'a' => 'Only Canadian retailers approved after a business document check can order. We do not sell to consumers.' ),
						array( 'q' => 'How long does approval take?', 'a' => 'We review applications within 1–2 business days and email you once you are approved.' ),
						array( 'q' => 'Is there a minimum order?', 'a' => 'Minimum order quantities and pricing are shown in the wholesale shop after approval.' ),
						array( 'q' => 'Where do you ship?', 'a' => 'We ship only to provinces where our products can be sold. Other addresses cannot be selected at checkout.' ),
						array( 'q' => 'How do I pay?', 'a' => 'Pay by Interac e-Transfer or bank transfer.' ),
					),
				),
			),
		),
		'flavors'  => array(
			'title'    => 'Flavour grid',
			'icon'     => 'eicon-gallery-masonry',
			'part'     => 'flavors',
			'note'     => 'Flavour cards with category filter, from your WooCommerce variations.',
			'controls' => array(
				'title' => array( 'label' => 'Title', 'type' => 'text', 'default' => 'Slim HYBRID', 'arg' => true ),
				'line'  => array( 'label' => 'Only this product (slug, optional)', 'type' => 'text', 'default' => '', 'arg' => true, 'description' => 'e.g. slim-hybrid. Empty = all flavours.' ),
			),
		),
		'hero'     => array(
			'title'    => 'Flavour slider',
			'icon'     => 'eicon-slides',
			'part'     => 'hero',
			'note'     => 'The big colour slider used at the top of the Slim HYBRID product page.',
			'controls' => array(
				'title'  => array( 'label' => 'Title', 'type' => 'textarea', 'default' => "Slim\nHYBRID.", 'arg' => true ),
				'kicker' => array( 'label' => 'Small label', 'type' => 'text', 'default' => '', 'arg' => true, 'description' => 'Empty = "2ml Disposable · 15 flavours"' ),
				'line'   => array( 'label' => 'Product (slug)', 'type' => 'text', 'default' => 'slim-hybrid', 'arg' => true ),
			),
		),
		'signup'   => array(
			'title'    => 'Wholesale application form',
			'icon'     => 'eicon-form-horizontal',
			'part'     => '',
			'note'     => 'The sign-up form. Applications appear in Users › Pending wholesale.',
			'controls' => array(),
		),
	);
}

/* ---------------------------------------------------------------------------
 * Elementor 에 등록
 * ------------------------------------------------------------------------- */
add_action(
	'elementor/elements/categories_registered',
	function ( $manager ) {
		$manager->add_category( 'oneam', array( 'title' => '1AM', 'icon' => 'fa fa-plug' ) );
	}
);

add_action(
	'elementor/widgets/register',
	function ( $widgets ) {
		require_once get_template_directory() . '/inc/elementor-widget.php';
		foreach ( array_keys( oneam_elementor_widgets() ) as $key ) {
			$class = 'OneAM_El_' . ucfirst( $key );
			if ( class_exists( $class ) ) {
				$widgets->register( new $class() );
			}
		}
	}
);

/** 편집기에서도 테마 스타일 · 폰트 */
add_action(
	'elementor/preview/enqueue_styles',
	function () {
		wp_enqueue_style( 'oneam-main' );
	}
);

/* ---------------------------------------------------------------------------
 * 홈 구성을 Elementor 페이지로 만들기 (관리자 버튼)
 * ------------------------------------------------------------------------- */
function oneam_el_id() {
	return substr( md5( wp_generate_password( 12, false ) ), 0, 7 );
}

/** 위젯 하나를 꽉 찬 섹션에 담은 Elementor 데이터 */
function oneam_el_section( $widget, $settings = array() ) {
	return array(
		'id'       => oneam_el_id(),
		'elType'   => 'section',
		'settings' => array( 'layout' => 'full_width', 'gap' => 'no', 'stretch_section' => 'section-stretched' ),
		'elements' => array(
			array(
				'id'       => oneam_el_id(),
				'elType'   => 'column',
				'settings' => array( '_column_size' => 100 ),
				'elements' => array(
					array(
						'id'         => oneam_el_id(),
						'elType'     => 'widget',
						'widgetType' => 'oneam-' . $widget,
						'settings'   => (object) $settings,
						'elements'   => array(),
					),
				),
				'isInner'  => false,
			),
		),
		'isInner'  => false,
	);
}

function oneam_home_layout() {
	return array_map( 'oneam_el_section', array( 'film', 'about', 'lineup', 'device', 'cta' ) );
}

add_action(
	'admin_post_oneam_build_home',
	function () {
		check_admin_referer( 'oneam_build_home' );
		if ( ! current_user_can( 'edit_pages' ) || ! did_action( 'elementor/loaded' ) ) {
			wp_die( 'Not allowed.' );
		}
		$id = 'page' === get_option( 'show_on_front' ) ? (int) get_option( 'page_on_front' ) : 0;
		if ( ! $id ) {
			$id = wp_insert_post( array( 'post_type' => 'page', 'post_status' => 'publish', 'post_title' => 'Home' ) );
			update_option( 'show_on_front', 'page' );
			update_option( 'page_on_front', $id );
		}
		// 홈이 "최신 글" 페이지로도 지정돼 있으면 해제 (같은 페이지면 편집이 안 됨)
		if ( (int) get_option( 'page_for_posts' ) === $id ) {
			update_option( 'page_for_posts', 0 );
		}
		update_post_meta( $id, '_elementor_edit_mode', 'builder' );
		update_post_meta( $id, '_elementor_template_type', 'wp-page' );
		update_post_meta( $id, '_wp_page_template', 'default' );
		if ( defined( 'ELEMENTOR_VERSION' ) ) {
			update_post_meta( $id, '_elementor_version', ELEMENTOR_VERSION );
		}
		update_post_meta( $id, '_elementor_data', wp_slash( wp_json_encode( oneam_home_layout() ) ) );
		delete_post_meta( $id, '_elementor_css' );
		wp_safe_redirect( admin_url( 'post.php?post=' . $id . '&action=elementor' ) );
		exit;
	}
);

/** 버튼: 페이지 목록 · 대시보드 · 홈 편집 화면 상단 */
add_action(
	'admin_notices',
	function () {
		if ( ! did_action( 'elementor/loaded' ) || ! current_user_can( 'edit_pages' ) ) {
			return;
		}
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( ! $screen || ! in_array( $screen->id, array( 'dashboard', 'edit-page', 'page' ), true ) ) {
			return;
		}
		$home = 'page' === get_option( 'show_on_front' ) ? (int) get_option( 'page_on_front' ) : 0;
		if ( $home && oneam_is_builder_page( $home ) ) {
			if ( 'edit-page' === $screen->id ) {
				printf( '<div class="notice notice-info"><p><strong>1AM:</strong> Your homepage is built with Elementor. <a class="button button-primary" href="%s">Edit homepage in Elementor</a></p></div>', esc_url( admin_url( 'post.php?post=' . $home . '&action=elementor' ) ) );
			}
			return;
		}
		$url = wp_nonce_url( admin_url( 'admin-post.php?action=oneam_build_home' ), 'oneam_build_home' );
		printf(
			'<div class="notice notice-info"><p><strong>1AM:</strong> Edit the homepage visually in Elementor — the current sections (video, flavour line-up, products, device, call to action) are added as 1AM widgets you can move, delete and edit. <a class="button button-primary" href="%s">Build homepage in Elementor</a></p></div>',
			esc_url( $url )
		);
	}
);
