<?php
/**
 * 마지막 섹션 — 도매 가입/구매 유도
 */
$flavors = oneam_get_flavors();
$fan     = array_slice( $flavors, 0, 9 );
$mid     = ( count( $fan ) - 1 ) / 2;

// 제목 속 "1AM" 을 로고로 (Elementor 위젯에서 끄거나 다른 로고 이미지로 바꿀 수 있음)
$title = oneam_rich( oneam_opt( 'oneam_cta_title' ) );
if ( 'no' !== oneam_ctx( 'logo_on', 'yes' ) ) {
	$logo = oneam_ctx( 'logo', '' );
	if ( ! $logo ) {
		$logo = has_custom_logo() ? wp_get_attachment_image_url( get_theme_mod( 'custom_logo' ), 'full' ) : oneam_asset( 'img/logo-black.png' );
	}
	$mark  = sprintf( '<span class="cta__logo" role="img" aria-label="1AM" style="--logo:url(\'%s\')"></span>', esc_url( $logo ) );
	$title = preg_replace( '~(<em>)?1AM\.?(</em>)?~', $mark, $title, 1 );
}
?>
<section class="cta" id="wholesale">
	<div class="cta__fan" aria-hidden="true">
		<?php foreach ( $fan as $i => $f ) : ?>
			<img src="<?php echo esc_url( $f['img'] ); ?>" alt="" width="246" height="1400" loading="lazy" style="--o:<?php echo esc_attr( $i - $mid ); ?>">
		<?php endforeach; ?>
	</div>
	<h2 class="cta__title" data-fill><?php echo $title; // phpcs:ignore ?></h2>
	<p class="cta__text"><?php echo esc_html( oneam_opt( 'oneam_cta_text' ) ); ?></p>
	<div class="cta__btns">
		<?php oneam_member_cta( 'btn--xl' ); ?>
		<?php if ( 'guest' === oneam_member_state() ) : ?>
			<a class="btn btn--ghost btn--xl" href="<?php echo esc_url( oneam_login_url() ); ?>" data-magnetic><span>Log in</span></a>
		<?php endif; ?>
	</div>
	<p class="cta__note"><?php echo esc_html( oneam_ctx( 'note', 'Retailers only · ' . (int) oneam_opt( 'oneam_min_age' ) . '+ · Canada' ) ); ?></p>
</section>
