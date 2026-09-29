<?php
// 워드프레스 없이 테마를 정적 HTML 로 렌더링하기 위한 최소 shim (미리보기 용)
define('ABSPATH', '/');
// 사용법: php tools/preview-shim.php 1am-vivid front-page.php > preview/index.html
$THEME = realpath($argv[1] ?? __DIR__.'/../1am-vivid'); $TPL = $argv[2] ?? 'front-page.php';
$GLOBALS['styles']=[]; $GLOBALS['scripts']=[]; $GLOBALS['l10n']=[]; $GLOBALS['actions']=[];
function add_action($h,$cb,$p=10,$a=1){ $GLOBALS['actions'][$h][]=$cb; }
function add_filter(){ }
function apply_filters($h,$v){ return $h==='oneam_video_placeholder' ? true : $v; }
function is_user_logged_in(){ return false; } function wp_login_url(){ return './#login'; } function esc_textarea($s){ return htmlspecialchars($s); }
function do_action($h){ foreach($GLOBALS['actions'][$h]??[] as $cb) $cb(); }
function get_template_directory(){ return $GLOBALS['THEME']; }
function get_template_directory_uri(){ return '../1am-vivid'; }
function add_theme_support(){} function register_nav_menus(){} function register_post_type(){}
function get_theme_mod($k,$d=false){ return $d; }
function home_url($p=''){ return $p==='/'?'./':(strpos($p,'/#')===0?substr($p,1):'./'.ltrim($p,'/')); }
function esc_url($u){ return htmlspecialchars($u,ENT_QUOTES); } function esc_url_raw($u){return $u;}
function esc_attr($s){ return htmlspecialchars((string)$s,ENT_QUOTES); } function esc_html($s){ return htmlspecialchars((string)$s,ENT_QUOTES); }
function language_attributes(){ echo 'lang="ko"'; } function bloginfo($k){ echo 'UTF-8'; } function get_bloginfo($k){ return '1AM'; }
function body_class($c=''){ echo 'class="home '.$c.'"'; } function wp_body_open(){}
function has_custom_logo(){ return false; }
function wp_enqueue_style($h,$src){ $GLOBALS['styles'][]=$src; }
function wp_enqueue_script($h,$src){ $GLOBALS['scripts'][$h]=$src; }
function wp_localize_script($h,$n,$d){ $GLOBALS['l10n'][$n]=$d; }
function wp_head(){ do_action("wp_enqueue_scripts"); do_action("wp_head"); echo "<title>1AM — Slim HYBRID</title>\n"; foreach($GLOBALS['styles'] as $s) echo '<link rel="stylesheet" href="'.htmlspecialchars($s).'">'."\n"; }
function wp_footer(){ foreach($GLOBALS['l10n'] as $n=>$d) echo "<script>var $n = ".json_encode($d).";</script>\n"; foreach($GLOBALS['scripts'] as $s) echo '<script src="'.$s.'"></script>'."\n"; }
function wp_nav_menu($a){ call_user_func($a['fallback_cb']); }
function get_header(){ include get_template_directory().'/header.php'; }
function get_footer(){ include get_template_directory().'/footer.php'; }
function get_template_part($s,$n=null,$args=array()){ include get_template_directory().'/'.$s.'.php'; }
function get_posts(){ return []; }
function selected(){} function wp_nonce_field(){}
function sanitize_textarea_field($s){return $s;}
require $THEME.'/functions.php';
do_action('init'); do_action('after_setup_theme');
include $THEME.'/'.$TPL;
