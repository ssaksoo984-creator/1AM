<?php
/**
 * 1AM 상품 상세 (테마 제공 · /products/{slug}/)
 * 예전 slim-hybrid.html 과 같은 구성: 맛 슬라이더 → 상품 정보 → 맛 목록 → Stock 1AM
 * WooCommerce 에 같은 상품이 발행돼 있으면 그 상품 페이지로 자동 이동합니다.
 */
$oneam_p    = oneam_product_by_slug( get_query_var( 'oneam_product' ) );
$oneam_line = $oneam_p['slug'];
if ( ! oneam_flavours_for_line( $oneam_line ) ) {
	$oneam_line = '';
}
get_header();
if ( 'soon' !== $oneam_p['status'] ) {
	get_template_part(
		'template-parts/hero',
		null,
		array(
			'line'   => $oneam_line,
			'title'  => $oneam_p['name'] . '.',
			'kicker' => $oneam_p['tag'] ? $oneam_p['tag'] : count( oneam_flavours_for_line( $oneam_line ) ) . ' flavours',
		)
	);
}
get_template_part( 'template-parts/pinfo', null, array( 'product' => $oneam_p ) );
if ( 'soon' !== $oneam_p['status'] ) {
	get_template_part( 'template-parts/flavors', null, array( 'line' => $oneam_line, 'title' => $oneam_p['name'] ) );
}
get_template_part( 'template-parts/cta' );
get_footer();
