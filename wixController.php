<?php
/*
Plugin Name: Web Index
Plugin URI: http://www.db.ics.keio.ac.jp
Description: Auto Linking & Creating Web Contents Support Plugin.
Version: 0.0.1
Author: sakusa
Author URI: http://www.db.ics.keio.ac.jp
Text Domain: WIX
*/

define( 'PatternFile', dirname( __FILE__ ) . '/WixPattern.txt' );
define( 'WixDecideFiles', dirname( __FILE__ ) . '/WIXDecideFiles/' );
define( 'wiximage', dirname( __FILE__ ) . '/css/images/' );
define( 'wix_settings_css', plugins_url('/css/wixSetting.css', __FILE__) );
define( 'wix_detail_settings_css', plugins_url('/css/wixDetailSetting.css', __FILE__) );
define( 'wix_decide_css', plugins_url('/css/wixDecide.css', __FILE__) );
define( 'wix_eternal_link_css', plugins_url('/css/wixEternalLink.css', __FILE__) );
define( 'popupwindow_css', plugins_url('/css/popupwindow.css', __FILE__) );
define( 'wix_settings_js', plugins_url('/js/wixSetting.js', __FILE__) );
define( 'wix_detail_settings_js', plugins_url('/js/wixDetailSetting.js', __FILE__) );
define( 'wix_decide_js', plugins_url('/js/wixDecide.js', __FILE__) );
define( 'wix_decide_iframe_js', plugins_url('/js/wixDecide_iframe.js', __FILE__) );
define( 'wix_eternal_link_js', plugins_url('/js/wixEternalLink.js', __FILE__) );
define( 'popupwindow_js', plugins_url('/js/popupwindow-1.8.1.js', __FILE__) );
define( 'wix_favicon', plugins_url('/favicon.ico', __FILE__) );

require_once( dirname( __FILE__ ) . '/newBody.php' );
require_once( dirname( __FILE__ ) . '/wixSetting.php' );
require_once( dirname( __FILE__ ) . '/wixDecide.php' );
require_once( dirname( __FILE__ ) . '/wixSimilarity.php' );
require_once( dirname( __FILE__ ) . '/wixAutocreate.php' );
require_once( dirname( __FILE__ ) . '/wixEvaluation.php' );

add_action( 'admin_init', 'wix_admin_init' );
function wix_admin_init() {
	wp_register_style( 'wix-settings-css', wix_settings_css );
    wp_register_style( 'wix-detail-settings-css', wix_detail_settings_css );
    wp_register_style( 'popupwindow-css', popupwindow_css );
    wp_register_style( 'wix-decide-css', wix_decide_css );
	wp_register_script( 'wix-settings-js', wix_settings_js );
    wp_register_script( 'wix-detail-settings-js', wix_detail_settings_js );
	wp_register_script( 'wix-decide-js', wix_decide_js );
    wp_register_script( 'popupwindow-js', popupwindow_js );
    wp_register_style( 'wix-favicon', wix_favicon );

	add_action( 'admin_enqueue_scripts', 'wix_admin_decide_scripts' );
    add_action( 'admin_enqueue_scripts', 'wix_admin_setting_scripts' );
}

//スクリプトの読み込み
function wix_admin_settings_scripts() {
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
    }

}

function wix_admin_setting_scripts($hook_suffix) {
    $post_pages = array(
                        'toplevel_page_wix-admin-settings', 
                        'wix-admin-settings_page_wix-admin-similarity',
                        );
    $post_pages2 = array(
                        'wix-admin-settings_page_wix-detail-settings',
                        );

    if ( in_array($hook_suffix, $post_pages) ) {
        wp_enqueue_style( 'wix-settings-css', wix_settings_css, array() );
        wp_enqueue_script( 'wix-settings-js', wix_settings_js, array('jquery') );
        wp_enqueue_style( 'popupwindow-css', popupwindow_css, array() );
        wp_enqueue_script( 'popupwindow-js', popupwindow_js );
        echo "<link rel=\"shortcut icon\" href=\"" . wix_favicon . "\">;";
    } else if ( in_array($hook_suffix, $post_pages2) ) {
        wp_enqueue_style( 'wix-detail-settings-css', wix_detail_settings_css, array() );
        wp_enqueue_script( 'wix-detail-settings-js', wix_detail_settings_js, array('jquery') );
        wp_enqueue_style( 'popupwindow-css', popupwindow_css, array() );
        wp_enqueue_script( 'popupwindow-js', popupwindow_js );
        wp_enqueue_script( 'wix-decide-js', wix_decide_js );
        echo "<link rel=\"shortcut icon\" href=\"" . wix_favicon . "\">;";
    }
}

add_action( 'wp_enqueue_scripts', 'wix_eternal_link_scripts' );
function wix_eternal_link_scripts($hook_suffix) {
    wp_enqueue_style( 'wix-eternal-link-css', wix_eternal_link_css, array() );
    wp_enqueue_script( 'wix-eternal-link-js', wix_eternal_link_js, array( 'jquery' ) );

    wp_enqueue_style( 'wix-decide-css', wix_decide_css, array() );
    wp_enqueue_script( 'wix-decide-iframe-js', wix_decide_iframe_js, array( 'jquery' ) );
}

//dumpのファイル書き込み
function dump( $filename, $obj ) {
    $filepath = '/Library/WebServer/Documents/wordpress/wp-content/plugins/WIX/' . $filename;

    ob_start();

    print_r($obj);

    $out = ob_get_contents();
    ob_end_clean();
    file_put_contents($filepath, $out . PHP_EOL, FILE_APPEND);
}

//--------------------------------------------------------------------------
//
//  プラグイン有効の際に行うオプションの追加
//
//--------------------------------------------------------------------------
register_activation_hook( __FILE__, 'wix_manual_decide_init' );
function wix_manual_decide_init() {
    // update_option( 'manual_decideFlag', 'true' );
    add_option( 'manual_decideFlag', 'true' );
}

//--------------------------------------------------------------------------
//
//  プラグイン有効の際に行うオプションの追加
//
//--------------------------------------------------------------------------
register_activation_hook( __FILE__, 'wix_table_create' );
function wix_table_create() {
    global $wpdb;
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

    $table_name = $wpdb->prefix . 'wix_keyword_similarity';
    $is_db_exists = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table_name));
    if ( $is_db_exists == $table_name ) return;
    $sql = "CREATE TABLE " . $table_name . " (
             doc_id bigint(20) UNSIGNED,
             keyword tinytext NOT NULL,
             tf float NOT NULL DEFAULT 0,
             idf float NOT NULL DEFAULT 0,
             df float NOT NULL DEFAULT 0,
             tf_idf float NOT NULL DEFAULT 0,
             tf_df float NOT NULL DEFAULT 0,
             bm25 float NOT NULL DEFAULT 0,
             textrank float NOT NULL DEFAULT 0,
             PRIMARY KEY(doc_id,keyword(255)),
             FOREIGN KEY (doc_id) REFERENCES " . $wpdb->prefix . 'posts' . "(ID)
             ON UPDATE CASCADE ON DELETE CASCADE
            ) CHARACTER SET utf8 COLLATE = utf8_general_ci;";
    dbDelta($sql);


    $table_name = $wpdb->prefix . 'wix_document_similarity';
    $is_db_exists = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table_name));
    if ( $is_db_exists == $table_name ) return;
    $sql = "CREATE TABLE " . $table_name . " (
             doc_id bigint(20) UNSIGNED,
             doc_id2 bigint(20) UNSIGNED,
             cos_similarity_tfidf float NOT NULL DEFAULT 0,
             cos_similarity_bm25 float NOT NULL DEFAULT 0,
             jaccard float NOT NULL DEFAULT 0,
             minhash float NOT NULL DEFAULT 0,
             PRIMARY KEY(doc_id,doc_id2),
             FOREIGN KEY (doc_id) REFERENCES " . $wpdb->prefix . 'posts' . "(ID)
              ON UPDATE CASCADE ON DELETE CASCADE,
             FOREIGN KEY (doc_id2) REFERENCES " . $wpdb->prefix . 'posts' . "(ID)
              ON UPDATE CASCADE ON DELETE CASCADE
            ) CHARACTER SET utf8 COLLATE = utf8_general_ci;";
    dbDelta($sql);


    $table_name = $wpdb->prefix . 'wixfilemeta';
    $is_db_exists = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table_name));
    if ( $is_db_exists == $table_name ) return;
    $sql = "CREATE TABLE " . $table_name . " (
            id bigint(20) NOT NULL, 
            keyword tinytext NOT NULL, 
            target_num mediumint(9) NOT NULL, 
            doc_num mediumint(9) NOT NULL, 
            PRIMARY KEY id (id)
            ) CHARACTER SET utf8 COLLATE = utf8_general_ci;";
    dbDelta($sql);


    $table_name = $wpdb->prefix . 'wixfile_targets';
    $is_db_exists = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table_name));
    if ( $is_db_exists == $table_name ) return;
    $sql = "CREATE TABLE " . $table_name . " (
            keyword_id bigint(20) NOT NULL, 
            target tinytext NOT NULL, 
            PRIMARY KEY(keyword_id, target(255)), 
            FOREIGN KEY (keyword_id) REFERENCES wp_wixfilemeta(id) ON UPDATE CASCADE ON DELETE CASCADE
            ) CHARACTER SET utf8 COLLATE = utf8_general_ci;";
    dbDelta($sql);


    $table_name = $wpdb->prefix . 'wixfilemeta_posts';
    $is_db_exists = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table_name));
    if ( $is_db_exists == $table_name ) return;
    $sql = "CREATE TABLE " . $table_name . " (
            keyword_id bigint(20) NOT NULL, 
            doc_id bigint(20) UNSIGNED NOT NULL, 
            context_info TEXT,
            time DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, 
            PRIMARY KEY(keyword_id, doc_id), 
            FOREIGN KEY (keyword_id) REFERENCES wp_wixfilemeta(id) 
                ON UPDATE CASCADE ON DELETE CASCADE, 
            FOREIGN KEY (doc_id) REFERENCES wp_posts(ID) 
                ON UPDATE CASCADE ON DELETE CASCADE
            ) CHARACTER SET utf8 COLLATE = utf8_general_ci;";
    dbDelta($sql);


    $table_name = $wpdb->prefix . 'wix_minhash';
    $is_db_exists = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table_name));
    if ( $is_db_exists == $table_name ) return;
    $sql = "CREATE TABLE " . $table_name . " (
            doc_id bigint(20) UNSIGNED NOT NULL, 
            minhash TEXT NOT NULL, 
            PRIMARY KEY(doc_id), 
            FOREIGN KEY (doc_id) REFERENCES wp_posts(ID)
             ON UPDATE CASCADE ON DELETE CASCADE
            ) CHARACTER SET utf8 COLLATE = utf8_general_ci;";
    dbDelta($sql);


    $table_name = $wpdb->prefix . 'wix_decidefile_index';
    $is_db_exists = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table_name));
    if ( $is_db_exists == $table_name ) return;
    $sql = "CREATE TABLE " . $table_name . " (
            dfile_id bigint(20) auto_increment, 
            doc_id bigint(20) UNSIGNED NOT NULL, 
            version bigint(20) NOT NULL, 
            time DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, 
            PRIMARY KEY(dfile_id), 
            FOREIGN KEY(doc_id) REFERENCES wp_posts(ID) 
                ON UPDATE CASCADE ON DELETE CASCADE
            ) CHARACTER SET utf8 COLLATE = utf8_general_ci;";
    dbDelta($sql);


    $table_name = $wpdb->prefix . 'wix_decidefile_history';
    $is_db_exists = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table_name));
    if ( $is_db_exists == $table_name ) return;
    $sql = "CREATE TABLE " . $table_name . " (
            dfile_id bigint(20) NOT NULL, 
            start bigint(20) NOT NULL, 
            end bigint(20) NOT NULL, 
            nextStart bigint(20) NOT NULL, 
            keyword_id bigint(20) NOT NULL, 
            target tinytext NOT NULL, 
            PRIMARY KEY(dfile_id, start), 
            FOREIGN KEY(dfile_id) REFERENCES wp_wix_decidefile_index(dfile_id) 
                ON UPDATE CASCADE ON DELETE CASCADE, 
            FOREIGN KEY(keyword_id) REFERENCES wp_wixfilemeta(id) 
                ON UPDATE CASCADE ON DELETE CASCADE
            ) CHARACTER SET utf8 COLLATE = utf8_general_ci;";
    dbDelta($sql);


    $table_name = $wpdb->prefix . 'wix_entry_ranking';
    $is_db_exists = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table_name));
    if ( $is_db_exists == $table_name ) return;
    $sql = "CREATE TABLE " . $table_name . " (
            doc_id bigint(20) UNSIGNED NOT NULL, 
            keyword_id bigint(20) NOT NULL, 
            target tinytext NOT NULL, 
            rank int(20) NOT NULL DEFAULT 0, 
            PRIMARY KEY(doc_id, keyword_id, target(100)), 
            FOREIGN KEY (doc_id) REFERENCES wp_posts(ID)
             ON UPDATE CASCADE ON DELETE CASCADE, 
            FOREIGN KEY (keyword_id) REFERENCES wp_wixfilemeta(id)
             ON UPDATE CASCADE ON DELETE CASCADE
            ) CHARACTER SET utf8 COLLATE = utf8_general_ci;";
    dbDelta($sql);



    $table_name = $wpdb->prefix . 'posts';
    $sql = "ALTER TABLE " . $table_name . " ADD COLUMN doc_length bigint DEFAULT 0 NOT NULL;";
    dbDelta($sql);

    $table_name = $wpdb->prefix . 'posts';
    $sql = "ALTER TABLE " . $table_name . " ADD COLUMN words_obj text NOT NULL;";
    dbDelta($sql);

    $table_name = $wpdb->prefix . 'posts';
    $sql = "ALTER TABLE " . $table_name . " ADD COLUMN eval_words text DEFAULT NULL;";
    dbDelta($sql);

    /**
        以下、評価実験用テーブル
    */
    $table_name = $wpdb->prefix . 'wix_eval_keyword_similarity';
    $is_db_exists = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table_name));
    if ( $is_db_exists == $table_name ) return;
    $sql = "CREATE TABLE " . $table_name . " (
             doc_id bigint(20) UNSIGNED,
             keyword tinytext NOT NULL,
             tf float NOT NULL DEFAULT 0,
             idf float NOT NULL DEFAULT 0,
             df float NOT NULL DEFAULT 0,
             tf_idf float NOT NULL DEFAULT 0,
             tf_df float NOT NULL DEFAULT 0,
             bm25 float NOT NULL DEFAULT 0,
             textrank float NOT NULL DEFAULT 0,
             PRIMARY KEY(doc_id,keyword(255)),
             FOREIGN KEY (doc_id) REFERENCES " . $wpdb->prefix . 'posts' . "(ID)
             ON UPDATE CASCADE ON DELETE CASCADE
            ) CHARACTER SET utf8 COLLATE = utf8_general_ci;";
    dbDelta($sql);

    $table_name = $wpdb->prefix . 'wix_eval_document_similarity';
    $is_db_exists = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table_name));
    if ( $is_db_exists == $table_name ) return;
    $sql = "CREATE TABLE " . $table_name . " (
             doc_id bigint(20) UNSIGNED,
             doc_id2 bigint(20) UNSIGNED,
             cos_similarity_tfidf float NOT NULL DEFAULT 0,
             cos_similarity_bm25 float NOT NULL DEFAULT 0,
             jaccard float NOT NULL DEFAULT 0,
             minhash float NOT NULL DEFAULT 0,
             PRIMARY KEY(doc_id,doc_id2),
             FOREIGN KEY (doc_id) REFERENCES " . $wpdb->prefix . 'posts' . "(ID)
              ON UPDATE CASCADE ON DELETE CASCADE,
             FOREIGN KEY (doc_id2) REFERENCES " . $wpdb->prefix . 'posts' . "(ID)
              ON UPDATE CASCADE ON DELETE CASCADE
            ) CHARACTER SET utf8 COLLATE = utf8_general_ci;";
    dbDelta($sql);

    $table_name = $wpdb->prefix . 'wix_eval_minhash';
    $is_db_exists = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table_name));
    if ( $is_db_exists == $table_name ) return;
    $sql = "CREATE TABLE " . $table_name . " (
            doc_id bigint(20) UNSIGNED NOT NULL, 
            minhash TEXT NOT NULL, 
            PRIMARY KEY(doc_id), 
            FOREIGN KEY (doc_id) REFERENCES wp_posts(ID)
             ON UPDATE CASCADE ON DELETE CASCADE
            ) CHARACTER SET utf8 COLLATE = utf8_general_ci;";
    dbDelta($sql);

}

//--------------------------------------------------------------------------
//
//  プラグイン削除の際に行うオプションの削除
//
//--------------------------------------------------------------------------
register_deactivation_hook(__FILE__, 'wix_uninstall_hook');
function wix_uninstall_hook () {
    delete_option('manual_decideFlag');
}

?>