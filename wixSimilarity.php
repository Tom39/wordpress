<?php
require_once( dirname( __FILE__ ) . '/murmurhash3.php' );
$words_countArray_num = 0;
$term_featureObj = array();
/*
  $term_featureObj = [keyword:
						[
							'tf' => tf socre,
							'idf' => idf score,
							'tf_idf' => tf_idf score,
							'bm25' => bm25 score,
							'textrank' => textrank score
						]
					]
*/
$doc_simObj = array();
/*
	$doc_simObj = [doc_id:
					[
						'cos_similarity_tfidf' => ['doc_id' => cos similarity score],
						'cos_similarity_bm25' => ['doc_id' => cos similarity score],
						'jaccard' => ['doc_id' => jaccard score],
						'minhash' => ['doc_id' => minhash score],
					]
				]
*/

//yahoo形態素解析
function wix_morphological_analysis($content) {
	$yahooID = get_option( 'yahoo_id' );
	//HTMLタグを除去しつつ戻り値をパースする
	$url = "http://jlp.yahooapis.jp/MAService/V1/parse?appid=" . $yahooID .
									 "&results=ma&sentence=" . urlencode(strip_tags($content));
	$returnValue = simplexml_load_file($url);

	return $returnValue;
}

//Mecab(php-Mecab)を使った形態素解析
function wix_morphological_analysis_mecab($content) {
	//半角スペースが読み込まれないので、一旦全角スペースに変換している
	$content = strip_tags( mb_convert_kana( $content, 'S') );

	$mecab = new MeCab_Tagger();
	$nodes = $mecab->parseToNode($content);

	return $nodes;
}

//形態素解析結果から複合名詞の作成
function wix_compound_noun_extract($parse) {
	$tmpArray = array();
	$returnValue = array();

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
			array_push($returnValue, $tmp);
		}
	}
	if ( !empty($tmpArray) ) {
		$tmp = '';
		foreach ($tmpArray as $key => $value) {
			$tmp = $tmp . $value;
		}
		array_push($returnValue, $tmp);
	}

	return $returnValue;
}

//Mecabを使って、形態素解析結果から複合名詞の作成
function wix_compound_noun_extract_mecab($parse) {
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
function wix_blank_remove($array) {
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
function wix_stopwords_remove($array) {
	$returnValue = array();

	foreach ($array as $key => $word) {
		if ( !preg_match("/^[0-9]+$/", $word) ) {
			array_push($returnValue, $word);
		}
	}
	
	return $returnValue;
}

//作成ドキュメントにおける各キーワードの出現回数カウンタ
function array_word_count($array) {
	/*
	* $returnValue: [keyword => count]
	*/

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
function wix_tfidf( $words_countArray ) {
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
function wix_bm25( $words_countArray, $doc_id ) {
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
function wix_tf($array) {
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

//Entry Disambiuation用のTFランキング計算
function wix_tf_ranking($array, $words_num) {
	$returnValue = array();

	//各単語の出現回数を計算
	foreach ($array as $arrayIndex => $valueArray) {
		foreach ($valueArray as $index => $word) {
			if ( array_key_exists($word, $returnValue) ) {
				$count = $returnValue[$word] + 1;
				$returnValue[$word] = $count;
			} else {
				$returnValue[$word] = 1;
			}
		}
	}

	foreach ($returnValue as $word => $count) {
		$returnValue[$word] = $count / $words_num;
	}

	arsort($returnValue);

	return $returnValue;
}

//IDF値の計算
function wix_idf() {
	global $wpdb, $post, $term_featureObj;

	$document_num = (int) $wpdb->get_var("SELECT COUNT(*) FROM $wpdb->posts WHERE post_status!=\"inherit\" and post_status!=\"trash\" and post_status!=\"auto-save\" and post_status!=\"auto-draft\"");
	$table_name = $wpdb->prefix . 'wix_keyword_similarity';

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

//ドキュメント作成中におけるキーワード推薦時のidf計算
function wix_idf_creating_document( $id ) {
	global $wpdb, $term_featureObj;

	$document_num = (int) $wpdb->get_var("SELECT COUNT(*) FROM $wpdb->posts WHERE post_status!=\"inherit\" and post_status!=\"trash\" and post_status!=\"auto-save\" and post_status!=\"auto-draft\"");
	$table_name = $wpdb->prefix . 'wix_keyword_similarity';

	foreach ($term_featureObj as $keyword => $obj) {
		//「テーブル内に該当キーワードがあるあないか」、「エントリの挿入か、更新か」でcountが変わる
		$count = 0;
		$tmp = 0;
		$sql = 'SELECT COUNT(*) FROM ' . $table_name . ' WHERE keyword = "' . $keyword . '"';
		if ( $wpdb->get_var($sql) == 0 ) {
			$count = 1;
		} else {
			$tmp = $wpdb->get_var($sql);
			$sql = 'SELECT COUNT(*) FROM ' . $table_name . ' WHERE doc_id = ' . $id . ' AND keyword = "' . $keyword . '"';
			if ( $wpdb->get_var($sql) == 0 ) {
				$tmp++;
			}
			$count = $tmp;
		}

		//logの定数を10にするか、e(ただのlog()は底がe)にするか
		$idf = log($document_num / $count);
		// $idf = log10($document_num / $count);

		$obj['idf'] = $idf;
		$term_featureObj[$keyword] = $obj;
	}
}

//textrankの計算
function wix_textrank($wordsArray) {
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

		/*
			$num_terms: 上位N件に絞るボーダー変数
		*/
		// $num_terms = 20;
		// if ($num_terms < 1) $num_terms = 1;
		// $rank = array_slice($rank, 0, $num_terms);
		// foreach ($rank as $vertex => $score) {
		//	 $terms .= ' ' . $vertex;
		// }

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

//Cosine Similarityの計算
function wix_cosSimilarity($doc_id) {
	global $wpdb, $doc_simObj;

	$wix_keyword_similarity = $wpdb->prefix . 'wix_keyword_similarity';

	//計算対象ドキュメント群の単語特徴量など
	$sql = 'SELECT ID, keyword, tf_idf AS tfidf, bm25 
			FROM ' . $wpdb->posts . ', ' . $wix_keyword_similarity . 
			' WHERE post_status!="inherit" AND post_status!="trash" AND post_status!="auto-save" AND post_status!="auto-draft" 
				AND ID = doc_id AND ID != ' . $doc_id . 
				' GROUP BY ID, keyword, tf_idf, bm25' . 
				' ORDER BY ID ASC';
	$docInfoObj = $wpdb->get_results($sql);


	if ( !empty($docInfoObj) ) {
		//doc_idの単語特徴量Objectを配列に整形
		$sql = 'SELECT keyword, tf_idf AS tfidf, bm25 
				FROM ' . $wpdb->posts . ', ' . $wix_keyword_similarity . 
				' WHERE ID = doc_id AND ID = ' . $doc_id;
		$subjectDocInfoObj = $wpdb->get_results($sql);

		$subjectDocInfoArray = array();
		$bunbo2Array = array();

		foreach ($subjectDocInfoObj as $index => $value) {
			$subjectDocInfoArray[$value->keyword] = array(
															'tfidf' => $value->tfidf,
															'bm25' => $value->bm25,
															);

			if ( empty($bunbo2Array) ) {
				$bunbo2Array['tfidf'] = $value->tfidf * $value->tfidf;
				$bunbo2Array['bm25'] = $value->bm25 * $value->bm25;
			} else {
				foreach ($bunbo2Array as $key => $val) {
					if ( $key == 'tfidf' ) 
						$bunbo2Array[$key] += $value->tfidf * $value->tfidf;
					else if ( $key == 'bm25' )
						$bunbo2Array[$key] += $value->bm25 * $value->bm25;
				}
			}
		}

		$tmpId = '';
		$bunsiArray = array(); $bunbo1Array = array(); $cos_similarityArray = array();
		foreach ($docInfoObj as $key => $value) {
			$doc_id2 = $value->ID;
			$keyword = $value->keyword;
			$tfidf = $value->tfidf;
			$bm25 = $value->bm25;
			
			if ( $tmpId == '' ) $tmpId = $doc_id2;

			if ( $tmpId != $doc_id2 ) {
				foreach ($bunbo1Array as $method => $value) {
					if ( $value != 0 ) {
						if ( $bunsiArray[$method] == 0 ) 
							$cos_similarityArray[$method] = 0;
						else
							$cos_similarityArray[$method] = $bunsiArray[$method] / (sqrt($value) * sqrt($bunbo2Array[$method]));
					} else { 
						$cos_similarityArray[$method] = 0;
					}
				}
				
				if ( array_key_exists($tmpId, $doc_simObj) ) {
					$tmpArray = $doc_simObj[$tmpId];
					$tmpArray['cos_similarity_tfidf'] = $cos_similarityArray['tfidf'];
					$tmpArray['cos_similarity_bm25'] = $cos_similarityArray['bm25'];
					$doc_simObj[$tmpId] = $tmpArray;
				} else {
					$doc_simObj[$tmpId] = array(
											'cos_similarity_tfidf' => $cos_similarityArray['tfidf'],
											'cos_similarity_bm25' => $cos_similarityArray['bm25'],
										);
				}

				$tmpId = $doc_id2; 
				$bunsiArray = array(); $bunbo1Array = array(); $cos_similarityArray = array();
			}

			if ( array_key_exists($keyword, $subjectDocInfoArray) ) {
				if ( empty($bunsiArray) ){
					$bunsiArray['tfidf'] = $tfidf * $subjectDocInfoArray[$keyword]['tfidf'];
					$bunsiArray['bm25'] = $bm25 * $subjectDocInfoArray[$keyword]['bm25'];
				} else {
					$bunsiArray['tfidf'] += $tfidf * $subjectDocInfoArray[$keyword]['tfidf'];
					$bunsiArray['bm25'] += $bm25 * $subjectDocInfoArray[$keyword]['bm25'];
				}
			} else {
				if ( empty($bunsiArray) ){
					$bunsiArray['tfidf'] = 0;
					$bunsiArray['bm25'] = 0;
				} else {
					$bunsiArray['tfidf'] += 0;
					$bunsiArray['bm25'] += 0;
				}
			}
			if ( empty($bunbo1Array) ) {
				$bunbo1Array['tfidf'] = $tfidf * $tfidf;
				$bunbo1Array['bm25'] = $bm25 * $bm25;
			} else {
				$bunbo1Array['tfidf'] += $tfidf * $tfidf;
				$bunbo1Array['bm25'] += $bm25 * $bm25;
			}
		}
		//最後のドキュメント分
		foreach ($bunbo1Array as $method => $value) {
			if ( $value != 0 )
				if ( $bunsiArray[$method] == 0 ) 
					$cos_similarityArray[$method] = 0;
				else
					$cos_similarityArray[$method] = $bunsiArray[$method] / (sqrt($value) * sqrt($bunbo2Array[$method]));
			else 
				$cos_similarityArray[$method] = 0;
		}
		
		if ( array_key_exists($tmpId, $doc_simObj) ) {
			$tmpArray = $doc_simObj[$tmpId];
			$tmpArray['cos_similarity_tfidf'] = $cos_similarityArray['tfidf'];
			$tmpArray['cos_similarity_bm25'] = $cos_similarityArray['bm25'];
			$doc_simObj[$tmpId] = $tmpArray;
		} else {
			$doc_simObj[$tmpId] = array(
									'cos_similarity_tfidf' => $cos_similarityArray['tfidf'],
									'cos_similarity_bm25' => $cos_similarityArray['bm25'],
								);
		}
	}
}

//Jaccard類似度の計算
function wix_jaccard($doc_id) {
	global $wpdb, $doc_simObj;
	
	$wix_keyword_similarity = $wpdb->prefix . 'wix_keyword_similarity';

	$sql = 'SELECT doc_id, keyword FROM ' . $wix_keyword_similarity . ' WHERE doc_id!=' . $doc_id . ' ORDER BY doc_id ASC';
	$docInfoObj = $wpdb->get_results($sql);

	if ( !empty($docInfoObj) ) {

		$docInfoArray = array();
		foreach ($docInfoObj as $index => $value) {
			$doc_id2 = $value->doc_id;
			$keyword = $value->keyword;

			if ( array_key_exists($doc_id2, $docInfoArray) ) {
				$tmpArray = $docInfoArray[$doc_id2];
				array_push($tmpArray, $keyword);
				$docInfoArray[$doc_id2] = $tmpArray;
			} else {
				$docInfoArray[$doc_id2] = array($keyword);
			}
		}

		$sql = 'SELECT doc_id, keyword FROM ' . $wix_keyword_similarity . ' WHERE doc_id=' . $doc_id;
		$subjectDocInfoObj = $wpdb->get_results($sql);

		$subjectDocInfoArray = array();
		foreach ($subjectDocInfoObj as $index => $value) {
			array_push($subjectDocInfoArray, $value->keyword);
		}

		//Jaccard類似度計算
		foreach ($docInfoArray as $doc_id2 => $valueArray) {
			$intersect = array_intersect($subjectDocInfoArray, $valueArray);

			if ( array_key_exists($doc_id2, $doc_simObj) ) {
				$tmpArray = $doc_simObj[$doc_id2];
				$tmpArray['jaccard'] = count($intersect)/(count($valueArray) + count($subjectDocInfoArray) - count($intersect));
				$doc_simObj[$doc_id2] = $tmpArray;
			} else {
				$doc_simObj[$doc_id2] = array( 'jaccard' => count($intersect)/(count($valueArray) + count($subjectDocInfoArray) - count($intersect)) );
			}
		}

	}

}

//MinHashの計算
function wix_minhash($doc_id) {
	global $wpdb, $doc_simObj;
	$k = 128;

	$wix_keyword_similarity = $wpdb->prefix . 'wix_keyword_similarity';
	$wix_minhash =  $wpdb->prefix . 'wix_minhash';

	//doc_idのハッシュ値をDBに登録
	$minhash1 = regist_hashscore($doc_id);

	$sql = 'SELECT * FROM ' . $wix_minhash . ' WHERE doc_id!=' . $doc_id;
	$docInfoObj = $wpdb->get_results($sql);
	if ( !empty($docInfoObj) ) {
		foreach ($docInfoObj as $key => $value) {
			$doc_id2 = $value->doc_id;
			$minhash2_str = $value->minhash;
			$minhash2 = explode(',', $minhash2_str);
			$count = 0;

			foreach ($minhash1 as $index => $value) {
				if ( $value == $minhash2[$index] ) $count++; 
			}
			
			if ( array_key_exists($doc_id2, $doc_simObj) ) {
				$tmpArray = $doc_simObj[$doc_id2];
				$tmpArray['minhash'] = $count/$k;
				$doc_simObj[$doc_id2] = $tmpArray;
			} else {
				$doc_simObj[$doc_id2] = array('minhash' => $count/$k);
			}
		}
	}
}
//ハッシュ値のDB登録
function regist_hashscore($doc_id) {
	global $wpdb;

	$wix_keyword_similarity = $wpdb->prefix . 'wix_keyword_similarity';
	$wix_minhash =  $wpdb->prefix . 'wix_minhash';

	//doc_idのキーワード配列をDBから持ってくる
	$sql = 'SELECT keyword FROM ' . $wpdb->posts . ', ' . $wix_keyword_similarity . 
			' WHERE ID = doc_id AND ID = ' . $doc_id;
	$subjectDocInfoObj = $wpdb->get_results($sql);

	$subjectDocInfoArray = array();
	foreach ($subjectDocInfoObj as $key => $value) {
		array_push($subjectDocInfoArray, $value->keyword);
	}

	//doc_idのハッシュ値計算
	$hashArray = array();
	if ( get_option('wix_seeds') == false ) {
		$k = 128;
		$seeds = random_number($k);
		add_option( 'wix_seeds', $seeds );
	} else {
		$seeds = get_option('wix_seeds');
	}

	foreach ($seeds as $key => $seed) {
    	array_push($hashArray, calc_minhash($subjectDocInfoArray, $seed));
    }
    //$hashArrayをカンマ区切りの文字列に
    $hashArray_str = implode(',', $hashArray);

    //DBに登録(挿入 or 更新)
    $sql = 'INSERT INTO ' . $wix_minhash . '(doc_id, minhash) VALUES (' . $doc_id . ', "' . $hashArray_str . '") ON DUPLICATE KEY UPDATE minhash="' . $hashArray_str . '"';
    $wpdb->query( $sql );

    return $hashArray;
}
//乱数を用意
function random_number($num) {
	$seeds = array();
	while ( $num > 0 ) {
		array_push($seeds, mt_rand(0, 999999999));
		$num--;
	}

	return $seeds;
}
//minhash値計算関数
function calc_minhash($targets, $seed) {
	/*
		引数: 単語配列, ある乱数
		返り値: 単語配列にハッシュ関数を適用した際の、最小ハッシュ値
	*/
	$hash_values = array();
	foreach ($targets as $key => $str) {
		array_push( $hash_values, murmurhash3($str, $seed) );
	}

	sort( $hash_values );
	return $hash_values[0];
}

//WIXファイルエントリのランキング
// function wix_entry_ranking($doc_id) {
// 	global $wpdb;

// 	$wixfilemeta_posts = $wpdb->prefix . 'wixfilemeta_posts';
// 	$wixfile_targets = $wpdb->prefix . 'wixfile_targets';
// 	$wix_document_similarity = $wpdb->prefix . 'wix_document_similarity';


// 	$sql = 'SELECT * FROM ' . $wix_document_similarity . ' WHERE doc_id=' . $doc_id . ' OR doc_id2=' . $doc_id;
// 	$simObj = $wpdb->get_results($sql);

// 	$simArray = array();
// 	foreach ($simObj as $index => $value) {
// 		$simArray[$value->doc_id2] = array(
// 											'cos_similarity_tfidf' => $value->cos_similarity_tfidf,
// 											'cos_similarity_bm25' => $value->cos_similarity_bm25,
// 											'jaccard' => $value->jaccard,
// 											'minhash' => $value->minhash,
// 										);
// 	}

// 	foreach ($simArray as $doc_id2 => $valueArray) {
// 		// normalize
// 	}
// }











function no_wixfile_entry($array) {
	global $wpdb;

	$distinctKeywordsArray = array();
	$returnValue = array();

	$sql = 'SELECT keyword FROM ' . $wpdb->prefix . 'wixfilemeta';
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
	global $wpdb, $doc_title;

	$returnValue = array();

	$results = $wpdb->get_results("SELECT ID, post_title, post_content FROM $wpdb->posts WHERE post_status = \"publish\" OR post_status = \"draft\"");

	foreach ($array as $key => $word) {
		$tmpArray = array();
		foreach ($results as $value) {
			if ( strpos($value->post_content, $word) ) {
				if ( $doc_title != $value->post_title ) {
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


?>