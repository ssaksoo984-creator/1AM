<?php
$flavors = oneam_get_flavors();
$hero    = $flavors[0];
// Elementor 에서 디바이스 이미지를 따로 올리면 그 이미지 하나만
$stack  = array_slice( $flavors, 0, 7 );
$custom = (array) oneam_ctx( 'device_image', array() );
if ( $custom ) {
	$mine = array();
	foreach ( $custom as $url ) {
		$mine[] = array_merge( $hero, array( 'img' => $url ) );
	}
}
$callouts = oneam_ctx( 'callouts', array() ) ? array_map( function ( $r ) { return array( $r['title'], $r['text'] ); }, oneam_ctx( 'callouts' ) ) : array(
	array( 'Crystal shell', 'A clear crystal body shows the colour core inside.' ),
	array( 'Gradient core', 'Each flavour has its own gradient.' ),
	array( 'Slim HYBRID', 'A slim body that sits easily in the hand.' ),
	array( 'Soft mouthpiece', 'A smooth, rounded mouthpiece.' ),
);
$specs = oneam_ctx( 'specs', array() );
if ( ! $specs ) {
	for ( $n = 1; $n <= 3; $n++ ) {
		$specs[] = array( 'num' => oneam_opt( "oneam_spec_{$n}_num" ), 'label' => oneam_opt( "oneam_spec_{$n}_label" ) );
	}
}
?>
<section class="device" id="device" style="<?php echo oneam_flavor_style( $hero ); // phpcs:ignore ?>">
	<div class="device__pin">
		<h2 class="device__title">
			<?php foreach ( preg_split( '/\r?\n/', trim( oneam_opt( 'oneam_device_title' ) ) ) as $part ) : ?>
				<span class="line"><span><?php echo oneam_rich( trim( $part ) ); // phpcs:ignore ?></span></span>
			<?php endforeach; ?>
		</h2>

		<div class="device__stage">
			<div class="device__ring" aria-hidden="true"></div>
			<div class="device__float">
			<?php
			// 제품 하나만 둥둥 (직접 올린 이미지가 있으면 그 첫 장, 없으면 첫 번째 맛)
			$stack = array_slice( $custom ? $mine : $stack, 0, 1 );
			?>
			<div class="device__stack is-single">
				<?php foreach ( $stack as $i => $f ) : ?>
					<img src="<?php echo esc_url( $f['img'] ); ?>" alt="<?php echo 0 === $i ? esc_attr( '1AM Slim HYBRID' ) : ''; ?>" width="246" height="1400" loading="lazy" data-c1="<?php echo esc_attr( $f['c1'] ); ?>" data-c2="<?php echo esc_attr( $f['c2'] ); ?>">
				<?php endforeach; ?>
			</div>
			</div>
			<?php foreach ( $callouts as $i => $c ) : ?>
				<div class="callout callout--<?php echo (int) $i + 1; ?>">
					<i></i>
					<strong><?php echo esc_html( $c[0] ); ?></strong>
					<span><?php echo esc_html( $c[1] ); ?></span>
				</div>
			<?php endforeach; ?>
		</div>

		<?php if ( 'yes' === oneam_ctx( 'show_specs', '' ) ) : ?>
		<dl class="specs">
			<?php foreach ( $specs as $sp ) : ?>
				<div class="spec">
					<dt><?php echo esc_html( $sp['label'] ); ?></dt>
					<dd data-count="<?php echo esc_attr( $sp['num'] ); ?>"><?php echo esc_html( $sp['num'] ); ?></dd>
				</div>
			<?php endforeach; ?>
		</dl>
		<?php endif; ?>
	</div>
</section>
