<?php
/**
 * About — 큰 소개 문장 + 숫자
 */
$flavors = oneam_get_flavors();
$stats   = oneam_ctx( 'stats', array() );
if ( ! $stats ) {
	$stats = array(
		array( 'num' => (string) count( $flavors ), 'label' => 'Flavours' ),
		array( 'num' => (string) count( oneam_flavor_categories() ), 'label' => 'Flavour families' ),
		array( 'num' => trim( preg_replace( '/\s*business days?/i', '', oneam_opt( 'oneam_dispatch_time' ) ) ), 'label' => 'Business days to ship' ),
	);
}
?>
<section class="story">
	<p class="eyebrow"><?php echo esc_html( oneam_ctx( 'eyebrow', 'About 1AM' ) ); ?></p>
	<h2 class="story__title" data-reveal><?php echo oneam_lines( oneam_ctx( 'title', "Made for\n*Canadian retail.*" ) ); // phpcs:ignore ?></h2>
	<div class="story__body">
		<p class="story__text"><?php echo esc_html( oneam_ctx( 'text', '1AM is a vape brand built for adult-only vape retailers in ' . oneam_regions_phrase() . '. Our products are distributed by ' . oneam_opt( 'oneam_company' ) . ' and sold exclusively to verified, approved retail partners — never directly to consumers.' ) ); ?></p>
		<dl class="story__stats">
			<?php foreach ( $stats as $st ) : ?>
				<div><dd data-count="<?php echo esc_attr( preg_match( '/^\d+$/', $st['num'] ) ? $st['num'] : '' ); ?>"><?php echo esc_html( $st['num'] ); ?></dd><dt><?php echo esc_html( $st['label'] ); ?></dt></div>
			<?php endforeach; ?>
		</dl>
	</div>
</section>
