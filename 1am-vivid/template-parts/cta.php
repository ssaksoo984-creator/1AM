<?php
$flavors = oneam_get_flavors();
$fan     = array_slice( $flavors, 0, 9 );
$mid     = ( count( $fan ) - 1 ) / 2;
?>
<section class="cta" id="find">
	<div class="cta__fan" aria-hidden="true">
		<?php foreach ( $fan as $i => $f ) : ?>
			<img src="<?php echo esc_url( $f['img'] ); ?>" alt="" width="246" height="1400" loading="lazy" style="--o:<?php echo esc_attr( $i - $mid ); ?>">
		<?php endforeach; ?>
	</div>
	<h2 class="cta__title" data-fill><?php echo esc_html( oneam_opt( 'oneam_cta_title' ) ); ?></h2>
	<a class="btn btn--solid btn--xl" href="<?php echo esc_url( oneam_opt( 'oneam_cta_url' ) ); ?>" data-magnetic><span>Where to buy &rarr;</span></a>
</section>
