<?php

//innerLinkArrayは自動生成したWIXファイルのキーワードから、位置を算出して作るもの
$innerLinkArray = array();

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
	if ( get_option('manual_decide') == 'true' ) {
	echo '<td>';
	echo '<input name="wix" type="button" class="button button-primary button-large" id="wixDecide" value="WIXDecide" />';
	echo '</td>';
	}
	echo '<td>';
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


//ManualDecideするかいなかのフラグをjs側へ返す
add_action( 'wp_ajax_wix_manual_decide_check', 'wix_manual_decide_check' );
add_action( 'wp_ajax_nopriv_wix_manual_decide_check', 'wix_manual_decide_check' );
function wix_manual_decide_check() {
	header("Access-Control-Allow-Origin: *");
	header('Content-type: application/javascript; charset=utf-8');

	$json = array(
		"manual_decide_check" => get_option('manual_decide'),
	);
	echo json_encode( $json );

	die();
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


	$wixfilemeta = $wpdb->prefix . 'wixfilemeta';
	$wixfile_targets = $wpdb->prefix . 'wixfile_targets';
	$insertKeywordArray = array();
	$insertTargetArray = array();
	$latest_id = 0;

	$keyword = $_POST['keyword'];
	$target = $_POST['target'];
	if ( !empty($keyword) ) {
		$sql = 'SELECT COUNT(*) FROM ' . $wixfilemeta . ' WHERE keyword="' . $keyword . '"';
		//まだwixfilemetaテーブルに存在しないキーワードの場合(つまりこれから挿入しなければならない)
		if ( $wpdb->get_var($sql) == 0 ) {
			$sql = 'SELECT MAX(id) FROM ' . $wixfilemeta;
			if ( $wpdb->get_var($sql) == NULL ) {
				$latest_id = count($insertKeywordArray); //DBにまだなんの行もない時
			} else {
				$tmp = (int) $wpdb->get_var($sql);
				$latest_id = $tmp + count($insertKeywordArray) + 1;
			}
			
			array_push($insertKeywordArray, 
						array(
							'id' => $latest_id,
							'keyword' => $keyword
							)
						);
			array_push($insertTargetArray, 
						array(
							'keyword_id' => $latest_id,
							'target' => $target
							)
						);

		} else {
			//wixfile_targetsテーブルに該当ターゲットが既に存在しなければ、キーワードのidを返す
			$sql = 'SELECT wm.id FROM ' . $wixfilemeta . ' wm WHERE wm.keyword="' . $keyword . 
						'" AND wm.id NOT IN (SELECT wt.keyword_id FROM ' . 
							$wixfile_targets . ' wt WHERE wt.target="' . $target . '")';
			$keyword_idObj = $wpdb->get_results($sql);

			if ( !empty($keyword_idObj) ) {
				array_push($insertTargetArray, 
							array(
								'keyword_id' => (int)$keyword_idObj[0]->id,
								'target' => $target
								)
							);
			}
		}
	}

	if ( !empty($insertKeywordArray) ) {
		$insertKeyword = '';

		foreach ($insertKeywordArray as $index => $valueArray) {
			$keyword = $valueArray['keyword'];
			$id = $valueArray['id'];
			if ( $index == 0 ) {
				$insertKeyword = '(' . $id . ', "' . $keyword .'"), ';
			} else {
				$insertKeyword = $insertKeyword . '(' . $id . ', "' . $keyword .'"), ';
			}
		}

		$sql = 'INSERT INTO ' . $wixfilemeta . '(id, keyword) VALUES ' . $insertKeyword;
		$sql = mb_substr($sql, 0, (mb_strlen($sql)-2));
		$result = $wpdb->query( $sql );					

		if ( $result != 0 ) $test = 'SUCESS';
		else $test = 'FAIL';
	}

	if ( !empty($insertTargetArray) ) {
		$insertTarget = '';

		foreach ($insertTargetArray as $index => $valueArray) {
			$target = $valueArray['target'];
			$id = $valueArray['keyword_id'];
			if ( $index == 0 ) {
				$insertTarget = '(' . $id . ', "' . $target . '"), ';
			} else {
				$insertTarget = $insertTarget . '(' . $id . ', "' . $target . '"), ';
			}
		}

		$sql = 'INSERT INTO ' . $wixfile_targets . '(keyword_id, target) VALUES ' . $insertTarget;
		$sql = mb_substr($sql, 0, (mb_strlen($sql)-2));
		$result = $wpdb->query( $sql );					

		if ( $result != 0 ) $test = 'SUCESS';
		else $test = 'FAIL 2';
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

	$wixfilemeta = $wpdb->prefix . 'wixfilemeta';
	$wixfile_targets = $wpdb->prefix . 'wixfile_targets';
	$target_checker = array();
	$insertKeywordArray = array();
	$insertTargetArray = array();
	$latest_id = 0;

	$entry = $_POST['entry'];

	foreach ($entry as $index => $obj) {
		$keyword = $obj['keyword'];
		$target = $obj['target'];

		if ( !empty($keyword) ) {
			$keyword_flag = false;
			$target_flag = false;

			//二重挿入しないようにフラグ立てる
			foreach ($target_checker as $key => $valueArray) {
				if ( $key == $keyword ) {
					$keyword_flag = true;
					foreach ($valueArray as $i => $value) {
						if ( $value == $target ) {
							$target_flag = true;
							break;
						}
					}
				}
			}

			if ( $target_flag == false ) {
				$sql = 'SELECT COUNT(*) FROM ' . $wixfilemeta . ' WHERE keyword="' . $keyword . '"';
				//まだwixfilemetaテーブルに存在しないキーワードの場合(つまりこれから挿入しなければならない)
				if ( $wpdb->get_var($sql) == 0 ) {
					//キーワードが既に挿入用Arrayにセットされていたら今回はいれない.そしてidを合わせる
					if ( $keyword_flag == false ) {
						$sql = 'SELECT MAX(id) FROM ' . $wixfilemeta;
						if ( $wpdb->get_var($sql) == NULL ) {
							$latest_id = count($insertKeywordArray); //DBにまだなんの行もない時
						} else {
							$tmp = (int) $wpdb->get_var($sql);
							$latest_id = $tmp + count($insertKeywordArray) + 1;
						}
						
						array_push($insertKeywordArray, 
									array(
										'id' => $latest_id,
										'keyword' => $keyword
										)
									);
					} else {
						foreach ($insertKeywordArray as $key => $value) {
							if ( $value['keyword'] == $keyword ) $latest_id = $value['id'];
						}
					}
					array_push($insertTargetArray, 
								array(
									'keyword_id' => $latest_id,
									'target' => $target
									)
								);

				} else {
					//wixfile_targetsテーブルに該当ターゲットが既に存在しなければ、キーワードのidを返す
					$sql = 'SELECT wm.id FROM ' . $wixfilemeta . ' wm WHERE wm.keyword="' . $keyword . 
								'" AND wm.id NOT IN (SELECT wt.keyword_id FROM ' . 
									$wixfile_targets . ' wt WHERE wt.target="' . $target . '")';
					$keyword_idObj = $wpdb->get_results($sql);

					if ( !empty($keyword_idObj) ) {
						array_push($insertTargetArray, 
									array(
										'keyword_id' => (int)$keyword_idObj[0]->id,
										'target' => $target
										)
									);
					}
				}

				//二重挿入チェッカーに追加
				if ( empty($target_checker) ) {
					$target_checker[$keyword] = array($target);
				} else {
					if ( array_key_exists($keyword, $target_checker) ) {
						$valueArray = $target_checker[$keyword];
						array_push($valueArray, $target);
						$target_checker[$keyword] = $valueArray;
					} else {
						$target_checker[$keyword] = array($target);
					}
				}
			}
		}
	}

	if ( !empty($insertKeywordArray) ) {
		$insertKeyword = '';

		foreach ($insertKeywordArray as $index => $valueArray) {
			$keyword = $valueArray['keyword'];
			$id = $valueArray['id'];
			if ( $index == 0 ) {
				$insertKeyword = '(' . $id . ', "' . $keyword .'"), ';
			} else {
				$insertKeyword = $insertKeyword . '(' . $id . ', "' . $keyword .'"), ';
			}
		}

		$sql = 'INSERT INTO ' . $wixfilemeta . '(id, keyword) VALUES ' . $insertKeyword;
		$sql = mb_substr($sql, 0, (mb_strlen($sql)-2));
		$result = $wpdb->query( $sql );					

		if ( $result != 0 ) $test = 'SUCESS';
		else $test = 'FAIL';
	}

	if ( !empty($insertTargetArray) ) {
		$insertTarget = '';

		foreach ($insertTargetArray as $index => $valueArray) {
			$target = $valueArray['target'];
			$id = $valueArray['keyword_id'];
			if ( $index == 0 ) {
				$insertTarget = '(' . $id . ', "' . $target . '"), ';
			} else {
				$insertTarget = $insertTarget . '(' . $id . ', "' . $target . '"), ';
			}
		}

		$sql = 'INSERT INTO ' . $wixfile_targets . '(keyword_id, target) VALUES ' . $insertTarget;
		$sql = mb_substr($sql, 0, (mb_strlen($sql)-2));
		$result = $wpdb->query( $sql );					

		if ( $result != 0 ) $test = 'SUCESS 2';
		else $test = 'FAIL 2';
	}

	$json = array(
		"test" => $test,
		// "entry" => $entry,
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

		$publish_post_url = $wpdb->get_var("SELECT guid FROM " . $wpdb->posts . " 
										 WHERE (post_type='post' OR post_type='page') AND post_status='publish'
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
			/* wpautopを使ってるから、必ずpタグ自動挿入の影響を受ける。the_contentにremove_filterしてると合わなくなる */
			// $innerLinkArray = keyword_location( strip_tags($_POST['after_body_part']) );
			$innerLinkArray = keyword_location( strip_tags(wpautop($_POST['after_body_part'])) );

			if ( count($innerLinkArray) != 0 ) {
				$tmp = json_encode($innerLinkArray, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
				$newBody = new_body( wpautop($_POST['after_body_part']), $tmp, true );
			} else {
				$newBody = new_body( wpautop($_POST['after_body_part']), '', true );
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
			// "js" => $tmp,
			// "js2" => html_entity_decode($_POST['after_body_part']),
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

	$wixfilemeta = $wpdb->prefix . 'wixfilemeta';
	$wixfile_targets = $wpdb->prefix . 'wixfile_targets';
	$is_db_exists = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $wixfilemeta));

	if ( $is_db_exists == $wixfilemeta ) {

		$sql = 'SELECT COUNT(*) FROM ' . $wixfilemeta;
		if ( $wpdb->get_var($sql) != 0 ) {

			$sql = 'SELECT keyword FROM ' . $wixfilemeta;
			$distinctKeywords = $wpdb->get_results($sql);

			if ( !empty($distinctKeywords) ) {
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
						$sql = 'SELECT wt.target FROM ' . $wixfilemeta . ' wm, ' . $wixfile_targets . ' wt WHERE wm.id = wt.keyword_id AND wm.keyword="' . $keyword . '"';
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
	}

	return $returnValue;
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