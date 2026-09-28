<?php
$text = oneam_opt( 'oneam_marquee' );
?>
<section class="marquee" aria-label="<?php echo esc_attr( $text ); ?>">
	<div class="marquee__band marquee__band--a" data-marquee="1">
		<div class="marquee__track">
			<?php for ( $i = 0; $i < 4; $i++ ) : ?>
				<span aria-hidden="true"><?php echo esc_html( $text ); ?>&nbsp;</span>
			<?php endfor; ?>
		</div>
	</div>
	<div class="marquee__band marquee__band--b" data-marquee="-1">
		<div class="marquee__track">
			<?php for ( $i = 0; $i < 4; $i++ ) : ?>
				<span aria-hidden="true"><?php echo esc_html( $text ); ?>&nbsp;</span>
			<?php endfor; ?>
		</div>
	</div>
</section>
