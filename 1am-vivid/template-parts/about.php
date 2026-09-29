<?php
/**
 * 회사 간략 소개 (견적서: 메인 > 회사 간략 소개)
 */
$products = oneam_get_products();
$flavors  = oneam_get_flavors();
$live     = count( array_filter( $products, function ( $p ) { return 'available' === $p['status']; } ) );
$stats    = array(
	array( count( $products ), 'Product lines', $live . ' available · ' . ( count( $products ) - $live ) . ' coming soon' ),
	array( count( $flavors ), 'Flavours', 'Slim HYBRID 2ml' ),
	array( (int) oneam_opt( 'oneam_min_age' ) . '+', 'Retailers only', 'Approved wholesale accounts' ),
);
?>
<section class="about" id="about">
	<div class="about__grid">
		<p class="eyebrow about__eyebrow">About 1AM</p>
		<h2 class="about__title" data-words><?php echo esc_html( oneam_opt( 'oneam_about_title' ) ); ?></h2>
		<div class="about__body">
			<p><?php echo esc_html( oneam_opt( 'oneam_about_text' ) ); ?></p>
			<a class="link-arrow" href="<?php echo esc_url( oneam_opt( 'oneam_about_url' ) ); ?>">About us <span>&rarr;</span></a>
		</div>
	</div>
	<dl class="about__stats">
		<?php foreach ( $stats as $s ) : ?>
			<div class="stat">
				<dt><?php echo esc_html( $s[1] ); ?></dt>
				<dd>
					<strong data-count="<?php echo esc_attr( (int) $s[0] ); ?>"><?php echo esc_html( $s[0] ); ?></strong>
					<span><?php echo esc_html( $s[2] ); ?></span>
				</dd>
			</div>
		<?php endforeach; ?>
	</dl>
</section>
