<?php
/**
 * Template Name: About Us
 * 소개 → 만드는 것 → Why stock 1AM (4가지) → 함께하기(신청 카드 · 이메일 카드)
 * 페이지 본문에 직접 쓴 내용이 있으면 소개 아래에 함께 보여줍니다.
 */
get_header();
while ( have_posts() ) :
	the_post();
	get_template_part( 'template-parts/page-head' );
	get_template_part( 'template-parts/story' );
	// "Set up 1AM pages" 로 만든 기본 글은 아래 섹션들과 같은 내용이라 숨김
	$oneam_content = get_the_content();
	if ( trim( wp_strip_all_tags( $oneam_content ) ) && false === strpos( $oneam_content, 'Made for Canadian retail.' ) ) :
		?>
		<div class="page-body entry__content"><?php the_content(); ?></div>
		<?php
	endif;
endwhile;
get_template_part( 'template-parts/make' );
get_template_part( 'template-parts/benefits' );
get_template_part( 'template-parts/work' );
get_footer();
