<?php
/**
 * 마지막 섹션 — 도매 가입/구매 유도
 */
$flavors = oneam_get_flavors();
$fan     = array_slice( $flavors, 0, 9 );
$mid     = ( count( $fan ) - 1 ) / 2;
?>
<section class="cta" id="wholesale">
	<div class="cta__fan" aria-hidden="true">
		<?php foreach ( $fan as $i => $f ) : ?>
			<img src="<?php echo esc_url( $f['img'] ); ?>" alt="" width="246" height="1400" loading="lazy" style="--o:<?php echo esc_attr( $i - $mid ); ?>">
		<?php endforeach; ?>
	</div>
	<h2 class="cta__title" data-fill><?php echo esc_html( oneam_opt( 'oneam_cta_title' ) ); ?></h2>
	<p class="cta__text"><?php echo esc_html( oneam_opt( 'oneam_cta_text' ) ); ?></p>
	<div class="cta__btns">
		<?php oneam_member_cta( 'btn--xl' ); ?>
		<?php if ( 'guest' === oneam_member_state() ) : ?>
			<a class="btn btn--ghost btn--xl" href="<?php echo esc_url( oneam_login_url() ); ?>" data-magnetic><span>Log in</span></a>
		<?php endif; ?>
	</div>
	<p class="cta__note">Retailers only · <?php echo (int) oneam_opt( 'oneam_min_age' ); ?>+ · Canada</p>
</section>
