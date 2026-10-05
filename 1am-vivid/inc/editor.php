<?php
/**
 * 편집기 화면을 사이트와 같은 모양으로 + 1AM 블록 패턴.
 *
 * - 페이지/글 편집기와 WooCommerce 상품 설명 편집기에서 글꼴·제목·버튼이 사이트와 똑같이 보입니다.
 * - 편집기 "+" → 패턴(Patterns) → 1AM 에서 섹션을 클릭 한 번으로 끼워 넣을 수 있습니다.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action(
	'after_setup_theme',
	function () {
		add_theme_support( 'editor-styles' );
		add_theme_support( 'align-wide' );
		add_theme_support( 'responsive-embeds' );
		// 클래식 편집기(WooCommerce 상품 설명)용
		add_editor_style( array( 'assets/css/editor.css', 'assets/css/blocks.css' ) );
	}
);

// 블록 에디터(사이트 미리보기 iframe) + 사이트 모두에 패턴 스타일
add_action(
	'enqueue_block_assets',
	function () {
		wp_enqueue_style( 'oneam-blocks', oneam_asset( 'css/blocks.css' ), array(), ONEAM_VERSION );
		if ( is_admin() ) {
			wp_enqueue_style( 'oneam-editor', oneam_asset( 'css/editor.css' ), array(), ONEAM_VERSION );
		}
	}
);

/* ---------------------------------------------------------------------------
 * 1AM 블록 패턴
 * ------------------------------------------------------------------------- */
add_action(
	'init',
	function () {
		if ( ! function_exists( 'register_block_pattern' ) ) {
			return;
		}
		register_block_pattern_category( 'oneam', array( 'label' => '1AM' ) );

		register_block_pattern(
			'oneam/hero',
			array(
				'title'      => '1AM — Colour header',
				'categories' => array( 'oneam' ),
				'content'    => '<!-- wp:group {"className":"oa-hero","layout":{"type":"constrained"}} -->
<div class="wp-block-group oa-hero"><!-- wp:paragraph {"className":"oa-eyebrow"} -->
<p class="oa-eyebrow">About 1AM</p>
<!-- /wp:paragraph -->

<!-- wp:heading {"level":2} -->
<h2 class="wp-block-heading">Built for <em>Canadian retailers.</em></h2>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>Write a short introduction here. Wrap a word in italics (Ctrl+I) to get the serif accent.</p>
<!-- /wp:paragraph --></div>
<!-- /wp:group -->',
			)
		);

		register_block_pattern(
			'oneam/cards',
			array(
				'title'      => '1AM — Four benefit cards',
				'categories' => array( 'oneam' ),
				'content'    => '<!-- wp:columns {"className":"oa-cards","align":"wide"} -->
<div class="wp-block-columns alignwide oa-cards"><!-- wp:column {"className":"oa-card"} -->
<div class="wp-block-column oa-card"><!-- wp:heading {"level":3} -->
<h3 class="wp-block-heading">Wholesale pricing</h3>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Trade pricing for approved accounts.</p>
<!-- /wp:paragraph --></div>
<!-- /wp:column -->

<!-- wp:column {"className":"oa-card"} -->
<div class="wp-block-column oa-card"><!-- wp:heading {"level":3} -->
<h3 class="wp-block-heading">Approved retailers only</h3>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>We only work with verified stores.</p>
<!-- /wp:paragraph --></div>
<!-- /wp:column -->

<!-- wp:column {"className":"oa-card"} -->
<div class="wp-block-column oa-card"><!-- wp:heading {"level":3} -->
<h3 class="wp-block-heading">Ships where allowed</h3>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Orders ship only to permitted provinces.</p>
<!-- /wp:paragraph --></div>
<!-- /wp:column -->

<!-- wp:column {"className":"oa-card"} -->
<div class="wp-block-column oa-card"><!-- wp:heading {"level":3} -->
<h3 class="wp-block-heading">Clear product info</h3>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Specs and flavours in one place.</p>
<!-- /wp:paragraph --></div>
<!-- /wp:column --></div>
<!-- /wp:columns -->',
			)
		);

		register_block_pattern(
			'oneam/steps',
			array(
				'title'      => '1AM — Three steps',
				'categories' => array( 'oneam' ),
				'content'    => '<!-- wp:group {"className":"oa-steps","align":"wide","layout":{"type":"default"}} -->
<div class="wp-block-group alignwide oa-steps"><!-- wp:heading {"level":2} -->
<h2 class="wp-block-heading">Three steps <em>to your first</em> order</h2>
<!-- /wp:heading -->

<!-- wp:columns -->
<div class="wp-block-columns"><!-- wp:column {"className":"oa-step"} -->
<div class="wp-block-column oa-step"><!-- wp:heading {"level":3} -->
<h3 class="wp-block-heading">Apply</h3>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Submit the application with your business documents.</p>
<!-- /wp:paragraph --></div>
<!-- /wp:column -->

<!-- wp:column {"className":"oa-step"} -->
<div class="wp-block-column oa-step"><!-- wp:heading {"level":3} -->
<h3 class="wp-block-heading">Get approved</h3>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>We review your documents and email you.</p>
<!-- /wp:paragraph --></div>
<!-- /wp:column -->

<!-- wp:column {"className":"oa-step"} -->
<div class="wp-block-column oa-step"><!-- wp:heading {"level":3} -->
<h3 class="wp-block-heading">Order</h3>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Log in and order at wholesale prices.</p>
<!-- /wp:paragraph --></div>
<!-- /wp:column --></div>
<!-- /wp:columns --></div>
<!-- /wp:group -->',
			)
		);

		register_block_pattern(
			'oneam/faq',
			array(
				'title'      => '1AM — FAQ',
				'categories' => array( 'oneam' ),
				'content'    => '<!-- wp:details -->
<details class="wp-block-details"><summary>Who can buy from 1AM?</summary><!-- wp:paragraph -->
<p>Only approved Canadian retailers.</p>
<!-- /wp:paragraph --></details>
<!-- /wp:details -->

<!-- wp:details -->
<details class="wp-block-details"><summary>How long does approval take?</summary><!-- wp:paragraph -->
<p>Usually 1–2 business days.</p>
<!-- /wp:paragraph --></details>
<!-- /wp:details -->

<!-- wp:details -->
<details class="wp-block-details"><summary>Where do you ship?</summary><!-- wp:paragraph -->
<p>Only to provinces where our products can be sold.</p>
<!-- /wp:paragraph --></details>
<!-- /wp:details -->',
			)
		);

		register_block_pattern(
			'oneam/cta',
			array(
				'title'      => '1AM — Wholesale call to action',
				'categories' => array( 'oneam' ),
				'content'    => '<!-- wp:group {"className":"oa-cta","layout":{"type":"constrained"}} -->
<div class="wp-block-group oa-cta"><!-- wp:heading {"textAlign":"center","level":2} -->
<h2 class="wp-block-heading has-text-align-center">Stock <em>1AM.</em></h2>
<!-- /wp:heading -->

<!-- wp:paragraph {"align":"center"} -->
<p class="has-text-align-center">Apply, get verified, and order at wholesale prices.</p>
<!-- /wp:paragraph -->

<!-- wp:buttons {"layout":{"type":"flex","justifyContent":"center"}} -->
<div class="wp-block-buttons"><!-- wp:button -->
<div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="/wholesale-signup/">Apply for wholesale →</a></div>
<!-- /wp:button -->

<!-- wp:button {"className":"is-style-outline"} -->
<div class="wp-block-button is-style-outline"><a class="wp-block-button__link wp-element-button" href="/my-account/">Log in</a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons --></div>
<!-- /wp:group -->',
			)
		);
	}
);
