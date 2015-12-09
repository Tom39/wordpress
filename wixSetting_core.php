<?php
//形態素解析や推薦に使う要素が設定画面で設定されているか
$no_selection_morphological_analysis = 'false';
$no_selection_recommend_support = 'false';

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

						if ( empty($wids_filenames) ) {
							$pattern_filename = "\t" . stripslashes('/*') . ' : 0' . "\n";
							file_put_contents( PatternFile, $pattern_filename, FILE_APPEND | LOCK_EX );
						} else {
							foreach ( $_POST['pattern'] as $key => $pattern ) {
								foreach ($wids_filenames as $wid => $filename) {
									if ( $filename == $_POST['filename'][$key] ) {
										$pattern_filename = "\t" . stripslashes($pattern) . ' : ' . $wid . "\n";
										file_put_contents( PatternFile, $pattern_filename, FILE_APPEND | LOCK_EX );
										break;
									}
								}
							}
						}
					} else {
						file_put_contents( PatternFile, $hostName, FILE_USE_INCLUDE_PATH | LOCK_EX );
						$pattern_filename = "\t" . stripslashes('/*') . ' : 0' . "\n";
						file_put_contents( PatternFile, $pattern_filename, FILE_APPEND | LOCK_EX );
					}

					if ( isset( $_POST['minLength'] ) && $_POST['minLength'] ) {
						add_option( 'minLength', $_POST['minLength'] );
					} else {
						add_option( 'minLength', '3' );
					}

					//WIXファイル系
					if ( isset( $_POST['wixfile_autocreate'] ) && $_POST['wixfile_autocreate'] ) {
						if ( $_POST['wixfile_autocreate'] == 'true' )
							add_option( 'wixfile_autocreate', 'true' );
						else
							add_option( 'wixfile_autocreate', 'false' );

						if ( isset( $_POST['wixfile_autocreate_wordtype'] ) && $_POST['wixfile_autocreate_wordtype'] ) {
							if ( $_POST['wixfile_autocreate_wordtype'] == 'feature_word' )
								add_option( 'wixfile_autocreate_wordtype', 'feature_word' );
							else
								add_option( 'wixfile_autocreate_wordtype', 'freq_word' );
						}
					}
					if ( isset( $_POST['wixfile_manualupdate'] ) && $_POST['wixfile_manualupdate'] ) {
						if ( $_POST['wixfile_manualupdate'] == 'true' )
							add_option( 'wixfile_manualupdate', 'true' );
						else
							add_option( 'wixfile_manualupdate', 'false' );
					}
	

					//Decideファイル系
					if ( isset( $_POST['decidefile_apply'] ) && $_POST['decidefile_apply'] ) {
						if ( $_POST['decidefile_apply'] == 'true' )
							add_option( 'decidefile_apply', 'true' );
						else
							add_option( 'decidefile_apply', 'false' );
					}
					if ( isset( $_POST['decidefile_autocreate'] ) && $_POST['decidefile_autocreate'] ) {
						if ( $_POST['decidefile_autocreate'] == 'true' )
							add_option( 'decidefile_autocreate', 'true' );
						else
							add_option( 'decidefile_autocreate', 'false' );
					}
					if ( isset( $_POST['decidefile_manualupdate'] ) && $_POST['decidefile_manualupdate'] ) {
						if ( $_POST['decidefile_manualupdate'] == 'true' )
							add_option( 'manual_decide', 'true' );
						else 
							add_option( 'manual_decide', 'false' );
					}

					//その他系
					if ( isset($_POST['morphological_analysis']) && $_POST['morphological_analysis'] ) {
						if ( $_POST['morphological_analysis'] == 'Yahoo' ) {
							if ( !empty($_POST['yahoo_id']) ) {
								if ( get_option('morphological_analysis') == false ) 
									add_option( 'morphological_analysis', $_POST['morphological_analysis'] );	

								if ( get_option('yahoo_id') == false ) 
									add_option( 'yahoo_id', $_POST['yahoo_id'] );	
							}

						} else if ( $_POST['morphological_analysis'] == 'Mecab' ) {
							if ( get_option('morphological_analysis') == false ) 
								add_option( 'morphological_analysis', $_POST['morphological_analysis'] );	
						}
					}

					if ( isset($_POST['recommend_support']) && $_POST['recommend_support'] ) {
						if ( $_POST['recommend_support'] == 'ドキュメント類似度' ) {
							if ( get_option('recommend_support') == false ) 
									add_option( 'recommend_support', $_POST['recommend_support'] );	

						} else if ( $_POST['recommend_support'] == 'Google検索' ) {
							if ( !empty($_POST['google_api_key']) && !empty($_POST['google_cx']) ) {
								if ( get_option('recommend_support') == false ) 
									add_option( 'recommend_support', $_POST['recommend_support'] );	

								if ( get_option('google_api_key') == false ) 
									add_option( 'google_api_key', $_POST['google_api_key'] );	
								if ( get_option('google_cx') == false ) 
									add_option( 'google_cx', $_POST['google_cx'] );	
							}
						}
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

				//初期設定系
				if ( isset( $_POST['pattern'] ) && $_POST['pattern'] && isset( $_POST['filename'] ) && $_POST['filename'] ) {
					$hostName = '<' . get_option( 'wix_host_name' ) . '>' . "\n";

					file_put_contents( PatternFile, $hostName, FILE_USE_INCLUDE_PATH | LOCK_EX );

					if ( empty($wids_filenames) ) {
						foreach ( $_POST['pattern'] as $key => $pattern ) {
							$pattern_filename = "\t" . stripslashes($pattern) . ' : ' . 0 . "\n";
							file_put_contents( PatternFile, $pattern_filename, FILE_APPEND | LOCK_EX );
						}

					} else {
						foreach ( $_POST['pattern'] as $key => $pattern ) {
							foreach ($wids_filenames as $wid => $filename) {
								if ( $filename == $_POST['filename'][$key] ) {
									$pattern_filename = "\t" . stripslashes($pattern) . ' : ' . $wid . "\n";
									file_put_contents( PatternFile, $pattern_filename, FILE_APPEND | LOCK_EX );
									break;
								}
							}
						}
					}
				}
				if ( isset( $_POST['minLength'] ) && $_POST['minLength'] ) {
					if ( get_option('minLength') == false ) 
						add_option( 'minLength', $_POST['minLength'] );
					update_option( 'minLength', $_POST['minLength'] );
				}


				//WIXファイル系
				if ( isset( $_POST['wixfile_autocreate'] ) && $_POST['wixfile_autocreate'] ) {
					if ( $_POST['wixfile_autocreate'] == 'true' ) {
						if ( get_option('wixfile_autocreate') == false ) 
							add_option( 'wixfile_autocreate', 'true' );
						update_option( 'wixfile_autocreate', 'true' );
					} else {
						if ( get_option('wixfile_autocreate') == false ) 
							add_option( 'wixfile_autocreate', 'false' );
						update_option( 'wixfile_autocreate', 'false' );
					}

					if ( isset( $_POST['wixfile_autocreate_wordtype'] ) && $_POST['wixfile_autocreate_wordtype'] ) {
						if ( $_POST['wixfile_autocreate_wordtype'] == 'feature_word' ) {
							if ( get_option('wixfile_autocreate_wordtype') == false ) 
								add_option( 'wixfile_autocreate_wordtype', 'feature_word' );
							update_option( 'wixfile_autocreate_wordtype', 'feature_word' );
						} else {
							if ( get_option('wixfile_autocreate_wordtype') == false ) 
								add_option( 'wixfile_autocreate_wordtype', 'freq_word' );
							update_option( 'wixfile_autocreate_wordtype', 'freq_word' );
						}
					}
				}
				if ( isset( $_POST['wixfile_manualupdate'] ) && $_POST['wixfile_manualupdate'] ) {
					if ( $_POST['wixfile_manualupdate'] == 'true' ) {
						if ( get_option('wixfile_manualupdate') == false ) 
							add_option( 'wixfile_manualupdate', 'true' );
						update_option( 'wixfile_manualupdate', 'true' );
					} else {
						if ( get_option('wixfile_manualupdate') == false ) 
							add_option( 'wixfile_manualupdate', 'false' );
						update_option( 'wixfile_manualupdate', 'false' );
					}
				}

				//Decideファイル系
				if ( isset( $_POST['decidefile_apply'] ) && $_POST['decidefile_apply'] ) {
					if ( $_POST['decidefile_apply'] == 'true' ) {
						if ( get_option('decidefile_apply') == false ) 
							add_option( 'decidefile_apply', 'true' );
						update_option( 'decidefile_apply', 'true' );
					} else {
						if ( get_option('decidefile_apply') == false ) 
							add_option( 'decidefile_apply', 'false' );
						update_option( 'decidefile_apply', 'false' );
					}
				}
				if ( isset( $_POST['decidefile_autocreate'] ) && $_POST['decidefile_autocreate'] ) {
					if ( $_POST['decidefile_autocreate'] == 'true' ) {
						if ( get_option('decidefile_autocreate') == false ) 
							add_option( 'decidefile_autocreate', 'true' );
						update_option( 'decidefile_autocreate', 'true' );
					} else {
						if ( get_option('decidefile_autocreate') == false ) 
							add_option( 'decidefile_autocreate', 'false' );
						update_option( 'decidefile_autocreate', 'false' );
					}
				}
				if ( isset( $_POST['decidefile_manualupdate'] ) && $_POST['decidefile_manualupdate'] ) {
					if ( $_POST['decidefile_manualupdate'] == 'true' ) {
						if ( get_option('manual_decide') == false ) 
							add_option( 'manual_decide', 'true' );
						update_option( 'manual_decide', 'true' );
					} else {
						if ( get_option('manual_decide') == false ) 
							add_option( 'manual_decide', 'false' );
						update_option( 'manual_decide', 'false' );
					}
				}

				//その他系
				if ( isset($_POST['morphological_analysis']) && $_POST['morphological_analysis'] ) {
					if ( $_POST['morphological_analysis'] == 'Yahoo' ) {
						if ( !empty($_POST['yahoo_id']) ) {
							if ( get_option('morphological_analysis') == false ) 
								add_option( 'morphological_analysis', $_POST['morphological_analysis'] );	
							update_option( 'morphological_analysis', $_POST['morphological_analysis'] );

							if ( get_option('yahoo_id') == false ) 
								add_option( 'yahoo_id', $_POST['yahoo_id'] );	
							update_option( 'yahoo_id', $_POST['yahoo_id'] );
						}

					} else if ( $_POST['morphological_analysis'] == 'Mecab' ) {
						if ( get_option('morphological_analysis') == false ) 
							add_option( 'morphological_analysis', $_POST['morphological_analysis'] );	
						update_option( 'morphological_analysis', $_POST['morphological_analysis'] );

					} else {
						delete_option( 'morphological_analysis' );
					}

				} else {
					delete_option( 'morphological_analysis' );
				}

				if ( isset($_POST['recommend_support']) && $_POST['recommend_support'] ) {
					if ( $_POST['recommend_support'] == 'ドキュメント類似度' ) {
						if ( get_option('recommend_support') == false ) 
								add_option( 'recommend_support', $_POST['recommend_support'] );	
							update_option( 'recommend_support', $_POST['recommend_support'] );

					} else if ( $_POST['recommend_support'] == 'Google検索' ) {
						if ( !empty($_POST['google_api_key']) && !empty($_POST['google_cx']) ) {
							if ( get_option('recommend_support') == false ) 
								add_option( 'recommend_support', $_POST['recommend_support'] );	
							update_option( 'recommend_support', $_POST['recommend_support'] );

							if ( get_option('google_api_key') == false ) 
								add_option( 'google_api_key', $_POST['google_api_key'] );	
							update_option( 'google_api_key', $_POST['google_api_key'] );
							if ( get_option('google_cx') == false ) 
								add_option( 'google_cx', $_POST['google_cx'] );	
							update_option( 'google_cx', $_POST['google_cx'] );

						}
					} else {
						delete_option( 'recommend_support' );
					}

				} else {
					delete_option( 'recommend_support' );
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
						$result = $wpdb->query( $sql );	
						// var_dump($sql);

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
						// var_dump($sql);

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
								// var_dump($sql);
								$wpdb->query( $sql );
							}

							$sql = 'UPDATE ' . $wixfile_targets . ' SET keyword_id=' . $new_keyword_id . ', target="' .
								 $new_target . '" WHERE keyword_id=' . $org_keyword_id . ' AND target="' . $org_target . '"';
							// var_dump($sql);
							$wpdb->query( $sql );
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
							// var_dump($sql);
							$wpdb->query( $sql );
						}
					}

					if ( !empty($delete_targetsArray) ) {
						foreach ($delete_targetsArray as $index => $valueArray) {
							$org_keyword_id = $valueArray['org_keyword_id'];
							$org_target = $valueArray['org_target'];

							$sql = 'DELETE FROM ' . $wixfile_targets . ' WHERE keyword_id = ' . $org_keyword_id . ' AND target = "' . $org_target . '"';
							// var_dump($sql);
							$wpdb->query( $sql );
						}
					} 

					if ( !empty($delete_metaArray) ) {
						foreach ($delete_metaArray as $index => $org_keyword_id) {
							$sql = 'DELETE FROM ' . $wixfilemeta . ' WHERE id=' . $org_keyword_id;
							// var_dump($sql);
							$wpdb->query( $sql );
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
							// var_dump($sql);
							$wpdb->query( $sql );
						}

					}

					$sql = 'SELECT id, keyword FROM ' . $wixfilemeta . ' WHERE id NOT IN (SELECT DISTINCT keyword_id FROM ' . $wixfile_targets . ')';
					$delete_keywordObj = $wpdb->get_results($sql);
					if ( !empty($delete_keywordObj) ) {
						foreach ($delete_keywordObj as $index => $value) {
							$id = $value->id;
							$keyword = $value->keyword;
							$sql = 'DELETE FROM ' . $wixfilemeta . ' WHERE id=' . $id;
							// var_dump($sql);
							$wpdb->query( $sql );
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

add_action( 'admin_init', 'wix_decidefile_update_core' );
function wix_decidefile_update_core() {
	global $wpdb;

	if ( isset( $_POST['nonce_decidefile_settings'] ) && $_POST['nonce_decidefile_settings'] ) {
		if ( check_admin_referer( 'my-nonce-key', 'nonce_decidefile_settings' ) ) {

			$e = new WP_Error();

			if ( isset( $_POST['decidefile_settings'] ) && $_POST['decidefile_settings'] ) {

				if ( isset( $_POST['update_decidefileInfo'] ) && $_POST['update_decidefileInfo'] ) {
					
					$wixfilemeta = $wpdb->prefix . 'wixfilemeta';
					$wix_decidefile_index = $wpdb->prefix . 'wix_decidefile_index';
					$wix_decidefile_history = $wpdb->prefix . 'wix_decidefile_history';

					$doc_id = $_POST['update_decidefileInfo'][0];
					$version = $_POST['update_decidefileInfo'][1];
					$version = substr($version, strlen('ver.') );

					//最新版に昇格するDecide情報
					$sql = 'SELECT dfile_id, start, end, nextStart, keyword_id, keyword, target FROM ' . 
								$wixfilemeta . ' wf, ' . $wix_decidefile_history . 
								' wdh WHERE wf.id=wdh.keyword_id AND wdh.dfile_id = (SELECT dfile_id FROM wp_wix_decidefile_index WHERE doc_id=' . 
								$doc_id . ' AND version=' . $version . ')';
					$updateObj = $wpdb->get_results($sql);

					//まずindexに挿入
					$version++;
					$sql = 'INSERT INTO ' . $wix_decidefile_index . '(doc_id, version) VALUES (' . $doc_id . ', ' . $version . ')';   ;
					$wpdb->query( $sql );

					//続いて上で挿入したメタ情報のdfile_id(最新dfile_id)を取ってくる
					$sql = 'SELECT dfile_id FROM ' . $wix_decidefile_index . ' ORDER BY dfile_id DESC LIMIT 1';
					$dfile_idObj = $wpdb->get_results($sql);
					$dfile_id = $dfile_idObj[0]->dfile_id;

					//DB & ファイル形式のDecide情報をアップデート
					$dirname = dirname( __FILE__ ) . '/WIXDecideFiles/';
					if ( file_exists($dirname . $doc_id . '.txt') )
						unlink($dirname . $doc_id . '.txt');

					foreach ($updateObj as $index => $value) {
						$start = $value->start;
						$end = $value->end;
						$nextStart = $value->nextStart;
						$keyword_id = $value->keyword_id;
						$keyword = $value->keyword;
						$target = $value->target;

						//最新Decide情報を挿入
						$sql = 'INSERT INTO ' . $wix_decidefile_history . 
								'(dfile_id, start, end, nextStart, keyword_id, target) VALUES (' . 
									$dfile_id . ', ' . $start . ', ' . $end . ', ' . 
									$nextStart . ', ' . $keyword_id . ', "' . $target .'")';
						$wpdb->query( $sql );

						//ファイルへと挿入
						$line = 'start:' . $start . ',end:' . $end . ',nextStart:' . $nextStart . ',keyword:' . $keyword . ',target:' . $target . "\n";
						file_put_contents( $dirname . $doc_id . '.txt', $line, FILE_APPEND | LOCK_EX );
					}


					/**
					最新版にした情報の、既DB情報を消さなきゃいけないけど、メンドクサクテ...
					*/

					set_transient( 'decidefile_settings', 'Decideファイル履歴更新しました', 10 );

				}
			}
		} else {
			$e -> add('error', __( 'Please entry one more', 'decidefile_settings' ) );
			set_transient( 'decidefile_settings_errors', $e->get_error_message(), 10 );
		}
	}
}

add_action( 'admin_init', 'wix_default_detailDecide_core' );
function wix_default_detailDecide_core() {
	global $wpdb;

	if ( isset( $_POST['nonce_default_detail_settings'] ) && $_POST['nonce_default_detail_settings'] ) {
		if ( check_admin_referer( 'my-nonce-key', 'nonce_default_detail_settings' ) ) {

			$e = new WP_Error();

			if ( isset( $_POST['default_detail_decideInfo'] ) && $_POST['default_detail_decideInfo'] ) {

				set_transient( 'default_detail_decide_settings', 'Default・詳細設定保存しました', 10 );

			}
		} else {
			$e -> add('error', __( 'Please entry one more', 'default_detail_decideInfo' ) );
			set_transient( 'default_detail_decide_settings_errors', $e->get_error_message(), 10 );
		}
	}
}



//WIXファイルに挿入が行われた時の、「WIXファイル内キーワードが出現するドキュメント」を表すテーブルをupdate
function wixfilemeta_posts_insert( $array ) {
	var_dump( $array );
	global $wpdb;
	$insert_wixfilemeta_postsArray = array();

	if ( !empty($array) ) {
		$wixfilemeta_posts = $wpdb->prefix . 'wixfilemeta_posts';

		//まだDBに１つもドキュメントがなかったら計算しない.(でも基本的にemptyにならないみたい)
		$sql = 'SELECT ID, post_content FROM ' . $wpdb->posts . ' WHERE post_status!="inherit" and post_status!="trash" and post_status!="auto-save" and post_status!="auto-draft" ORDER BY id ASC';
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
				// var_dump($sql);
				$wpdb->query( $sql );
			}
		}

	}
}

/**
	↓２つの関数を別のファイルに移動したい。ここにあるのはキモチワルイ
**/
//ドキュメントの投稿ステータスが変わったら、WIXファイル内のどのキーワードが出現するかを算出
// add_action( 'transition_post_status', 'wix_keyword_appearance_in_doc', 10, 3 );
function wix_keyword_appearance_in_doc( $new_status, $old_status, $post ) {
	global $wpdb;
	$doc_id = $post->ID;
	$wixfilemeta_posts = $wpdb->prefix . 'wixfilemeta_posts';

	//ゴミ箱行きだったらDELTE.次にリビジョンに対するエントリを作らないように.
	if ( $new_status == 'trash' ) {

		$sql = 'DELETE FROM ' . $wixfilemeta_posts . ' WHERE doc_id = ' . $doc_id;
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
			$sql = 'SELECT COUNT(*) FROM ' . $wixfilemeta_posts . ' WHERE doc_id = ' . $doc_id;
			if ( $wpdb->get_var($sql) != 0 ) {
				$sql = 'DELETE FROM ' . $wixfilemeta_posts . ' WHERE doc_id = ' . $doc_id;
				$wpdb->query( $sql );
			}
			$sql = 'INSERT INTO ' . $wixfilemeta_posts . '(keyword_id, doc_id) VALUES ' . $insertEntry;
			$sql = mb_substr($sql, 0, (mb_strlen($sql)-2));
			$wpdb->query( $sql );
		} else {
			$sql = 'SELECT COUNT(*) FROM ' . $wixfilemeta_posts . ' WHERE doc_id = ' . $doc_id;
			if ( $wpdb->get_var($sql) != 0 ) {
				$sql = 'DELETE FROM ' . $wixfilemeta_posts . ' WHERE doc_id = ' . $doc_id;
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


//推薦エントリを提示
add_action( 'wp_ajax_wix_similarity_entry_recommend', 'wix_similarity_entry_recommend' );
add_action( 'wp_ajax_nopriv_wix_similarity_entry_recommend', 'wix_similarity_entry_recommend' );
function wix_similarity_entry_recommend($doc_id = 0, $type = 'js') {
	global $wpdb;

	header("Access-Control-Allow-Origin: *");
	header('Content-type: application/javascript; charset=utf-8');

	if ( $doc_id == 0 )
		$doc_id = $_POST['doc_id'];

	$wix_keyword_similarity = $wpdb->prefix . 'wix_keyword_similarity';
	$wix_document_similarity = $wpdb->prefix . 'wix_document_similarity';

	$returnValue = array();
	/*
		$returnValue: [
						'feature_words' => [ranked keyword],
						'page_freq_words' => ,
						'page_freq_words_in_site' => ,
						'site_freq_words' => ,
						'candidate_targets' => [candidate_targets_info],
						]
	*/


	//候補キーワード群
	$sql = 'SELECT * FROM ' . $wix_keyword_similarity . ' WHERE doc_id=' . $doc_id;
	$candidate_keywords = $wpdb->get_results($sql);

	//候補ターゲット群
	$sql = 'SELECT * FROM ' . $wix_document_similarity .
			' WHERE doc_id=' . $doc_id . ' OR doc_id2=' . $doc_id; 
	$similar_documents = $wpdb->get_results($sql);

	if ( !empty($candidate_keywords) && !empty($similar_documents) ) {
		$tf_sortArray = array(); 
		$idf_sortArray = array(); 
		$tfidf_sortArray = array(); 
		$bm25_sortArray = array();
		$textrank_sortArray = array();

		foreach ($candidate_keywords as $index => $value) {
			$tf_sortArray[$value->keyword] = $value->tf;
			$idf_sortArray[$value->keyword] = $value->idf;
			$tfidf_sortArray[$value->keyword] = $value->tf_idf;
			$bm25_sortArray[$value->keyword] = $value->bm25;
			$textrank_sortArray[$value->keyword] = $value->textrank;
		}
		//ページ内頻出順
		arsort($tf_sortArray, SORT_NUMERIC);
		$returnValue['page_freq_words'] = $tf_sortArray;

		//サイト内頻出順
		asort($idf_sortArray, SORT_NUMERIC);
		$returnValue['page_freq_words_in_site'] = $idf_sortArray;

		//特徴語
		$featureArray = wix_feature_words_sort( $tfidf_sortArray, $bm25_sortArray, $textrank_sortArray );
		$returnValue['feature_words'] = $featureArray;

		//サイト内頻出語(上位10件)
		$sql = 'SELECT keyword, idf FROM ' . $wix_keyword_similarity . 
				' GROUP BY keyword ORDER BY idf ASC, bm25 DESC LIMIT 10';
		$site_freq_words = $wpdb->get_results($sql);
		$returnValue['site_freq_words'] = $site_freq_words;

		//ランキング済みターゲット
		$selectQuery = '';
		$similar_documentsArray = wix_candidate_targets_sort( $similar_documents, $doc_id );
		foreach ($similar_documentsArray as $doc_id2 => $score) {
			if ( empty($selectQuery) )
				$selectQuery = 'ID=' . $doc_id2 . ' ';
			else
				$selectQuery = $selectQuery . 'OR ID=' . $doc_id2 . ' ';
		}
		$sql = 'SELECT ID, post_title, guid FROM ' . $wpdb->posts . ' WHERE ' . $selectQuery;
		$candidate_targets = $wpdb->get_results($sql);

		$candidate_targetsArray = array();
		foreach ($similar_documentsArray as $doc_id2 => $score) {
			foreach ($candidate_targets as $index => $value) {
				if ( $value->ID ==  $doc_id2 ) {
					array_push( $candidate_targetsArray, $value );
					break;
				}
			}	
		}

		$returnValue['candidate_targets'] = $candidate_targetsArray;
	}

	if ( $type == 'js' ) {
		$json = array(
			"entrys" => $returnValue,
			// "test" => $candidate_targetsArray,
		);

		echo json_encode( $json );

		
	    die();

	} else if ( $type == 'php' ) {

		return $returnValue;
	}
}

/**
		↓重みよりも、閾値をどうやって一意に定めるか。
*/
function wix_feature_words_sort( $array1, $array2, $array3 ) {
	$sumArray = array();
	//重み
	$w1=0.3; $w2=0.3; $w3=0.4;

	foreach ($array1 as $keyword => $value) {
		$sumArray[$keyword] = $w1*$value + $w2*$array2[$keyword] + $w3*$array3[$keyword];
	}

	arsort($sumArray, SORT_NUMERIC);
	// dump('dump.txt', $sumArray);

	return $sumArray;
}
/**
	↓類似ドキュメントの重み、閾値をどうするか？
*/
function wix_candidate_targets_sort( $similar_documents, $doc_id ) {
	$similar_documentsArray = array();
	//重み
	$w1=0.3; $w2=0.3; $w3=0; $w4=0.4;

	foreach ($similar_documents as $index => $value) {
		if ( (int)$value->doc_id == $doc_id )
			$doc_id2 = $value->doc_id2;
		else
			$doc_id2 = $value->doc_id;

		$score = $w1 * $value->cos_similarity_tfidf
				 + $w2 * $value->cos_similarity_bm25
				  + $w3 * $value->jaccard
				   + $w4 *  $value->minhash;

		$similar_documentsArray[$doc_id2] = $score;
	}

	arsort($similar_documentsArray);

	return $similar_documentsArray;
}

//既存該当キーワードを提示
add_action( 'wp_ajax_wix_exisitng_entry_presentation', 'wix_exisitng_entry_presentation' );
add_action( 'wp_ajax_nopriv_wix_exisitng_entry_presentation', 'wix_exisitng_entry_presentation' );
function wix_exisitng_entry_presentation() {
	global $wpdb;

	header("Access-Control-Allow-Origin: *");
	header('Content-type: application/javascript; charset=utf-8');

	$keyword = $_POST['keyword'];

	$wixfilemeta = $wpdb->prefix . 'wixfilemeta';
	$wixfile_targets = $wpdb->prefix . 'wixfile_targets';
	
	$sql = 'SELECT keyword, target FROM ' . $wixfilemeta . ', ' . $wixfile_targets . ' WHERE id=keyword_id AND keyword="' . $keyword . '"';
	$returnValue = $wpdb->get_results($sql);

	$json = array(
		"entrys" => $returnValue,
	);

	echo json_encode( $json );

	
    die();
}

//WIX Detail Settingsにおける手動Decide処理用body
add_action( 'wp_ajax_wix_setting_decideBody', 'wix_setting_decideBody' );
add_action( 'wp_ajax_nopriv_wix_setting_decideBody', 'wix_setting_decideBody' );
function wix_setting_decideBody() {
	global $wpdb;

	header("Access-Control-Allow-Origin: *");
	header('Content-type: application/javascript; charset=utf-8');

	$doc_id = $_POST['doc_id'];
	$url = $_POST['url'];

	//decide処理する箇所のbodyを作る
	$sql = 'SELECT post_content FROM ' . $wpdb->posts . ' WHERE ID=' . $doc_id;
	$docObj = $wpdb->get_results($sql);

	$tmpBody = wpautop($docObj[0]->post_content);
	$innerLinkArray = keyword_location( strip_tags($tmpBody) );
	if ( count($innerLinkArray) != 0 ) {
		$tmp = json_encode($innerLinkArray, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
		$decide_body = new_body( $tmpBody, $tmp, true );
	} else {
		$decide_body = new_body( $tmpBody, '', true );
	}


	$json = array(
		"html" => $decide_body,
		"innerLinkArray" => $innerLinkArray,
	);

	echo json_encode( $json );

	
    die();
}

//最新の該当DecideFile情報を提示
add_action( 'wp_ajax_wix_existing_decidefile_presentation', 'wix_existing_decidefile_presentation' );
add_action( 'wp_ajax_nopriv_wix_existing_decidefile_presentation', 'wix_existing_decidefile_presentation' );
function wix_existing_decidefile_presentation() {
	global $wpdb;
	$returnValue = array();

	header("Access-Control-Allow-Origin: *");
	header('Content-type: application/javascript; charset=utf-8');

	$doc_id = $_POST['doc_id'];

	$wixfilemeta = $wpdb->prefix . 'wixfilemeta';
	$wix_decidefile_index = $wpdb->prefix . 'wix_decidefile_index';
	$wix_decidefile_history = $wpdb->prefix . 'wix_decidefile_history';

	if ( isset( $_POST['tab'] ) && $_POST['tab'] )
		$tab = $_POST['tab'];


	if ( !isset($tab) ) {
		//まずDecideファイル情報があるかチェックかつ、最新版のdfile_idを取ってくる
		$sql = 'SELECT * FROM ' . $wix_decidefile_index . ' WHERE doc_id=' . $doc_id . ' ORDER BY version DESC LIMIT 1';
		$decideObj = $wpdb->get_results($sql);
		if ( !empty($decideObj) ) {
			$dfile_id = $decideObj[0]->dfile_id;

			$sql = 'SELECT start, end, nextStart, keyword, wdh.target, (CASE WHEN p.guid=wdh.target then post_content ELSE wdh.target END) AS title FROM ' . 
						$wpdb->posts . ' p, ' . $wix_decidefile_history . ' wdh, ' . $wixfilemeta . 
						' wm WHERE wdh.dfile_id=' . $dfile_id . ' AND wm.id=wdh.keyword_id AND p.ID=' . $doc_id;
			$decideFileInfo = $wpdb->get_results($sql);

			foreach ($decideFileInfo as $index => $value) {
				$returnValue[$value->start] = [
												'end' => $value->end, 
												'keyword' => $value->keyword, 
												'target' => $value->target,
												'nextStart' => $value->nextStart,
												'title' => $value->title
												];
			}
		}

		$json = array(
			"latest_decideinfo" => $returnValue,
		);

	} else {
		//まずDecideファイル情報があるかチェックかつ、最新版のdfile_idを取ってくる
		$sql = 'SELECT * FROM ' . $wix_decidefile_index . ' WHERE doc_id=' . $doc_id . ' ORDER BY version DESC LIMIT 1';
		$decideObj = $wpdb->get_results($sql);
		if ( !empty($decideObj) ) {
			$dfile_id = $decideObj[0]->dfile_id;

			$sql = 'SELECT start, end, nextStart, keyword, wdh.target, (CASE WHEN p.guid=wdh.target then post_content ELSE wdh.target END) AS title FROM ' . 
						$wpdb->posts . ' p, ' . $wix_decidefile_history . ' wdh, ' . $wixfilemeta . 
						' wm WHERE wdh.dfile_id=' . $dfile_id . ' AND wm.id=wdh.keyword_id AND p.ID=' . $doc_id;
			$decideFileInfo = $wpdb->get_results($sql);

			foreach ($decideFileInfo as $index => $value) {
				$returnValue[$value->start] = [
												'end' => $value->end, 
												'keyword' => $value->keyword, 
												'target' => $value->target,
												'nextStart' => $value->nextStart,
												'title' => $value->title
												];
			}
		}

		$sql = 'SELECT post_content FROM ' . $wpdb->posts . ' WHERE ID=' . $doc_id;
		$bodyObj = $wpdb->get_results($sql);
		$body = strip_tags( wpautop($bodyObj[0]->post_content) );

		$json = array(
			"latest_decideinfo" => $returnValue,
			"body" => $body,
		);
	}

	echo json_encode( $json );

	
    die();
}

//該当ドキュメントのDecide情報履歴を提示
add_action( 'wp_ajax_wix_decidefile_history', 'wix_decidefile_history' );
add_action( 'wp_ajax_nopriv_wix_decidefile_history', 'wix_decidefile_history' );
function wix_decidefile_history() {
	global $wpdb;
	$returnValue = array();

	header("Access-Control-Allow-Origin: *");
	header('Content-type: application/javascript; charset=utf-8');

	$doc_id = $_POST['doc_id'];

	$wixfilemeta = $wpdb->prefix . 'wixfilemeta';
	$wix_decidefile_index = $wpdb->prefix . 'wix_decidefile_index';
	$wix_decidefile_history = $wpdb->prefix . 'wix_decidefile_history';


	$sql = 'SELECT * FROM ' . $wix_decidefile_index . ' WHERE doc_id=' . $doc_id;
	$decideObj = $wpdb->get_results($sql);
	if ( !empty($decideObj) ) {
		$sql = 'SELECT post_content FROM ' . $wpdb->posts . ' WHERE ID=' . $doc_id;
		$bodyObj = $wpdb->get_results($sql);
		$body = strip_tags( wpautop($bodyObj[0]->post_content) );

		foreach ($decideObj as $index => $valueArray) {
			$dfile_id = $valueArray->dfile_id;

			$sql = 'SELECT start, end, nextStart, keyword, wdh.target, (CASE WHEN p.guid=wdh.target then post_content ELSE wdh.target END) AS title, time FROM ' . 
						$wpdb->posts . ' p, ' . $wix_decidefile_history . ' wdh, ' . $wixfilemeta . ' wm, ' . $wix_decidefile_index . 
						' wdi WHERE wdh.dfile_id=' . $dfile_id . ' AND wdi.dfile_id=' . $dfile_id . ' AND wm.id=wdh.keyword_id AND p.ID=' . $doc_id . ' ORDER BY start ASC';

			$decideFileInfo = $wpdb->get_results($sql);
			$time;
			$tmpArray = array();
			foreach ($decideFileInfo as $i => $value) {
				$start = $value->start;
				$tmpArray[$start] = array(
												'end' => $value->end, 
												'keyword' => $value->keyword, 
												'target' => $value->target,
												'nextStart' => $value->nextStart,
												'title' => $value->title
											);
				$time = $value->time;
			}

			$returnValue[$valueArray->version] = array(
														'body' => $body,
														'time' => $time,
														'decideInfo' => $tmpArray
													);

		}

	}
	


	$json = array(
		"decideinfo" => $returnValue,
	);

	echo json_encode( $json );

	
    die();
}

//WIX Detail Settingsにおける手動Decide処理用body
add_action( 'wp_ajax_wix_setting_createDecidefile', 'wix_setting_createDecidefile' );
add_action( 'wp_ajax_nopriv_wix_setting_createDecidefile', 'wix_setting_createDecidefile' );
function wix_setting_createDecidefile() {
	global $wpdb;

	header("Access-Control-Allow-Origin: *");
	header('Content-type: application/javascript; charset=utf-8');

	if ( isset( $_POST['defaultLinkArray'] ) && $_POST['defaultLinkArray'] )
		$defaultLinkArray = $_POST['defaultLinkArray'];

	if ( isset( $_POST['decideLinkArray'] ) && $_POST['decideLinkArray'] )
		$decideLinkArray = $_POST['decideLinkArray'];


	if ( isset($defaultLinkArray) && !empty($defaultLinkArray) ) {
		foreach ($defaultLinkArray as $doc_id => $valueArray) {
			$sql = 'SELECT post_content FROM ' . $wpdb->posts . ' WHERE ID=' . $doc_id;
			$docObj = $wpdb->get_results($sql);
			$body = strip_tags( wpautop($docObj[0]->post_content) );

			//対象ドキュメントにおけるキーワード全位置情報
			$innerLinkArray = keyword_location( $body );

			$decidefileArray = array();
			foreach ($innerLinkArray as $start => $array) {
				$keyword = $array['keyword'][0];
				$end = $array['end'][0];
				$nextStart = $array['nextStart'][0];

				foreach ($valueArray as $keyword_id => $ar) {
					$tmp_keyword = $ar['keyword'];

					if ( $keyword == $tmp_keyword ) {
						$target = $ar['target'];
						$decidefileArray[$start] = array(
															'keyword' => $keyword,
															'end' => $end,
															'nextStart' => $nextStart,
															'target' => $target,
														);
					}
				}
			}

			// dump('dump.txt', $decidefileArray);

			if ( isset($decideLinkArray) && !empty($decideLinkArray) ) {
				foreach ($decideLinkArray as $doc_id2 => $array) {
					if ( $doc_id == $doc_id2 ) {
						foreach ($array as $start => $ar) {
							$tmpArray = $decidefileArray[$start];
							$tmpArray['target'] = $ar['target'];
							$decidefileArray[$start] = $tmpArray;
						}
					}
				}
			}

			// dump('dump.txt', $decidefileArray);

			//Decideファイル生成 & DB更新
			$dirname = dirname( __FILE__ ) . '/WIXDecideFiles/';
			if ( !file_exists($dirname) )
				mkdir($dirname, 0777, true);

			if ( file_exists($dirname . $doc_id . '.txt') )
				unlink($dirname . $doc_id . '.txt');

			foreach ($decidefileArray as $start => $array) {
				$keyword = $array['keyword'];
				$end = $array['end'];
				$nextStart = $array['nextStart'];
				$target = $array['target'];

				$line = 'start:' . $start . ',end:' . $end . ',nextStart:' . $nextStart . ',keyword:' . $keyword . ',target:' . $target . "\n";
				file_put_contents( $dirname . $doc_id . '.txt', $line, FILE_APPEND | LOCK_EX );
			}
			//DBへの挿入
			wix_setting_createDecidefile_inDB($doc_id, $decidefileArray);
		}

	} else if ( isset($decideLinkArray) && !empty($decideLinkArray) ) {
		foreach ($decideLinkArray as $doc_id => $valueArray) {
			$sql = 'SELECT post_content FROM ' . $wpdb->posts . ' WHERE ID=' . $doc_id;
			$docObj = $wpdb->get_results($sql);
			$body = strip_tags( wpautop($docObj[0]->post_content) );

			//対象ドキュメントにおけるキーワード全位置情報
			$innerLinkArray = keyword_location( $body );

			$decidefileArray = array();
			foreach ($innerLinkArray as $start => $array) {
				$keyword = $array['keyword'][0];
				$end = $array['end'][0];
				$nextStart = $array['nextStart'][0];

				foreach ($valueArray as $tmp_start => $ar) {
					$tmp_keyword = $ar['keyword'];

					if ( $start == $tmp_start ) {
						$target = $ar['target'];
						$decidefileArray[$start] = array(
															'keyword' => $keyword,
															'end' => $end,
															'nextStart' => $nextStart,
															'target' => $target,
														);
					}
				}
			}

			//nextStartの調整
			$count = 0;
			$tmp_start;
			foreach ($decidefileArray as $start => $array) {
				if ( $count == 0 )	$tmp_start = $start;
				if ( $tmp_start != $start ) {
					$decidefileArray[$tmp_start]['nextStart'] = $start;
					$tmp_start = $start;
				}

				$count++;
			}
			$decidefileArray[$tmp_start]['nextStart'] = 0;

			//Decideファイル生成 & DB更新
			$dirname = dirname( __FILE__ ) . '/WIXDecideFiles/';
			if ( !file_exists($dirname) )
				mkdir($dirname, 0777, true);

			if ( file_exists($dirname . $doc_id . '.txt') )
				unlink($dirname . $doc_id . '.txt');

			foreach ($decidefileArray as $start => $array) {
				$keyword = $array['keyword'];
				$end = $array['end'];
				$nextStart = $array['nextStart'];
				$target = $array['target'];

				$line = 'start:' . $start . ',end:' . $end . ',nextStart:' . $nextStart . ',keyword:' . $keyword . ',target:' . $target . "\n";
				file_put_contents( $dirname . $doc_id . '.txt', $line, FILE_APPEND | LOCK_EX );
			}

			//DBへの挿入
			wix_setting_createDecidefile_inDB($doc_id, $decidefileArray);
		}

	}



	$json = array(
		"test" => 'a',
	);

	echo json_encode( $json );
	
    die();
}

function wix_setting_createDecidefile_inDB($doc_id, $object) {
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
	$count = 0;
	foreach ($object as $start => $array) {
		$end = $array['end'];
		$nextStart = $array['nextStart'];
		$keyword_id = $keywordArray[$array['keyword']];
		$target = $array['target'];

		if ( $count == 0 ) 
			$insertRecord = '(' . $dfile_id . ', ' . $start . ', ' . $end . ', ' . $nextStart . ', ' . $keyword_id . ', "' . $target . '")';
		else 
			$insertRecord = $insertRecord . ', (' . $dfile_id . ', ' . $start . ', ' . $end . ', ' . $nextStart . ', ' . $keyword_id . ', "' . $target . '")';	
	
		$count++;
	}
	$sql = 'INSERT INTO ' . $wix_decidefile_history . '(dfile_id, start, end, nextStart, keyword_id, target) VALUES ' . $insertRecord;
	$wpdb->query( $sql );
}


//Defaultエントリ推薦などを提示
add_action( 'wp_ajax_wix_disambiguation_recommend', 'wix_disambiguation_recommend' );
add_action( 'wp_ajax_nopriv_wix_disambiguation_recommend', 'wix_disambiguation_recommend' );
function wix_disambiguation_recommend() {
	global $wpdb, $no_selection_morphological_analysis, $no_selection_recommend_support;

	header("Access-Control-Allow-Origin: *");
	header('Content-type: application/javascript; charset=utf-8');

	$doc_id = $_POST['doc_id'];

	$wixfilemeta = $wpdb->prefix . 'wixfilemeta';
	$wixfilemeta_posts = $wpdb->prefix . 'wixfilemeta_posts';
	$wixfile_targets = $wpdb->prefix . 'wixfile_targets';

	/*
		WIXファイルにおいて、対象ドキュメントに出現する各キーワードが持つターゲットを配列で管理
		$entrysArray: [
						keyword: [
									'keyword_id' => ,
									'target' => [targets]
								]
					]

		ターゲット候補を複数持つエントリのうち、内部リンクを持つキーワードを最初のキーにしている。
		その内部リンクのタイトルなどの情報を持ち合わせている。
		$keyword_innerlinkArray: [
									keyword: [
												[
													'keyword_id' => ,
													'doc_id' => ,
													'post_title' => ,
													'url' => $
												]
											]
								]
	*/

	$sql = 'SELECT wm.id, wm.keyword, wft.target FROM ' . 
			$wpdb->posts . ' p, ' . 
			$wixfilemeta . ' wm, ' . 
			$wixfilemeta_posts . ' wfp, ' . 
			$wixfile_targets . ' wft' . 
			' WHERE wm.id=wft.keyword_id AND wm.id=wfp.keyword_id AND p.ID=wfp.doc_id AND p.ID=' . $doc_id;
	$entrysObj = $wpdb->get_results($sql);

	$entrysArray = array();
	foreach ($entrysObj as $index => $value) {
		if ( array_key_exists($value->keyword, $entrysArray) ) {
			$tmpArray = $entrysArray[$value->keyword]['target'];
			array_push( $tmpArray, $value->target );
			$entrysArray[$value->keyword]['target'] = $tmpArray;
		} else {
			$entrysArray[$value->keyword] = array(
													'keyword_id' => $value->id,
													'target' => array($value->target)
												);
		}
	}

	$outerLinkArray = $entrysArray;
	$keyword_innerlinkArray = array();
	$doc_idArray = array(); //ドキュメント類似度を計算すべきドキュメントのIDを持つ
	$siteURL = get_option('home');

	foreach ($outerLinkArray as $keyword => $valueArray) {

		//WIXファイル内のtargetがwp_postsのguid OR post_titleの一致したら、それは内部URLだと判断する
		$sql = 'SELECT ID, post_title, guid FROM ' . $wpdb->posts . ' WHERE ';
		foreach ($valueArray['target'] as $index => $target) {
			$title = substr( $target, strlen($siteURL)+1 );
			if ( $index == 0 ) 
				$sql = $sql . 'guid="' . $target . '" OR post_title="' . $title . '"'; 
			else
				$sql = $sql . ' OR guid="' . $target . '" OR post_title="' . $title . '"'; 
		}
		$keyword_innerlinkObj = $wpdb->get_results($sql);

		foreach ($keyword_innerlinkObj as $index => $value) {
			if ( $doc_id != $value->ID ) {
				if ( array_key_exists($keyword, $keyword_innerlinkArray) ) {
					$tmpArray = $keyword_innerlinkArray[$keyword];
					$tmp = [
							'keyword_id' => $valueArray['keyword_id'],
							'post_title' => $value->post_title,
							'url' => $value->guid
						];
					$tmpArray[$value->ID] = $tmp;
					$keyword_innerlinkArray[$keyword] = $tmpArray;

				} else {
					$keyword_innerlinkArray[$keyword] = array(
																$value->ID => [
																	'keyword_id' => $valueArray['keyword_id'],
																	'post_title' => $value->post_title,
																	'url' => $value->guid
																]
															);
				}
				$doc_idArray[$value->ID] = null;
			}
			//entrsArrayの中身を外部URLのみにする
			foreach ($valueArray['target'] as $index => $target) {
				if ( $target == $value->post_title || $target == $value->guid ) {
					unset( $valueArray['target'][$index] );
					$valueArray['target'] = array_values( $valueArray['target'] );
				}
			}
		}

		$outerLinkArray[$keyword] = $valueArray;
	}
// dump('dump.txt', $entrysArray);
// dump('dump.txt', $outerLinkArray);
// dump('dump.txt', $keyword_innerlinkArray);
// dump('dump.txt', $doc_idArray);

	if ( get_option('recommend_support') != false ) {
		if ( get_option('recommend_support') == 'ドキュメント類似度' ) {
			$keyword_innerlinkArray = wix_entry_disambiuation_with_docSim($doc_id, $doc_idArray, $keyword_innerlinkArray, $entrysArray);

		} else {
			$keyword_innerlinkArray = wix_entry_disambiuation_with_googleSearch($doc_id, $doc_idArray, $keyword_innerlinkArray, $entrysArray);

		}
	}

	//クライアントサイドオブジェクト作成
	$returnValue = array();
	foreach ($keyword_innerlinkArray as $keyword => $valueArray) {
		$tmpArray = array();

		foreach ($valueArray as $doc_id2 => $array) {
			$keyword_id = $array['keyword_id'];
			array_push($tmpArray, ['url' => $array['url'], 'title' => $array['post_title'], 'doc_id' => $doc_id2 ]);
		}

		$returnValue[$keyword] = array(
										'keyword_id' => $keyword_id,
										'targets' => $tmpArray
									);
	}

	foreach ($outerLinkArray as $keyword => $valueArray) {
		if ( array_key_exists($keyword, $returnValue) ) {
			$tmpArray = $returnValue[$keyword]['targets'];
		} else {
			$tmpArray = array();
			$returnValue[$keyword]['keyword_id'] = $valueArray['keyword_id'];
		}

		foreach ($valueArray['target'] as $index => $value) {
			array_push($tmpArray, ['url' => $value]);
		}
		$returnValue[$keyword]['targets'] = $tmpArray;
	}

	if ( $no_selection_morphological_analysis == 'false' && $no_selection_recommend_support == 'false' ) {
		$message = '';

	} else {
		$tmp = 0;
		if ( $no_selection_morphological_analysis == 'true' )
			$tmp += 1;
		if ( $no_selection_recommend_support == 'true' )
			$tmp += 2;

		if ( $tmp == 1 )
			$message = 'no_selection_morphological_analysis';
		else if ( $tmp == 2 )
			$message = 'no_selection_recommend_support';
		else
			$message = 'double';
	}

	$json = array(
		"entrys" => $returnValue,
		"no_selection_option" => $message
	);

	echo json_encode( $json );

	
    die();
}

function wix_entry_disambiuation_with_docSim($doc_id, $doc_idArray, $keyword_innerlinkArray, $entrysArray) {

	//とりあえず類似度計算すべきdoc_idが2つ以上あったら計算。それがたとえ別々のキーワードにおけるdefault候補ターゲットだったとしても。
	if ( count($doc_idArray) > 1 ) {
		//類似度計算
		$doc_simArray = wix_documentSim_for_ranking( $doc_id, $doc_idArray );
		/**
			ambiguationScore計算
		*/
		$keyword_innerlinkArray = wix_calc_disambiguation_score($keyword_innerlinkArray, $entrysArray);
		// $keyword_innerlinkArray = wix_calc_disambiguation_score_hard($keyword_innerlinkArray, $entrysArray);
		// $keyword_innerlinkArray = wix_calc_disambiguation_score_veryhard($keyword_innerlinkArray, $entrysArray);

		//Ranking
		foreach ($keyword_innerlinkArray as $keyword => $valueArray) {
			if ( count($valueArray) > 1 ) {
				$tmpArray = array();
				$tmp_simscoreArray = array();

				foreach ($valueArray as $doc_id2 => $array) {
					$tmp_simscoreArray[$doc_id2] = $doc_simArray[$doc_id2];
					$tmpArray[$doc_id2] = $array['disambiguatoin_score'] * $doc_simArray[$doc_id2];
					unset($keyword_innerlinkArray[$keyword][$doc_id2]);
				}
				arsort($tmpArray);
				foreach ($tmpArray as $doc_id2 => $finalScore) {
					$valueArray[$doc_id2]['sim_score'] = $tmp_simscoreArray[$doc_id2];
					$valueArray[$doc_id2]['final_score'] = $tmpArray[$doc_id2];

					$keyword_innerlinkArray[$keyword][$doc_id2] = $valueArray[$doc_id2];
				}
			}
		}
	}

	return $keyword_innerlinkArray;
}

/**
	↓類似ドキュメントの重み、閾値をどうするか？
*/
function wix_documentSim_for_ranking( $doc_id, $doc_idArray ) {
	global $wpdb;

	$wix_document_similarity = $wpdb->prefix . 'wix_document_similarity';
	$doc_simArray = $doc_idArray;

	$w1=0.3; $w2=0.3; $w3=0; $w4=0.4;

	foreach ($doc_idArray as $doc_id2 => $null) {
		$sql = 'SELECT * FROM ' . $wix_document_similarity .
			' WHERE (doc_id=' . $doc_id . ' AND doc_id2=' . $doc_id2 . 
				') OR (doc_id=' . $doc_id2 . ' AND doc_id2=' . $doc_id . ')';
		$similar_documents = $wpdb->get_results($sql);


		foreach ($similar_documents as $index => $value) {
			$score = $w1 * $value->cos_similarity_tfidf
					 + $w2 * $value->cos_similarity_bm25
					  + $w3 * $value->jaccard
					   + $w4 *  $value->minhash;

			$doc_simArray[$doc_id2] = $score;
		}
		
	}

	return $doc_simArray;
}

function wix_calc_disambiguation_score($keyword_innerlinkArray, $entrysArray) {
	$siteURL = get_option('home');

	foreach ($keyword_innerlinkArray as $keyword => $valueArray) {
		//内部リンクターゲット候補が2つ以上ある場合のみ、default-entry決定してあげる必要がある
		if ( count($valueArray) > 1 ) { 
			$keyword_num = count($entrysArray);

			foreach ($valueArray as $ID => $array) {
				$match = 0; 
				$url = $array['url'];
				$title = $array['post_title'];

				foreach ($entrysArray as $key => $value) {
					foreach ($value['target'] as $index => $target) {
						if ( $target == $url || $target == $siteURL.$title ) {
							$match++;
						}
					}
				}
				$array['disambiguatoin_score'] = $match / $keyword_num;
				$valueArray[$ID] = $array;
				$keyword_innerlinkArray[$keyword] = $valueArray;
			}

		}
		
	}

	return $keyword_innerlinkArray;
}


function wix_calc_disambiguation_score_hard($keyword_innerlinkArray, $entrysArray) {
	$siteURL = get_option('home');

	foreach ($keyword_innerlinkArray as $keyword => $valueArray) {
		//内部リンクターゲット候補が2つ以上ある場合のみ、default-entry決定してあげる必要がある
		if ( count($valueArray) > 1 ) { 
			$weight = 1;

			foreach ($valueArray as $ID => $array) {
				$url = $array['url'];
				$title = $array['post_title'];
				$match = 0;

				foreach ($entrysArray as $key => $value) {
					$entry_num = count($value['target']);
					foreach ($value['target'] as $index => $target) {
						if ( $target == $url || $target == $siteURL.$title ) {
							$match = $match + $weight / $entry_num;
						}
					}
				}
				$array['disambiguatoin_score'] = $match;
				$valueArray[$ID] = $array;
				$keyword_innerlinkArray[$keyword] = $valueArray;
			}

		}
		
	}

	return $keyword_innerlinkArray;
}

function wix_calc_disambiguation_score_veryhard($keyword_innerlinkArray, $entrysArray) {
	$siteURL = get_option('home');

	foreach ($keyword_innerlinkArray as $keyword => $valueArray) {
		//内部リンクターゲット候補が2つ以上ある場合のみ、default-entry決定してあげる必要がある
		if ( count($valueArray) > 1 ) { 
			$weight = 1;
			$keyword_num = count($entrysArray);

			foreach ($valueArray as $ID => $array) {
				$url = $array['url'];
				$title = $array['post_title'];
				$match = 0;

				foreach ($entrysArray as $key => $value) {
					$entry_num = count($value['target']);
					foreach ($value['target'] as $index => $target) {
						if ( $target == $url || $target == $siteURL.$title ) {
							$match = $match + $weight / $entry_num;
						}
					}
				}
				$array['disambiguatoin_score'] = $match / $keyword_num;
				$valueArray[$ID] = $array;
				$keyword_innerlinkArray[$keyword] = $valueArray;
			}

		}
		
	}

	return $keyword_innerlinkArray;
}

/**
	hayashi法 or sakusa法か
*/
function wix_entry_disambiuation_with_googleSearch($doc_id, $doc_idArray, $keyword_innerlinkArray, $entrysArray) {
	global $wpdb, $no_selection_morphological_analysis, $no_selection_recommend_support;
	$wixfilemeta_posts = $wpdb->prefix . 'wixfilemeta_posts';

	if ( get_option('google_api_key') != false ) {

		if ( 'a' == 'b' ) {
			/* hayashi法 */
			//ターゲットのDocを形態素解析し、Google検索する

			foreach ($keyword_innerlinkArray as $keyword => $valueArray) {
				if ( count($valueArray) > 1 ) {

					//毎回Google検索するんじゃなくて、既にDBに情報があり、まだ検索して間もないならそれを使う。結構時間(1日)が経ってるならNew検索
					foreach ($valueArray as $doc_id2 => $array) {
						$keyword_id = $array['keyword_id'];
						$sql = 'SELECT COUNT(*) FROM ' . $wixfilemeta_posts . ' WHERE doc_id='. $doc_id2 . ' AND keyword_id=' . $keyword_id . ' AND context_info IS NOT NULL AND time > (NOW()-INTERVAL 1 DAY)';

						if ( $wpdb->get_var($sql) == 0 ) {
							//AND検索用の単語群をDBから取得
							$sql = 'SELECT words_obj FROM ' . $wpdb->posts . ' WHERE ID=' . $doc_id2;
							$wordsObj = $wpdb->get_results($sql);

							foreach ($wordsObj as $index => $value) {
								$wordsArray = explode(',', $value->words_obj);
							}
							
							//Context情報の取得
							$context_info = wix_get_contextInfo($keyword, $wordsArray);

							if ( $context_info == 'no_selection_morphological_analysis' ) {
								$no_selection_morphological_analysis = 'true';

							} else if ( $context_info == 'no_selection_recommend_support' ) {
								$no_selection_recommend_support = 'true';

							} else {
								//Context情報をDBに登録(挿入 or 更新)
							    $sql = 'UPDATE ' . $wixfilemeta_posts . ' SET context_info="' . $context_info . '" WHERE keyword_id=' . $keyword_id . ' AND doc_id=' . $doc_id2;
							    $wpdb->query( $sql );
							}

						}

					}

					//対象ドキュメントの対象キーワードの周辺N単語取得
					$sql = 'SELECT words_obj FROM ' . $wpdb->posts . ' WHERE ID=' . $doc_id;
					$wordsObj = $wpdb->get_results($sql);
					foreach ($wordsObj as $index => $value) {
						$wordsArray = explode(',', $value->words_obj);
					}
					//対象ドキュメントの対象キーワードの周辺N単語取得
					$surwordsArray = wix_surrounding_words($keyword, $wordsArray);

					//Context情報とのマッチング
					foreach ($valueArray as $doc_id2 => $array) {
						$keyword_id = $array['keyword_id'];

						//Context情報をDBから取得
						$sql = 'SELECT context_info FROM ' . $wixfilemeta_posts . ' WHERE doc_id=' . $doc_id2 . ' AND keyword_id=' . $keyword_id;
						$contextObj = $wpdb->get_results($sql);

						$count = 0;
						foreach ($contextObj as $index => $value) {
							foreach ($surwordsArray as $i => $ar) {
								foreach ($ar as $j => $word) {
									if ( strpos($value->context_info, $word) !== false ) {
										$count++;
									}
								}
							}
						}


						$array['disambiguatoin_score'] = $count;
						$valueArray[$doc_id2] = $array;
						$keyword_innerlinkArray[$keyword] = $valueArray;
					}
				}
			}

		} else {
			/* hayashi法・改 */

			//対象ドキュメントにおいて、Ambiguate Entryに含まれるKeywordの周辺N文字列を探る
			$sql = 'SELECT words_obj FROM ' . $wpdb->posts . ' WHERE ID=' . $doc_id;
			$wordsObj = $wpdb->get_results($sql);
			foreach ($wordsObj as $index => $value) {
				$wordsArray = explode(',', $value->words_obj);
			}

			foreach ($keyword_innerlinkArray as $keyword => $valueArray) {

				if ( count($valueArray) > 1 ) {
					foreach ($valueArray as $doc_id2 => $array) {
						$keyword_id = $array['keyword_id'];
						break;
					}

					//毎回Google検索するんじゃなくて、既にDBに情報があり、まだ検索して間もないならそれを使う。結構時間(1日)が経ってるならNew検索
					$sql = 'SELECT COUNT(*) FROM ' . $wixfilemeta_posts . ' WHERE doc_id='. $doc_id . ' AND keyword_id=' . $keyword_id . ' AND context_info IS NOT NULL AND time > (NOW()-INTERVAL 1 DAY)';
					if ( $wpdb->get_var($sql) == 0 ) {
						//Context情報の取得
						$context_info = wix_get_contextInfo($keyword, $wordsArray);

						if ( $context_info == 'no_selection_morphological_analysis' ) {
							$no_selection_morphological_analysis = 'true';

						} else if ( $context_info == 'no_selection_recommend_support' ) {
							$no_selection_recommend_support = 'true';

						} else {
							//Context情報をDBに登録(挿入 or 更新)
						    $sql = 'UPDATE ' . $wixfilemeta_posts . ' SET context_info="' . $context_info . '" WHERE keyword_id=' . $keyword_id . ' AND doc_id=' . $doc_id;
						    $wpdb->query( $sql );
						}
					}

				    //Context情報をDBから取得
					$sql = 'SELECT context_info FROM ' . $wixfilemeta_posts . ' WHERE doc_id=' . $doc_id . ' AND keyword_id=' . $keyword_id;
					$contextObj = $wpdb->get_results($sql);

				    //TargetドキュメントとContext情報のマッチング
				    foreach ($valueArray as $doc_id2 => $array) {
						$sql = 'SELECT words_obj FROM ' . $wpdb->posts . ' WHERE ID=' . $doc_id2;
						$wordsObj = $wpdb->get_results($sql);
						foreach ($wordsObj as $index => $value) {
							$wordsArray = explode(',', $value->words_obj);
						}
						
						//Targetドキュメントの対象キーワードの周辺N単語取得
						$surwordsArray = wix_surrounding_words($keyword, $wordsArray);

						$count = 0;
						foreach ($contextObj as $index => $value) {
							foreach ($surwordsArray as $i => $ar) {
								foreach ($ar as $j => $word) {
									if ( strpos($value->context_info, $word) !== false ) {
										$count++;
									}
								}
							}
						}


						$array['disambiguatoin_score'] = $count;
						$valueArray[$doc_id2] = $array;
						$keyword_innerlinkArray[$keyword] = $valueArray;
					}
				}

			}


		}

		//Ranking
		foreach ($keyword_innerlinkArray as $keyword => $valueArray) {
			if ( count($valueArray) > 1 ) {
				$tmpArray = array();

				foreach ($valueArray as $doc_id2 => $array) {
					$tmpArray[$doc_id2] = $array['disambiguatoin_score'];
					unset($keyword_innerlinkArray[$keyword][$doc_id2]);
				}
				arsort($tmpArray);
				foreach ($tmpArray as $doc_id2 => $finalScore) {
					$keyword_innerlinkArray[$keyword][$doc_id2] = $valueArray[$doc_id2];
				}
			}
		}

	}

	return $keyword_innerlinkArray;
}


//Context情報の取得
function wix_get_contextInfo($keyword, $wordsArray) {
	$M = 5; //上位M件をContext情報とする

	//対象ドキュメントの対象キーワードの周辺N単語取得
	$surwordsArray = wix_surrounding_words($keyword, $wordsArray);

	//Google検索
	$google_resultsArray = get_snippet_by_google($keyword, $surwordsArray);

	if ( !empty($google_resultsArray) ) {

		//Google検索の結果から形態素解析
		$snippet_wordsArray = array();
		$words_num = 0;
		foreach ($google_resultsArray as $search_query => $obj) {
			$snippet = $obj['snippet'];
			// $title = $obj['title'];

			if ( get_option('morphological_analysis') != false ) {
				if ( get_option('morphological_analysis') == 'Yahoo' ) {
					/* Yahoo形態素解析使用 */
					$parse = wix_morphological_analysis($snippet);
					$wordsArray = wix_compound_noun_extract($parse);

				} else {
					/* Mecab使用 */
					$parse = wix_morphological_analysis_mecab($snippet);
					$wordsArray = wix_compound_noun_extract_mecab($parse);

				}

			} else {
				break;
			}

			//空白要素の削除
			$wordsArray = wix_blank_remove($wordsArray);
			//Stopwords removal
			$wordsArray = wix_stopwords_remove($wordsArray);
			array_push($snippet_wordsArray, $wordsArray);

			$words_num += count($wordsArray);
		}

		if ( !empty($snippet_wordsArray) ) {
			//スニペットから抽出した単語群の出現回数とTFを計算
			$tf_rankingArray = wix_tf_ranking($snippet_wordsArray, $words_num);

			$context_info = '';
			$roop = 0;
			foreach ($tf_rankingArray as $word => $tf) {
				if ( $roop == 0 )
					$context_info = $word;
				else 
					$context_info = $context_info . ',' . $word;

				$roop++;
				if ( $roop >= $M ) break;
			}

		} else {
			$context_info = 'no_selection_morphological_analysis';
		}

	} else {
		$context_info = 'no_selection_recommend_support';
	}

	return $context_info;
}


//対象単語の周辺N単語を返す
function wix_surrounding_words($subjectWord, $wordsArray) {
	$returnValue = array();
	$N = 2; //前後2件の周辺文字列を返す
	$queue = array();

	foreach ($wordsArray as $index => $word) {
		$index = intval($index);

		if ( $word == $subjectWord ) {

			if ( count($queue) < 2 ) {
				if ( count($queue) == 0 ) 
					$tmpArray = array();
				else
					$tmpArray = $queue;

				if ( count($wordsArray) < $index + $N  )
					$limit = count($wordsArray) + 1;
				else
					$limit = $index + $N + ($N - count($tmpArray)) + + 1;

			} else {
				//前2単語
				$tmpArray = $queue;

				//後2単語
				if ( count($wordsArray) < $index + $N  )
					$limit = count($wordsArray) + 1;
				else
					$limit = $index + $N + 1;
			}
	
			for ( $i = $index + 1; $i < $limit; $i++ ) {
				array_push($tmpArray, $wordsArray[$i]);
			}
			array_push($returnValue, $tmpArray);
		}

		array_push($queue, $word);
		if ( $index > $N - 1 ) {
			unset($queue[0]);
			$queue = array_values($queue);
		}
	}

	return $returnValue;
}

//Google検索によるスニペット取得
function get_snippet_by_google($word, $and_searchArray) {
	$returnValue = array();

	if ( get_option('google_api_key') != false && get_option('google_cx') != false  ) {

		$google_api_key = get_option('google_api_key');
		$google_cx = get_option('google_cx');

		// $google_api_key = 'AIzaSyAx1KGY7MrMTGWYAvMG8OzdZyCb4w-G-ao';
		// $google_cx = '006932243093891704093:kkupxt3ya1e';

		// 検索用URL
		$tmp_url = "https://www.googleapis.com/customsearch/v1?";

		foreach ($and_searchArray as $index => $array) {
			foreach ($array as $i => $andWord) {

				//検索クエリ
				$search_query = $word . '+' . $andWord;

				// 検索パラメタ発行
				$params_list = array(
									'q'=>$search_query,
									'key'=>$google_api_key,
									'cx'=>$google_cx,
									'alt'=>'json',
									'start'=>'1');

				// リクエストパラメータ作成
				$req_param = http_build_query($params_list);

				// リクエスト本体作成
				$request = $tmp_url.$req_param;

				// jsonデータ取得
				$response = file_get_contents($request,true);
				$json_obj = json_decode($response,true);

				foreach ($json_obj['items'] as $index => $value) {
					$snippet = $value['snippet'];
					$title = $value['title'];
					// $url = $value['link'];

					$returnValue[$search_query] = array('snippet' => $snippet, 'title' => $title);
				}

			}
		}

	}

	return $returnValue;
}

//YahooIDとかGoogle IDが既にDBにあったら返り値へ。
add_action( 'wp_ajax_wix_contents_option', 'wix_contents_option' );
add_action( 'wp_ajax_nopriv_wix_contents_option', 'wix_contents_option' );
function wix_contents_option() {
	global $wpdb;

	header("Access-Control-Allow-Origin: *");
	header('Content-type: application/javascript; charset=utf-8');

	$contents_option = $_POST['contents_option'];

	if ( get_option($contents_option) == false )
		$returnValue = '';
	else
		$returnValue = get_option( $contents_option );

	$json = array(
		"contents_option" => $returnValue,
	);

	echo json_encode( $json );

	
    die();
}

















//既に作成済みのWIXファイルのIDとファイル名の関係抽出
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


//更新・エラーメッセージを表示する
add_action( 'admin_notices', 'wix_settings_notices' );	
function wix_settings_notices() {
?>
	<?php if ( $messages = get_transient( 'wix_init_settings_errors' ) ): ?>
	<div class="error">
		<ul>
			<?php foreach( (array)$messages as $message ): ?>
				<li><?php echo esc_html($message); ?></li>
			<?php endforeach; ?>
		</ul>
	</div>
	<?php elseif ( $messages = get_transient( 'wix_init_settings' ) ): ?>
	<div class="updated">
		<ul>
			<?php foreach( (array)$messages as $message ): ?>
				<li><?php echo esc_html($message); ?></li>
			<?php endforeach; ?>
		</ul>
	</div>
	<?php elseif ( $messages = get_transient( 'wix_settings_errors' ) ): ?>
	<div class="error">
		<ul>
			<?php foreach( (array)$messages as $message ): ?>
				<li><?php echo esc_html($message); ?></li>
			<?php endforeach; ?>
		</ul>
	</div>
	<?php elseif ( $messages = get_transient( 'wix_settings' ) ): ?>
	<div class="updated">
		<ul>
			<?php foreach( (array)$messages as $message ): ?>
				<li><?php echo esc_html($message); ?></li>
			<?php endforeach; ?>
		</ul>
	</div>
	<?php elseif ( $messages = get_transient( 'wixfile_settings_errors' ) ): ?>
	<div class="error">
		<ul>
			<?php foreach( (array)$messages as $message ): ?>
				<li><?php echo esc_html($message); ?></li>
			<?php endforeach; ?>
		</ul>
	</div>
	<?php elseif ( $messages = get_transient( 'wixfile_settings' ) ): ?>
	<div class="updated">
		<ul>
			<?php foreach( (array)$messages as $message ): ?>
				<li><?php echo esc_html($message); ?></li>
			<?php endforeach; ?>
		</ul>
	</div>
	<?php elseif ( $messages = get_transient( 'decidefile_settings_errors' ) ): ?>
	<div class="error">
		<ul>
			<?php foreach( (array)$messages as $message ): ?>
				<li><?php echo esc_html($message); ?></li>
			<?php endforeach; ?>
		</ul>
	</div>
	<?php elseif ( $messages = get_transient( 'decidefile_settings' ) ): ?>
	<div class="updated">
		<ul>
			<?php foreach( (array)$messages as $message ): ?>
				<li><?php echo esc_html($message); ?></li>
			<?php endforeach; ?>
		</ul>
	</div>
	<?php elseif ( $messages = get_transient( 'default_detail_decide_settings_errors' ) ): ?>
	<div class="error">
		<ul>
			<?php foreach( (array)$messages as $message ): ?>
				<li><?php echo esc_html($message); ?></li>
			<?php endforeach; ?>
		</ul>
	</div>
	<?php elseif ( $messages = get_transient( 'default_detail_decide_settings' ) ): ?>
	<div class="updated">
		<ul>
			<?php foreach( (array)$messages as $message ): ?>
				<li><?php echo esc_html($message); ?></li>
			<?php endforeach; ?>
		</ul>
	</div>
	<?php endif; ?>
<?php
}



//WIXFileのエントリ候補をwix_document_similarityテーブルから推薦
// add_action( 'wp_ajax_wix_similarity_entry_recommend', 'wix_similarity_entry_recommend' );
// add_action( 'wp_ajax_nopriv_wix_similarity_entry_recommend', 'wix_similarity_entry_recommend' );
// function wix_similarity_entry_recommend() {
// 	global $wpdb;

// 	header("Access-Control-Allow-Origin: *");
// 	header('Content-type: application/javascript; charset=utf-8');

// 	$doc_id = $_POST['doc_id'];

// 	//候補キーワード群
// 	$sql = 'SELECT * FROM ' . $wpdb->prefix . 'wix_keyword_similarity' .
// 			 ' WHERE doc_id = ' . $doc_id . ' AND tf_idf != 0 order by tf_idf desc';
// 	$candidate_keywords = $wpdb->get_results($sql);

// /**
// 	↓のsqlの cos_similarityはもうない。今はcos_similarity_tfidfとcos_similarity_bm25
// */
// 	//クリックされたドキュメントとの関連度を持つドキュメント群
// 	// $sql = 'SELECT * FROM ' . $wpdb->prefix . 'wix_document_similarity' .
// 	//  ' WHERE cos_similarity != 0 AND (doc_id=' . $doc_id . ' OR doc_id2=' . $doc_id . ') order by cos_similarity desc';
// 	$similar_documents = $wpdb->get_results($sql);


// 	if ( !empty($candidate_keywords) && !empty($similar_documents) ) {
// 		//候補ターゲット群をDBから持ってくる
// 		$selectQuery = '';
// 		foreach ($similar_documents as $key => $value) {
// 			if ( (int)$value->doc_id == $doc_id )
// 				$candidate_doc_id = $value->doc_id2;
// 			else
// 				$candidate_doc_id = $value->doc_id;


// 			if ( empty($selectQuery) )
// 				$selectQuery = 'ID=' . $candidate_doc_id . ' ';
// 			else
// 				$selectQuery = $selectQuery . 'OR ID=' . $candidate_doc_id . ' ';
// 		}
// 		$sql = 'SELECT ID, post_title, guid FROM ' . $wpdb->posts . ' WHERE ' . $selectQuery;
// 		$candidate_targets = $wpdb->get_results($sql);

// 		$returnValue = array();
// 		foreach ($candidate_keywords as $key => $value) {
// 			$returnValue[$value->keyword] = $candidate_targets;
// 		}


// 	} else {
// 		$returnValue = array();
// 	}

// 	$json = array(
// 		"entrys" => $returnValue,
// 		// "entrys" => $similar_documents,
// 	);

// 	echo json_encode( $json );

	
//     die();
// }





?>