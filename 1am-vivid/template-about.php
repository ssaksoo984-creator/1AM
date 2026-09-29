<?php
/**
 * Template Name: About Us
 */
get_header();
while ( have_posts() ) :
	the_post();
	get_template_part( 'template-parts/page-head' );
	?>
	<div class="page-body entry__content"><?php the_content(); ?></div>
	<?php
endwhile;
get_template_part( 'template-parts/benefits' );
get_template_part( 'template-parts/cta' );
get_footer();
