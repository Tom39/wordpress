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
define( 'wix_style', plugins_url('/css/wixSetting.css', __FILE__) );
define( 'popupwindow_css', plugins_url('/css/popupwindow.css', __FILE__) );
define( 'wix_settings_js', plugins_url('/js/wixSetting.js', __FILE__) );
define( 'wix_decide_js', plugins_url('/js/wixDecide.js', __FILE__) );
define( 'popupwindow_js', plugins_url('/js/popupwindow-1.8.1.js', __FILE__) );


require_once( dirname( __FILE__ ) . '/newBody.php' );
require_once( dirname( __FILE__ ) . '/wixSetting.php' );
require_once( dirname( __FILE__ ) . '/wixDecide.php' );






add_action( 'admin_init', 'wix_admin_init' );


function wix_admin_init() {
	wp_register_style( 'wix-style', wix_style );
    wp_register_style( 'popupwindow-css', popupwindow_css );
	wp_register_script( 'wix-settings-js', wix_settings_js );
	wp_register_script( 'wix-decide-js', wix_decide_js );
    wp_register_script( 'popupwindow-js', popupwindow_js );

	add_action( 'admin_enqueue_scripts', 'wix_admin_decide_scripts' );
}

//スクリプトの読み込み
function wix_admin_settings_scripts() {
	wp_enqueue_style( 'wix-style', wix_style, array() );
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

function wix_admin_decide_scripts() {
    wp_enqueue_style( 'popupwindow-css', popupwindow_css, array() );
	wp_enqueue_script( 'wix-decide-js', wix_decide_js );
    wp_enqueue_script( 'popupwindow-js', popupwindow_js );
}



?>