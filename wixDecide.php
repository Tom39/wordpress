<?php

require_once( dirname( __FILE__ ) . '/patternMatching.php' );

$pm = new patternMatching;
$interLinkArray = array();
$interLinkArray['長友佑都'] = array('http://www.db.ics.keio.ac.jp', 'http://aqua.db.ics.keio.ac.jp');
$interLinkArray['佐草友也'] = array('http://www.db.ics.keio.ac.jp', 'http://aqua.db.ics.keio.ac.jp');


//Javascript→phpへのAjax通信を可能にするための変数定義
add_action("admin_head-admin.php", 'ajaxURL');
function ajaxURL() {
	$str = "<script type=\"text/javascript\"> var ajaxurl = '%s' </script>";
	$ajaxurl = admin_url( 'admin-ajax.php' );
	printf($str, $ajaxurl);
}


//Manula Decideプレビュー画面のBody
add_action( 'wp_ajax_wix_decide_preview', 'wix_decide_preview' );
add_action( 'wp_ajax_nopriv_wix_decide_preview', 'wix_decide_preview' );
function wix_decide_preview() {
	global $pm;
	global $interLinkArray;

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

	$query_args = array( 'preview' => 'true' );
	$query_args['preview_id'] = $post->ID;
	$query_args['post_format'] = empty( $_POST['post_format'] ) ? 'standard' : sanitize_key( $_POST['post_format'] );
	$url = add_query_arg( $query_args, urldecode(esc_url_raw(get_permalink( $post->ID ))) );
	$response = wp_remote_get( $url );

	if ( ! is_wp_error( $response ) && wp_remote_retrieve_response_code( $response ) === 200 ) {
		
		//返り値はbodyというか<html></html><html>まで
		$response_html = wp_remote_retrieve_body( $response );

		if ( strpos($response_html, '<div class="entry-content">') !== false ) {
			//編集後のBodyに、アタッチしてから置換
			// $decode_oldBody = htmlspecialchars_decode($_POST['before_body_part']);
			// $newBody = new_body($_POST['after_body_part']);
			// $returnValue = str_replace( $decode_oldBody, $newBody, $response_html );


			// $allEntry = wix_set_link( $_POST['after_body_part'] );

			// //とりあえず各エントリに分離
			// $tmp_entryArray = preg_split("/[,]+/", $allEntry);

			// $entryArray = array();
			// $count = 0;
			// foreach ($tmp_entryArray as $index => $entry) {

			// 	$keyword = explode('=', $entry)[0];
			// 	$targets = explode('=', $entry)[1];

			// 	//keywordに{が付いてたら取り除く。それ以外は"などを取り除く
			// 	if ( strpos($keyword, '{') !== false )
			// 		$keyword = substr( $keyword, strpos($keyword, '{')+1 );
			// 	else
			// 		$keyword = ltrim( str_replace('"', '', $keyword) );

			// 	//targetに}が付いてたら取り除く。
			// 	if ( $count != count($tmp_entryArray)-1 ) {
			// 		$targetArray = $pm -> splitSpace( $targets );
			// 	} else {
			// 		$tmp_targetArray = $pm -> splitSpace( $targets );
			// 		$targetArray = array();

			// 		foreach ($tmp_targetArray as $index => $target) {
			// 			if ( strpos($target, '}') !== false )
			// 				$target = rtrim($target);
			// 				// $target = substr($target, 0, strpos($target, '}') );

			// 			array_push($targetArray, $target);
			// 		}
			// 	}


			// 	$entryArray[$keyword] = $targetArray;

			// 	$count++;

			// }




			$allEntry = wix_set_link( $_POST['after_body_part'] );

			if ( $allEntry != false ) {
				/*
				* allEntry: 既にLibraryに登録済みかつ、パターンファイルからマッチしたWIXファイルのエントリ情報
				* exLinkArray: allEntryを連想配列にパースしたもの(外部リンク情報)
				* interLinkArray: WordPress上で算出した内部リンク情報
				*/

				$exLinkArray = json_decode($allEntry, true);

				foreach ($exLinkArray as $keyword => $exLinkArray_targets) {
					//exLinkArrayのキーワードが、interLinkArrayのキーワードにもある場合
					if ( isset($interLinkArray[$keyword]) ) {

						//外部リンクtargetが複数ある時
						if ( count($exLinkArray_targets) > 1 ) {
							//一旦、内部リンクtargetを持つ
							$interLinkArray_targets = $interLinkArray[$keyword];

							//既にinterLinkarrayのターゲットに存在したらpushしない
							foreach ($exLinkArray_targets as $exIndex => $exTarget) {
								$flag = false;
								foreach ($interLinkArray_targets as $interIndex => $interTarget) {
									if ( $exTarget == $interTarget ) { $flag = true; break; }
								}
								if ( $flag == false ) array_push($interLinkArray_targets, $exTarget);
							}

							$interLinkArray[$keyword] = $interLinkArray_targets;
						}
						

					} else {

						$interLinkArray[$keyword] = $exLinkArray_targets;

					}
				}

			} else {
				//interLinkArrayのみでOK
			}

			

		} else {
			$returnValue = $response_html;
		}

		$json = array(
			// "html" => $returnValue,
			"test" => $interLinkArray
			// "test" => $test
		);
		 echo json_encode( $json );
	} else {
		var_dump( $response );
	}
	
    die();
}


function wix_set_link( $body ) {

	$WixFileNames = 'サッカー日本代表.wix,20111101SamuraiBlue.wix';


	$URL = 'http://wixdev.db.ics.keio.ac.jp/WIXAuthorEditor_0.0.1/GetEntryInfo';
	
	$ch = curl_init();
	$data = array(
	    'filenames' => $WixFileNames
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



//モーダルウィンドウの<head>にwixDecide_iframe.jsのインクルード
function wix_decide_include_file(){
    if ( is_preview() == true ) {
    	$path = wix_decide_iframe_js;
    	echo "<script type=\"text/javascript\" src=\"" . $path . "\"></script>";
    }
}
add_action('wp_head','wix_decide_include_file');

















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