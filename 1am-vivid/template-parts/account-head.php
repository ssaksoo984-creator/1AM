<?php
/**
 * 내 계정 상단 (로그인 후): 큰 컬러 제목 대신 작은 인사 + 승인 상태
 */
$oneam_user  = wp_get_current_user();
$oneam_state = oneam_member_state();
$oneam_prov  = oneam_customer_province();
$oneam_store = get_user_meta( $oneam_user->ID, 'oneam_business_name', true ) ?: get_user_meta( $oneam_user->ID, 'billing_company', true );
$oneam_name  = $oneam_user->first_name ?: $oneam_user->display_name;
?>
<section class="acct-head">
	<div class="acct-head__text">
		<p class="eyebrow">My account</p>
		<h1 class="acct-head__title">Hi, <?php echo esc_html( $oneam_name ); ?></h1>
		<?php if ( $oneam_store ) : ?>
			<p class="acct-head__store"><?php echo esc_html( $oneam_store ); ?></p>
		<?php endif; ?>
	</div>
	<div class="acct-head__meta">
		<?php if ( 'approved' === $oneam_state ) : ?>
			<span class="acct__pill is-ok">Approved retailer</span>
			<?php if ( function_exists( 'oneam_user_tier' ) && oneam_user_tier() ) : ?>
				<?php echo oneam_tier_badge( oneam_user_tier(), true ); // phpcs:ignore ?>
			<?php endif; ?>
		<?php else : ?>
			<span class="acct__pill is-wait">Application under review</span>
		<?php endif; ?>
		<?php if ( $oneam_prov ) : ?>
			<span class="acct__pill"><?php echo esc_html( oneam_provinces()[ $oneam_prov ] ?? $oneam_prov ); ?></span>
		<?php endif; ?>
		<span class="acct__pill"><?php echo esc_html( $oneam_user->user_email ); ?></span>
	</div>
</section>
