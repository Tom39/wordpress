<?php

//ドキュメント長の計算
add_action( 'transition_post_status', 'wix_documnt_length', 10, 3 );
function wix_documnt_length( $new_status, $old_status, $post ) {
	global $wpdb;
	if ( $new_status != 'trash' && $new_status != 'inherit' && $new_status != 'auto-draft' ) {
		$doc_length = mb_strlen( strip_tags($post->post_content), 'UTF-8');

		$sql = 'UPDATE ' . $wpdb->posts . ' SET doc_length=' . $doc_length . ' WHERE ID=' . $post->ID;
		$wpdb->query( $sql );
	}
}

//ドキュメントの投稿ステータスが変わったら、類似度計算
add_action( 'transition_post_status', 'wix_similarity_func', 10, 3 );
function wix_similarity_func( $new_status, $old_status, $post ) {
	global $wpdb, $term_featureObj, $doc_simObj;

	//ゴミ箱行きだったらDELTE.次にリビジョンに対するエントリを作らないように.
	if ( $new_status == 'trash' ) {

		wix_similarity_score_deletes($post->ID, 'wix_keyword_similarity');
		wix_similarity_score_deletes($post->ID, 'wix_document_similarity');
		wix_similarity_score_deletes($post->ID, 'wix_minhash');

	} else if ( $new_status != 'inherit' && $new_status != 'auto-draft' ) {
		/*
		* $parse: 形態素解析結果
		* $wordsArray: [(複合)名詞]
		* $words_countArray: [単語 => 単語数]
		*/
		$parse = wix_morphological_analysis($post->post_content);
		$wordsArray = wix_compound_noun_extract($parse);
		$words_countArray = array_word_count($wordsArray);

		//まだDBに１つもドキュメントがなかったら計算しないでDBに挿入するだけ.	
		$sql = 'SELECT COUNT(*) FROM ' . $wpdb->posts . ' WHERE post_status!="inherit" and post_status!="trash" and post_status!="auto-save" and post_status!="auto-draft"';
		if ( $wpdb->get_var($sql) == 1 ) {
			//ドキュメント数2になるなら、まだ計算してない１つ目のドキュメントに対する計算
/*
*
*
*
*
*
*
*/

		} else {
			$doc_id = $post->ID;

			//TextRank計算
			// wix_textrank( $wordsArray );

			//TF-IDF計算
			// wix_tfidf( $words_countArray );

			//BM25計算
			// wix_bm25( $words_countArray, $doc_id );


			//コサイン類似度計算
			// wix_cosSimilarity($doc_id);
			// dump('dump.txt', $doc_simObj);

			//MinHash値計算
			// wix_minhash($doc_id);

			//DBに挿入・更新
			// wix_keyword_similarity_score_inserts_updates($doc_id);
			// wix_document_similarity_score_inserts_updates($doc_id);
		}
	}

}

/**
固定ページは固定ページのみ、投稿は投稿のみを対象として計算したほうがいい？？（2015/09/25）
**/
//TF-IDF値などをDBに保存・更新
function wix_keyword_similarity_score_inserts_updates($doc_id) {
	global $wpdb, $term_featureObj;

	$wix_keyword_similarity = $wpdb->prefix . 'wix_keyword_similarity';

	if ( !empty($term_featureObj) ) {
		$sql = 'SELECT COUNT(*) FROM ' . $wix_keyword_similarity . ' WHERE doc_id = ' . $doc_id;
		$entry_check_flag = $wpdb->get_var($sql);

		//INSERT
		if ( $entry_check_flag == 0 ) {
			$insertEntry = '';
			$methodArray = array();
			$methodFlag = false;
			foreach ($term_featureObj as $keyword => $array) {
				$tmpEntry = '';
				foreach ($array as $method => $value) {
					if ( $methodFlag == false )
						array_push($methodArray, $method);

					if ( !empty($tmpEntry) ) 
						$tmpEntry = $tmpEntry . ', '; 

					$tmpEntry = $tmpEntry . $value;
				}
				if ( empty($insertEntry) ) 
					$insertEntry = '(' . $doc_id . ', "' . $keyword .'", ' . $tmpEntry . ')';
				else
					$insertEntry = $insertEntry . ', (' . $doc_id . ', "' . $keyword .'", ' . $tmpEntry . ')';
				
				$methodFlag = true;
			}

			$sql = 'INSERT INTO ' . $wix_keyword_similarity . '(doc_id, keyword';
			foreach ($methodArray as $index => $method) {
				$sql = $sql . ', ' . $method;
			}
			$sql = $sql . ') VALUES ' . $insertEntry;
			$wpdb->query( $sql );

		//UPDATE
		} else {
			//新たにDBに加わるキーワードはUPDATE + INSERTをする必要アリ
			$sql = 'SELECT keyword FROM ' . $wix_keyword_similarity . ' WHERE  doc_id = ' . $doc_id;
			$existing_keywordObj = $wpdb->get_results($sql);
			//既にDBに存在するキーワード一覧を取得
			$existing_keywordList = array();
			foreach ($existing_keywordObj as $key => $value) {
				$existing_keywordList[$value->keyword] = '';
			}

			$methodArray = array();
			$methodFlag = false;
			foreach ($term_featureObj as $keyword => $array) {
				if ( array_key_exists($keyword, $existing_keywordList) ) {
					$valueArray = array();
					foreach ($array as $method => $value) {
						if ( $methodFlag == false )
							array_push($methodArray, $method);
						
						array_push($valueArray, $value);
					}

					$sql = 'UPDATE ' . $wix_keyword_similarity . ' SET ';
					foreach ($methodArray as $index => $method) {
						if ( $index != count($methodArray)-1 ) 
							$sql = $sql . $method . '=' . $valueArray[$index] . ', ';
						else 
							$sql = $sql . $method . '=' . $valueArray[$index];
					}
					$sql = $sql . ' WHERE doc_id = ' . $doc_id .' AND keyword = "' . $keyword . '"';

					unset($existing_keywordList[$keyword]);

				} else {
					$insertEntry = '';
					$tmpEntry = '';
					foreach ($array as $method => $value) {
						if ( $methodFlag == false )
							array_push($methodArray, $method);

						if ( !empty($tmpEntry) ) 
							$tmpEntry = $tmpEntry . ', '; 

						$tmpEntry = $tmpEntry . $value;
					}
					$insertEntry = '(' . $doc_id . ', "' . $keyword .'", ' . $tmpEntry . ')';

					$sql = 'INSERT INTO ' . $wix_keyword_similarity . '(doc_id, keyword';
					foreach ($methodArray as $index => $method) {
						$sql = $sql . ', ' . $method;
					}
					$sql = $sql . ') VALUES ' . $insertEntry;
				}
				// dump('dump.txt', $sql);
				$wpdb->query( $sql );
				$methodFlag = true;
			}

			//最後は"現在"作成したドキュメントに存在しないキーワードをDBから削除
			foreach ($existing_keywordList as $keyword => $value) {
				$sql = 'DELETE FROM ' . $wix_keyword_similarity . ' WHERE doc_id = ' . $doc_id . ' and keyword = "' . $keyword . '"';
				$wpdb->query( $sql );
				// dump('dump.txt', $sql);
			}
		}

	}
}

//COS類似度などをDBに保存・更新
function wix_document_similarity_score_inserts_updates($doc_id) {
	global $wpdb, $doc_simObj;

	$wix_document_similarity = $wpdb->prefix . 'wix_document_similarity';

	if ( !empty($doc_simObj) ) {
		$sql = 'SELECT COUNT(*) FROM ' . $wix_document_similarity . ' WHERE doc_id = ' . $doc_id . ' OR doc_id2 = ' . $doc_id;
		$entry_check_flag = $wpdb->get_var($sql);

		//INSERT(初期登録)
		if ( $entry_check_flag == 0 ) {

			$insertEntry = '';
			$methodArray = array();
			$methodFlag = false;
			foreach ($doc_simObj as $doc_id2 => $array) {
				$tmpEntry = '';
				foreach ($array as $method => $value) {
					if ( $methodFlag == false )
						array_push($methodArray, $method);

					if ( $tmpEntry != '' ) 
						$tmpEntry = $tmpEntry . ', '; 

					$tmpEntry = $tmpEntry . $value;
				}
				if ( empty($insertEntry) ) 
					$insertEntry = '(' . $doc_id . ', ' . $doc_id2 .', ' . $tmpEntry . ')';
				else
					$insertEntry = $insertEntry . ', (' . $doc_id . ', ' . $doc_id2 .', ' . $tmpEntry . ')';
				$methodFlag = true;
			}

			$sql = 'INSERT INTO ' . $wix_document_similarity . '(doc_id, doc_id2';
			foreach ($methodArray as $index => $method) {
				$sql = $sql . ', ' . $method;
			}
			$sql = $sql . ') VALUES ' . $insertEntry;
			$wpdb->query( $sql );

		//UPDATE
		} else {
			$sql = 'SELECT doc_id, doc_id2 FROM ' . $wix_document_similarity . ' WHERE doc_id=' . $doc_id. ' OR doc_id2=' . $doc_id;
			$existing_docObj = $wpdb->get_results($sql);

			$existing_docList = array();
			foreach ($existing_docObj as $key => $value) {
				if ( $value->doc_id == $doc_id ) $existing_docList[$value->doc_id2] = '';
				else $existing_docList[$value->doc_id] = '';
			}

			$methodArray = array();
			$methodFlag = false;
			foreach ($doc_simObj as $doc_id2 => $array) {
				if ( array_key_exists($doc_id2, $existing_docList) ) {

					$valueArray = array();
					foreach ($array as $method => $value) {
						if ( $methodFlag == false )
							array_push($methodArray, $method);
						
						array_push($valueArray, $value);
					}

					$sql = 'UPDATE ' . $wix_document_similarity . ' SET ';
					foreach ($methodArray as $index => $method) {
						if ( $index != count($methodArray)-1 ) 
							$sql = $sql . $method . '=' . $valueArray[$index] . ', ';
						else 
							$sql = $sql . $method . '=' . $valueArray[$index];
					}
					$sql = $sql . ' WHERE (doc_id=' . $doc_id . ' AND doc_id2=' . $doc_id2 . ') OR (doc_id=' . $doc_id2 . ' AND doc_id2=' . $doc_id . ')';

				} else {

					$insertEntry = '';
					$tmpEntry = '';
					foreach ($array as $method => $value) {
						if ( $methodFlag == false )
							array_push($methodArray, $method);

						if ( !empty($tmpEntry) ) 
							$tmpEntry = $tmpEntry . ', '; 

						$tmpEntry = $tmpEntry . $value;
					}
					$insertEntry = '(' . $doc_id . ', ' . $doc_id2 .', ' . $tmpEntry . ')';

					$sql = 'INSERT INTO ' . $wix_document_similarity . '(doc_id, doc_id2';
					foreach ($methodArray as $index => $method) {
						$sql = $sql . ', ' . $method;
					}
					$sql = $sql . ') VALUES ' . $insertEntry;
				}
				// dump('dump.txt', $sql);
				$wpdb->query( $sql );
				$methodFlag = true;
			}
		}
	}
}

//各類似度エントリをDBから削除
function wix_similarity_score_deletes($doc_id, $table) {
	global $wpdb;

	if ( $table == 'wix_keyword_similarity' || $table == 'wix_minhash' ) {
		$sql = 'DELETE FROM ' . $wpdb->prefix . $table . ' WHERE doc_id = ' . $doc_id;
	} else if ( $table == 'wix_document_similarity' ) {
		$sql = 'DELETE FROM ' . $wpdb->prefix . $table . ' WHERE doc_id = ' . $doc_id . ' OR doc_id2 = ' . $doc_id;
	}
	$wpdb->query( $sql );
}


//IDF値の更新
// add_action( 'transition_post_status', 'wix_status_update_idf_update', 20, 3 );
function wix_status_update_idf_update( $new_status, $old_status, $post ) {
	global $wpdb;
	$wix_keyword_similarity = $wpdb->prefix . 'wix_keyword_similarity';

	if ( $new_status == 'trash' ) {

		wix_similarity_score_deletes($post->ID, 'wix_keyword_similarity');
		wix_similarity_score_deletes($post->ID, 'wix_document_similarity');
		wix_similarity_score_deletes($post->ID, 'wix_minhash');

	} else if ( $new_status != 'inherit' && $new_status != 'auto-draft' ) {
		$sql = 'SELECT COUNT(*) FROM ' . $wix_keyword_similarity;
		if ( $wpdb->get_var($sql) == 0 ) return;

		$updateArray = array();
		$document_num = (int) $wpdb->get_var("SELECT COUNT(*) FROM $wpdb->posts WHERE post_status!=\"inherit\" and post_status!=\"trash\" and post_status!=\"auto-save\" and post_status!=\"auto-draft\"");
		
		//1回以上ドキュメントに出現したキーワードと、その回数(つまりidfの分母)
		$sql = 'SELECT keyword, COUNT(DISTINCT doc_id) AS num FROM ' . $wix_keyword_similarity . ' GROUP BY keyword';
		$keywords_in_alldocuments = $wpdb->get_results($sql);
		foreach ($keywords_in_alldocuments as $index => $value) {
			$keyword = $value->keyword;
			$occurences = $value->num;
			// dump('dump.txt', $keyword . ' : ' . $occurences);

			$updateArray[$keyword] = log($document_num / $occurences);
		}
		
		foreach ($updateArray as $keyword => $value) {
			$sql = 'UPDATE ' . $wix_keyword_similarity . ' SET idf=' . $value . ' WHERE keyword="' . $keyword . '"';
			$wpdb->query( $sql );
			// dump('dump.txt', $sql);
		}


		wix_word_features_update();
		wix_cosSimilarity_update();
	}
}
//単語ベクトル(TF-IDF, BM25)の更新
function wix_word_features_update() {
	global $wpdb;
	$wix_keyword_similarity = $wpdb->prefix . 'wix_keyword_similarity';

	$sql = 'SELECT doc_id, keyword, tf, idf FROM ' . $wix_keyword_similarity;
	$keyword_similarityObj = $wpdb->get_results($sql);

	//平均ドキュメント長(bm25用)
	$sql = 'SELECT COUNT(ID) AS doc_num, SUM(doc_length) AS all_doc_length FROM ' . $wpdb->posts . ' WHERE post_status!="inherit" AND post_status!="trash" AND post_status!="auto-save" AND post_status!="auto-draft"';
	$docObj = $wpdb->get_results($sql);
	$doc_num = $docObj[0]->doc_num;
	$avg_doc_length = ($docObj[0]->all_doc_length) / $doc_num;

	//bm25用のパラメータ
	$k1 = 2;
	$b = 0.75;

	foreach ($keyword_similarityObj as $index => $value) {
		$doc_id = $value->doc_id;
		$keyword = $value->keyword;
		$tf_idf = $value->tf * $value->idf;
		$bm25 = ($value->tf * $value->idf * ($k1 + 1)) / ($value->tf + $k1 * ((1 - $b + $b * ($doc_length / $avg_doc_length))));

		$sql = 'UPDATE ' . $wix_keyword_similarity . ' SET tf_idf=' . $tf_idf . ', bm25=' . $bm25 . ' WHERE doc_id=' . $doc_id . ' AND keyword="' . $keyword . '"';
		$wpdb->query( $sql );
	}
}

//TF-IDF値の更新
function wix_tfidf_update() {
	global $wpdb;
	$wix_keyword_similarity = $wpdb->prefix . 'wix_keyword_similarity';

	$sql = 'SELECT doc_id, keyword, tf, idf FROM ' . $wix_keyword_similarity;
	$keyword_similarityObj = $wpdb->get_results($sql);
	foreach ($keyword_similarityObj as $index => $value) {
		$doc_id = $value->doc_id;
		$keyword = $value->keyword;
		$tf_idf = $value->tf * $value->idf;

		$sql = 'UPDATE ' . $wix_keyword_similarity . ' SET tf_idf=' . $tf_idf . ' WHERE doc_id=' . $doc_id . ' AND keyword="' . $keyword . '"';
		$wpdb->query( $sql );
	}
}

//BM25値の更新
function wix_bm25_update() {
	global $wpdb;
	$wix_keyword_similarity = $wpdb->prefix . 'wix_keyword_similarity';

	$sql = 'SELECT doc_id, keyword, tf, idf, doc_length FROM ' . $wpdb->posts . ', ' . $wix_keyword_similarity . ' WHERE ID=doc_id';
	$keyword_similarityObj = $wpdb->get_results($sql);

	//平均ドキュメント長
	$sql = 'SELECT COUNT(ID) AS doc_num, SUM(doc_length) AS all_doc_length FROM ' . $wpdb->posts . ' WHERE post_status!="inherit" AND post_status!="trash" AND post_status!="auto-save" AND post_status!="auto-draft"';
	$docObj = $wpdb->get_results($sql);
	$doc_num = $docObj[0]->doc_num;
	$avg_doc_length = ($docObj[0]->all_doc_length) / $doc_num;

	//bm25用のパラメータ
	$k1 = 2;
	$b = 0.75;

	foreach ($keyword_similarityObj as $index => $value) {
		$doc_id = $value->doc_id;
		$keyword = $value->keyword;

		$bm25 = ($value->tf * $value->idf * ($k1 + 1)) / ($value->tf + $k1 * ((1 - $b + $b * ($doc_length / $avg_doc_length))));

		$sql = 'UPDATE ' . $wix_keyword_similarity . ' SET bm25=' . $bm25 . ' WHERE doc_id=' . $doc_id . ' AND keyword="' . $keyword . '"';
		$wpdb->query( $sql );
	}
}

//cosSimilarityの更新
function wix_cosSimilarity_update() {
	global $wpdb;

	$wix_keyword_similarity = $wpdb->prefix . 'wix_keyword_similarity';
	$wix_document_similarity = $wpdb->prefix . 'wix_document_similarity';

	$sql = 'SELECT doc_id, keyword, tf_idf AS tfidf, bm25 FROM ' . $wix_keyword_similarity . ' WHERE tf_idf!=0 OR bm25!=0 ORDER BY doc_id ASC';
	$keyword_similarityObj = $wpdb->get_results($sql);

	if ( !empty($keyword_similarityObj) ) {

		$keyword_similarityArray = array();
		$bunboArray = array();
		foreach ($keyword_similarityObj as $index => $array) {
			$doc_id = $array->doc_id;
			$keyword = $array->keyword;

			if ( array_key_exists($doc_id, $keyword_similarityArray) ) {
				$tmpArray = $keyword_similarityArray[$doc_id];
				$tmpArray[$keyword] = array('tfidf' => $array->tfidf, 'bm25' => $array->bm25);
				$keyword_similarityArray[$doc_id] = $tmpArray;
			} else {
				$keyword_similarityArray[$doc_id] = array(
															$keyword => array(
																				'tfidf' => $array->tfidf,
																				'bm25' => $array->bm25
																			)
														);
			}

			if ( array_key_exists($doc_id, $bunboArray) ) {
				$tmpArray = $bunboArray[$doc_id];
				foreach ($tmpArray as $method => $value) {
					$tmpArray[$method] = $value + $array->$method * $array->$method;
				}
				$bunboArray[$doc_id] = $tmpArray;
			} else {
				$bunboArray[$doc_id] = array(
											'tfidf' => $array->tfidf * $array->tfidf,
											'bm25' => $array->bm25 * $array->bm25,
										 );
			}
		}

		$copy = $keyword_similarityArray;
		$bunsiArray = array();
		foreach ($keyword_similarityArray as $doc_id => $array) {
			unset($copy[$doc_id]);
			foreach ($array as $keyword => $valueArray) {
				foreach ($copy as $doc_id2 => $ar) {
					if ( array_key_exists($keyword, $ar) ) {
						// dump('dump.txt', $doc_id . ' : ' . $doc_id2 . ' = ' . $keyword . '*' . $valueArray['tfidf'] . ' & ' . $ar[$keyword]['tfidf']);
						if ( array_key_exists($doc_id, $bunsiArray) ) {
							$tmpArray = $bunsiArray[$doc_id];
							if ( array_key_exists($doc_id2, $tmpArray) ) {
								$tmp = $tmpArray[$doc_id2];
								foreach ($tmp as $method => $value) {
									$tmp[$method] = $value + $valueArray[$method] * $ar[$keyword][$method];
								}
								$tmpArray[$doc_id2] = $tmp;
							} else {
								$tmpArray[$doc_id2] = array(
															'tfidf' => $valueArray['tfidf'] * $ar[$keyword]['tfidf'],
															'bm25' => $valueArray['bm25'] * $ar[$keyword]['bm25'],
														);
							}

							$bunsiArray[$doc_id] = $tmpArray;

						} else {
							$bunsiArray[$doc_id] = array(
															$doc_id2 => array(
																				'tfidf' => $valueArray['tfidf'] * $ar[$keyword]['tfidf'],
																				'bm25' => $valueArray['bm25'] * $ar[$keyword]['bm25'],
																			)
													);
						}
					}
				}
			}
		}

		foreach ($bunsiArray as $doc_id => $array) {
			foreach ($array as $doc_id2 => $valueArray) {
				$sql = 'UPDATE ' . $wix_document_similarity . ' SET ';
				$count = count($valueArray);
				foreach ($valueArray as $method => $value) {
					if ( $value == 0 ) 
						$cos = 0;
					else
						$cos = $value / (sqrt($bunboArray[$doc_id][$method]) * sqrt($bunboArray[$doc_id2][$method]));

					if ( $count == 1 ) 
						$sql = $sql . 'cos_similarity_' . $method . '=' . $cos;
					else 
						$sql = $sql . 'cos_similarity_' . $method . '=' . $cos . ', ';

					$count--;
				}
				$sql = $sql . ' WHERE (doc_id=' . $doc_id . ' AND doc_id2=' . $doc_id2 . ') OR (doc_id=' . $doc_id2 . ' AND doc_id2=' . $doc_id . ')';
				$wpdb->query( $sql );
			}
		}

	}

	
}





?>