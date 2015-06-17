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
$words_countArray_num = 0;
$sample_title;


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
	echo '<input name="wix" type="button" class="button button-primary button-large" id="wix_tf_idf" value="Keyword Extract" />';
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
add_action( 'wp_ajax_wix_tf_idf', 'wix_tf_idf' );
add_action( 'wp_ajax_nopriv_wix_tf_idf', 'wix_tf_idf' );
function wix_tf_idf() {
	global $wpdb, $sample_title;

	header("Access-Control-Allow-Origin: *");
	header('Content-type: application/javascript; charset=utf-8');

	$yahooID = 'dj0zaiZpPUlGTmRoTElndjRuVCZzPWNvbnN1bWVyc2VjcmV0Jng9Nzk-';
	$sentence = $_POST['sentence'];
	$sample_title = $_POST['sample-title'];
	$url = "http://jlp.yahooapis.jp/MAService/V1/parse?appid=" . $yahooID . "&results=ma&sentence=" . urlencode($sentence);
	//戻り値をパースする
	$parse = simplexml_load_file($url);

	$wordsArray = array();

	//情報 と 工学科 などを繋げる
	$tmpArray = array();
	foreach($parse->ma_result->word_list->word as $value){
		$word_class = $value->pos;
		if ( $word_class == '名詞' || $word_class == '接尾辞' ) {
			$word = trim($value->surface);
			array_push($tmpArray, $word);
		} else {
			$tmp = '';
			foreach ($tmpArray as $key => $value) {
				$tmp = $tmp . $value;
				unset($tmpArray[$key]);
			}
			array_push($wordsArray, $tmp);
		}
	}
	if ( !empty($tmpArray) ) {
		$tmp = '';
		foreach ($tmpArray as $key => $value) {
			$tmp = $tmp . $value;
		}
		array_push($wordsArray, $tmp);
	}

	$words_countArray = array_word_count($wordsArray);
	$words_tfArray = wix_tf($words_countArray);
	$words_idfArray = wix_idf($words_countArray);

	//tf-idf計算
	foreach ($words_countArray as $word => $count) {
		$tf_idf = $words_tfArray[$word] * $words_idfArray[$word];
		$words_tf_idfArray[$word] = $tf_idf;
	}
	//tf-idf値の降順に並び替え
	arsort($words_tf_idfArray);

	//wp_wixfileに入ってない単語が出現するページタイトルの提示
	$returnValue = wix_post_title(no_wixfile_entry($words_tf_idfArray));


	$json = array(
		"returnValue" => $returnValue,
	);
	echo json_encode( $json );

	die();
}

function array_word_count($array) {
	global $words_countArray_num;
	$returnValue = array();

	$words_countArray_num = 0;

	foreach($array as $key => $word){
		$word = trim($word);

		if ( !empty($word) ) {
			if ( array_key_exists($word, $returnValue) ) {
				$count = $returnValue[$word] + 1;
				$returnValue[$word] = $count;
			} else {
				$returnValue[$word] = 1;
			} 
		}
		$words_countArray_num++;
	}


	return $returnValue;
}

function wix_tf($array) {
	global $words_countArray_num;
	$returnValue = array();
	$all_words_len = count($array);

	foreach ($array as $word => $count) {
		$tf = $count / 	$words_countArray_num;
		$returnValue[$word] = $tf;
	}

	return $returnValue;
}

function wix_idf($array) {
	global $wpdb;
	$returnValue = array();

	$document_num = $wpdb->get_var("SELECT COUNT(*) FROM $wpdb->posts WHERE post_status = \"publish\" OR post_status = \"draft\"");
	$results = $wpdb->get_results("SELECT post_title, post_content FROM $wpdb->posts WHERE post_status = \"publish\" OR post_status = \"draft\"");

	foreach ($array as $word => $count) {
		$count = 0;
		foreach ($results as $value) {
			if ( strpos($value->post_content, $word) )
				$count++;
		}

		//$count = 0だとInfinityになるから0にしてる
		if ( $count != 0 ) {
			$idf = log($document_num / $count);
		} else {
			$idf = 0;
		}

		$returnValue[$word] = $idf;
	}

	return $returnValue;
}

function no_wixfile_entry($array) {
	global $wpdb;
	$distinctKeywordsArray = array();
	$returnValue = array();

	$sql = 'SELECT distinct keyword FROM ' . $wpdb->prefix . 'wixfile';
	$distinctKeywords_obj = $wpdb->get_results($sql);
	foreach ($distinctKeywords_obj as $key => $value) {
		array_push($distinctKeywordsArray, $value->keyword);
	}

	foreach ( $array as $keyword => $value ) {
		if ( !in_array($keyword, $distinctKeywordsArray) ) {
			array_push($returnValue, $keyword);
		}

	}

	return $returnValue;
}

function wix_post_title($array) {
	global $wpdb, $sample_title;
	$returnValue = array();

	$results = $wpdb->get_results("SELECT ID, post_title, post_content FROM $wpdb->posts WHERE post_status = \"publish\" OR post_status = \"draft\"");

	foreach ($array as $key => $word) {
		$tmpArray = array();
		foreach ($results as $value) {
			if ( strpos($value->post_content, $word) ) {
				if ( $sample_title != $value->post_title ) {
					$permalink = get_permalink($value->ID);
					array_push($tmpArray, $value->post_title . ' 【' . urldecode($permalink) . '】' );
				}
			}
		}
		if ( !empty($tmpArray) )
			$returnValue[$word] = $tmpArray;
	}

	return $returnValue;
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



//Manula Decideプレビュー画面のBody
add_action( 'wp_ajax_wix_decide_preview', 'wix_decide_preview' );
add_action( 'wp_ajax_nopriv_wix_decide_preview', 'wix_decide_preview' );
function wix_decide_preview() {
	global $innerLinkArray, $wpdb;


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

	$post_page = get_post_type( $post_ID );
	$page_status = get_post_status( $post_ID );

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
			$innerLinkArray = keyword_location(strip_tags($_POST['after_body_part']));

			if ( count($innerLinkArray) != 0 ) {
				$tmp = json_encode($innerLinkArray, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
				$newBody = new_body($_POST['after_body_part'], $tmp);
			} else {
				$newBody = new_body($_POST['after_body_part']);
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
			// "test" => $innerLinkArray,
			"test" => $test,
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

//WIXファイルのキーワード + パターンファイル記述済みのWIXファイル を元にドキュメント内でのキーワード位置を求める
function keyword_location($body) {
	global $wpdb;
	$returnValue = array();

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

			foreach ($distinctKeywords as $key => $value) {
				$keyword = $value->keyword;
				$len = mb_strlen($keyword, "UTF-8");
				$offset = 0;
				/* locationArray : 文字列マッチングが成立した位置を保持 */
				$locationArray = array();
				while ( ($pos = mb_strpos($body, $keyword, $offset, "UTF-8")) !== false ) {
					if ( in_array($pos, $allLocationArray) == false ) {
						array_push($locationArray, $pos);
						array_push($allLocationArray, $pos);
					}
					$offset = $pos + $len;
				}

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

//Decide処理が行われなかったら、勝手にDecideファイルを作成してエントリ確保
add_filter( 'wp_insert_post_data' , 'force_create_decideFile' , 99, 2 );
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