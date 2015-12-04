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


//ドキュメント作成中に行うエントリ推薦
add_action( 'wp_ajax_wix_entry_recommendation_creating_document', 'wix_entry_recommendation_creating_document' );
add_action( 'wp_ajax_nopriv_wix_entry_recommendation_creating_document', 'wix_entry_recommendation_creating_document' );
function wix_entry_recommendation_creating_document() {
	global $doc_title, $term_featureObj;

	header("Access-Control-Allow-Origin: *");
	header('Content-type: application/javascript; charset=utf-8');

	$returnValue = '';

	$post_id = (int) substr( $_POST['target'], strlen('wp-preview-') );

	if ( get_option('morphological_analysis') != false ) {
		$parse = wix_morphological_analysis($_POST['sentence']);
		$wordsArray = wix_compound_noun_extract($parse);
		$words_countArray = array_word_count($wordsArray);

		//tf, idfの計算
		if ( empty($term_featureObj) ) {
			wix_tf($words_countArray);
			wix_idf_creating_document($post_id);
		}

		//tf-idf値の降順に並び替え
		$tf_idfArray = array();
		foreach ($term_featureObj as $key => $value) {
			$tf_idf = $value['tf'] * $value['idf'];
			$value['tf_idf'] = $tf_idf;
			$term_featureObj[$key] = $value;

			$tf_idfArray[] = $tf_idf;
		}
		array_multisort($tf_idfArray, SORT_DESC, SORT_NUMERIC, $term_featureObj);


		//wp_wixfileテーブルに入ってない単語が出現するページタイトルの提示
/**
	これ違う気がする。テーブルに入ってない奴も推薦していいんじゃね？（2015/09/22）
**/
		$doc_title = $_POST['doc-title'];
		$returnValue = wix_post_title(no_wixfile_entry($term_featureObj));
		
	} else {
		$returnValue = 'no_selection_morphological_analysis';
	}

	$json = array(
		"returnValue" => $returnValue,
		"similarity" => $term_featureObj,
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

		if ( $result != 0 ) $result = 'SUCCESS';
		else $result = 'FAIL';
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

		if ( $result != 0 ) $result = 'SUCCESS';
		else $result = 'FAIL 2';
	}

	$json = array(
		"result" => $result,
		"keyword_id" => $latest_id,
		"keyword" => $keyword,
	);
	echo json_encode( $json );

	die();
}

//WIXファイルに挿入・更新・削除が行われた時の、「WIXファイル内キーワードが出現するドキュメント」を表すテーブルをupdate
add_action( 'wp_ajax_wix_wixfilemeta_posts_insert', 'wix_wixfilemeta_posts_insert' );
add_action( 'wp_ajax_nopriv_wix_wixfilemeta_posts_insert', 'wix_wixfilemeta_posts_insert' );
function wix_wixfilemeta_posts_insert() {
	global $wpdb;

	header("Access-Control-Allow-Origin: *");
	header('Content-type: application/javascript; charset=utf-8');

	$wixfilemeta_posts = $wpdb->prefix . 'wixfilemeta_posts';
	$insert_wixfilemeta_postsArray = array();

	//まだDBに１つもドキュメントがなかったら計算しない.(でも基本的にemptyにならないみたい)
	$sql = 'SELECT id, post_content FROM ' . $wpdb->posts . ' WHERE post_status!="inherit" and post_status!="trash" and post_status!="auto-save" and post_status!="auto-draft" ORDER BY id ASC';
	$doc_Obj = $wpdb->get_results($sql);
	if ( !empty($doc_Obj) ) {
		$keyword_id = $_POST['keyword_id'];
		$keyword = $_POST['keyword'];

		foreach ($doc_Obj as $index => $value) {
			$body = $value->post_content;
			$doc_id = $value->id;

			if ( strpos($body, $keyword) !== false )
				array_push( $insert_wixfilemeta_postsArray, 
								array(
										'keyword_id' => $keyword_id,
										'doc_id' => $doc_id
									)
							 );
		}

		//DB挿入
		if ( !empty($insert_wixfilemeta_postsArray) ) {
			$insertTuple = '';

			foreach ($insert_wixfilemeta_postsArray as $index => $valueArray) {
				$keyword_id = $valueArray['keyword_id'];
				$doc_id = $valueArray['doc_id'];
				if ( $index == 0 ) {
					$insertTuple = '(' . $keyword_id . ', ' . $doc_id .'), ';
				} else {
					$insertTuple = $insertTuple . '(' . $keyword_id . ', ' . $doc_id .'), ';
				}
			}

			$sql = 'INSERT INTO ' . $wixfilemeta_posts . '(keyword_id, doc_id) VALUES ' . $insertTuple;
			$sql = mb_substr($sql, 0, (mb_strlen($sql)-2));
			$result = $wpdb->query( $sql );

			if ( $result != 0 ) $result = 'SUCCESS';
			else $result = 'FAIL';
		}
	}

	$json = array(
		"result" => $result,
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
			// "test" => $innerLinkArray,
			// "test2" => $response_html,
			// "test2" => strip_tags(wpautop($_POST['after_body_part'])),
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

	//DBへの挿入
	wix_create_decidefile_inDB($post_ID, $object);

	$json = array(
		"response" => 'create decidefile'
	);
	echo json_encode($json);

	die();
}

function wix_create_decidefile_inDB($doc_id, $object) {
	global $wpdb;

	$wixfilemeta = $wpdb->prefix . 'wixfilemeta';
	$wix_decidefile_index = $wpdb->prefix . 'wix_decidefile_index';
	$wix_decidefile_history = $wpdb->prefix . 'wix_decidefile_history';
	$version = 0;
	$dfile_id = 0;
	$keywordArray = array();

	//現在の最新バージョンの値を取ってきてから挿入
	$sql = 'SELECT * FROM ' . $wix_decidefile_index . ' WHERE doc_id=' . $doc_id . ' ORDER BY version DESC LIMIT 1';
	$latest_decideObj = $wpdb->get_results($sql);
	if ( !empty($latest_decideObj) ) {
		foreach ($latest_decideObj as $index => $value) {
			$version = intval( $value->version ) + 1;
		}
	}
	$sql = 'INSERT INTO ' . $wix_decidefile_index . '(doc_id, version) VALUES ' . '(' . $doc_id . ', ' . $version . ')';
	$wpdb->query( $sql );

	//キーワードとID一覧
	$sql = 'SELECT * FROM ' . $wixfilemeta;
	$keywordObj = $wpdb->get_results($sql);
	foreach ($keywordObj as $index => $value) {
		$keywordArray[$value->keyword] = $value->id;
	}

	//先程挿入したdfile_idの取得
	$sql = 'SELECT dfile_id FROM ' . $wix_decidefile_index . ' ORDER BY dfile_id DESC LIMIT 1';
	$latest_Obj = $wpdb->get_results($sql);
	foreach ($latest_Obj as $index => $value) {
		$dfile_id = $value->dfile_id;
	}

	//Decideファイル情報の挿入
	$insertRecord = '';
	foreach ($object as $index => $array) {
		$start = $array['start'];
		$end = $array['end'];
		$nextStart = $array['nextStart'];
		$keyword_id = $keywordArray[$array['keyword']];
		$target = $array['target'];

		if ( $index == 0 ) 
			$insertRecord = '(' . $dfile_id . ', ' . $start . ', ' . $end . ', ' . $nextStart . ', ' . $keyword_id . ', ' . $target . ')';
		else 
			$insertRecord = $insertRecord . ', (' . $dfile_id . ', ' . $start . ', ' . $end . ', ' . $nextStart . ', ' . $keyword_id . ', ' . $target . ')';	
	}
	$sql = 'INSERT INTO ' . $wix_decidefile_history . '(dfile_id, start, end, nextStart, keyword_id, target) VALUES ' . $insertRecord;
	$wpdb->query( $sql );
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




?>