<?php
/**
 * 서브 페이지 상단
 */
$first = oneam_get_flavors()[0];
?>
<section class="page-head" style="<?php echo oneam_flavor_style( $first ); // phpcs:ignore ?>">
	<div class="page-head__bg" aria-hidden="true"></div>
	<p class="eyebrow"><?php echo esc_html( get_bloginfo( 'name' ) ?: '1AM' ); ?></p>
	<h1 class="page-head__title"><?php echo oneam_split( get_the_title() ); // phpcs:ignore ?></h1>
	<?php if ( has_excerpt() ) : ?>
		<p class="page-head__lead"><?php echo esc_html( get_the_excerpt() ); ?></p>
	<?php endif; ?>
</section>
