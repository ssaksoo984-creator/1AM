<?php
/**
 * Template Name: How to Order (구매 절차)
 */
get_header();
while ( have_posts() ) :
	the_post();
	get_template_part( 'template-parts/page-head' );
	get_template_part( 'template-parts/steps' );
	?>
	<div class="page-body entry__content"><?php the_content(); ?></div>
	<?php
endwhile;
get_template_part( 'template-parts/faq' );
get_template_part( 'template-parts/cta' );
get_footer();
