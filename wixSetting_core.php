<?php

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
		     tf_idf float NOT NULL DEFAULT 0,
		     bm25 float NOT NULL DEFAULT 0,
		     PRIMARY KEY(doc_id,keyword(255)),
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
		     cos_similarity_tfidf float NOT NULL DEFAULT 0,
		     cos_similarity_bm25 float NOT NULL DEFAULT 0,
		     jaccard float NOT NULL DEFAULT 0,
		     minhash float NOT NULL DEFAULT 0,
		     PRIMARY KEY(doc_id,doc_id2),
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
			PRIMARY KEY id (id)
			);";
	dbDelta($sql);


	$table_name = $wpdb->prefix . 'wixfile_targets';
	$is_db_exists = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table_name));
	if ( $is_db_exists == $table_name ) return;
	$sql = "CREATE TABLE " . $table_name . " (
			keyword_id bigint(20) NOT NULL, 
			target tinytext NOT NULL, 
			PRIMARY KEY(keyword_id, target(255)), 
			FOREIGN KEY (keyword_id) REFERENCES wp_wixfilemeta(id) ON UPDATE CASCADE ON DELETE CASCADE
			);";
	dbDelta($sql);


	$table_name = $wpdb->prefix . 'wixfilemeta_posts';
	$is_db_exists = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table_name));
	if ( $is_db_exists == $table_name ) return;
	$sql = "CREATE TABLE " . $table_name . " (
			keyword_id bigint(20) NOT NULL, 
			doc_id bigint(20) UNSIGNED NOT NULL, 
			PRIMARY KEY(keyword_id, doc_id), 
			FOREIGN KEY (keyword_id) REFERENCES wp_wixfilemeta(id) 
				ON UPDATE CASCADE ON DELETE CASCADE, 
			FOREIGN KEY (doc_id) REFERENCES wp_posts(ID) 
				ON UPDATE CASCADE ON DELETE CASCADE
			);";
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
			);";
	dbDelta($sql);


	$table_name = $wpdb->prefix . 'wix_decidefile_index';
	$is_db_exists = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table_name));
	if ( $is_db_exists == $table_name ) return;
	$sql = "CREATE TABLE " . $table_name . " (
			dfile_id bigint(20) auto_increment, 
			doc_id bigint(20) UNSIGNED NOT NULL, 
			version bigint(20) NOT NULL, 
			time DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, 
			PRIMARY KEY(dfile_id, doc_id, version), 
			FOREIGN KEY(doc_id) REFERENCES wp_posts(ID) 
				ON UPDATE CASCADE ON DELETE CASCADE
			);";
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



/* 設定部分 */
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

					if ( isset( $_POST['decidefile_manualupdate'] ) && $_POST['decidefile_manualupdate'] ) {
						if ( $_POST['decidefile_manualupdate'] == 'true' )
							add_option( 'manual_decide', 'true' );
						else 
							add_option( 'manual_decide', 'false' );
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

				// if ( isset( $_POST['pattern'] ) && $_POST['pattern'] && isset( $_POST['filename'] ) && $_POST['filename'] ) {

				// 	$hostName = '<' . get_option( 'wix_host_name' ) . '>' . "\n";

				// 	file_put_contents( PatternFile, $hostName, FILE_USE_INCLUDE_PATH | LOCK_EX );

				// 	foreach ( $_POST['pattern'] as $key => $pattern ) {
				// 		foreach ($wids_filenames as $wid => $filename) {
				// 			if ( $filename == $_POST['filename'][$key] ) {
				// 				$pattern_filename = "\t" . stripslashes($pattern) . ' : ' . $wid . "\n";
				// 				file_put_contents( PatternFile, $pattern_filename, FILE_APPEND | LOCK_EX );
				// 				break;
				// 			}
				// 		}
				// 	}

				// 	set_transient( 'wix_settings', '設定更新しました', 10 );
				// }

				//WIXファイル系
				if ( isset( $_POST['wixfile_autocreate'] ) && $_POST['wixfile_autocreate'] ) {
					if ( $_POST['wixfile_autocreate'] == 'true' )
						update_option( 'wixfile_autocreate', 'true' );
					else
						update_option( 'wixfile_autocreate', 'false' );
				}
				if ( isset( $_POST['wixfile_manualupdate'] ) && $_POST['wixfile_manualupdate'] ) {
					if ( $_POST['wixfile_manualupdate'] == 'true' )
						update_option( 'wixfile_manualupdate', 'true' );
					else
						update_option( 'wixfile_manualupdate', 'false' );
				}

				//Decideファイル系
				if ( isset( $_POST['decidefile_apply'] ) && $_POST['decidefile_apply'] ) {
					if ( $_POST['decidefile_apply'] == 'true' )
						update_option( 'decidefile_apply', 'true' );
					else
						update_option( 'decidefile_apply', 'false' );
				}
				if ( isset( $_POST['decidefile_autocreate'] ) && $_POST['decidefile_autocreate'] ) {
					if ( $_POST['decidefile_autocreate'] == 'true' )
						update_option( 'decidefile_autocreate', 'true' );
					else
						update_option( 'decidefile_autocreate', 'false' );
				}
				if ( isset( $_POST['decidefile_manualupdate'] ) && $_POST['decidefile_manualupdate'] ) {
					if ( $_POST['decidefile_manualupdate'] == 'true' )
						update_option( 'manual_decide', 'true' );
					else 
						update_option( 'manual_decide', 'false' );
				}

				set_transient( 'wix_settings', '設定更新しました', 10 );
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
			/*
			* wixfilemeta_postsテーブルの新規キーワード分挿入用Array
			*/
			$wixfilemeta_posts_array = array();

			if ( isset( $_POST['wixfile_settings'] ) && $_POST['wixfile_settings'] ) {
				$wixfilemeta = $wpdb->prefix . 'wixfilemeta';
				$wixfile_targets = $wpdb->prefix . 'wixfile_targets';

				//エントリ挿入用モジュール
				if ( isset( $_POST['insert_keywords'] ) && $_POST['insert_keywords'] && isset( $_POST['insert_targets'] ) && $_POST['insert_targets'] ) {
					
					$target_checker = array();
					$insertKeywordArray = array();
					$insertTargetArray = array();
					$latest_id = 0;

					foreach ($_POST['insert_keywords'] as $index => $keyword) {
						if ( !empty($keyword) ) {
							$target = $_POST['insert_targets'][$index];
							$keyword_flag = false;
							$entry_flag = false;

							//二重挿入しないようにフラグ立てる
							foreach ($target_checker as $key => $valueArray) {
								if ( $key == $keyword ) {
									$keyword_flag = true;
									foreach ($valueArray as $i => $value) {
										if ( $value == $target ) {
											$entry_flag = true;
											break;
										}
									}
								}
							}

							if ( $entry_flag == false ) {
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
										$wixfilemeta_posts_array += array($latest_id => $keyword);


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
						// $result = $wpdb->query( $sql );	
						var_dump($sql);

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
						// $result = $wpdb->query( $sql );					
						var_dump($sql);

						if ( $result != 0 ) set_transient( 'wix_settings', 'WIX FILE 更新しました', 1 );
						else set_transient( 'wix_settings', 'WIX FILE 更新に失敗しました', 1 );

					} else {
						set_transient( 'wix_settings', '既にある情報、もしくはフォームに値がなかったため更新しませんでした', 1 );
					}

				}

				//エントリ更新用モジュール
				if ( isset( $_POST['update_keywords'] ) && $_POST['update_keywords'] && isset( $_POST['update_targets'] ) && $_POST['update_targets'] ) {

					$insertArray = array();
					$updateArray = array();
					$delete_metaArray = array();
					$delete_targetsArray = array();
					$entry_checker = array();

					foreach ($_POST['update_keywords'] as $index => $keyword) {
						$target = $_POST['update_targets'][$index];
						$org_keyword = $_POST['org_update_keywords'][$index];
						$org_target = $_POST['org_update_targets'][$index];

						$keyword_flag = false;
						$entry_flag = false;
						$new_keyword_id = 0;

						//二重挿入・更新しないようにフラグ立てる
						foreach ($entry_checker as $key => $valueArray) {
							if ( $key == $keyword ) {
								$keyword_flag = true;
								$targetArray = $valueArray['target'];
								$new_keyword_id = $valueArray['id'];

								foreach ($targetArray as $i => $value) {
									if ( $value == $target ) {
										$entry_flag = true;
										break;
									}
								}
							}
						}

						if ( $entry_flag == false ) {
							//更新しようとしている新エントリが既に存在しているか確認
							$sql = 'SELECT COUNT(*) FROM ' . $wixfilemeta . ', ' . 
										$wixfile_targets . ' WHERE id = keyword_id AND keyword="' . 
										$keyword . '" AND target="' . $target . '"';

							//既に存在している場合は、何もしない。
							if ( $wpdb->get_var($sql) == 0 ) {
								/** 
									keyword固定と、target固定の2つにしても、多分同じ結果が得られる(2015/10/22) 
								**/
								if ( ($keyword != $org_keyword) && ($target != $org_target) ) {
									//エントリに変更がある場合
									//更新予定キーワードが既に存在するかチェック (大槻研, A) -> (大槻, B)
									$sql = 'SELECT id FROM ' . $wixfilemeta . ' WHERE keyword="' . $keyword . '"';
									$keyword_checkerObj = $wpdb->get_results($sql);

									//キーワードがない場合挿入。あるならid含め更新
									if ( empty($keyword_checkerObj) ) {
										//エントリを挿入用Arrayに挿入
										if ( $keyword_flag == false ) {
											$sql = 'SELECT MAX(id) FROM ' . $wixfilemeta;
											$new_keyword_id = (int) $wpdb->get_var($sql) + 
																count($insertArray) + count($updateArray) +  1;
										}

										$sql = 'SELECT id FROM ' . $wixfilemeta . ' WHERE keyword="' . $org_keyword . '"';
										$org_keywordObj = $wpdb->get_results($sql);
										$org_keyword_id = (int)$org_keywordObj[0]->id;
										
										array_push($insertArray, 
													array(
														'new_keyword_id' => $new_keyword_id,
														'org_keyword_id' => $org_keyword_id,
														'new_keyword' => $keyword,
														'new_target' => $target,
														'org_target' => $org_target,
														'keyword_flag' => $keyword_flag,
														)
													);
										$wixfilemeta_posts_array += array($new_keyword_id => $keyword);


									} else {
										//エントリを更新用Arrayに挿入
										$new_keyword_id = (int)$keyword_checkerObj[0]->id;

										$sql = 'SELECT id FROM ' . $wixfilemeta . ' WHERE keyword="' . $org_keyword . '"';
										$org_keywordObj = $wpdb->get_results($sql);
										$org_keyword_id = (int)$org_keywordObj[0]->id;

										array_push($updateArray, 
													array(
														'new_keyword_id' => $new_keyword_id,
														'org_keyword_id' => $org_keyword_id,
														'new_target' => $target,
														'org_target' => $org_target,
														)
													);						
									}


								} else if ( ($keyword == $org_keyword) && ($target != $org_target) ) {
									//キーワードには変更がない場合
									$sql = 'SELECT id FROM ' . $wixfilemeta . ' WHERE keyword="' . $org_keyword . '"';
									$org_keywordObj = $wpdb->get_results($sql);
									$org_keyword_id = (int)$org_keywordObj[0]->id; //$new_keyword_id = $org_keyword_id;

									array_push($updateArray, 
												array(
													'new_keyword_id' => $org_keyword_id,
													'org_keyword_id' => $org_keyword_id,
													'new_target' => $target,
													'org_target' => $org_target,
													)
												);									

								} else if ( ($keyword != $org_keyword) && ($target == $org_target) ) {
									//ターゲットには変更がない場合
									//更新予定キーワードが既に存在するかチェック
									$sql = 'SELECT id FROM ' . $wixfilemeta . ' WHERE keyword="' . $keyword . '"';
									$keyword_checkerObj = $wpdb->get_results($sql);

									//キーワードがない場合挿入。あるならid含め更新
									if ( empty($keyword_checkerObj) ) {
										//エントリを挿入用Arrayに挿入
										if ( $keyword_flag == false ) {
											$sql = 'SELECT MAX(id) FROM ' . $wixfilemeta;
											$new_keyword_id = (int) $wpdb->get_var($sql) + 
																count($insertArray) + count($updateArray) +  1;
										}

										$sql = 'SELECT id FROM ' . $wixfilemeta . ' WHERE keyword="' . $org_keyword . '"';
										$org_keywordObj = $wpdb->get_results($sql);
										$org_keyword_id = (int)$org_keywordObj[0]->id;
										
										array_push($insertArray, 
													array(
														'new_keyword_id' => $new_keyword_id,
														'org_keyword_id' => $org_keyword_id,
														'new_keyword' => $keyword,
														'new_target' => $target,
														'org_target' => $org_target,
														'keyword_flag' => $keyword_flag,
														)
													);
										$wixfilemeta_posts_array += array($new_keyword_id => $keyword);

									} else {
										//エントリを更新用Arrayに挿入
										$new_keyword_id = (int)$keyword_checkerObj[0]->id;

										$sql = 'SELECT id FROM ' . $wixfilemeta . ' WHERE keyword="' . $org_keyword . '"';
										$org_keywordObj = $wpdb->get_results($sql);
										$org_keyword_id = (int)$org_keywordObj[0]->id;
										
										array_push($updateArray, 
													array(
														'new_keyword_id' => $new_keyword_id,
														'org_keyword_id' => $org_keyword_id,
														'new_target' => $target,
														'org_target' => $org_target,
														)
													);										
									}

								}
							}

							//二重挿入チェッカーに追加
							if ( empty($entry_checker) ) {
								$entry_checker[$keyword] = array(
																'id' => $new_keyword_id,
																'target' => array($target),
																);
							} else {
								if ( array_key_exists($keyword, $entry_checker) ) {
									$valueArray = $entry_checker[$keyword];
									$targetArray = $valueArray['target'];
									array_push($targetArray, $target);
									$valueArray['target'] = $targetArray;
									$entry_checker[$keyword] = $valueArray;
								} else {
									$entry_checker[$keyword] = array(
																'id' => $new_keyword_id,
																'target' => array($target),
																);
								}
							}

						} else {
							//更新元のエントリをDB(主にwixfile_targetsテーブル)から削除する
							$sql = 'SELECT id FROM ' . $wixfilemeta . ' WHERE keyword="' . $org_keyword . '"';
							$org_keywordObj = $wpdb->get_results($sql);
							$org_keyword_id = (int)$org_keywordObj[0]->id;

							array_push($delete_targetsArray, 
										array(
											'org_keyword_id' => $org_keyword_id,
											'org_target' => $org_target,
											)
										);							
						}
					}

					/* もしtargetsテーブルのkeyword_idに出現しない、metaテーブルのidが残ってるなら、削除しないとって思ったけど、
									エントリ挿入時にちゃんとチェックしてるから見逃していいのかも。一応削除 */
					$sql = 'SELECT id, keyword FROM ' . $wixfilemeta . ' WHERE id NOT IN (SELECT DISTINCT keyword_id FROM ' . $wixfile_targets . ')';
					$delete_keywordObj = $wpdb->get_results($sql);
					if ( !empty($delete_keywordObj) ) {
						foreach ($delete_keywordObj as $index => $value) {
							$id = $value->id;
							$keyword = $value->keyword;
							array_push($delete_metaArray, $value->id);
						}
					}

					//DB操作
					if ( !empty($insertArray) ) {
						foreach ($insertArray as $index => $valueArray) {
							$new_keyword_id = $valueArray['new_keyword_id'];
							$org_keyword_id = $valueArray['org_keyword_id'];
							$new_keyword = $valueArray['new_keyword'];
							$new_target = $valueArray['new_target'];
							$org_target = $valueArray['org_target'];
							$keyword_flag = (boolean)$valueArray['keyword_flag'];

							if ( $keyword_flag == false ) {
								$sql = 'INSERT INTO ' . $wixfilemeta . '(id, keyword) VALUES ' . '(' . $new_keyword_id . ', "' . $new_keyword .'")';
								var_dump($sql);
								// $wpdb->query( $sql );
							}

							$sql = 'UPDATE ' . $wixfile_targets . ' SET keyword_id=' . $new_keyword_id . ', target="' .
								 $new_target . '" WHERE keyword_id=' . $org_keyword_id . ' AND target="' . $org_target . '"';
							var_dump($sql);
							// $wpdb->query( $sql );
						}
					}

					if ( !empty($updateArray) ) {
						foreach ($updateArray as $index => $valueArray) {
							$new_keyword_id = $valueArray['new_keyword_id'];
							$org_keyword_id = $valueArray['org_keyword_id'];
							$new_target = $valueArray['new_target'];
							$org_target = $valueArray['org_target'];

							$sql = 'UPDATE ' . $wixfile_targets . ' SET keyword_id=' . $new_keyword_id . ', target="' .
								 $new_target . '" WHERE keyword_id=' . $org_keyword_id . ' AND target="' . $org_target . '"';
							var_dump($sql);
							// $wpdb->query( $sql );
						}
					}

					if ( !empty($delete_targetsArray) ) {
						foreach ($delete_targetsArray as $index => $valueArray) {
							$org_keyword_id = $valueArray['org_keyword_id'];
							$org_target = $valueArray['org_target'];

							$sql = 'DELETE FROM ' . $wixfile_targets . ' WHERE keyword_id = ' . $org_keyword_id . ' AND target = "' . $org_target . '"';
							var_dump($sql);
							// $wpdb->query( $sql );
						}
					} 

					if ( !empty($delete_metaArray) ) {
						foreach ($delete_metaArray as $index => $org_keyword_id) {
							$sql = 'DELETE FROM ' . $wixfilemeta . ' WHERE id=' . $org_keyword_id;
							var_dump($sql);
							// $wpdb->query( $sql );
							/**
								この時、wixfilemeta_postsの行はカスケードDELETEされる
							**/
						}
					}
				}

				//エントリ削除用モジュール 
				if ( isset( $_POST['delete_keywords'] ) && $_POST['delete_keywords'] && isset( $_POST['delete_targets'] ) && $_POST['delete_targets'] ) {

					foreach ($_POST['delete_keywords'] as $index => $keyword) {
						$target = $_POST['delete_targets'][$index];

						$sql = 'SELECT id FROM ' . $wixfilemeta . ' wm, ' . 
									$wixfile_targets . ' wt WHERE wm.keyword="' . $keyword . 
										'" AND wt.target="' . $target . 
										'" AND wm.id = wt.keyword_id';
						$keyword_idObj = $wpdb->get_results($sql);
						if ( !empty($keyword_idObj) ) {
							$keyword_id = (int)$keyword_idObj[0]->id;

							$sql = 'DELETE FROM ' . $wixfile_targets . ' WHERE keyword_id = ' . $keyword_id . ' AND target = "' . $target . '"';
							var_dump($sql);
							// $wpdb->query( $sql );
						}

					}

					$sql = 'SELECT id, keyword FROM ' . $wixfilemeta . ' WHERE id NOT IN (SELECT DISTINCT keyword_id FROM ' . $wixfile_targets . ')';
					$delete_keywordObj = $wpdb->get_results($sql);
					if ( !empty($delete_keywordObj) ) {
						foreach ($delete_keywordObj as $index => $value) {
							$id = $value->id;
							$keyword = $value->keyword;
							$sql = 'DELETE FROM ' . $wixfilemeta . ' WHERE id=' . $id;
							var_dump($sql);
							// $wpdb->query( $sql );
							/**
								この時、wixfilemeta_postsの行はカスケードDELETEされる
							**/
						}
					}

				}

				wixfilemeta_posts_insert( $wixfilemeta_posts_array );
				set_transient( 'wix_settings', 'WIXファイル 更新しました', 1 );
			}
		} else {
			$e -> add('error', __( 'Please check various WIX FIle form', 'wixfile_settings' ) );
			set_transient( 'wixfile_settings_errors', $e->get_error_message(), 10 );
		}
	}
}

//WIXファイルに挿入・更新・削除が行われた時の、「WIXファイル内キーワードが出現するドキュメント」を表すテーブルをupdate
function wixfilemeta_posts_insert( $array ) {
	var_dump( $array );
	global $wpdb;
	$insert_wixfilemeta_postsArray = array();

	if ( !empty($array) ) {
		$wixfilemeta_posts = $wpdb->prefix . 'wixfilemeta_posts';

		//まだDBに１つもドキュメントがなかったら計算しない.(でも基本的にemptyにならないみたい)
		$sql = 'SELECT id, post_content FROM ' . $wpdb->posts . ' WHERE post_status!="inherit" and post_status!="trash" and post_status!="auto-save" and post_status!="auto-draft" ORDER BY id ASC';
		$doc_Obj = $wpdb->get_results($sql);
		if ( !empty($doc_Obj) ) {

			foreach ($doc_Obj as $index => $value) {
				$body = $value->post_content;
				$doc_id = $value->id;
				
				foreach ($array as $keyword_id => $keyword) {
					$keyword_checker = array();
					
					if ( !array_key_exists($keyword_id, $keyword_checker) ) {

						if ( strpos($body, $keyword) !== false )
							array_push( $insert_wixfilemeta_postsArray, 
											array(
													'keyword_id' => $keyword_id,
													'doc_id' => $doc_id
												)
										 );

						//二重チェッカーに追加
						$keyword_checker[$keyword_id] = null;
					}
					
				}

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
				var_dump($sql);
				// $wpdb->query( $sql );
			}
		}

	}
}

/**
	↓２つの関数を別のファイルに移動したい。ここにあるのはキモチワルイ
**/
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
		//まだDBに１つもドキュメントがなかったら計算しない.(でも基本的に0にならないみたい)
		$sql = 'SELECT COUNT(*) FROM ' . $wpdb->posts . ' WHERE post_status!="inherit" and post_status!="trash" and post_status!="auto-save" and post_status!="auto-draft"';
		if ( $wpdb->get_var($sql) == 0 ) return;

		$insertArray = wix_correspond_keywords( $post->post_content );
		//全削除からの全挿入
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
		} else {
			$sql = 'SELECT COUNT(*) FROM ' . $table_name . ' WHERE doc_id = ' . $doc_id;
			if ( $wpdb->get_var($sql) != 0 ) {
				$sql = 'DELETE FROM ' . $table_name . ' WHERE doc_id = ' . $doc_id;
				$wpdb->query( $sql );
			}
		}
	}

}

//WIXファイル内のどのキーワードが「その」ドキュメント上に出現するか
function wix_correspond_keywords( $body ) {
	global $wpdb;
	/*
	* $correspondKeywords: [wixfilemeta_postsテーブルのid]
	*/

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

/**
	↓のsqlの cos_similarityはもうない。今はcos_similarity_tfidfとcos_similarity_bm25
*/
	//クリックされたドキュメントとの関連度を持つドキュメント群
	// $sql = 'SELECT * FROM ' . $wpdb->prefix . 'wix_document_similarity' .
	//  ' WHERE cos_similarity != 0 AND (doc_id=' . $doc_id . ' OR doc_id2=' . $doc_id . ') order by cos_similarity desc';
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





?>