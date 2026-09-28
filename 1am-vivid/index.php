<?php
/**
 * 기본 템플릿 (블로그/페이지/검색 등)
 */
get_header();
?>
<div class="page">
	<?php if ( have_posts() ) : ?>
		<?php if ( ! is_singular() ) : ?>
			<h1 class="page__title"><?php echo esc_html( wp_strip_all_tags( get_the_archive_title() ?: get_bloginfo( 'name' ) ) ); ?></h1>
		<?php endif; ?>

		<?php
		while ( have_posts() ) :
			the_post();
			?>
			<article <?php post_class( 'entry' ); ?>>
				<?php if ( is_singular() ) : ?>
					<h1 class="page__title"><?php the_title(); ?></h1>
					<div class="entry__content"><?php the_content(); ?></div>
				<?php else : ?>
					<h2 class="entry__title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
					<div class="entry__content"><?php the_excerpt(); ?></div>
				<?php endif; ?>
			</article>
		<?php endwhile; ?>

		<?php the_posts_pagination(); ?>
	<?php else : ?>
		<h1 class="page__title">Nothing here.</h1>
		<p><a class="btn btn--solid" href="<?php echo esc_url( home_url( '/' ) ); ?>"><span>Back home</span></a></p>
	<?php endif; ?>
</div>
<?php
get_footer();
