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

// $innerLinkArray[0] = array('end'=>array('3'), 'nextStart'=>array('4'), 'keyword'=>array('遠山研'), 'targets'=>array('http://aqua.db.ics.keio.ac.jp'));
// $innerLinkArray[4] = array('end'=>array('10'), 'nextStart'=>array('30'), 'keyword'=>array('データベース'), 'targets'=>array('http://aqua.db.ics.keio.ac.jp'));
// $innerLinkArray[30] = array('end'=>array('40'), 'nextStart'=>array('0'), 'keyword'=>array('各'), 'targets'=>array('http://aqua.db.ics.keio.ac.jp'));

// $innerLinkArray[0] = array('end'=>array('6'), 'nextStart'=>array('7'), 'keyword'=>array('慶應義塾大学'), 'targets'=>array('http://aqua.db.ics.keio.ac.jp'));
// $innerLinkArray[7] = array('end'=>array('13'), 'nextStart'=>array('16'), 'keyword'=>array('慶應義塾大学'), 'targets'=>array('http://aqua.db.ics.keio.ac.jp'));
// $innerLinkArray[16] = array('end'=>array('21'), 'nextStart'=>array('0'), 'keyword'=>array('早稲田大学'), 'targets'=>array('http://aqua.db.ics.keio.ac.jp'));

//Decide処理を行った時は強制Decideファイル作成しない　ためのフラグ
$wixDecide_flag = false;
$doc_title;


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
    // if ( is_preview() == true ) {
    	echo "<script type=\"text/javascript\" src=\"" . wix_decide_iframe_js . "\"></script>";
    	echo "<link rel=\"stylesheet\" href=\"" . wix_decide_css . "\" type=\"text/css\" charset=\"UTF-8\" />";
    // }
}

//WIX用meta box
add_action( 'add_meta_boxes', 'wix_meta_box' );
function wix_meta_box() {
	add_meta_box( 'WIX Decide Link', 'WIX Decide Link', 'wix_decide_link', 'post', 'side', 'high' );
	add_meta_box( 'WIX Decide Link', 'WIX Decide Link', 'wix_decide_link', 'page', 'side', 'high' );

	add_meta_box( 'WIX New Entry', 'WIX New Entry', 'wix_new_entry', 'post', 'side', 'high' );
	add_meta_box( 'WIX New Entry', 'WIX New Entry', 'wix_new_entry', 'page', 'side', 'high' );
}
function wix_decide_link() {
	echo '<table><tr>';
	echo '<td>';
	echo '<input name="wix" type="button" class="button button-primary button-large" id="wixDecide" value="WIXDecide" />';
	echo '</td><td>';
	echo '<input name="wix" type="button" class="button button-primary button-large" id="wix_entry_recommendation" value="Keyword Extract" />';
	echo '</td>';
	echo '</tr></table>';

}
function wix_new_entry() {
	echo '<div id="newWIXFiles">
			<table class="newEntry" id="newEntry">
				<tr>
					<td><label for=keyword>Keyword</label></td>
					<td><input type="text" /></td>
				</tr>
				<tr>
					<td><label for=target>Target</label></td>
					<td><input type="text" /></td>
				</tr>
			</table>
			<div class="detailSettings" id="detail_show">
				<a style:"display: inline;">詳細設定</a>
			</div>
			<span class="detailSettings" id="detailSettings" style="display: none;">
				<input type=checkbox id=firstonly name=firstonly value="1" /><label for=firstonly>First Match Only</label>
				<input type=checkbox id=case name=case value="1" /><label for=case>Case Sensitivity</label>
				<input type=checkbox id=filter name=filter value="1" /><label for=filter>Filter in comments?</label>
				<span class="detailSettings" id="detail_hide">
					<a style:"display: inline;">閉じる</a>
				</span>
			</span>
			<br><br>
			<table>
				<tr>
					<td><input type="button" class="button button-primary button-large" id="new_entry_insert" value="New Entry" /></td>
					<td><span id="insert_success" style="display: none;">成功しました</span></td>
				</tr>
			</table>
		</div>';
}

//TF-IDFによるキーワード抽出
add_action( 'wp_ajax_wix_entry_recommendation', 'wix_entry_recommendation' );
add_action( 'wp_ajax_nopriv_wix_entry_recommendation', 'wix_entry_recommendation' );
function wix_entry_recommendation() {
	global $doc_title, $similarityObj;

	header("Access-Control-Allow-Origin: *");
	header('Content-type: application/javascript; charset=utf-8');

	$parse = wix_morphological_analysis($_POST['sentence']);
	$wordsArray = wix_compound_noun_extract($parse);
	$words_countArray = array_word_count($wordsArray);
	wix_tf($words_countArray);
	wix_idf();


	//tf-idf値の降順に並び替え
	$tf_idfArray = array();
	foreach ($similarityObj as $key => $value) {
		$tf_idf = $value['tf'] * $value['idf'];
		$value['tf_idf'] = $tf_idf;
		$similarityObj[$key] = $value;

		$tf_idfArray[] = $tf_idf;
	}
	array_multisort($tf_idfArray, SORT_DESC, SORT_NUMERIC, $similarityObj);





	//wp_wixfileテーブルに入ってない単語が出現するページタイトルの提示
/*これ違う気がする。テーブルに入ってない奴も推薦していいんじゃね？（2015/09/22）*/
	$doc_title = $_POST['doc-title'];
	$returnValue = wix_post_title(no_wixfile_entry($similarityObj));


	$json = array(
		"returnValue" => $returnValue,
		// "similarity" => $similarityObj,
		// "idf" => $words_idfArray,
	);
	echo json_encode( $json );

	die();
}


//post.phpからのEntry情報をDBに挿入
add_action( 'wp_ajax_wix_new_entry_insert', 'wix_new_entry_insert' );
add_action( 'wp_ajax_nopriv_wix_new_entry_insert', 'wix_new_entry_insert' );
function wix_new_entry_insert() {
	global $wpdb;

	header("Access-Control-Allow-Origin: *");
	header('Content-type: application/javascript; charset=utf-8');

	$table_name = $wpdb->prefix . 'wixfile';
	$insertEntry = '';

	$keyword = $_POST['keyword'];
	$target = $_POST['target'];

	$sql = 'SELECT COUNT(*) FROM ' . $table_name . ' WHERE keyword="' . $keyword . '"';
	$keywordNum_inDB = $wpdb->get_var($sql);

	$test = '';

	//まだテーブルにないキーワードの場合	
	if ( $keywordNum_inDB == 0 ) {
		$insertEntry = '("' . $keyword .'", "' . $target . '"), ';
	} else {
		$sql = 'SELECT COUNT(*) FROM ' . $table_name . ' WHERE keyword="' . $keyword . '" and target="' . $target . '"';
		$targetNum_inDB = $wpdb->get_var( $sql );

		if ( $targetNum_inDB == 0 ) {
			$insertEntry = '("' . $keyword .'", "' . $target . '"), ';
		}
	}

	if ( !empty($insertEntry)) {
		$sql = 'INSERT INTO ' . $table_name . '(keyword, target) VALUES ' . $insertEntry;
		$sql = mb_substr($sql, 0, (mb_strlen($sql)-2));
		$results = $wpdb->query( $sql );
		$test = 'success';
	} else {
		$test = 'fail';
	}

	$json = array(
		"test" => $test,
	);
	echo json_encode( $json );

	die();
}

//エントリの推薦からDBに挿入
add_action( 'wp_ajax_wix_new_entry_inserts', 'wix_new_entry_inserts' );
add_action( 'wp_ajax_nopriv_wix_new_entry_inserts', 'wix_new_entry_inserts' );
function wix_new_entry_inserts() {
	global $wpdb;

	header("Access-Control-Allow-Origin: *");
	header('Content-type: application/javascript; charset=utf-8');

	$table_name = $wpdb->prefix . 'wixfile';
	$insertEntry = '';
$test = '';
	$entry = $_POST['entry'];

	foreach ($entry as $key => $obj) {
		$keyword = $obj['keyword'];
		$target = $obj['target'];

		if ( empty($insertEntry) )
			$insertEntry = '("' . $keyword .'", "' . $target . '"), ';
		else
			$insertEntry = $insertEntry . '("' . $keyword .'", "' . $target . '"), ';
	}

	if ( !empty($insertEntry)) {
		$sql = 'INSERT INTO ' . $table_name . '(keyword, target) VALUES ' . $insertEntry;
		$sql = mb_substr($sql, 0, (mb_strlen($sql)-2));
		$results = $wpdb->query( $sql );
		// $test = 'SUCCESS';
	} else {
		$test = 'FAIL';
	}

	$json = array(
		"test" => $test,
		"entry" => $entry,
	);
	echo json_encode( $json );

	die();
}


//Manula Decideプレビュー画面のBody
add_action( 'wp_ajax_wix_decide_preview', 'wix_decide_preview' );
add_action( 'wp_ajax_nopriv_wix_decide_preview', 'wix_decide_preview' );
function wix_decide_preview() {
	global $innerLinkArray, $wpdb;


	header("Access-Control-Allow-Origin: *");
	header('Content-type: application/javascript; charset=utf-8');

	$post_id = (int) substr( $_POST['target'], strlen('wp-preview-') );
	$_POST['ID'] = $post_id;

	if ( ! $post = get_post( $post_id ) ) {
		wp_die( __( 'You are not allowed to edit this post.' ) );
	}
	if ( ! current_user_can( 'edit_post', $post->ID ) ) {
		wp_die( __( 'You are not allowed to edit this post.' ) );
	}

	$post_page = get_post_type( $post_id );
	$page_status = get_post_status( $post_id );

	if ( $page_status == 'publish' ) {

		$query_args = array( 'preview' => 'true' );
		$query_args['preview_id'] = $post->ID;
		if ( $post_page == 'post' )
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
			//pタグとかを省いたbodyを対象として、アタッチ対象文字列位置を求めている(ppBody的な)
			// $innerLinkArray = keyword_location( strip_tags($_POST['after_body_part']) );
			$innerLinkArray = keyword_location( strip_tags(wpautop($_POST['after_body_part'])) );

			if ( count($innerLinkArray) != 0 ) {
				$tmp = json_encode($innerLinkArray, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
				$newBody = new_body( wpautop($_POST['after_body_part']), $tmp);
			} else {
				$newBody = new_body( wpautop($_POST['after_body_part']) );
			}
			
			$start = strpos($response_html, '<div class="entry-content">') + strlen('<div class="entry-content">');
			$end = strpos($response_html, '</div>', $start);
			$former_response_html = substr($response_html, 0, $start);
			$later_response_html = substr($response_html, $end);

			$returnValue = $former_response_html . $newBody . $later_response_html;

		} else {
			$returnValue = $response_html;
		}
		foreach ($innerLinkArray as $key => $value) {
			$test = $key;
		}

		$json = array(
			"html" => $returnValue,
			"test" => $innerLinkArray,
			"js" => $_POST['after_body_part'],
			"js2" => wpautop($_POST['after_body_part']),
		);
		echo json_encode( $json );

	} else {
		$json = array(
			"html" => 'WIX Manual Decide Error',
		);
		echo json_encode( $json );
	}
	
    die();
}

//DB内のWIXファイル + パターンファイル記述済みのWIXファイル を元にドキュメント内でのキーワード位置を求める
function keyword_location($body) {
	global $wpdb;
	$returnValue = array();
	/*
	* $returnValue : 
	*	[start
			[
				end: ,
				keyword: ,
				targets: ,
				nextStart: 
			]
		]
	*/

	$table_name = $wpdb->prefix . 'wixfile';
	$is_db_exists = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table_name));

	if ( $is_db_exists == $table_name ) {

		$sql = 'SELECT COUNT(*) FROM ' . $table_name;
		if ( $wpdb->get_var($sql) != 0 ) {

			$sql = 'SELECT distinct keyword FROM ' . $table_name;
			$distinctKeywords = $wpdb->get_results($sql);

			/* $keyword_sort_array : キーワードを文字列の長い順番にして、キーワード間の部分一致対策*/
			$keyword_sort_array = array();
			foreach ($distinctKeywords as $key => $value) {
				$keyword_sort_array[$key] = strlen($value->keyword);
			}
			array_multisort($keyword_sort_array, SORT_DESC, $distinctKeywords);

			//全位置情報
			$allLocationArray = array();
			$offset = 0;

			//wixfileテーブル内のキーワード毎にループを回す
			foreach ($distinctKeywords as $key => $value) {
				$keyword = $value->keyword;
				$len = mb_strlen($keyword, "UTF-8");
				$offset = 0;
				/* locationArray : 文字列マッチングが成立した位置を保持（start取得） */
				$locationArray = array();
				while ( ($pos = mb_strpos($body, $keyword, $offset, "UTF-8")) !== false ) {
					if ( in_array($pos, $allLocationArray) == false ) {
						array_push($locationArray, $pos);
						array_push($allLocationArray, $pos);
					}
					$offset = $pos + $len;
				}

				//end, keyword, targetの作成
				if ( count($locationArray) != 0 ) {
					$locationArray_len = count($locationArray);
					$sql = 'SELECT target FROM ' . $table_name . ' WHERE keyword="' . $keyword . '"';
					$results = $wpdb->get_results($sql);
					$targetArray = array();
					foreach ($results as $key => $value) {
						array_push($targetArray, $value->target);
					}

					foreach ($locationArray as $key => $start) {
						$end = intval($start) + $len;
						if ( $key < $locationArray_len ) 
							$returnValue[intval($start)] = array('end'=>array(strval($end)), 'keyword'=>array($keyword), 'targets'=>$targetArray);
					}
				}
			}
			
			sort($allLocationArray);
			asort($returnValue);

			//nextStartの作成
			if ( count($allLocationArray) != 0 ) {
				$returnValue_len = count($returnValue);
				$count = 1;
				foreach ($returnValue as $start => $array) {
					if ( $returnValue_len != $count )
						$array['nextStart'] = array(strval($allLocationArray[$count]));
					else
						$array['nextStart'] = array('0');
					
					$returnValue[$start] = $array;
					$count++;
				}
			}
		}
	}

	return $returnValue;
}

// add_action('init', function() {
//     remove_filter('the_title', 'wptexturize');
//     remove_filter('the_content', 'wptexturize');
//     remove_filter('the_excerpt', 'wptexturize');
//     remove_filter('the_title', 'wpautop');
//     remove_filter('the_content', 'wpautop');
//     remove_filter('the_excerpt', 'wpautop');
//     remove_filter('the_editor_content', 'wp_richedit_pre');
// });
 
// add_filter('tiny_mce_before_init', function($init) {
//     $init['wpautop'] = false;
//     return $init;
// });


// add_action('save_post', 'save_custom_field_postdata');
function save_custom_field_postdata( $post_id, $content = '' ) {
	global $wpdb;

	remove_action('save_post', 'force_update_post');

	// $content = '<h1>大槻研究室</h1><p>慶應義塾大学 情報工学科<br> 情報工学専修</p><h3>研究分野</h3><ul><li>無線通信</li><li>見守り・セキュリティ</li></ul><div class=\"staff_urls\"><a href=\"http://www.ohtsuki.ics.keio.ac.jp\" target=\"_blank\" data-mce-href=\"http://www.ohtsuki.ics.keio.ac.jp\">研究室HP</a> | <a href=\"http://k-ris.keio.ac.jp/Profiles/76/0007557/profile.html\" target=\"_blank\" data-mce-href=\"http://k-ris.keio.ac.jp/Profiles/76/0007557/profile.html\">研究者プロフィール</a></div>';
	$sql = 'UPDATE ' . $wpdb->posts . ' SET post_content = "' . $content . '" WHERE ID = ' . $post_id;
	$wpdb->query( $sql );

	// remove_action('save_post', 'save_custom_field_postdata');
	add_action('save_post', 'save_custom_field_postdata');

}


//Decide処理が行われなかったら、ドキュメント保存時に強制的にDecideファイルを作成してエントリ確保するフィルター
//今使ってない（2015/9/29）
// add_filter( 'wp_insert_post_data' , 'force_create_decideFile' , 99, 2 );
function force_create_decideFile( $data ) {
	global $wixDecide_flag;

	$guid = $data['guid'];
	$start = intval(strpos($guid, '?page_id=')) + strlen('?page_id=');
	$id = substr( $guid, $start );

	if ( !file_exists(WixDecideFiles . $id . '.txt') && $wixDecide_flag == false ) {
		$dirname = dirname( __FILE__ ) . '/WIXDecideFiles/';
		if ( !file_exists($dirname) ) {
			mkdir($dirname, 0777, true);
		}
		$body = $data['post_content'];

		$object = array();
		$object = keyword_location( strip_tags($body) );

		foreach ($object as $start => $array) {
			$end = $array['end'][0];
			$nextStart = $array['nextStart'][0];
			$keyword = $array['keyword'][0];

			$targets = $array['targets'];
			$target = '';
			if ( count($targets) == 0 ) {
				$target = $targets[0];
			} else {
				foreach ($targets as $key => $value) {
					if ( $key == 0 ) 
						$target = $value;
					else 
						$target = $target . ',' . $value;
				}
			}

			$line = 'start:' . $start . ',end:' . $end . ',nextStart:' . $nextStart . ',keyword:' . $keyword . ',target:' . $target . "\n";
			file_put_contents( $dirname.$id.'.txt', $line, FILE_APPEND | LOCK_EX );
		}
	}


	return $data;
}


//各ページのDecideファイル作成
add_action( 'wp_ajax_wix_create_decidefile', 'wix_create_decidefile' );
add_action( 'wp_ajax_nopriv_wix_create_decidefile', 'wix_create_decidefile' );
function wix_create_decidefile() {
	global $wixDecide_flag;

	header("Access-Control-Allow-Origin: *");
	header('Content-type: application/javascript; charset=utf-8');

	$wixDecide_flag = true;

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






// $preview;
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
    // wp_safe_redirect( 'http://localhost/wordpress/wp-admin/post.php?post=56&action=edit', 301 );
    // exit;
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