<?php

//--------------------------------------------------------------------------
//
//  プラグイン有効の際に行うオプションの追加
//
//--------------------------------------------------------------------------
register_activation_hook( __FILE__, 'wix_manual_decide_init' );
function wix_manual_decide_init() {
	update_option( 'manual_decideFlag', 'true' );
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
		     tf float NOT NULL,
		     idf float NOT NULL,
		     tf_idf float NOT NULL,
		     UNIQUE(doc_id,keyword(20)),
		     FOREIGN KEY (doc_id) REFERENCES " . $wpdb->prefix . 'posts' . "(ID)
		     ON UPDATE CASCADE ON DELETE CASCADE
	        );";
	dbDelta($sql);


	$table_name = $wpdb->prefix . 'wix_document_similarity';
	$is_db_exists = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table_name));
	if ( $is_db_exists == $table_name ) return;
	$sql = "CREATE TABLE " . $table_name . " (
	         doc_id bigint(20) UNSIGNED,
		     doc_id2 bigint(20) UNSIGNED,
		     cos_similarity float NOT NULL,
		     UNIQUE(doc_id,doc_id2),
		     FOREIGN KEY (doc_id) REFERENCES " . $wpdb->prefix . 'posts' . "(ID)
		      ON UPDATE CASCADE ON DELETE CASCADE,
		     FOREIGN KEY (doc_id2) REFERENCES " . $wpdb->prefix . 'posts' . "(ID)
		      ON UPDATE CASCADE ON DELETE CASCADE
	        );";
	dbDelta($sql);


	$table_name = $wpdb->prefix . 'wixfilemeta';
	$is_db_exists = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table_name));
	if ( $is_db_exists == $table_name ) return;
	$sql = "CREATE TABLE " . $table_name . " (
			id bigint(20) NOT NULL, 
			keyword tinytext NOT NULL, 
			target_num mediumint(9) NOT NULL, 
			doc_num mediumint(9) NOT NULL, 
			UNIQUE KEY id (id)
			);";
	dbDelta($sql);


	$table_name = $wpdb->prefix . 'wixfile_targets';
	$is_db_exists = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table_name));
	if ( $is_db_exists == $table_name ) return;
	$sql = "CREATE TABLE " . $table_name . " (
			keyword_id bigint(20) NOT NULL, 
			target tinytext NOT NULL, 
			UNIQUE(keyword_id, target(255)), 
			FOREIGN KEY (keyword_id) REFERENCES wp_wixfilemeta(id) ON UPDATE CASCADE ON DELETE CASCADE
			);";
	dbDelta($sql);


	$table_name = $wpdb->prefix . 'wixfilemeta_posts';
	$is_db_exists = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table_name));
	if ( $is_db_exists == $table_name ) return;
	$sql = "CREATE TABLE " . $table_name . " (
			keyword_id bigint(20) NOT NULL, 
			doc_id bigint(20) UNSIGNED NOT NULL, 
			UNIQUE(keyword_id, doc_id), 
			FOREIGN KEY (keyword_id) REFERENCES wp_wixfilemeta(id) 
				ON UPDATE CASCADE ON DELETE CASCADE, 
			FOREIGN KEY (doc_id) REFERENCES wp_posts(ID) 
				ON UPDATE CASCADE ON DELETE CASCADE
			);";
	dbDelta($sql);


	$table_name = $wpdb->prefix . 'posts';
	if ( $is_db_exists == $table_name ) return;
	$sql = "ALTER TABLE " . $table_name . " ADD COLUMN doc_length bigint DEFAULT 0 NOT NULL;";
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




add_action( 'admin_init', 'wix_settings_core' );
function wix_settings_core() {
	global $wids_filenames;
	//nonceの値の✔
	if ( isset( $_POST['nonce_init_settings'] ) && $_POST['nonce_init_settings'] ) {

		if ( check_admin_referer( 'my-nonce-key', 'nonce_init_settings' ) ) {

			$e = new WP_Error();

			if ( isset( $_POST['wix_init_settings'] ) && $_POST['wix_init_settings'] ) {

				if ( isset( $_POST['authorName'] ) && $_POST['authorName'] ) {

					if ( !isset( $_POST['hostName'] ) || empty($_POST['hostName']) ) {
						update_option( 'wix_host_name', DB_HOST );
						$hostName = '<' . DB_HOST . '>' . "\n";
					} else {
						update_option( 'wix_host_name', $_POST['hostName'] );
						$hostName = '<' . $_POST['hostName'] . '>' . "\n";
					}

					//オーサ名のDB登録
					update_option( 'wix_author_name', $_POST['authorName'] );


					if ( isset( $_POST['pattern'] ) && $_POST['pattern'] && isset( $_POST['filename'] ) && $_POST['filename'] ) {
						// FILE_APPEND フラグはファイルの最後に追記することを表し、
						// LOCK_EX フラグは他の人が同時にファイルに書き込めないことを表します。
						// stripslashesでアンエスケープ
						file_put_contents( PatternFile, $hostName, FILE_USE_INCLUDE_PATH | LOCK_EX );

						foreach ( $_POST['pattern'] as $key => $pattern ) {
							foreach ($wids_filenames as $wid => $filename) {
								if ( $filename == $_POST['filename'][$key] ) {
									$pattern_filename = "\t" . stripslashes($pattern) . ' : ' . $wid . "\n";
									file_put_contents( PatternFile, $pattern_filename, FILE_APPEND | LOCK_EX );
									break;
								}
							}
						}
					} else {
						file_put_contents( PatternFile, $hostName, FILE_USE_INCLUDE_PATH | LOCK_EX );
						$pattern_filename = "\t" . stripslashes('/*') . ' : 0' . "\n";
						file_put_contents( PatternFile, $pattern_filename, FILE_APPEND | LOCK_EX );
					}
					set_transient( 'wix_init_settings', '初期設定完了しました', 10 );

				} else {
					$e -> add('error', __( 'Please entry author name', 'wix_init_settings' ) );
					set_transient( 'wix_init_settings_errors', $e->get_error_message(), 10 );
				}
			}
		} else {
			$e -> add('error', __( 'Please entry one more', 'wix_init_settings' ) );
			set_transient( 'wix_init_settings_errors', $e->get_error_message(), 10 );
		}

	} else if ( isset( $_POST['nonce_settings'] ) && $_POST['nonce_settings'] ) {

		if ( check_admin_referer( 'my-nonce-key', 'nonce_settings' ) ) {

			$e = new WP_Error();

			if ( isset( $_POST['wix_settings'] ) && $_POST['wix_settings'] ) {

				if ( isset( $_POST['pattern'] ) && $_POST['pattern'] && isset( $_POST['filename'] ) && $_POST['filename'] ) {

					$hostName = '<' . get_option( 'wix_host_name' ) . '>' . "\n";

					file_put_contents( PatternFile, $hostName, FILE_USE_INCLUDE_PATH | LOCK_EX );

					foreach ( $_POST['pattern'] as $key => $pattern ) {
						foreach ($wids_filenames as $wid => $filename) {
							if ( $filename == $_POST['filename'][$key] ) {
								$pattern_filename = "\t" . stripslashes($pattern) . ' : ' . $wid . "\n";
								file_put_contents( PatternFile, $pattern_filename, FILE_APPEND | LOCK_EX );
								break;
							}
						}
					}

					set_transient( 'wix_settings', '設定更新しました', 10 );
				}
			}
		} else {
			$e -> add('error', __( 'Please check various form', 'wix_settings' ) );
			set_transient( 'wix_settings_errors', $e->get_error_message(), 10 );
		}
	}

}

add_action( 'admin_init', 'wixfile_settings_core' );
function wixfile_settings_core() {
	global $wpdb;
	//nonceの値の✔
	if ( isset( $_POST['nonce_wixfile_settings'] ) && $_POST['nonce_wixfile_settings'] ) {

		if ( check_admin_referer( 'my-nonce-key', 'nonce_wixfile_settings' ) ) {

			$e = new WP_Error();

			if ( isset( $_POST['wixfile_settings'] ) && $_POST['wixfile_settings'] ) {

				if ( isset( $_POST['keywords'] ) && $_POST['keywords'] && isset( $_POST['targets'] ) && $_POST['targets'] ) {
					
					$wixfilemeta = $wpdb->prefix . 'wixfilemeta';
					$wixfile_targets = $wpdb->prefix . 'wixfile_targets';
					$target_checker = array();
					$insertKeywordArray = array();
					$insertTargetArray = array();
					$latest_id = 0;

					foreach ($_POST['keywords'] as $index => $keyword) {
						if ( !empty($keyword) ) {
							$target = $_POST['targets'][$index];
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

						if ( $result != 0 ) set_transient( 'wix_settings', 'WIX FILE 更新しました', 1 );
						else set_transient( 'wix_settings', 'WIX FILE 更新に失敗しました', 1 );

					} else {
						set_transient( 'wix_settings', '既にある情報、もしくはフォームに値がなかったため更新しませんでした', 1 );
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

						if ( $result != 0 ) set_transient( 'wix_settings', 'WIX FILE 更新しました', 1 );
						else set_transient( 'wix_settings', 'WIX FILE 更新に失敗しました', 1 );

					} else {
						set_transient( 'wix_settings', '既にある情報、もしくはフォームに値がなかったため更新しませんでした', 1 );
					}
				}

			}
		} else {
			$e -> add('error', __( 'Please check various WIX FIle form', 'wixfile_settings' ) );
			set_transient( 'wixfile_settings_errors', $e->get_error_message(), 10 );
		}
	}
}



function created_wixfile_info() {
	$URL = 'http://trezia.db.ics.keio.ac.jp/WIXAuthorEditor_0.0.1/GetCreatedWIXFileNames';
	
	$ch = curl_init();
	$data = array(
	    'author' => 'sakusa'
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
		    echo 'エラーです.';
	    	echo curl_error( $ch );

		} else {
		
			global $wids_filenames;

			if ( strlen($response) != 0 ) {

				$tmp = explode(",", $response);

				foreach ($tmp as $key => $value) {
					$wid = explode(":", $value)[0];
					$filename = explode(":", $value)[1];

					$wids_filenames[$wid] = $filename;
				}

			}

		}

	} catch ( Exception $e ) {
	
		echo '捕捉した例外: ',  $e -> getMessage(), "\n";
	
	} finally {

		curl_close($ch);
	
	}

}


//manual_decideFlagを返す
add_action( 'wp_ajax_wix_manual_decide', 'wix_manual_decide' );
add_action( 'wp_ajax_nopriv_wix_manual_decide', 'wix_manual_decide' );
function wix_manual_decide() {

	header("Access-Control-Allow-Origin: *");
	header('Content-type: application/javascript; charset=utf-8');
		
	$manual_decideFlag = (string)$_POST['manual_decideFlag'];
	update_option( 'manual_decideFlag', $manual_decideFlag );
	$json = array(
		"data" => $manual_decideFlag
	);

	echo json_encode( $json );

	
    die();
}

//WIXFileのエントリ候補をwix_document_similarityテーブルから推薦
add_action( 'wp_ajax_wix_similarity_entry_recommend', 'wix_similarity_entry_recommend' );
add_action( 'wp_ajax_nopriv_wix_similarity_entry_recommend', 'wix_similarity_entry_recommend' );
function wix_similarity_entry_recommend() {
	global $wpdb;

	header("Access-Control-Allow-Origin: *");
	header('Content-type: application/javascript; charset=utf-8');

	$doc_id = $_POST['doc_id'];

	//候補キーワード群
	$sql = 'SELECT * FROM ' . $wpdb->prefix . 'wix_keyword_similarity' .
			 ' WHERE doc_id = ' . $doc_id . ' AND tf_idf != 0 order by tf_idf desc';
	$candidate_keywords = $wpdb->get_results($sql);

	//クリックされたドキュメントとの関連度を持つドキュメント群
	$sql = 'SELECT * FROM ' . $wpdb->prefix . 'wix_document_similarity' .
	 ' WHERE cos_similarity != 0 AND (doc_id=' . $doc_id . ' OR doc_id2=' . $doc_id . ') order by cos_similarity desc';
	$similar_documents = $wpdb->get_results($sql);


	if ( !empty($candidate_keywords) && !empty($similar_documents) ) {
		//候補ターゲット群をDBから持ってくる
		$selectQuery = '';
		foreach ($similar_documents as $key => $value) {
			if ( (int)$value->doc_id == $doc_id )
				$candidate_doc_id = $value->doc_id2;
			else
				$candidate_doc_id = $value->doc_id;


			if ( empty($selectQuery) )
				$selectQuery = 'ID=' . $candidate_doc_id . ' ';
			else
				$selectQuery = $selectQuery . 'OR ID=' . $candidate_doc_id . ' ';
		}
		$sql = 'SELECT ID, post_title, guid FROM ' . $wpdb->posts . ' WHERE ' . $selectQuery;
		$candidate_targets = $wpdb->get_results($sql);

		$returnValue = array();
		foreach ($candidate_keywords as $key => $value) {
			$returnValue[$value->keyword] = $candidate_targets;
		}


	} else {
		$returnValue = array();
	}

	$json = array(
		"entrys" => $returnValue,
		// "entrys" => $similar_documents,
	);

	echo json_encode( $json );

	
    die();
}


//ドキュメントの投稿ステータスが変わったら、WIXファイル内のどのキーワードが出現するかを算出
add_action( 'transition_post_status', 'wix_keyword_appearance_in_doc', 10, 3 );
function wix_keyword_appearance_in_doc( $new_status, $old_status, $post ) {
	global $wpdb;
	$doc_id = $post->ID;
	$table_name = $wpdb->prefix . 'wixfilemeta_posts';

	//ゴミ箱行きだったらDELTE.次にリビジョンに対するエントリを作らないように.
	if ( $new_status == 'trash' ) {

		$sql = 'DELETE FROM ' . $table_name . ' WHERE doc_id = ' . $doc_id;
		$wpdb->query( $sql );

	} else if ( $new_status != 'inherit' && $new_status != 'auto-draft' ) {
		//まだDBに１つもドキュメントがなかったら計算しない.(基本的に0にならないみたい)
		$sql = 'SELECT COUNT(*) FROM ' . $wpdb->posts . ' WHERE post_status!="inherit" and post_status!="trash" and post_status!="auto-save" and post_status!="auto-draft"';
		if ( $wpdb->get_var($sql) == 0 ) return;
		
		$insertArray = wix_correspond_keywords( $post->post_content );
		if ( !empty($insertArray) ) {
			$insertEntry = '';
			foreach ($insertArray as $index => $keyword_id) {
				if ( empty($insertEntry) )
					$insertEntry = '(' . $keyword_id . ', ' . $doc_id . '), ';
				else
					$insertEntry = $insertEntry . '(' . $keyword_id . ', ' . $doc_id . '), ';
			}
			//既に該当doc_idのタプルが存在するなら削除してから
			$sql = 'SELECT COUNT(*) FROM ' . $table_name . ' WHERE doc_id = ' . $doc_id;
			if ( $wpdb->get_var($sql) != 0 ) {
				$sql = 'DELETE FROM ' . $table_name . ' WHERE doc_id = ' . $doc_id;
				$wpdb->query( $sql );
			}
			$sql = 'INSERT INTO ' . $table_name . '(keyword_id, doc_id) VALUES ' . $insertEntry;
			$sql = mb_substr($sql, 0, (mb_strlen($sql)-2));
			$wpdb->query( $sql );
		}
	}

}

function wix_correspond_keywords( $body ) {
	global $wpdb;

	$sql = 'SELECT id, keyword FROM ' . $wpdb->prefix . 'wixfilemeta';
	$distinctKeywords = $wpdb->get_results($sql);
	$correspondKeywords = array();

	if ( !empty($distinctKeywords) ) {
		foreach ($distinctKeywords as $key => $value) {
			$keyword = $value->keyword;

			if ( strpos($body, $keyword) !== false )
				array_push( $correspondKeywords, $value->id );
		}
	}
	return $correspondKeywords;
}





?>