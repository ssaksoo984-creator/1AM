<?php
/**
 * Template Name: How to Order (구매 절차)
 */
get_header();
while ( have_posts() ) :
	the_post();
	get_template_part( 'template-parts/page-head' );
	if ( trim( wp_strip_all_tags( get_the_content() ) ) ) :
		?>
		<div class="page-body entry__content order-intro"><?php the_content(); ?></div>
		<?php
	endif;
	get_template_part( 'template-parts/steps' );
endwhile;
get_template_part( 'template-parts/orderinfo' );
get_template_part( 'template-parts/faq' );
get_template_part( 'template-parts/cta' );
get_footer();
