<?php
/**
 * Flavor 커스텀 포스트 타입 + 기본 데이터.
 *
 * 관리자에서 Flavor 글을 하나도 만들지 않으면 테마에 포함된 15개 기본 맛이 그대로 노출됩니다.
 * Flavor 글을 등록하면 그 글들이 기본 데이터를 대체합니다.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** 카테고리 라벨 */
function oneam_flavor_categories() {
	return array(
		'ice'   => 'Ice',
		'fruit' => 'Fruity',
		'mint'  => 'Mint',
		'sweet' => 'Sweet',
	);
}

/** 테마에 포함된 기본 맛 데이터 (이미지: assets/img/flavors/{slug}.webp) */
function oneam_default_flavors() {
	return array(
		array( 'Grape Ice', 'grape-ice', 'fruit', '#8A3FFC', '#16C75A', 'Juicy purple grape, finished with a cold snap.' ),
		array( 'Blue Razz Ice', 'blue-razz-ice', 'ice', '#00C2A8', '#1E5BFF', 'Electric blue raspberry over crushed ice.' ),
		array( 'Watermelon Ice', 'watermelon-ice', 'fruit', '#FF2E4D', '#2BD45A', 'Summer-ripe watermelon, iced to the core.' ),
		array( 'Lemon Lime Ice', 'lemon-lime-ice', 'fruit', '#1FD65A', '#D7F21A', 'Zesty lemon and lime, bright and fizzy.' ),
		array( 'Raspberry Dragonfruit Ice', 'raspberry-dragonfruit-ice', 'fruit', '#B026FF', '#FF1464', 'Tart raspberry meets exotic dragonfruit.' ),
		array( 'Miami Mint', 'miami-mint', 'mint', '#12D16B', '#19C6E6', 'Sweet spearmint with a sunny coastal chill.' ),
		array( 'Double Mint', 'double-mint', 'mint', '#2F6BFF', '#12D16B', 'Twice the mint. Twice the freeze.' ),
		array( 'Classic Ice', 'classic-ice', 'ice', '#FF2A2A', '#FF8A7A', 'Clean, crisp, and ice-cold — the original.' ),
		array( 'Red Classic Ice', 'red-classic-ice', 'ice', '#E0115F', '#3D5BFF', 'A bold red twist on the classic.' ),
		array( 'Milky Ice', 'milky-ice', 'sweet', '#1FB8E6', '#F58CC0', 'Creamy, mellow milk with a soft chill.' ),
		array( 'Cheesy Strawberry', 'cheesy-strawberry', 'sweet', '#FF3B5C', '#FFB321', 'Strawberry cheesecake in a single breath.' ),
		array( 'Juicy Peach Ice', 'juicy-peach-ice', 'fruit', '#FF8A4D', '#FFC857', 'Soft, sweet peach nectar on ice.' ),
		array( 'Guava Ice', 'guava-ice', 'fruit', '#FF5C8A', '#E8174B', 'Tropical pink guava, cool and smooth.' ),
		array( 'Banana Ice', 'banana-ice', 'sweet', '#FFD60A', '#FF9F1C', 'Ripe banana, creamy and chilled.' ),
		array( 'Double Birch Ice', 'double-birch-ice', 'ice', '#F2B705', '#A0461F', 'Woody birch sweetness with a double chill.' ),
	);
}

/**
 * 모든 맛을 통일된 배열로 반환.
 *
 * @return array[] name, slug, cat, c1, c2, img, desc, url
 */
function oneam_get_flavors() {
	static $cache = null;
	if ( null !== $cache ) {
		return $cache;
	}

	$flavors = array();
	$posts   = get_posts(
		array(
			'post_type'      => 'oneam_flavor',
			'posts_per_page' => -1,
			'orderby'        => array( 'menu_order' => 'ASC', 'date' => 'ASC' ),
			'post_status'    => 'publish',
		)
	);

	foreach ( $posts as $p ) {
		$img = get_the_post_thumbnail_url( $p, 'large' );
		$flavors[] = array(
			'name' => get_the_title( $p ),
			'slug' => $p->post_name,
			'cat'  => get_post_meta( $p->ID, '_oneam_cat', true ) ?: 'ice',
			'c1'   => get_post_meta( $p->ID, '_oneam_c1', true ) ?: '#FF2E4D',
			'c2'   => get_post_meta( $p->ID, '_oneam_c2', true ) ?: '#2F6BFF',
			'img'  => $img ?: oneam_asset( 'img/flavors/' . $p->post_name . '.webp' ),
			'desc' => get_the_excerpt( $p ),
			'url'  => get_permalink( $p ),
		);
	}

	if ( empty( $flavors ) ) {
		foreach ( oneam_default_flavors() as $f ) {
			$flavors[] = array(
				'name' => $f[0],
				'slug' => $f[1],
				'cat'  => $f[2],
				'c1'   => $f[3],
				'c2'   => $f[4],
				'img'  => oneam_asset( 'img/flavors/' . $f[1] . '.webp' ),
				'desc' => $f[5],
				'url'  => home_url( '/#flavors' ),
			);
		}
	}

	$cache = $flavors;
	return $cache;
}

/** 맛 하나의 인라인 CSS 변수 */
function oneam_flavor_style( $f ) {
	return sprintf( '--c1:%s;--c2:%s;', esc_attr( $f['c1'] ), esc_attr( $f['c2'] ) );
}

/* ---------------------------------------------------------------------------
 * CPT
 * ------------------------------------------------------------------------- */
add_action(
	'init',
	function () {
		register_post_type(
			'oneam_flavor',
			array(
				'labels'       => array(
					'name'          => 'Flavors',
					'singular_name' => 'Flavor',
					'add_new_item'  => '새 맛 추가',
					'edit_item'     => '맛 편집',
				),
				'public'       => true,
				'has_archive'  => 'flavors',
				'rewrite'      => array( 'slug' => 'flavor' ),
				'menu_icon'    => 'dashicons-art',
				'supports'     => array( 'title', 'editor', 'excerpt', 'thumbnail', 'page-attributes' ),
				'show_in_rest' => true,
			)
		);
	}
);

add_action(
	'add_meta_boxes',
	function () {
		add_meta_box( 'oneam_flavor_meta', '맛 컬러 / 카테고리', 'oneam_flavor_meta_box', 'oneam_flavor', 'side' );
	}
);

function oneam_flavor_meta_box( $post ) {
	wp_nonce_field( 'oneam_flavor_meta', 'oneam_flavor_nonce' );
	$c1  = get_post_meta( $post->ID, '_oneam_c1', true ) ?: '#FF2E4D';
	$c2  = get_post_meta( $post->ID, '_oneam_c2', true ) ?: '#2F6BFF';
	$cat = get_post_meta( $post->ID, '_oneam_cat', true ) ?: 'ice';
	?>
	<p><label>메인 컬러<br><input type="color" name="oneam_c1" value="<?php echo esc_attr( $c1 ); ?>"></label></p>
	<p><label>서브 컬러<br><input type="color" name="oneam_c2" value="<?php echo esc_attr( $c2 ); ?>"></label></p>
	<p><label>카테고리<br>
		<select name="oneam_cat">
			<?php foreach ( oneam_flavor_categories() as $k => $label ) : ?>
				<option value="<?php echo esc_attr( $k ); ?>" <?php selected( $cat, $k ); ?>><?php echo esc_html( $label ); ?></option>
			<?php endforeach; ?>
		</select></label></p>
	<p class="description">대표 이미지 = 배경이 투명한 제품 PNG/WebP (세로형)</p>
	<?php
}

add_action(
	'save_post_oneam_flavor',
	function ( $post_id ) {
		if ( ! isset( $_POST['oneam_flavor_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['oneam_flavor_nonce'] ) ), 'oneam_flavor_meta' ) ) {
			return;
		}
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}
		foreach ( array( 'c1', 'c2' ) as $k ) {
			if ( isset( $_POST[ 'oneam_' . $k ] ) ) {
				update_post_meta( $post_id, '_oneam_' . $k, sanitize_hex_color( wp_unslash( $_POST[ 'oneam_' . $k ] ) ) );
			}
		}
		if ( isset( $_POST['oneam_cat'] ) && array_key_exists( wp_unslash( $_POST['oneam_cat'] ), oneam_flavor_categories() ) ) {
			update_post_meta( $post_id, '_oneam_cat', sanitize_key( wp_unslash( $_POST['oneam_cat'] ) ) );
		}
	}
);
