<?php
$words_countArray_num = 0;
$similarityObj = array();
/*
* ex) 
  $similarityObj: [keyword:
						[
							'tf' => tf socre,
							'idf' => idf score,
							'tf-idf' => tf-idf score
						]
					]
*/

//yahoo形態素解析
function wix_morphological_analysis($content) {
	$yahooID = 'dj0zaiZpPUlGTmRoTElndjRuVCZzPWNvbnN1bWVyc2VjcmV0Jng9Nzk-';
	//HTMLタグを除去しつつ戻り値をパースする
	$url = "http://jlp.yahooapis.jp/MAService/V1/parse?appid=" . $yahooID .
									 "&results=ma&sentence=" . urlencode(strip_tags($content));
	$returnValue = simplexml_load_file($url);



	return $returnValue;
}

//形態素解析結果から複合名詞の作成
function wix_compound_noun_extract($parse){
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

//作成ドキュメントにおけるキーワード数の出現カウンター
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

function wix_tf($array) {
	global $words_countArray_num, $similarityObj;
	$all_words_len = count($array);

	foreach ($array as $word => $count) {
		$tf = $count / 	$words_countArray_num;
		$similarityObj[$word] = ['tf' => $tf];
	}
}

function wix_idf() {
	global $wpdb, $post, $similarityObj;
	$document_num = (int) $wpdb->get_var("SELECT COUNT(*) FROM $wpdb->posts WHERE post_status!=\"inherit\" and post_status!=\"trash\" and post_status!=\"auto-save\" and post_status!=\"auto-draft\"");
	$table_name = $wpdb->prefix . 'wix_keyword_similarity';

	foreach ($similarityObj as $keyword => $obj) {
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

		$obj['idf'] = $idf;
		$similarityObj[$keyword] = $obj;
	}
}

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