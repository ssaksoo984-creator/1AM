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
	// 1~2장만 올렸으면 맛 이미지와 섞어서 3개가 돌아가게 (넓은 사진 한 장만 쓰려면 "Rotate with flavour images" 끄기)
	$stack = ( count( $mine ) < 3 && 'no' !== oneam_ctx( 'mix_flavours', 'yes' ) ) ? array_merge( $mine, $stack ) : $mine;
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
			// 이미지가 3장 이상이면 3개가 나란히 보이며 돌아가고, 직접 올린 이미지가 1~2장이면 그 이미지만 크게
			$oneam_fan = count( $stack ) >= 3;
			?>
			<div class="device__stack<?php echo $custom && ! $oneam_fan ? ' is-custom' : ''; ?><?php echo $oneam_fan ? ' is-fan' : ''; ?>">
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
