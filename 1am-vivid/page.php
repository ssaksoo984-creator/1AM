<?php
/**
 * 기본 페이지 (FAQ, 도매 가입, 정책 페이지 등)
 * FAQ 는 블록 편집기의 '세부 정보(Details)' 블록으로 작성하면 아코디언으로 표시됩니다.
 */
get_header();
while ( have_posts() ) :
	the_post();
	// Elementor 로 만든 페이지: 컬러 제목 + 꽉 찬 폭 Elementor 내용
	// (Elementor 페이지 설정의 "Hide Title" 을 켜면 컬러 제목 영역도 숨김)
	if ( oneam_is_builder_page() || oneam_is_builder_preview() ) {
		$oneam_ps = get_post_meta( get_the_ID(), '_elementor_page_settings', true );
		if ( empty( $oneam_ps['hide_title'] ) || 'yes' !== $oneam_ps['hide_title'] ) {
			get_template_part( 'template-parts/page-head' );
		}
		echo '<div class="builder-content">';
		the_content();
		echo '</div>';
		continue;
	}
	get_template_part( 'template-parts/page-head' );
	?>
	<div class="page-body entry__content">
		<?php the_content(); ?>
	</div>
	<?php
endwhile;
get_footer();
