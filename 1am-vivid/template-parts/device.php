<?php
$flavors = oneam_get_flavors();
$hero    = $flavors[0];
$callouts = array(
	array( 'Crystal shell', 'A clear crystal body shows the colour core inside.' ),
	array( 'Gradient core', 'Each flavour has its own gradient.' ),
	array( 'Slim HYBRID', 'A slim body that sits easily in the hand.' ),
	array( 'Soft mouthpiece', 'A smooth, rounded mouthpiece.' ),
);
?>
<section class="device" id="device" data-header="light" style="<?php echo oneam_flavor_style( $hero ); // phpcs:ignore ?>">
	<div class="device__pin">
		<h2 class="device__title">
			<?php foreach ( explode( '.', trim( oneam_opt( 'oneam_device_title' ), '.' ) ) as $part ) : ?>
				<?php if ( trim( $part ) ) : ?>
					<span class="line"><span><?php echo esc_html( trim( $part ) ); ?>.</span></span>
				<?php endif; ?>
			<?php endforeach; ?>
		</h2>

		<div class="device__stage">
			<div class="device__ring" aria-hidden="true"></div>
			<div class="device__stack">
				<?php foreach ( array_slice( $flavors, 0, 5 ) as $i => $f ) : ?>
					<img src="<?php echo esc_url( $f['img'] ); ?>" alt="<?php echo 0 === $i ? esc_attr( '1AM Slim HYBRID' ) : ''; ?>" width="246" height="1400" loading="lazy" data-c1="<?php echo esc_attr( $f['c1'] ); ?>" data-c2="<?php echo esc_attr( $f['c2'] ); ?>">
				<?php endforeach; ?>
			</div>
			<?php foreach ( $callouts as $i => $c ) : ?>
				<div class="callout callout--<?php echo (int) $i + 1; ?>">
					<i></i>
					<strong><?php echo esc_html( $c[0] ); ?></strong>
					<span><?php echo esc_html( $c[1] ); ?></span>
				</div>
			<?php endforeach; ?>
		</div>

		<dl class="specs">
			<?php for ( $n = 1; $n <= 3; $n++ ) : ?>
				<div class="spec">
					<dt><?php echo esc_html( oneam_opt( "oneam_spec_{$n}_label" ) ); ?></dt>
					<dd data-count="<?php echo esc_attr( oneam_opt( "oneam_spec_{$n}_num" ) ); ?>"><?php echo esc_html( oneam_opt( "oneam_spec_{$n}_num" ) ); ?></dd>
				</div>
			<?php endfor; ?>
		</dl>
	</div>
</section>
