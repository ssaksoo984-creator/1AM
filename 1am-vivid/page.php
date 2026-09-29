<?php
/**
 * 기본 페이지 (FAQ, 도매 가입, 정책 페이지 등)
 * FAQ 는 블록 편집기의 '세부 정보(Details)' 블록으로 작성하면 아코디언으로 표시됩니다.
 */
get_header();
while ( have_posts() ) :
	the_post();
	get_template_part( 'template-parts/page-head' );
	?>
	<div class="page-body entry__content">
		<?php the_content(); ?>
	</div>
	<?php
endwhile;
get_footer();
