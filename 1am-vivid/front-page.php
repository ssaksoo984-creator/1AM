<?php
/**
 * Home
 * Built with Elementor → the Elementor content (1AM widgets) is shown.
 * Otherwise the built-in layout:
 * Video intro → About + flavour line-up → 3 product lines → Slim outside/Loud inside → Wholesale CTA
 */
get_header();

if ( is_page() && ( oneam_is_builder_page() || oneam_is_builder_preview() ) ) {
	while ( have_posts() ) {
		the_post();
		echo '<div class="builder-content">';
		the_content();
		echo '</div>';
	}
} else {
	get_template_part( 'template-parts/film' );
	get_template_part( 'template-parts/about' );
	get_template_part( 'template-parts/lineup' );
	get_template_part( 'template-parts/device' );
	get_template_part( 'template-parts/cta' );
}

get_footer();
