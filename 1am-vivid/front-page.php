<?php
/**
 * 메인 페이지 (설정 > 읽기 > "최신 글" 또는 정적 페이지 어느 쪽이든 이 템플릿이 사용됩니다)
 */
get_header();

get_template_part( 'template-parts/hero' );
get_template_part( 'template-parts/marquee' );
get_template_part( 'template-parts/lab' );
get_template_part( 'template-parts/flavors' );
get_template_part( 'template-parts/device' );
get_template_part( 'template-parts/cta' );

get_footer();
