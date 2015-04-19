<?php

//innerLinkArrayは自動生成したWIXファイルのキーワードから、位置を算出して作るもの
$innerLinkArray = array();

// $innerLinkArray[0] = array('end'=>array('2'), 'nextStart'=>array('5'), 'keyword'=>array('女優'), 'targets'=>array('http://aqua.db.ics.keio.ac.jp'));
// $innerLinkArray[5] = array('end'=>array('13'), 'nextStart'=>array('15'), 'keyword'=>array('エクソンモービル'), 'targets'=>array('http://yahoo.co.jp'));
// $innerLinkArray[15] = array('end'=>array('19'), 'nextStart'=>array('24'), 'keyword'=>array('菅田将暉'), 'targets'=>array('http://www.db.ics.keio.ac.jp', 'http://aqua.db.ics.keio.ac.jp'));
// $innerLinkArray[24] = array('end'=>array('25'), 'nextStart'=>array('30'), 'keyword'=>array('佐草'), 'targets'=>array('http://www.db.ics.keio.ac.jp', 'http://aqua.db.ics.keio.ac.jp'));
// $innerLinkArray[30] = array('end'=>array('34'), 'nextStart'=>array('500'), 'keyword'=>array('オンエア'), 'targets'=>array('http://www.db.ics.keio.ac.jp', 'http://aqua.db.ics.keio.ac.jp'));
// // $innerLinkArray[73] = array('end'=>array('77'), 'nextStart'=>array('500'), 'keyword'=>array('大島優子'), 'targets'=>array('http://www.db.ics.keio.ac.jp', 'http://aqua.db.ics.keio.ac.jp'));
// $innerLinkArray[500] = array('end'=>array('501'), 'nextStart'=>array('0'), 'keyword'=>array('場'), 'targets'=>array('http://www.db.ics.keio.ac.jp', 'http://aqua.db.ics.keio.ac.jp'));

$innerLinkArray[0] = array('end'=>array('3'), 'nextStart'=>array('4'), 'keyword'=>array('遠山研'), 'targets'=>array('http://aqua.db.ics.keio.ac.jp'));
$innerLinkArray[4] = array('end'=>array('10'), 'nextStart'=>array('30'), 'keyword'=>array('データベース'), 'targets'=>array('http://aqua.db.ics.keio.ac.jp'));
$innerLinkArray[30] = array('end'=>array('40'), 'nextStart'=>array('0'), 'keyword'=>array('各'), 'targets'=>array('http://aqua.db.ics.keio.ac.jp'));



//Javascript→phpへのAjax通信を可能にするための変数定義
add_action("admin_head-admin.php", 'ajaxURL');
function ajaxURL() {
	$str = "<script type=\"text/javascript\"> var ajaxurl = '%s' </script>";
	$ajaxurl = admin_url( 'admin-ajax.php' );
	printf($str, $ajaxurl);
}

//モーダルウィンドウの<head>にインクルード
add_action('wp_head','wix_decide_include_file');
function wix_decide_include_file(){
    if ( is_preview() == true ) {
    	echo "<script type=\"text/javascript\" src=\"" . wix_decide_iframe_js . "\"></script>";
    	echo "<link rel=\"stylesheet\" href=\"" . wix_decide_css . "\" type=\"text/css\" charset=\"UTF-8\" />";
    }
}


//Manula Decideプレビュー画面のBody
add_action( 'wp_ajax_wix_decide_preview', 'wix_decide_preview' );
add_action( 'wp_ajax_nopriv_wix_decide_preview', 'wix_decide_preview' );
function wix_decide_preview() {
	global $innerLinkArray;
	global $wpdb;

	header("Access-Control-Allow-Origin: *");
	header('Content-type: application/javascript; charset=utf-8');

	$post_ID = (int) substr( $_POST['target'], strlen('wp-preview-') );
	$_POST['ID'] = $post_ID;

	if ( ! $post = get_post( $post_ID ) ) {
		wp_die( __( 'You are not allowed to edit this post.' ) );
	}
	if ( ! current_user_can( 'edit_post', $post->ID ) ) {
		wp_die( __( 'You are not allowed to edit this post.' ) );
	}

	$page_status = get_post_status( $post_ID );

	if ( $page_status == 'publish' ) {
		$query_args = array( 'preview' => 'true' );
		$query_args['preview_id'] = $post->ID;
		$query_args['post_format'] = empty( $_POST['post_format'] ) ? 'standard' : sanitize_key( $_POST['post_format'] );
		$url = add_query_arg( $query_args, urldecode(esc_url_raw(get_permalink( $post->ID ))) );
		$response = wp_remote_get( $url );
	} else if ( $page_status == 'draft' ) {
		$query_args = array( 'preview' => 'true' );
		$url = add_query_arg( $query_args, urldecode(esc_url_raw(get_permalink( $post->ID ))) );
		$response = wp_remote_get( $url );

		$publish_post_url = $wpdb->get_var("SELECT guid FROM " . $wpdb->prefix . "posts 
										 WHERE post_type='post' AND post_status='publish'
										 ORDER BY ID DESC"
										);
		$response = wp_remote_get( $publish_post_url );
	}

	if ( ! is_wp_error( $response ) && wp_remote_retrieve_response_code( $response ) === 200 ) {
		
		//返り値はbodyというか<html></html>まで
		$response_html = wp_remote_retrieve_body( $response );

		if ( strpos($response_html, '<div class="entry-content">') !== false ) {

			//編集後のBodyに、アタッチしてから置換
			$decode_oldBody = htmlspecialchars_decode($_POST['before_body_part']);

			$tmp = json_encode($innerLinkArray, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);

			$newBody = new_body($_POST['after_body_part'], $tmp);
			
			// $returnValue = str_replace( $decode_oldBody, '$newBody', $response_html );
			//↑だと上手く置換できなかった
			$start = strpos($response_html, '<div class="entry-content">') + strlen('<div class="entry-content">');
			$end = strpos($response_html, '</div>', $start);
			$former_response_html = substr($response_html, 0, $start);
			$later_response_html = substr($response_html, $end);

			$returnValue = $former_response_html . $newBody . $later_response_html;

		} else {
			$returnValue = $response_html;
		}

		$json = array(
			"html" => $returnValue,
		);
		echo json_encode( $json );

	} else {
		$json = array(
			"html" => $publish_post_url,
		);
		echo json_encode( $json );
	}
	
    die();
}

//各ページのDecideファイル作成
add_action( 'wp_ajax_wix_create_decidefile', 'wix_create_decidefile' );
add_action( 'wp_ajax_nopriv_wix_create_decidefile', 'wix_create_decidefile' );
function wix_create_decidefile() {
	header("Access-Control-Allow-Origin: *");
	header('Content-type: application/javascript; charset=utf-8');

	$post_ID = (int) substr( $_POST['post_ID'], strlen('wp-preview-') );

	if ( ! $post = get_post( $post_ID ) ) {
		wp_die( __( 'You are not allowed to edit this post.' ) );
	}
	if ( ! current_user_can( 'edit_post', $post->ID ) ) {
		wp_die( __( 'You are not allowed to edit this post.' ) );
	}
	// $test = urldecode($post->post_name);

	$object = $_POST['decideLink'];

	$dirname = dirname( __FILE__ ) . '/WIXDecideFiles/';
	if ( !file_exists($dirname) ) {
		mkdir($dirname, 0777, true);
	}
	if ( file_exists($dirname.$post_ID.'.txt') ) {
		unlink($dirname.$post_ID.'.txt');
	}

	foreach ($object as $index => $array) {
		$start = $array['start'];
		$end = $array['end'];
		$nextStart = $array['nextStart'];
		$keyword = $array['keyword'];
		$target = $array['target'];

		$line = 'start:' . $start . ',end:' . $end . ',nextStart:' . $nextStart . ',keyword:' . $keyword . ',target:' . $target . "\n";
		file_put_contents( $dirname.$post_ID.'.txt', $line, FILE_APPEND | LOCK_EX );
	}

	$json = array(
		"response" => 'aaa'
	);
	echo json_encode($json);

	die();
}

//Decideファイルの存在確認
add_action( 'wp_ajax_wix_decidefile_check', 'wix_decidefile_check' );
add_action( 'wp_ajax_nopriv_wix_decidefile_check', 'wix_decidefile_check' );
function wix_decidefile_check() {
	header("Access-Control-Allow-Origin: *");
	header('Content-type: application/javascript; charset=utf-8');

	$post_ID = (int) substr( $_POST['post_ID'], strlen('wp-preview-') );

	$filename = dirname( __FILE__ ) . '/WIXDecideFiles/' . $post_ID . '.txt';
	if ( file_exists($filename) ) {
		// $returnValue = file_get_contents($filename, FILE_USE_INCLUDE_PATH);
		$fopen = fopen($filename, 'r');

		if ($fopen){
			$count = 0;
			if ( flock($fopen, LOCK_SH) ){
				$existingDecideInfo = '<table><caption>既存WIX Decide情報</caption><tr style="background:#ccccff"><th>Keyword</th><th>Target</th></tr>';
				while ( !feof($fopen) ) {
					$line = fgets($fopen);
					if ( $line != '' ) {
						$tmp_line = substr($line, strpos($line, 'keyword') + 8);
						$keyword = substr($tmp_line, 0, strpos($tmp_line, ','));
						$target = substr($tmp_line, strpos($tmp_line, ':') + 1);
						
						$existingDecideInfo = $existingDecideInfo . '<tr><td>' . $keyword . '</td>' . '<td>' . $target . '</td></tr>';
					}
				}
				$existingDecideInfo = $existingDecideInfo . '</table>';
				flock($fopen, LOCK_UN);
			} else {
		        $existingDecideInfo = 'ファイルロックに失敗しました';
			}
		}
		fclose($fopen);
	} else {
		$existingDecideInfo = '';
	}


	$json = array(
		"existingDecideInfo" => $existingDecideInfo
	);
	echo json_encode($json);

	die();
}



//使ってない
function wixfile_entry_info( $filenames ) {

	$URL = 'http://trezia.db.ics.keio.ac.jp/WIXAuthorEditor_0.0.1/GetEntryInfo';
	
	$ch = curl_init();
	$data = array(
	    'filenames' => $filenames
	);
	$data = http_build_query($data, "", "&");

	try {
		//送信
		curl_setopt( $ch, CURLOPT_URL, $URL );
		curl_setopt( $ch, CURLOPT_HTTPHEADER, array('Content-Type: application/x-www-form-urlencoded') );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
		curl_setopt( $ch, CURLOPT_POST, true );
		curl_setopt( $ch, CURLOPT_POSTFIELDS, $data );

		$response = curl_exec($ch);

		if ( $response === false ) {

		    // エラー文字列を出力する
		    echo 'エラーです. http_test.php';
	    	echo curl_error( $ch );

		}

	} catch ( Exception $e ) {
	
		echo '捕捉した例外: ',  $e -> getMessage(), "\n";
	
	} finally {

		curl_close($ch);
	
	}

	return $response;

}	



















$preview;
function nixcraft_preview_link($url, $post) {
    // $slug = basename(get_permalink());
    // $mydomain = 'http://server1.cyberciti.biz';
    // $mydir = '/faq/';
    // $mynewpurl = "$mydomain$mydir$slug&preview=true";
    // return "$mynewpurl";

    global $preview;
    $preview = $url;

    // var_dump($preview);
    // var_dump(get_permalink());

    return $url;
}
add_filter( 'preview_post_link', 'nixcraft_preview_link', 10, 2 );





	// if ( isset( $_GET['post'] ) )
	//  	$post_id = $post_ID = (int) $_GET['post'];
	// elseif ( isset( $_POST['post_ID'] ) )
	//  	$post_id = $post_ID = (int) $_POST['post_ID'];
	// else
	//  	$post_id = $post_ID = 0;

	// global $post;

	// var_dump($post_id);
	// var_dump($_GET['preview_nonce']);
	// var_dump( urldecode(get_permalink( $post->ID )) );





//post.phpでID取得
// if ( isset( $_GET['post'] ) )
//  	$post_id = $post_ID = (int) $_GET['post'];
// elseif ( isset( $_POST['post_ID'] ) )
//  	$post_id = $post_ID = (int) $_POST['post_ID'];
// else
//  	$post_id = $post_ID = 0;
// var_dump($post_id);










//強制リダイレクト
// add_action( 'publish_post', 'aaa', 99, 2 );
function aaa($post_ID, $post) {
    // die("test");
    wp_safe_redirect( 'http://localhost/wordpress/wp-admin/post.php?post=56&action=edit', 301 );
    exit;
}







//強制的に"下書き"にする
// add_filter( 'wp_insert_post_data' , 'filter_handler' , 10, 2 );
function filter_handler( $data , $postarr ) {
  $data['post_status'] = 'draft';
  return $data;
}



//postboxにメニュー追加
// add_action( 'post_submitbox_misc_actions', 'check_proofreading_button' );    
function check_proofreading_button() {  
    if(get_post_status() == 'publish') {  
        return;  
    }  
        $html  = '<div class="misc-pub-section" style="overflow:hidden">';  
        $html .= '<div id="publishing-action">';  
        $html .= '<input type="submit" tabindex="5" value="wixDecideからです" class="button-primary" id="proofreading" name="proofreading">';  
        $html .= '</div>';  
        $html .= '</div>';  
        echo $html;  
}  





//コンソールログに
add_action("admin_head", 'suffix2console');
function suffix2console() {
    global $hook_suffix;
    if (is_user_logged_in()) {
        $str = "<script type=\"text/javascript\">console.log('%s')</script>";
        printf($str, $hook_suffix);
    }   

	//フック名など
    // global $wp_filter;
    // foreach ($wp_filter as $key => $value) {
    // 	if ( $key == 'save_post' ) {
    // 		var_dump($key);
    // 		var_dump($wp_filter[$key]);
    // 	}
    // }
}

// hook_suffixが一致するページのみでmy_func()が実行される
add_action('admin_head-post.php', 'my_func');
function my_func(){
	$str = "<script type=\"text/javascript\">console.log('%s')</script>";
	printf($str, 'admin_head-hook_suffixです');
}




//transition_post_statusのテスト
// add_action( 'transition_post_status', 'post_unpublished', 99, 3 );
function post_unpublished( $new_status, $old_status, $post ) {
    if ( $old_status == 'publish'  &&  $new_status != 'publish' ) {
        // A function to perform actions when a post status changes from publish to any non-public status.
    	update_option( 'sauksa', 'aaa' );
    } else {
    	update_option( 'sakusa', $new_status.'<-'.$old_status );
    }
}
// var_dump( get_option( 'sakusa', 'default' ) );







//remove系のテスト
// remove_all_actions( 'save_post' );
// remove_action( 'transition_post_status', '_transition_post_status' );








// add_action('submitpost_box', 'hidden_fields');
// function hidden_fields(){
// 	// var_dump('ここにいるよ');
// }







// 公開する前にアラートを表示する
// add_action('admin_footer', 'publish_confirm', 10);

function publish_confirm() {

	$c_message = '記事を公開します。宜しいでしょうか？';

	$post_status = get_post_status( get_the_ID() ); 

	if ( $post_status == ('publish' || 'auto-draft') ) {

		echo '<script type="text/javascript"><!--
		var publish = document.getElementById("publish");
		if (publish !== null) publish.onclick = function(){
			
			if ( window.confirm("'.$c_message.'") ) {

			} else {
				alert(\'キャンセルされました\');
			}

			return confirm("'.$c_message.'");
		};
		// --></script>';

	}
	
}


?>