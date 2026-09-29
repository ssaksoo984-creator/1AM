<?php
/**
 * Home
 * Video intro → About + flavour line-up → 3 product lines → Slim outside/Loud inside → Wholesale CTA
 * (Flavour slider & grid live on the product page, benefits on About, steps & FAQ on How to Order / FAQ.)
 */
get_header();

get_template_part( 'template-parts/film' );
get_template_part( 'template-parts/about' );
get_template_part( 'template-parts/lineup' );
get_template_part( 'template-parts/device' );
get_template_part( 'template-parts/cta' );

get_footer();
