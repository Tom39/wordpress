<?php
/*
Plugin Name: WIX Plugin
Plugin URI: http://localhost/wordpress/wp-content/plugins/WIX/wixController.php
Description: WIX AuthorLeading Plugin.
Version: 0.0.1
Author: sakusa
Author URI: http://localhost/
Text Domain: WIX
*/

define( 'PatternFile', dirname( __FILE__ ) . '/WixPattern.txt' );
define( 'WixDecideFiles', dirname( __FILE__ ) . '/WIXDecideFiles/' );
define( 'wix_settings_css', plugins_url('/css/wixSetting.css', __FILE__) );
define( 'wix_decide_css', plugins_url('/css/wixDecide.css', __FILE__) );
define( 'wix_eternal_link_css', plugins_url('/css/wixEternalLink.css', __FILE__) );
define( 'popupwindow_css', plugins_url('/css/popupwindow.css', __FILE__) );
define( 'wix_settings_js', plugins_url('/js/wixSetting.js', __FILE__) );
define( 'wix_decide_js', plugins_url('/js/wixDecide.js', __FILE__) );
define( 'wix_decide_iframe_js', plugins_url('/js/wixDecide_iframe.js', __FILE__) );
define( 'wix_eternal_link_js', plugins_url('/js/wixEternalLink.js', __FILE__) );
define( 'popupwindow_js', plugins_url('/js/popupwindow-1.8.1.js', __FILE__) );


require_once( dirname( __FILE__ ) . '/newBody.php' );
require_once( dirname( __FILE__ ) . '/wixSetting.php' );
require_once( dirname( __FILE__ ) . '/wixSetting_core.php' );
require_once( dirname( __FILE__ ) . '/wixDecide.php' );
require_once( dirname( __FILE__ ) . '/wixSimilarity.php' );
require_once( dirname( __FILE__ ) . '/wix_filecontents_autocreate.php' );





add_action( 'admin_init', 'wix_admin_init' );


function wix_admin_init() {
	wp_register_style( 'wix-settings-css', wix_settings_css );
    wp_register_style( 'popupwindow-css', popupwindow_css );
    wp_register_style( 'wix-decide-css', wix_decide_css );
	wp_register_script( 'wix-settings-js', wix_settings_js );
	wp_register_script( 'wix-decide-js', wix_decide_js );
    wp_register_script( 'popupwindow-js', popupwindow_js );

	add_action( 'admin_enqueue_scripts', 'wix_admin_decide_scripts' );
}

//スクリプトの読み込み
function wix_admin_settings_scripts() {
	wp_enqueue_style( 'wix-settings-css', wix_settings_css, array() );
	wp_enqueue_script( 'wix-settings-js', wix_settings_js, array('jquery') );

	//jQuery UI
	global $wp_scripts;
    $ui = $wp_scripts->query('jquery-ui-core');
    wp_enqueue_style(
        'jquery-ui',
        "//ajax.googleapis.com/ajax/libs/jqueryui/{$ui->ver}/themes/smoothness/jquery-ui.min.css",
        false,
        null
    );
    wp_enqueue_script(
        'jquery-ui',
        "//ajax.googleapis.com/ajax/libs/jqueryui/{$ui->ver}/jquery-ui.min.js",
        array('jquery')
    );
}

function wix_admin_decide_scripts($hook_suffix) {
    $post_pages = array('post.php', 'post-new.php');

    if ( in_array($hook_suffix, $post_pages) ) {
        wp_enqueue_style( 'wix-decide-css', wix_decide_css, array() );
        wp_enqueue_style( 'popupwindow-css', popupwindow_css, array() );
        wp_enqueue_script( 'wix-decide-js', wix_decide_js );
        wp_enqueue_script( 'popupwindow-js', popupwindow_js );

        //ManualDecideを行うかどうか(wixDecideボタンを出現させるか否か)
        $str = "<script type=\"text/javascript\"> var manual_decideFlag = '%s' </script>";
        $manual_decideFlag = get_option('manual_decideFlag');
        printf($str, $manual_decideFlag);
    }

}

//外部リンクの明示化用ファイル読み込み
add_action( 'wp_enqueue_scripts', 'wix_eternal_link_scripts' );
function wix_eternal_link_scripts() {
    wp_enqueue_style( 'wix-eternal-link-css', wix_eternal_link_css, array() );
    wp_enqueue_script( 'wix-eternal-link-js', wix_eternal_link_js, array( 'jquery' ) );
}



// add_action( 'wp_head', 'wix_decide_popup_css' );
function wix_decide_popup_css() {
    if ( is_preview() == true ) {
        wp_register_style( 'wix-decide-css', wix_decide_css );
        wp_enqueue_style( 'wix-decide-css', wix_decide_css, array() );
    }
}





?>