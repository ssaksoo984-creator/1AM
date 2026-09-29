<?php
/**
 * About (short) + line-up animation:
 * one device in the centre fans out into a straight row of all flavours, then the row glides sideways.
 */
$flavors = oneam_get_flavors();
?>
<section class="about" id="about">
	<div class="about__intro">
		<p class="eyebrow">About 1AM</p>
		<h2 class="about__title" data-words><?php echo esc_html( oneam_opt( 'oneam_about_title' ) ); ?></h2>
		<p class="about__text"><?php echo esc_html( oneam_opt( 'oneam_about_text' ) ); ?></p>
		<a class="btn btn--round" href="<?php echo esc_url( oneam_opt( 'oneam_about_url' ) ); ?>"><span>About</span><span class="btn__arrow" aria-hidden="true">&rarr;</span></a>
	</div>

	<div class="row" id="row">
		<div class="row__stage">
			<ul class="row__track" aria-label="<?php echo esc_attr( count( $flavors ) . ' Slim HYBRID flavours' ); ?>">
				<?php foreach ( $flavors as $i => $f ) : ?>
					<li class="row__item" style="<?php echo oneam_flavor_style( $f ); // phpcs:ignore ?>">
						<img src="<?php echo esc_url( $f['img'] ); ?>" alt="<?php echo esc_attr( $f['name'] ); ?>" width="246" height="1400" loading="lazy">
						<span class="row__name"><?php echo esc_html( $f['name'] ); ?></span>
					</li>
				<?php endforeach; ?>
			</ul>
			<p class="row__caption"><span data-row-count><?php echo count( $flavors ); ?></span> flavours · Slim HYBRID 2ml</p>
		</div>
	</div>
</section>
