<?php
/**
 * 1AM Elementor 위젯 (Elementor 가 켜져 있을 때만 불러옴)
 * 위젯 설정은 inc/elementor.php 의 oneam_elementor_widgets() 에 있음.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Elementor\Controls_Manager;
use Elementor\Widget_Base;

abstract class OneAM_El_Widget extends Widget_Base {

	/** oneam_elementor_widgets() 의 키 */
	protected static $key = '';

	protected function cfg() {
		$all = oneam_elementor_widgets();
		return $all[ static::$key ];
	}

	public function get_name() {
		return 'oneam-' . static::$key;
	}

	public function get_title() {
		return '1AM · ' . $this->cfg()['title'];
	}

	public function get_icon() {
		return $this->cfg()['icon'];
	}

	public function get_categories() {
		return array( 'oneam' );
	}

	public function get_keywords() {
		return array( '1am', 'oneam', static::$key );
	}

	/** 우리 타입 이름 → Elementor 컨트롤 */
	protected function control( $c ) {
		$map  = array(
			'text'     => Controls_Manager::TEXT,
			'textarea' => Controls_Manager::TEXTAREA,
			'url'      => Controls_Manager::URL,
			'image'    => Controls_Manager::MEDIA,
			'video'    => Controls_Manager::MEDIA,
			'color'    => Controls_Manager::COLOR,
			'repeater' => Controls_Manager::REPEATER,
		);
		$type = $c['type'];
		$out  = array(
			'label' => $c['label'],
			'type'  => $map[ $type ],
		);
		if ( isset( $c['description'] ) ) {
			$out['description'] = $c['description'];
		}
		if ( 'textarea' === $type ) {
			$out['rows'] = 3;
		}
		if ( 'url' === $type ) {
			$out['default'] = array( 'url' => isset( $c['default'] ) ? $c['default'] : '' );
		} elseif ( 'video' === $type ) {
			$out['media_types'] = array( 'video' );
		} elseif ( 'repeater' === $type ) {
			$fields = array();
			foreach ( $c['fields'] as $name => $f ) {
				$fields[] = array_merge( array( 'name' => $name ), $this->control( $f ) );
			}
			$first             = array_keys( $c['fields'] )[0];
			$out['fields']      = $fields;
			$out['title_field'] = '{{{ ' . $first . ' }}}';
			$out['default']     = $c['default'];
		} elseif ( isset( $c['default'] ) && 'image' !== $type ) {
			$out['default'] = $c['default'];
		}
		return $out;
	}

	protected function register_controls() {
		$cfg = $this->cfg();
		$this->start_controls_section( 'oneam_content', array( 'label' => $cfg['title'] ) );
		if ( ! empty( $cfg['note'] ) ) {
			$this->add_control(
				'oneam_note',
				array(
					'type'            => Controls_Manager::RAW_HTML,
					'raw'             => esc_html( $cfg['note'] ),
					'content_classes' => 'elementor-descriptor',
				)
			);
		}
		foreach ( $cfg['controls'] as $id => $c ) {
			$this->add_control( $id, $this->control( $c ) );
		}
		$this->end_controls_section();
	}

	protected function render() {
		if ( 'signup' === static::$key ) {
			echo oneam_wholesale_form(); // phpcs:ignore WordPress.Security.EscapeOutput
			return;
		}
		$cfg  = $this->cfg();
		$s    = $this->get_settings_for_display();
		$ctx  = array();
		$args = array();
		foreach ( $cfg['controls'] as $id => $c ) {
			$v = isset( $s[ $id ] ) ? $s[ $id ] : '';
			if ( is_array( $v ) && array_key_exists( 'url', $v ) ) {
				$v = (string) $v['url']; // 이미지 · 영상 · 링크
			} elseif ( 'repeater' === $c['type'] ) {
				$v = array_values( array_filter( (array) $v, 'is_array' ) );
			}
			if ( ! empty( $c['arg'] ) ) {
				if ( '' !== $v ) {
					$args[ $id ] = $v;
				}
			} else {
				$ctx[ $id ] = $v;
			}
		}
		oneam_render_part( $cfg['part'], $ctx, $args );
	}
}

// 위젯마다 클래스 하나 (이름만 다름)
class OneAM_El_Film extends OneAM_El_Widget { protected static $key = 'film'; }
class OneAM_El_About extends OneAM_El_Widget { protected static $key = 'about'; }
class OneAM_El_Lineup extends OneAM_El_Widget { protected static $key = 'lineup'; }
class OneAM_El_Device extends OneAM_El_Widget { protected static $key = 'device'; }
class OneAM_El_Cta extends OneAM_El_Widget { protected static $key = 'cta'; }
class OneAM_El_Benefits extends OneAM_El_Widget { protected static $key = 'benefits'; }
class OneAM_El_Steps extends OneAM_El_Widget { protected static $key = 'steps'; }
class OneAM_El_Faq extends OneAM_El_Widget { protected static $key = 'faq'; }
class OneAM_El_Flavors extends OneAM_El_Widget { protected static $key = 'flavors'; }
class OneAM_El_Hero extends OneAM_El_Widget { protected static $key = 'hero'; }
class OneAM_El_Signup extends OneAM_El_Widget { protected static $key = 'signup'; }
