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
	// 빈 Elementor 페이지는 "아직 안 만든 것"으로 봄 (홈이 하얗게 비지 않도록)
	return $post_id && 'builder' === get_post_meta( $post_id, '_elementor_edit_mode', true ) && did_action( 'elementor/loaded' ) && ! oneam_el_is_empty( $post_id );
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

/** Elementor 이미지/링크 값 → 주소 (주소가 비어 있고 ID 만 있으면 ID 로 찾음) */
function oneam_media_url( $v ) {
	if ( ! is_array( $v ) ) {
		return (string) $v;
	}
	$url = isset( $v['url'] ) ? (string) $v['url'] : '';
	if ( '' === $url && ! empty( $v['id'] ) ) {
		$url = (string) wp_get_attachment_url( (int) $v['id'] );
	}
	return $url;
}

/** 상품 카드 링크: 비었거나 예전 "#products" 링크면 그 상품의 상세 페이지로 */
function oneam_row_url( $r, $soon ) {
	$url = isset( $r['link'] ) ? oneam_media_url( $r['link'] ) : '';
	if ( $url && false === strpos( $url, '#products' ) ) {
		return $url;
	}
	if ( $soon ) {
		return oneam_signup_url();
	}
	$p = oneam_product_by_slug( sanitize_title( isset( $r['name'] ) ? $r['name'] : '' ) );
	return $p ? $p['url'] : oneam_signup_url();
}

/** Product info 위젯 기본값 (Slim HYBRID) */
function oneam_el_product_field( $key ) {
	$p = oneam_product_by_slug( 'slim-hybrid' );
	return $p && isset( $p[ $key ] ) ? (string) $p[ $key ] : '';
}
function oneam_el_product_specs() {
	$p    = oneam_product_by_slug( 'slim-hybrid' );
	$rows = array();
	foreach ( $p ? (array) $p['specs'] : array() as $k => $v ) {
		$rows[] = array( 'label' => $k, 'value' => (string) $v );
	}
	return $rows;
}

/** 지금 상품(WooCommerce)을 "Products" 목록 기본값으로 — 편집 화면에 기존 상품이 그대로 보이게 */
function oneam_products_as_rows() {
	$rows = array();
	foreach ( oneam_get_products() as $p ) {
		$specs = array();
		foreach ( (array) $p['specs'] as $k => $val ) {
			$specs[] = $k . ': ' . $val;
		}
		$rows[] = array(
			'name'  => $p['name'],
			'tag'   => $p['tag'],
			'desc'  => $p['desc'],
			'specs' => implode( "\n", $specs ),
			'image' => array( 'url' => $p['img'], 'id' => '' ),
			'c1'    => $p['c1'],
			'c2'    => $p['c2'],
			'link'  => array( 'url' => 'soon' === $p['status'] ? '' : $p['url'] ),
			'soon'  => 'soon' === $p['status'] ? 'yes' : '',
		);
	}
	return $rows;
}

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
				'oneam_video_poster'  => array( 'label' => 'Background image', 'type' => 'image', 'description' => 'Shown before the video loads, or instead of it when no video is set.' ),
			),
			'style'    => array(
				array( 'tint', 'Dark tint strength', 'slider', '.film__tint', 'opacity' ),
				array( 'title_color', 'Headline colour', 'color', '.film__title', 'color' ),
				array( 'title_font', 'Headline font', 'typography', '.film__title' ),
				array( 'text_color', 'Caption colour', 'color', '.film__text', 'color' ),
				array( 'btn_bg', 'Button colour', 'color', '.film__btns .btn--solid', '--bg' ),
				array( 'btn_fg', 'Button text colour', 'color', '.film__btns .btn--solid', '--fg' ),
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
				'bloom1'            => array( 'label' => 'Pastel circle colour 1', 'type' => 'color' ),
				'bloom2'            => array( 'label' => 'Pastel circle colour 2', 'type' => 'color' ),
				'bloom3'            => array( 'label' => 'Pastel circle colour 3', 'type' => 'color' ),
			),
			'style'    => array(
				array( 'bg', 'Background colour', 'color', '.about', 'background-color' ),
				array( 'eyebrow_color', 'Small label colour', 'color', '.about__intro .eyebrow', 'color' ),
				array( 'title_color', 'Title colour', 'color', '.about__title', 'color' ),
				array( 'title_font', 'Title font', 'typography', '.about__title' ),
				array( 'text_color', 'Text colour', 'color', '.about__text', 'color' ),
				array( 'btn_color', 'Button colour', 'color', '.about__intro .btn', 'color' ),
			),
		),
		'lineup'   => array(
			'title'    => 'Product line-up',
			'icon'     => 'eicon-posts-carousel',
			'part'     => 'lineup',
			'note'     => 'Full-screen product panels (01 / 02 / 03). Scrolling moves one panel at a time.',
			'controls' => array(
				'eyebrow' => array( 'label' => 'Small label', 'type' => 'text', 'default' => 'Product lineup' ),
				'products' => array(
					'label'       => 'Products',
					'description' => 'Starts with your current WooCommerce products. Edit, reorder, delete or add cards here.',
					'type'        => 'repeater',
					'fields'      => array(
						'name'  => array( 'label' => 'Name', 'type' => 'text' ),
						'tag'   => array( 'label' => 'Label (e.g. 2ml Disposable)', 'type' => 'text' ),
						'desc'  => array( 'label' => 'Description', 'type' => 'textarea' ),
						'specs' => array( 'label' => 'Specs (one per line, e.g. E-liquid: 2ml)', 'type' => 'textarea' ),
						'image' => array( 'label' => 'Image (tall, transparent PNG/WebP)', 'type' => 'image' ),
						'c1'    => array( 'label' => 'Colour 1', 'type' => 'color' ),
						'c2'    => array( 'label' => 'Colour 2', 'type' => 'color' ),
						'link'  => array( 'label' => 'Link', 'type' => 'url' ),
						'soon'  => array( 'label' => 'Coming soon', 'type' => 'switch' ),
					),
					'default'     => oneam_products_as_rows(),
				),
			),
			'style'    => array(
				array( 'bg', 'Background colour', 'color', '.plist', 'background-color' ),
												array( 'name_font', 'Product name font', 'typography', '.pitem__name' ),
				array( 'card_color', 'Card text colour', 'color', '.pitem', 'color' ),
				array( 'radius', 'Card corner radius', 'slider_px', '.pitem', 'border-radius' ),
			),
		),
		'device'   => array(
			'title'    => 'Device + specs',
			'icon'     => 'eicon-device-mobile',
			'part'     => 'device',
			'controls' => array(
				'oneam_device_title' => array( 'label' => 'Title', 'type' => 'textarea', 'default' => oneam_opt( 'oneam_device_title' ), 'description' => 'One line per row. ' . $italic ),
				'device_image'       => array( 'label' => 'Device image (optional)', 'type' => 'gallery', 'description' => 'The first image floats in the middle. Empty = the first flavour from WooCommerce.' ),
				'show_specs'         => array( 'label' => 'Show the spec numbers under the device', 'type' => 'switch', 'default' => '' ),
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
			'style'    => array(
				array( 'c1', 'Glow colour 1', 'color', '.device', '--c1' ),
				array( 'c2', 'Glow colour 2', 'color', '.device', '--c2' ),
				array( 'bg', 'Background colour', 'color', '.device', 'background-color' ),
				array( 'text_color', 'Text colour', 'color', '.device', 'color' ),
				array( 'title_font', 'Title font', 'typography', '.device__title' ),
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
				'logo_on'         => array( 'label' => 'Show "1AM" as the logo', 'type' => 'switch', 'default' => 'yes' ),
				'logo'            => array( 'label' => 'Logo image (optional)', 'type' => 'image', 'description' => 'Empty = the site logo.' ),
			),
			'style'    => array(
				array( 'c1', 'Fill colour 1', 'color', '.cta', '--c1' ),
				array( 'c2', 'Fill colour 2', 'color', '.cta', '--c2' ),
				array( 'bg', 'Background colour', 'color', '.cta', 'background-color' ),
				array( 'stroke', 'Title outline colour', 'color', '.cta__title', '-webkit-text-stroke-color' ),
				array( 'title_font', 'Title font', 'typography', '.cta__title' ),
				array( 'text_color', 'Text colour', 'color', '.cta__text', 'color' ),
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
						array( 'title' => 'Wholesale only', 'text' => 'Every account is reviewed and verified before it can see pricing or place orders.', 'c1' => '#8A3FFC', 'c2' => '#1E5BFF' ),
						array( 'title' => 'Compliance first', 'text' => 'Products are supplied for sale in line with Canadian excise stamp and provincial requirements.', 'c1' => '#00C2A8', 'c2' => '#16C75A' ),
						array( 'title' => 'Product integrity', 'text' => 'Original packaging, warning labels and regulatory markings stay intact from our warehouse to your shelf.', 'c1' => '#FF2E4D', 'c2' => '#FFB321' ),
						array( 'title' => 'Fast, tracked shipping', 'text' => 'Orders ship by Purolator, UPS or Canpar Ground within ' . oneam_opt( 'oneam_dispatch_time' ) . ' of payment and typically arrive ' . oneam_opt( 'oneam_transit_time' ) . ' after shipping.', 'c1' => '#FFD60A', 'c2' => '#FF8A4D' ),
					),
				),
			),
			'style'    => array(
				array( 'bg', 'Background colour', 'color', '.benefits', 'background-color' ),
				array( 'title_color', 'Title colour', 'color', '.benefits .section-title', 'color' ),
				array( 'title_font', 'Title font', 'typography', '.benefits .section-title' ),
				array( 'card_bg', 'Card colour', 'color', '.benefit', 'background-color' ),
				array( 'card_color', 'Card text colour', 'color', '.benefit', 'color' ),
			),
		),
		'steps'    => array(
			'title'    => 'How to order steps',
			'icon'     => 'eicon-number-field',
			'part'     => 'steps',
			'controls' => array(
				'eyebrow' => array( 'label' => 'Small label', 'type' => 'text', 'default' => 'How to order' ),
				'title'   => array( 'label' => 'Title', 'type' => 'textarea', 'default' => "Four steps\n*to your first* order", 'description' => 'One line per row. ' . $italic ),
				'bg_image' => array( 'label' => 'Background image (optional)', 'type' => 'image', 'description' => 'Empty = plain background colour (Style tab).' ),
				'overlay'  => array( 'label' => 'Image tint (colour + transparency)', 'type' => 'color', 'default' => 'rgba(13,11,18,0.6)', 'description' => 'Darkens the image so the white text stays readable. Move the transparency slider to make it lighter or darker.' ),
				'items'   => array(
					'label'   => 'Steps',
					'type'    => 'repeater',
					'fields'  => array(
						'title' => array( 'label' => 'Title', 'type' => 'text' ),
						'text'  => array( 'label' => 'Text', 'type' => 'textarea' ),
					),
					'default' => array(
						array( 'title' => 'Apply', 'text' => 'Submit the retail account application with your business information and documents confirming an authorized adult-only vape store.' ),
						array( 'title' => 'Get verified', 'text' => 'Our team reviews your application, usually within 1–2 business days, and emails you once your account is approved.' ),
						array( 'title' => 'Order online', 'text' => 'Log in to see wholesale pricing. Choose your store\'s province — we ship only products stamped for it — and confirm compliance at checkout.' ),
						array( 'title' => 'Pay & ship', 'text' => 'Pay by ' . ( oneam_opt( 'oneam_processor' ) ? 'Interac e-Transfer or card' : 'Interac e-Transfer' ) . '. Orders ship within ' . oneam_opt( 'oneam_dispatch_time' ) . ' of payment by Purolator, UPS or Canpar Ground and typically arrive ' . oneam_opt( 'oneam_transit_time' ) . ' after shipping.' ),
					),
				),
			),
			'style'    => array(
				array( 'bg', 'Background colour', 'color', '.steps', 'background-color' ),
				array( 'text_color', 'Text colour', 'color', '.steps', 'color' ),
				array( 'title_font', 'Title font', 'typography', '.steps .section-title' ),
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
					'default' => array_map( function ( $q ) { return array( 'q' => $q[0], 'a' => $q[1] ); }, array_slice( oneam_faq_items(), 0, 5 ) ),
				),
			),
			'style'    => array(
				array( 'bg', 'Background colour', 'color', '.faq', 'background-color' ),
				array( 'title_color', 'Title colour', 'color', '.faq .section-title', 'color' ),
				array( 'title_font', 'Title font', 'typography', '.faq .section-title' ),
				array( 'q_color', 'Question colour', 'color', '.faq__item summary', 'color' ),
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
			'style'    => array(
				array( 'bg', 'Background colour', 'color', '.flavors', 'background-color' ),
				array( 'title_font', 'Title font', 'typography', '.flavors .section-title' ),
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
		'story'    => array(
			'title'    => 'About intro + numbers',
			'icon'     => 'eicon-t-letter',
			'part'     => 'story',
			'controls' => array(
				'eyebrow' => array( 'label' => 'Small label', 'type' => 'text', 'default' => 'About 1AM' ),
				'title'   => array( 'label' => 'Title', 'type' => 'textarea', 'default' => "Made for\n*Canadian retail.*", 'description' => 'One line per row. ' . $italic ),
				'text'    => array( 'label' => 'Text', 'type' => 'textarea', 'default' => '1AM is a vape brand built for adult-only vape retailers in ' . oneam_regions_phrase() . '. Our products are distributed by ' . oneam_opt( 'oneam_company' ) . ' and sold exclusively to verified, approved retail partners — never directly to consumers.' ),
				'stats'   => array(
					'label'   => 'Numbers',
					'type'    => 'repeater',
					'fields'  => array(
						'num'   => array( 'label' => 'Number', 'type' => 'text' ),
						'label' => array( 'label' => 'Label', 'type' => 'text' ),
					),
					'default' => array(
						array( 'num' => '15', 'label' => 'Flavours' ),
						array( 'num' => '3', 'label' => 'Flavour families' ),
						array( 'num' => trim( preg_replace( '/\s*business days?/i', '', oneam_opt( 'oneam_dispatch_time' ) ) ), 'label' => 'Business days to ship' ),
					),
				),
			),
			'style'    => array(
				array( 'bg', 'Background colour', 'color', '.story', 'background-color' ),
				array( 'title_color', 'Title colour', 'color', '.story__title', 'color' ),
				array( 'title_font', 'Title font', 'typography', '.story__title' ),
				array( 'text_color', 'Text colour', 'color', '.story__text', 'color' ),
			),
		),
		'make'     => array(
			'title'    => 'What we make',
			'icon'     => 'eicon-product-images',
			'part'     => 'make',
			'note'     => 'Product images, flavour families and coming-soon lines come from WooCommerce.',
			'controls' => array(
				'eyebrow' => array( 'label' => 'Small label', 'type' => 'text', 'default' => 'What we make' ),
				'title'   => array( 'label' => 'Title (empty = first product name)', 'type' => 'text', 'default' => '' ),
				'text'    => array( 'label' => 'Text', 'type' => 'textarea', 'default' => '' ),
			),
			'style'    => array(
				array( 'c1', 'Card colour 1', 'color', '.make__card', '--c1' ),
				array( 'c2', 'Card colour 2', 'color', '.make__card', '--c2' ),
				array( 'title_font', 'Title font', 'typography', '.make__title' ),
			),
		),
		'work'     => array(
			'title'    => 'Work with us (apply + email cards)',
			'icon'     => 'eicon-envelope',
			'part'     => 'work',
			'controls' => array(
				'eyebrow'     => array( 'label' => 'Small label', 'type' => 'text', 'default' => 'Work with us' ),
				'title'       => array( 'label' => 'Title', 'type' => 'textarea', 'default' => "Let’s stock\n*your shelves.*", 'description' => 'One line per row. ' . $italic ),
				'apply_label' => array( 'label' => 'Apply card — label', 'type' => 'text', 'default' => 'Retail account' ),
				'apply_title' => array( 'label' => 'Apply card — title', 'type' => 'text', 'default' => 'Run an adult-only vape store in ' . oneam_regions_phrase() . '?' ),
				'apply_text'  => array( 'label' => 'Apply card — text', 'type' => 'textarea', 'default' => 'Apply for a wholesale account. Once your business is verified, you can see pricing and order online.' ),
				'mail_title'  => array( 'label' => 'Email card — title', 'type' => 'text', 'default' => 'Talk to our sales team' ),
				'mail_text'   => array( 'label' => 'Email card — text', 'type' => 'textarea', 'default' => 'Questions about accounts, products, excise stamps or orders? We are happy to help.' ),
				'email'       => array( 'label' => 'Email (empty = site sales email)', 'type' => 'text', 'default' => '' ),
			),
			'style'    => array(
				array( 'c1', 'Apply card colour 1', 'color', '.work', '--c1' ),
				array( 'c2', 'Apply card colour 2', 'color', '.work', '--c2' ),
				array( 'mail_bg', 'Email card colour', 'color', '.work__card--mail', 'background-color' ),
				array( 'title_font', 'Title font', 'typography', '.work__head .section-title' ),
			),
		),
		'orderinfo' => array(
			'title'    => 'Ordering info cards',
			'icon'     => 'eicon-info-box',
			'part'     => 'orderinfo',
			'controls' => array(
				'eyebrow' => array( 'label' => 'Small label', 'type' => 'text', 'default' => 'Good to know' ),
				'title'   => array( 'label' => 'Title', 'type' => 'textarea', 'default' => "Before you\n*place an order.*", 'description' => 'One line per row. ' . $italic ),
				'items'   => array(
					'label'   => 'Cards',
					'type'    => 'repeater',
					'fields'  => array(
						'icon'  => array( 'label' => 'Icon (check, card, truck, mail)', 'type' => 'text' ),
						'title' => array( 'label' => 'Title', 'type' => 'text' ),
						'lines' => array( 'label' => 'Points (one per line)', 'type' => 'textarea' ),
					),
					'default' => oneam_orderinfo_defaults(),
				),
			),
			'style'    => array(
				array( 'bg', 'Background colour', 'color', '.orderinfo', 'background-color' ),
				array( 'card_bg', 'Card colour', 'color', '.orderinfo__card', 'background-color' ),
				array( 'title_font', 'Title font', 'typography', '.orderinfo .section-title' ),
			),
		),
		'pinfo'    => array(
			'title'    => 'Product info',
			'icon'     => 'eicon-product-info',
			'part'     => 'pinfo',
			'note'     => 'Name, label, description and specs of a product. Approved retailers also see the price and Add to cart (from the WooCommerce product with the same slug).',
			'controls' => array(
				'slug'  => array( 'label' => 'Product (slug)', 'type' => 'text', 'default' => 'slim-hybrid' ),
				'label' => array( 'label' => 'Small label', 'type' => 'text', 'default' => oneam_el_product_field( 'tag' ) ),
				'title' => array( 'label' => 'Name', 'type' => 'text', 'default' => oneam_el_product_field( 'name' ) ),
				'text'  => array( 'label' => 'Description', 'type' => 'textarea', 'default' => oneam_el_product_field( 'desc' ) ),
				'specs' => array(
					'label'   => 'Specs',
					'type'    => 'repeater',
					'fields'  => array(
						'label' => array( 'label' => 'Label', 'type' => 'text' ),
						'value' => array( 'label' => 'Value', 'type' => 'text' ),
					),
					'default' => oneam_el_product_specs(),
				),
			),
			'style'    => array(
				array( 'c1', 'Colour 1', 'color', '.pinfo', '--c1' ),
				array( 'c2', 'Colour 2', 'color', '.pinfo', '--c2' ),
				array( 'title_font', 'Name font', 'typography', '.pinfo__title' ),
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
function oneam_el_uses_containers() {
	return class_exists( '\Elementor\Plugin' ) && isset( \Elementor\Plugin::$instance->experiments )
		&& \Elementor\Plugin::$instance->experiments->is_feature_active( 'container' );
}

function oneam_el_section( $widget, $settings = array() ) {
	// 'core:text-editor' 처럼 쓰면 Elementor 기본 위젯 (글 페이지용, 가운데 정렬된 본문 폭)
	if ( 0 === strpos( $widget, 'core:' ) ) {
		return oneam_el_text_section( substr( $widget, 5 ), $settings );
	}
	$w = array(
		'id'         => oneam_el_id(),
		'elType'     => 'widget',
		'widgetType' => 'oneam-' . $widget,
		'settings'   => (object) $settings,
		'elements'   => array(),
	);
	// 새 Elementor (Flexbox 컨테이너)
	if ( oneam_el_uses_containers() ) {
		return array(
			'id'       => oneam_el_id(),
			'elType'   => 'container',
			'settings' => array(
				'content_width' => 'full',
				'flex_gap'      => array( 'size' => 0, 'column' => '0', 'row' => '0', 'unit' => 'px', 'isLinked' => true ),
				'padding'       => array( 'unit' => 'px', 'top' => '0', 'right' => '0', 'bottom' => '0', 'left' => '0', 'isLinked' => true ),
			),
			'elements' => array( $w ),
			'isInner'  => false,
		);
	}
	// 예전 Elementor (섹션 + 칼럼)
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

/** Elementor 기본 위젯을 본문 폭 섹션에 (약관 · 반품 · 가입 안내 등 글) */
function oneam_el_text_section( $type, $settings ) {
	$w = array(
		'id'         => oneam_el_id(),
		'elType'     => 'widget',
		'widgetType' => $type,
		'settings'   => (object) $settings,
		'elements'   => array(),
	);
	if ( oneam_el_uses_containers() ) {
		return array(
			'id'       => oneam_el_id(),
			'elType'   => 'container',
			'settings' => array( 'content_width' => 'full', 'css_classes' => 'oneam-text entry__content' ),
			'elements' => array( $w ),
			'isInner'  => false,
		);
	}
	return array(
		'id'       => oneam_el_id(),
		'elType'   => 'section',
		'settings' => array( 'layout' => 'full_width', 'gap' => 'no', 'css_classes' => 'oneam-text entry__content' ),
		'elements' => array(
			array(
				'id'       => oneam_el_id(),
				'elType'   => 'column',
				'settings' => array( '_column_size' => 100 ),
				'elements' => array( $w ),
				'isInner'  => false,
			),
		),
		'isInner'  => false,
	);
}

function oneam_home_layout() {
	return array_map( 'oneam_el_section', array( 'film', 'about', 'lineup', 'device', 'cta' ) );
}

/** 홈이 "최신 글 페이지"로도 지정돼 있으면 해제 (같은 페이지면 Elementor 로 편집할 수 없음) */
function oneam_fix_posts_page() {
	$front = (int) get_option( 'page_on_front' );
	if ( $front && 'page' === get_option( 'show_on_front' ) && (int) get_option( 'page_for_posts' ) === $front ) {
		update_option( 'page_for_posts', 0 );
	}
}
add_action( 'admin_init', 'oneam_fix_posts_page' );
add_filter(
	'option_page_for_posts',
	function ( $v ) {
		return ( (int) $v && (int) $v === (int) get_option( 'page_on_front' ) && 'page' === get_option( 'show_on_front' ) ) ? 0 : $v;
	}
);

/** Elementor 데이터가 비어 있는지 */
function oneam_el_is_empty( $id ) {
	$data = get_post_meta( $id, '_elementor_data', true );
	if ( is_string( $data ) ) {
		$data = json_decode( $data, true );
	}
	return empty( $data );
}

/** 페이지에 1AM 홈 구성을 채워 넣음 */
function oneam_fill_home( $id ) {
	update_post_meta( $id, '_elementor_edit_mode', 'builder' );
	update_post_meta( $id, '_elementor_template_type', 'wp-page' );
	if ( defined( 'ELEMENTOR_VERSION' ) ) {
		update_post_meta( $id, '_elementor_version', ELEMENTOR_VERSION );
	}
	update_post_meta( $id, '_elementor_data', wp_slash( wp_json_encode( oneam_home_layout() ) ) );
	delete_post_meta( $id, '_elementor_css' );
	if ( class_exists( '\Elementor\Plugin' ) && isset( \Elementor\Plugin::$instance->files_manager ) ) {
		\Elementor\Plugin::$instance->files_manager->clear_cache();
	}
}

/** 홈 페이지 (없으면 만들어서 홈으로 지정) */
function oneam_home_page_id( $create = false ) {
	$id = 'page' === get_option( 'show_on_front' ) ? (int) get_option( 'page_on_front' ) : 0;
	if ( ! $id && $create ) {
		$id = wp_insert_post( array( 'post_type' => 'page', 'post_status' => 'publish', 'post_title' => 'Home' ) );
		update_option( 'show_on_front', 'page' );
		update_option( 'page_on_front', $id );
	}
	oneam_fix_posts_page();
	return $id;
}

/** 버튼: 홈을 1AM 구성으로 (다시) 만들기 */
add_action(
	'admin_post_oneam_build_home',
	function () {
		check_admin_referer( 'oneam_build_home' );
		if ( ! current_user_can( 'edit_pages' ) || ! did_action( 'elementor/loaded' ) ) {
			wp_die( 'Not allowed.' );
		}
		$id = oneam_home_page_id( true );
		oneam_fill_home( $id );
		wp_safe_redirect( admin_url( 'post.php?post=' . $id . '&action=elementor' ) );
		exit;
	}
);

/** 빈 홈을 Elementor 로 열면 1AM 구성을 자동으로 넣어 줌 (빈 화면 대신) */
add_action(
	'admin_action_elementor',
	function () {
		$id = isset( $_GET['post'] ) ? absint( $_GET['post'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification
		if ( $id && current_user_can( 'edit_post', $id ) && $id === oneam_home_page_id() && oneam_el_is_empty( $id ) ) {
			oneam_fill_home( $id );
		}
	},
	1
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
		$home  = oneam_home_page_id();
		$build = wp_nonce_url( admin_url( 'admin-post.php?action=oneam_build_home' ), 'oneam_build_home' );
		if ( $home && oneam_is_builder_page( $home ) && ! oneam_el_is_empty( $home ) ) {
			if ( 'dashboard' === $screen->id ) {
				return;
			}
			printf(
				'<div class="notice notice-info"><p><strong>1AM ' . ONEAM_VERSION . ':</strong> Your homepage is built with Elementor. <a class="button button-primary" href="%1$s">Edit homepage in Elementor</a> <a class="button" href="%2$s" onclick="return confirm(\'Replace the homepage with the original 1AM sections? Your Elementor changes on the homepage will be lost.\');">Reset to 1AM layout</a></p></div>',
				esc_url( admin_url( 'post.php?post=' . $home . '&action=elementor' ) ),
				esc_url( $build )
			);
			return;
		}
		printf(
			'<div class="notice notice-info"><p><strong>1AM ' . ONEAM_VERSION . ':</strong> Edit the homepage visually in Elementor — the current sections (video, flavour line-up, products, device, call to action) are added as 1AM widgets you can move, delete and edit. <a class="button button-primary" href="%s">Build homepage in Elementor</a></p></div>',
			esc_url( $build )
		);
	}
);
