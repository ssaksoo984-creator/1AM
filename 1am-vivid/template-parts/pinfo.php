<?php
/**
 * 상품 정보 (예전 slim-hybrid.html 의 pinfo) — args: product (oneam_get_products() 형식)
 */
$p = isset( $args['product'] ) ? $args['product'] : null;
if ( ! $p ) {
	return;
}
?>
<section class="pinfo" id="details" style="<?php echo oneam_flavor_style( $p ); // phpcs:ignore ?>">
	<div class="pinfo__copy">
		<?php if ( ! empty( $p['tag'] ) ) : ?>
			<p class="eyebrow"><?php echo esc_html( $p['tag'] ); ?></p>
		<?php endif; ?>
		<h1 class="pinfo__title"><?php echo esc_html( $p['name'] ); ?></h1>
		<?php if ( ! empty( $p['desc'] ) ) : ?>
			<p class="pinfo__text"><?php echo esc_html( $p['desc'] ); ?></p>
		<?php endif; ?>
	</div>
	<?php if ( ! empty( $p['specs'] ) ) : ?>
		<dl class="pinfo__specs">
			<?php foreach ( $p['specs'] as $k => $v ) : ?>
				<div><dt><?php echo esc_html( $k ); ?></dt><dd><?php echo esc_html( $v ); ?></dd></div>
			<?php endforeach; ?>
		</dl>
	<?php endif; ?>
</section>
