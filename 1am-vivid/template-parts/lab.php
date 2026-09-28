<?php
/**
 * Flavor Lab — 스크롤하면 가로로 넘어가는 핀 섹션. 앞 5개 맛이 노출됩니다.
 */
$flavors = array_slice( oneam_get_flavors(), 0, 5 );
?>
<section class="lab" id="lab">
	<div class="lab__bg"></div>
	<div class="lab__head">
		<p class="eyebrow">Flavor Lab</p>
		<p class="lab__hint">Keep scrolling &rarr;</p>
	</div>
	<div class="lab__track">
		<?php foreach ( $flavors as $i => $f ) : ?>
			<article class="lab__panel" style="<?php echo oneam_flavor_style( $f ); // phpcs:ignore ?>" data-c1="<?php echo esc_attr( $f['c1'] ); ?>" data-c2="<?php echo esc_attr( $f['c2'] ); ?>">
				<div class="lab__num"><?php echo esc_html( str_pad( (string) ( $i + 1 ), 2, '0', STR_PAD_LEFT ) ); ?></div>
				<div class="lab__blob" aria-hidden="true"></div>
				<img class="lab__img" src="<?php echo esc_url( $f['img'] ); ?>" alt="<?php echo esc_attr( $f['name'] ); ?>" width="246" height="1400" loading="lazy">
				<div class="lab__text">
					<h2 class="lab__name"><?php echo oneam_split( $f['name'] ); // phpcs:ignore ?></h2>
					<p class="lab__desc"><?php echo esc_html( $f['desc'] ); ?></p>
					<a class="btn btn--solid" href="<?php echo esc_url( $f['url'] ); ?>" data-magnetic><span>Taste it</span></a>
				</div>
			</article>
		<?php endforeach; ?>
	</div>
	<div class="lab__bar"><span></span></div>
</section>
