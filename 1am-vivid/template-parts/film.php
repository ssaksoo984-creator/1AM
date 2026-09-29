<?php
/**
 * Home intro — full-screen brand video with headline and wholesale button.
 * Video source (first match wins):
 *   1. Customize > 1AM Settings > Home video (MP4 upload or YouTube URL)
 *   2. Theme file assets/video/brand.mp4 (+ brand.jpg poster)
 *   3. Animated colour background with the product (no video yet)
 */
$mp4    = oneam_opt( 'oneam_video_mp4' );
$yt     = oneam_opt( 'oneam_video_youtube' );
$poster = oneam_opt( 'oneam_video_poster' );
$mp4_sm = '';
if ( ! $mp4 && file_exists( get_template_directory() . '/assets/video/brand.mp4' ) ) {
	$mp4 = oneam_asset( 'video/brand.mp4' );
	if ( file_exists( get_template_directory() . '/assets/video/brand-720.mp4' ) ) {
		$mp4_sm = oneam_asset( 'video/brand-720.mp4' );
	}
	if ( ! $poster && file_exists( get_template_directory() . '/assets/video/brand.jpg' ) ) {
		$poster = oneam_asset( 'video/brand.jpg' );
	}
}
$yt_id = '';
if ( ! $mp4 && $yt && preg_match( '~(?:youtu\.be/|v=|embed/|shorts/)([A-Za-z0-9_-]{11})~', $yt, $m ) ) {
	$yt_id = $m[1];
}
$first = oneam_get_flavors()[0];
$title = explode( "\n", oneam_opt( 'oneam_intro_title' ) );
?>
<section class="film<?php echo $mp4 ? ' film--video' : ''; ?>" id="top" data-header="light" style="<?php echo oneam_flavor_style( $first ); // phpcs:ignore ?>">
	<div class="film__pin">
		<div class="film__frame">
			<?php if ( $mp4 ) : ?>
				<video class="film__media" <?php echo $poster ? 'poster="' . esc_url( $poster ) . '"' : ''; ?> autoplay muted loop playsinline preload="auto">
					<?php if ( $mp4_sm ) : ?>
						<source src="<?php echo esc_url( $mp4_sm ); ?>" type="video/mp4" media="(max-width: 800px)">
					<?php endif; ?>
					<source src="<?php echo esc_url( $mp4 ); ?>" type="video/mp4">
				</video>
			<?php elseif ( $yt_id ) : ?>
				<iframe class="film__media film__media--yt" src="https://www.youtube-nocookie.com/embed/<?php echo esc_attr( $yt_id ); ?>?autoplay=1&amp;mute=1&amp;loop=1&amp;playlist=<?php echo esc_attr( $yt_id ); ?>&amp;controls=0&amp;playsinline=1&amp;rel=0&amp;modestbranding=1" title="1AM brand film" allow="autoplay; encrypted-media; picture-in-picture"></iframe>
			<?php else : ?>
				<div class="film__media film__media--empty" aria-hidden="true">
					<img src="<?php echo esc_url( $first['img'] ); ?>" alt="" width="246" height="1400">
				</div>
			<?php endif; ?>
			<div class="film__shade" aria-hidden="true"></div>

			<div class="film__copy">
				<p class="film__kicker"><?php echo esc_html( oneam_opt( 'oneam_intro_kicker' ) ); ?></p>
				<h1 class="film__title">
					<?php foreach ( $title as $line ) : ?>
						<span class="line"><span><?php echo esc_html( $line ); ?></span></span>
					<?php endforeach; ?>
				</h1>
				<p class="film__text"><?php echo esc_html( oneam_opt( 'oneam_intro_text' ) ); ?></p>
				<div class="film__btns">
					<?php oneam_member_cta(); ?>
					<a class="btn btn--glass" href="#products"><span>View products</span></a>
				</div>
			</div>

			<?php if ( $mp4 ) : ?>
				<button type="button" class="film__sound" aria-pressed="false">Sound off</button>
			<?php endif; ?>
		</div>
	</div>
</section>
