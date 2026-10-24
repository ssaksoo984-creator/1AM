<?php
/**
 * Work with us — 도매 신청 카드 + 영업팀 이메일 카드
 */
$email = oneam_ctx( 'email', oneam_opt( 'oneam_email' ) );
?>
<section class="work">
	<div class="work__head">
		<p class="eyebrow"><?php echo esc_html( oneam_ctx( 'eyebrow', 'Work with us' ) ); ?></p>
		<h2 class="section-title section-title--md" data-reveal><?php echo oneam_lines( oneam_ctx( 'title', "Let’s stock\n*your shelves.*" ) ); // phpcs:ignore ?></h2>
	</div>
	<div class="work__cards">
		<article class="work__card work__card--apply">
			<span class="work__badge"><?php echo esc_html( oneam_ctx( 'apply_label', 'Retail account' ) ); ?></span>
			<h3><?php echo esc_html( oneam_ctx( 'apply_title', 'Own or operate a vape store?' ) ); ?></h3>
			<p><?php echo esc_html( oneam_ctx( 'apply_text', 'Apply for a 1AM retailer account. Once your business is verified and approved, you can view pricing and place orders online.' ) ); ?></p>
			<?php oneam_member_cta( 'btn--light', 'Apply for a retailer account' ); ?>
		</article>
		<a class="work__card work__card--mail" href="mailto:<?php echo esc_attr( $email ); ?>">
			<span class="work__icon"><?php echo oneam_icon( 'mail' ); // phpcs:ignore ?></span>
			<h3><?php echo esc_html( oneam_ctx( 'mail_title', 'Talk to our sales team' ) ); ?></h3>
			<p><?php echo esc_html( oneam_ctx( 'mail_text', 'Questions about accounts, products, excise stamps or orders? We are happy to help.' ) ); ?></p>
			<span class="work__email"><?php echo esc_html( $email ); ?> <?php echo oneam_icon( 'arrow' ); // phpcs:ignore ?></span>
		</a>
	</div>
</section>
