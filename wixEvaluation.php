<?php
/**
		評価実験用のプログラム
*/

/****************** CSVファイル書き込み ***********************/
// $file = fopen( dirname( __FILE__ ) . '/eval/csv/' . $doc_id .  '.csv', 'w');
// if( $file )
//   fputcsv($file, $tmpArray);
// fclose($file);

/****************** CSVファイル読み込み ***********************/
// $file = dirname( __FILE__ ) . '/eval/csv/' . $doc_id .  '.csv';
// if ( ($handle = fopen($file, 'r')) !== FALSE ) {
//     while ( ($data = fgetcsv($handle, 1000, ',')) !== FALSE ) {
// 		$num = count($data);
// 		// dump('dump.txt', '*** ' . $doc_id . ' ***');
// 		for ($c = 0; $c < $num; $c++) {
// 			// dump('dump.txt', $data[$c]);
// 			/* $data[$c]: CSVファイルの各行の取得結果 */

// 		}
// 	}
//     fclose($handle);
// }

/*******************************************単語特徴量**********************************************************/
//ドキュメントの投稿ステータスが変わったら、類似度計算
add_action( 'transition_post_status', 'wix_eval_similarity_func', 10, 3 );
function wix_eval_similarity_func( $new_status, $old_status, $post ) {
	global $wpdb, $term_featureObj, $doc_simObj;

	//ゴミ箱行きだったらDELTE.次にリビジョンに対するエントリを作らないように.
	if ( $new_status == 'trash' ) {

		// wix_similarity_score_deletes($post->ID, 'wix_keyword_similarity');
		// wix_similarity_score_deletes($post->ID, 'wix_document_similarity');
		// wix_similarity_score_deletes($post->ID, 'wix_minhash');

	} else if ( $new_status != 'inherit' && $new_status != 'auto-draft' ) {

		$doc_id = $post->ID;

		/***********************PreProcess*********************************************/
		// $sql = 'SELECT ID, post_content FROM ' . $wpdb->posts . ' WHERE post_status!="inherit" and post_status!="trash" and post_status!="auto-save" and post_status!="auto-draft" ORDER BY id ASC';
		// $doc_Obj = $wpdb->get_results($sql);
		// foreach ($doc_Obj as $index => $value) {
		// 	$doc_id = $value->ID;
		// 	$content = $value->post_content;
		// 	wix_wordext_preprocess_getfile($doc_id, $content);
		// }

		// wix_wordext_preprocess_getfile($doc_id, $post->post_content);
		// wix_wordext_preprocess_insertDB();
		/*****************************************************************************/



		/***********************Process*********************************************/
		$sql = 'SELECT ID FROM ' . $wpdb->posts . ' WHERE post_status!="inherit" and post_status!="trash" and post_status!="auto-save" and post_status!="auto-draft" ORDER BY id ASC';
		$doc_Obj = $wpdb->get_results($sql);
		$evalArray = array();

		$time_start = microtime(true);
		foreach ($doc_Obj as $index => $value) {
			$doc_id = $value->ID;
			
			$sql = 'SELECT eval_words FROM ' . $wpdb->posts . ' WHERE ID=' . $doc_id;
			$eval_wordsObj = $wpdb->get_results($sql);
			$eval_wordsArray = explode(',', $eval_wordsObj[0]->eval_words);
			$words_countArray = wix_eval_array_word_count($eval_wordsArray);

			//TF-IDF計算
			wix_eval_tfidf( $words_countArray );

			//BM25計算
			// wix_eval_bm25( $words_countArray, $doc_id );

			//TextRank計算
			// wix_eval_textrank( $wordsArray );

			$evalArray[$doc_id] = $term_featureObj;

			$term_featureObj = array();
		}

		$timelimit = microtime(true) - $time_start;
		dump('dump.txt', $timelimit . ' sec');
		/* 上記部分の計算時間を計測 ↓のDB挿入は別にしないと。*/

		//DBに挿入
		foreach ($evalArray as $doc_id => $valueArray) {
			//DBに単語特徴量挿入・更新
			$term_featureObj = $valueArray;
			wix_eval_keyword_similarity_score_inserts_updates($doc_id);
		}
		//IDF, TF-IDF, BM25の更新
		wix_eval_idf_update();
		wix_eval_tfidf_update();
		// wix_eval_bm25_update();
		// wix_eval_textrank_update();
		/*****************************************************************************/

	}

}

function wix_wordext_preprocess_getfile($doc_id, $content) {
	global $wpdb;

	/* Mecab使用の形態素解析結果をDBにInsert */
	$parse = wix_eval_morphological_analysis_mecab($content);
	$wordsArray = wix_eval_compound_noun_extract_mecab($parse);
	$wordsArray = wix_eval_blank_remove($wordsArray);
	$wordsArray = wix_eval_stopwords_remove($wordsArray);

	$tmpArray = array();
	foreach ($wordsArray as $index => $word) {
		if ( !empty($word) )
			array_push($tmpArray, $word); 
	}
	$wordsArray_toString = implode(',', $tmpArray);

	$filename = dirname( __FILE__ ) . '/eval/wordext/' . $doc_id . '.txt';
	if ( file_exists($filename) )
		unlink($filename);

	file_put_contents( $filename, $wordsArray_toString, FILE_APPEND | LOCK_EX );
}

function wix_wordext_preprocess_insertDB() {
	global $wpdb;

	$sql = 'SELECT ID, post_content FROM ' . $wpdb->posts . ' WHERE post_status!="inherit" and post_status!="trash" and post_status!="auto-save" and post_status!="auto-draft" ORDER BY id ASC';
	$doc_Obj = $wpdb->get_results($sql);

	foreach ($doc_Obj as $index => $value) {
		$doc_id = $value->ID;
		$filename = dirname( __FILE__ ) . '/eval/wordext/' . $doc_id . '.txt';

		$file = fopen($filename, 'r');

		if ( flock($file, LOCK_SH) ){
			while ( !feof($file) ) {
				$wordsArray_toString = fgets($file);

				if ( $line === false ) break;

				$sql = 'UPDATE ' . $wpdb->posts . ' SET eval_words="' . $wordsArray_toString . '" WHERE ID=' . $doc_id;
				$wpdb->query( $sql );
			}
		}

		fclose($file);
	}

	
}

//Mecab(php-Mecab)を使った形態素解析
function wix_eval_morphological_analysis_mecab($content) {
	//半角スペースが読み込まれないので、一旦全角スペースに変換している
	$content = strip_tags( mb_convert_kana( $content, 'S') );

	$mecab = new MeCab_Tagger();
	$nodes = $mecab->parseToNode($content);

	return $nodes;
}

//Mecabを使って、形態素解析結果から複合名詞の作成
function wix_eval_compound_noun_extract_mecab($parse) {
	$tmpString = '';
	$returnValue = array();

	foreach ($parse as $node => $value) {
		$str = $value->getSurface();

		if ( !empty($str) ) {
			$array = explode(',', $value->getFeature());
			if ( $array[0] == '名詞' ) {
				$tmpString = $tmpString . $str;
			} else {
				array_push($returnValue, $tmpString);
				$tmpString = '';
			}
		}
	}
	if ( !empty($tmpString) )
		array_push($returnValue, $tmpString);

	return $returnValue;
}

//複合名詞を持つ配列から空白要素の削除
function wix_eval_blank_remove($array) {
	$returnValue = array();

	foreach ($array as $key => $word) {
		$word = trim($word);
		// $word = preg_replace('/(\s|　)/','', $word);

		if ( !empty($word) ) {
			array_push($returnValue, $word);
		}
	}
	
	return $returnValue;
}

//要素が全部数字などの削除
function wix_eval_stopwords_remove($array) {
	$returnValue = array();

	foreach ($array as $key => $word) {
		if ( !preg_match("/^[0-9]+$/", $word) ) {
			array_push($returnValue, $word);
		}
	}
	
	return $returnValue;
}

function wix_eval_array_word_count($array) {
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
			$words_countArray_num++;
		}
		
	}

	return $returnValue;
}

//TF-IDFの計算
function wix_eval_tfidf( $words_countArray ) {
	global $term_featureObj;

	//tf, idfの計算
	if ( empty($term_featureObj) ) {
		wix_tf($words_countArray);
		wix_idf();
	} else {
		foreach ($term_featureObj as $keyword => $method_scoreArray) {
			if ( !array_key_exists('tf_idf', $method_scoreArray) ) {
				wix_tf($words_countArray);
				wix_idf();
				break;
			}
		}
	}

	//tf-idf計算
	foreach ($term_featureObj as $key => $value) {
		$tf_idf = $value['tf'] * $value['idf'];
		$value['tf_idf'] = $tf_idf;
		$term_featureObj[$key] = $value;
	}
}

//BM25の計算
function wix_eval_bm25( $words_countArray, $doc_id ) {
	global $wpdb, $term_featureObj;
	
	//tf, idfの計算
	if ( empty($term_featureObj) ) {
		wix_tf($words_countArray);
		wix_idf();
	} else {
		foreach ($term_featureObj as $keyword => $method_scoreArray) {
			if ( !array_key_exists('tf_idf', $method_scoreArray) ) {
				wix_tf($words_countArray);
				wix_idf();
				break;
			}
		}
	}

	//ドキュメント長など
	$sql = 'SELECT doc_length FROM ' . $wpdb->posts . ' WHERE ID=' . $doc_id;
	$doc_lengthObj = $wpdb->get_results($sql);
	$doc_length = $doc_lengthObj[0]->doc_length;

	$sql = 'SELECT COUNT(ID) AS doc_num, SUM(doc_length) AS all_doc_length FROM ' . $wpdb->posts . ' WHERE post_status!="inherit" AND post_status!="trash" AND post_status!="auto-save" AND post_status!="auto-draft"';
	$docObj = $wpdb->get_results($sql);
	$doc_num = $docObj[0]->doc_num;
	$avg_doc_length = ($docObj[0]->all_doc_length) / $doc_num;

	//bm25用のパラメータ
	$k1 = 2;
	$b = 0.75;

	//bm25値計算
	foreach ($term_featureObj as $key => $value) {
		$bm25 = ($value['tf'] * $value['idf'] * ($k1 + 1)) 
					/ ($value['tf'] + $k1 * (1 - $b + $b * ($doc_length / $avg_doc_length)));


		$value['bm25'] = $bm25;
		$term_featureObj[$key] = $value;
	}
}

//TF値の計算
function wix_eval_tf($array) {
	global $words_countArray_num, $term_featureObj;

	if ( empty($term_featureObj) ) {
		foreach ($array as $word => $count) {
			$tf = $count / 	$words_countArray_num;
			$term_featureObj[$word] = array('tf' => $tf);
		}
	} else {
		foreach ($term_featureObj as $key => $value) {
			$count = $array[$key];
			$tf = $count / 	$words_countArray_num;
			$value['tf'] = $tf;
			$term_featureObj[$key] = $value;
		}
	}
}

//IDF値の計算
function wix_eval_idf() {
	global $wpdb, $post, $term_featureObj;

	$document_num = (int) $wpdb->get_var("SELECT COUNT(*) FROM $wpdb->posts WHERE post_status!=\"inherit\" and post_status!=\"trash\" and post_status!=\"auto-save\" and post_status!=\"auto-draft\"");
	$table_name = $wpdb->prefix . 'wix_eval_keyword_similarity';

	foreach ($term_featureObj as $keyword => $obj) {
		//「テーブル内に該当キーワードがあるあないか」、「エントリの挿入か、更新か」でcountが変わる
		$count = 0;
		$tmp = 0;
		$sql = 'SELECT COUNT(*) FROM ' . $table_name . ' WHERE keyword = "' . $keyword . '"';
		if ( $wpdb->get_var($sql) == 0 ) {
			$count = 1;
		} else {
			$tmp = $wpdb->get_var($sql);
			$sql = 'SELECT COUNT(*) FROM ' . $table_name . ' WHERE doc_id = ' . $post->ID . ' AND keyword = "' . $keyword . '"';
			if ( $wpdb->get_var($sql) == 0 ) {
				$tmp++;
			}
			$count = $tmp;
		}

		//logの定数を10にするか、e(ただのlog()は底がe)にするか
		$idf = log($document_num / $count);
		// $idf = log10($document_num / $count);

		if ( empty($term_featureObj) ) {
			$term_featureObj[$keyword] = array('idf' => $idf);
		} else {
			$obj['idf'] = $idf;
			$term_featureObj[$keyword] = $obj;
		}
	}
}

//Textrankの計算
function wix_eval_textrank($wordsArray) {
	global $wpdb, $term_featureObj;
	$table_name = $wpdb->posts;
	$terms = '';

	// build a directed graph with words as vertices and, as edges, the words which precede them
	$prev_word = 'aaaaa';
	$graph = array();
	$out_edges = array();
	$words = implode(' ', $wordsArray);
	$word = strtok($words, ' ');

	while ($word !== false) {
		if ( !array_key_exists($word, $graph) )
			$graph[$word][$prev_word] = 1;
		else 
			$graph[$word][$prev_word] += 1; // list the incoming words and keep a tally of how many times words co-occur

		if ( !array_key_exists($prev_word, $out_edges) )
			$out_edges[$prev_word] = 1;
		else
			$out_edges[$prev_word] += 1; // count the number of different words that follow each word
		$prev_word = $word;
		$word = strtok(' ');
	}
	// initialise the list of PageRanks-- one for each unique word 
	reset($graph);
	while (list($vertex, $in_edges) =  each($graph)) {
		$oldrank[$vertex] = 0.25;
	}
	$n = count($graph);
	if ($n > 0) {
		$base = 0.15 / $n; 
		$error_margin = $n * 0.005;
		do {
			$error = 0.0;
			// the edge-weighted PageRank calculation
			reset($graph);
			while (list($vertex, $in_edges) =  each($graph)) {
				$r = 0;
				reset($in_edges);
				while (list($edge, $weight) =  each($in_edges)) {
					$r += ($weight * $oldrank[$edge]) / $out_edges[$edge];
				}
				$rank[$vertex] = $base + 0.95 * $r;
				$error += abs($rank[$vertex] - $oldrank[$vertex]);		
			}
			$oldrank = $rank;
		} while ($error > $error_margin);
		arsort($rank);

		if ( empty($term_featureObj) ) {
			foreach ($rank as $vertex => $score) {
				$term_featureObj[$vertex] = array('textrank' => $score);
			}
		} else {
			foreach ($term_featureObj as $key => $value) {
				$value['textrank'] = $rank[$key];
				$term_featureObj[$key] = $value;
			}
		}
	}	
	// $res[] = $terms;
	// dump('dump.txt', $res);
}

//TF-IDF値などをDBに保存・更新
function wix_eval_keyword_similarity_score_inserts_updates($doc_id) {
	global $wpdb, $term_featureObj;

	$wix_eval_keyword_similarity = $wpdb->prefix . 'wix_eval_keyword_similarity';

	if ( !empty($term_featureObj) ) {
		$sql = 'SELECT COUNT(*) FROM ' . $wix_eval_keyword_similarity . ' WHERE doc_id = ' . $doc_id;
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

			$sql = 'INSERT INTO ' . $wix_eval_keyword_similarity . '(doc_id, keyword';
			foreach ($methodArray as $index => $method) {
				$sql = $sql . ', ' . $method;
			}
			$sql = $sql . ') VALUES ' . $insertEntry;
			$wpdb->query( $sql );
			// dump('dump.txt', $sql);

		//UPDATE
		} else {
			//新たにDBに加わるキーワードはUPDATE + INSERTをする必要アリ
			$sql = 'SELECT keyword FROM ' . $wix_eval_keyword_similarity . ' WHERE  doc_id = ' . $doc_id;
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

					$sql = 'UPDATE ' . $wix_eval_keyword_similarity . ' SET ';
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

					$sql = 'INSERT INTO ' . $wix_eval_keyword_similarity . '(doc_id, keyword';
					foreach ($methodArray as $index => $method) {
						$sql = $sql . ', ' . $method;
					}
					$sql = $sql . ') VALUES ' . $insertEntry;
				}
				$wpdb->query( $sql );
				// dump('dump.txt', $sql);
				$methodFlag = true;
			}

			//最後は"現在"作成したドキュメントに存在しないキーワードをDBから削除
			foreach ($existing_keywordList as $keyword => $value) {
				$sql = 'DELETE FROM ' . $wix_eval_keyword_similarity . ' WHERE doc_id = ' . $doc_id . ' and keyword = "' . $keyword . '"';
				$wpdb->query( $sql );
				// dump('dump.txt', $sql);
			}
		}

	}
}

//IDFの更新
function wix_eval_idf_update() {
	global $wpdb;
	$wix_eval_keyword_similarity = $wpdb->prefix . 'wix_eval_keyword_similarity';

	$updateArray = array();
	$document_num = (int) $wpdb->get_var('SELECT COUNT(*) FROM $wpdb->posts WHERE post_status!="inherit" AND post_status!="trash" AND post_status!="auto-save" AND post_status!="auto-draft"');
	
	//1回以上ドキュメントに出現したキーワードと、その回数(つまりidfの分母)
	$sql = 'SELECT keyword, COUNT(DISTINCT doc_id) AS num FROM ' . $wix_eval_keyword_similarity . ' GROUP BY keyword';
	$keywords_in_alldocuments = $wpdb->get_results($sql);
	foreach ($keywords_in_alldocuments as $index => $value) {
		$keyword = $value->keyword;
		$occurences = $value->num;

		$updateArray[$keyword] = log($document_num / $occurences);
	}
	
	foreach ($updateArray as $keyword => $value) {
		$sql = 'UPDATE ' . $wix_eval_keyword_similarity . ' SET idf=' . $value . ' WHERE keyword="' . $keyword . '"';
		$wpdb->query( $sql );
	}
}

//TF-IDF値の更新
function wix_eval_tfidf_update() {
	global $wpdb;
	$wix_eval_keyword_similarity = $wpdb->prefix . 'wix_eval_keyword_similarity';

	$sql = 'SELECT doc_id, keyword, tf, idf FROM ' . $wix_eval_keyword_similarity;
	$keyword_similarityObj = $wpdb->get_results($sql);
	foreach ($keyword_similarityObj as $index => $value) {
		$doc_id = $value->doc_id;
		$keyword = $value->keyword;
		$tf_idf = $value->tf * $value->idf;

		$sql = 'UPDATE ' . $wix_eval_keyword_similarity . ' SET tf_idf=' . $tf_idf . ' WHERE doc_id=' . $doc_id . ' AND keyword="' . $keyword . '"';
		$wpdb->query( $sql );
	}
}

//BM25値の更新
function wix_eval_bm25_update() {
	global $wpdb;
	$wix_eval_keyword_similarity = $wpdb->prefix . 'wix_eval_keyword_similarity';

	$sql = 'SELECT doc_id, keyword, tf, idf, doc_length FROM ' . $wpdb->posts . ', ' . $wix_eval_keyword_similarity . ' WHERE ID=doc_id';
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

		$sql = 'UPDATE ' . $wix_eval_keyword_similarity . ' SET bm25=' . $bm25 . ' WHERE doc_id=' . $doc_id . ' AND keyword="' . $keyword . '"';
		$wpdb->query( $sql );
	}
}


//TextRank値の更新
function wix_eval_textrank_update() {
	global $wpdb, $term_featureObj;
	$wix_eval_keyword_similarity = $wpdb->prefix . 'wix_eval_keyword_similarity';

	$sql = 'SELECT doc_id, keyword FROM ' . $wix_eval_keyword_similarity . ' ORDER BY doc_id ASC';
	$docObj = $wpdb->get_results($sql);

	$tmpId = '';
	$tmpArray = array();
	$term_featureObj = array();
	foreach ($docObj as $index => $value) {
		$keyword = $value->keyword;

		if ( $tmpId == '' ) $tmpId = $value->doc_id;
		if ( $tmpId == $value->doc_id ) {
			array_push($tmpArray, $keyword);
		} else {
			wix_textrank($tmpArray);

			foreach ($term_featureObj as $keyword => $method_scoreArray) {
				$sql = 'UPDATE ' . $wix_eval_keyword_similarity . ' SET textrank=' . $method_scoreArray['textrank'] . ' WHERE doc_id=' . $tmpId . ' AND keyword="' . $keyword . '"';
				$wpdb->query( $sql );
			}

			$term_featureObj = array();
			$tmpId = $value->doc_id;
			array_push($tmpArray, $keyword);
		}
	}
	wix_textrank($wordsArray);
	foreach ($term_featureObj as $keyword => $method_scoreArray) {
		$sql = 'UPDATE ' . $wix_eval_keyword_similarity . ' SET textrank=' . $method_scoreArray['textrank'] . ' WHERE doc_id=' . $tmpId . ' AND keyword="' . $keyword . '"';
		$wpdb->query( $sql );
	}
}

/*******************************************ドキュメント類似度計算**********************************************************/













?>