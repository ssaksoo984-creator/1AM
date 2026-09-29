<?php
/**
 * 브랜드 영상 — 스크롤하면 카드에서 화면 전체로 커집니다.
 * 사용자 정의하기 > 1AM 설정 > 영상 에서 MP4 업로드(권장) 또는 YouTube 주소 입력.
 */
$mp4    = oneam_opt( 'oneam_video_mp4' );
$yt     = oneam_opt( 'oneam_video_youtube' );
$poster = oneam_opt( 'oneam_video_poster' );
$yt_id  = '';
if ( $yt && preg_match( '~(?:youtu\.be/|v=|embed/|shorts/)([A-Za-z0-9_-]{11})~', $yt, $m ) ) {
	$yt_id = $m[1];
}
$placeholder = ! $mp4 && ! $yt_id;
if ( $placeholder && ! apply_filters( 'oneam_video_placeholder', false ) ) {
	return;
}
?>
<section class="film" id="film" data-header="light">
	<div class="film__pin">
		<div class="film__frame">
			<?php if ( $mp4 ) : ?>
				<video class="film__media" src="<?php echo esc_url( $mp4 ); ?>" <?php echo $poster ? 'poster="' . esc_url( $poster ) . '"' : ''; ?> autoplay muted loop playsinline preload="metadata"></video>
			<?php elseif ( $yt_id ) : ?>
				<iframe class="film__media film__media--yt" src="https://www.youtube-nocookie.com/embed/<?php echo esc_attr( $yt_id ); ?>?autoplay=1&amp;mute=1&amp;loop=1&amp;playlist=<?php echo esc_attr( $yt_id ); ?>&amp;controls=0&amp;playsinline=1&amp;rel=0&amp;modestbranding=1" title="1AM brand film" allow="autoplay; encrypted-media; picture-in-picture" loading="lazy"></iframe>
			<?php else : ?>
				<div class="film__media film__media--empty">
					<span class="film__play" aria-hidden="true"></span>
					<span class="film__note">영상 자리 · 사용자 정의하기 &gt; 1AM 설정 &gt; 영상</span>
				</div>
			<?php endif; ?>
			<div class="film__shade" aria-hidden="true"></div>
			<h2 class="film__title"><?php echo esc_html( oneam_opt( 'oneam_video_title' ) ); ?></h2>
			<?php if ( $mp4 ) : ?>
				<button type="button" class="film__sound" aria-pressed="false" data-magnetic>Sound off</button>
			<?php endif; ?>
		</div>
	</div>
</section>
