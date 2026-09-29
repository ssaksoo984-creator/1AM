<?php
/**
 * 메인 페이지
 * 경고문 → 메인 비주얼 → 영상 → 회사 소개 → 상품 3종(가로 스크롤) → 맛 → 디바이스 → 장점 → 구매 절차 → FAQ → 도매 가입
 */
get_header();

get_template_part( 'template-parts/hero' );
get_template_part( 'template-parts/marquee' );
get_template_part( 'template-parts/film' );
get_template_part( 'template-parts/about' );
get_template_part( 'template-parts/lineup' );
get_template_part( 'template-parts/flavors', null, array( 'line' => 'slim-hybrid' ) );
get_template_part( 'template-parts/device' );
get_template_part( 'template-parts/benefits' );
get_template_part( 'template-parts/steps' );
get_template_part( 'template-parts/faq' );
get_template_part( 'template-parts/cta' );

get_footer();
