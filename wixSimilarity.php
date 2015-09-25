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

//キーワード数の出現カウンター
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
	/*
	* $returnValue: [keyword => tf]
	*/
	// global $words_countArray_num, $similarityObj;
	// $returnValue = array();
	// $all_words_len = count($array);

	// foreach ($array as $word => $count) {
	// 	$tf = $count / 	$words_countArray_num;
	// 	$returnValue[$word] = $tf;
	// 	$similarityObj[$word] = ['tf' => $tf];
	// }

	// return $returnValue;


	global $words_countArray_num, $similarityObj;
	$all_words_len = count($array);

	foreach ($array as $word => $count) {
		$tf = $count / 	$words_countArray_num;
		$similarityObj[$word] = ['tf' => $tf];
	}

}

function wix_idf() {
	// global $wpdb, $similarityObj;
	// $returnValue = array();

	// $document_num = $wpdb->get_var("SELECT COUNT(*) FROM $wpdb->posts WHERE post_status = \"publish\" OR post_status = \"draft\"");
	// $results = $wpdb->get_results("SELECT post_title, post_content FROM $wpdb->posts WHERE post_status = \"publish\" OR post_status = \"draft\"");
	
	// $document_num = $wpdb->get_var("SELECT COUNT(*) FROM $wpdb->posts WHERE post_status!=\"inherit\" and post_status!=\"trash\" and post_status!=\"auto-save\" and post_status!=\"auto-draft\"");
	// $results = $wpdb->get_results("SELECT post_title, post_content FROM $wpdb->posts WHERE post_status!=\"inherit\" and post_status!=\"trash\" and post_status!=\"auto-save\" and post_status!=\"auto-draft\"");

	// foreach ($array as $word => $count) {
	// 	//countはこの関数に持ってきてるけど、いらないから=0しちゃってるっぽい
	// 	$count = 0;
	// 	foreach ($results as $value) {
	// 		if ( strpos($value->post_content, $word) )
	// 			$count++;
	// 	}

	// 	//$count = 0だとInfinityになるから0にしてる
	// 	if ( $count != 0 ) {
	// 		$idf = log($document_num / $count);
	// 	} else {
	// 		$idf = 0;
	// 	}

	// 	$returnValue[$word] = $idf;


	// 	$valueObj = $similarityObj[$word];
	// 	$valueObj['idf'] = $idf;
	// 	$similarityObj[$word] = $valueObj;
	// }


	global $wpdb, $similarityObj;
	$document_num = (int) $wpdb->get_var("SELECT COUNT(*) FROM $wpdb->posts WHERE post_status!=\"inherit\" and post_status!=\"trash\" and post_status!=\"auto-save\" and post_status!=\"auto-draft\"");

	foreach ($similarityObj as $keyword => $obj) {
		$sql = 'SELECT COUNT(*) FROM ' . $wpdb->prefix . 'wix_keyword_similarity' . ' WHERE keyword = "' . $keyword . '"';
		$count = (int) $wpdb->get_var($sql);
		if ( $count != 0 ) {
			//logの定数を10にするか、e(ただのlog()は底がe)にするか
			$idf = log($document_num / $count);
			// $idf = log10($document_num / $count);
		} else {
			$idf = 0;
		}

		$obj['idf'] = $idf;
		$similarityObj[$keyword] = $obj;
	}


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