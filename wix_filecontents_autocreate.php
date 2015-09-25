<?php

// $test_array = array();
// $test_array['tf'] = ['a' => 'aa','b' => 'bb'];
// $test_array['idf'] = ['c' => 'cc', 'd' => 'dd'];
// var_dump($test_array);
// foreach ($test_array as $similarity => $array) {
// 	var_dump('similarityの名前は' . $similarity);
// 	foreach ($array as $key => $val) {
// 		var_dump('keyは' . $key);
// 		var_dump('valは' . $val);
// 	}
// }


//ドキュメントの投稿ステータスが変わったら、類似度計算
add_action( 'transition_post_status', 'similarity_test', 10, 3 );
function similarity_test( $new_status, $old_status, $post ) {
	global $similarityObj;

	//リビジョンに対するエントリを作らないように
	if ( $new_status != 'inherit' ) {
		$parse = wix_morphological_analysis($post->post_content);
		$wordsArray = wix_compound_noun_extract($parse);

		$words_countArray = array_word_count($wordsArray);
		$words_tfArray = wix_tf($words_countArray);
		$words_idfArray = wix_idf($words_countArray);


		//tf-idf計算
		foreach ($similarityObj as $key => $value) {
			$tf_idf = $value['tf'] * $value['idf'];
			$value['tf-idf'] = $tf_idf;
			$similarityObj[$key] = $value;
		}


		wix_keyword_similarity_score_inserts_updates($post->ID);
		// $test = wix_document_similarity_score_inserts_updates($post->ID);
		// var_dump($test);
	}

}

/*固定ページは固定ページのみ、投稿は投稿のみを対象として計算したほうがいい？？（2015/09/25）*/
//TF-IDF値などをDBに保存・更新
function wix_keyword_similarity_score_inserts_updates($doc_id) {
	global $wpdb, $similarityObj;

	$table_name = $wpdb->prefix . 'wix_keyword_similarity';
	$sql = 'SELECT COUNT(*) FROM ' . $table_name . ' WHERE doc_id = ' . $doc_id;
	$entry_check_flag = $wpdb->get_var($sql);

	//INSERT
	if ( $entry_check_flag == 0 ) {
		$insertEntry = '';
		foreach ($similarityObj as $keyword => $array) {

			$tf = $array['tf'];
			$idf = $array['idf'];
			$tf_idf = $array['tf-idf'];

			if ( empty($insertEntry) )
				$insertEntry = '(' . $doc_id . ', "' . $keyword .'", ' . $tf . ', ' . $idf . ', ' . $tf_idf . '), ';
			else
				$insertEntry = $insertEntry . '(' . $doc_id . ', "' . $keyword .'", ' . $tf . ', ' . $idf . ', ' . $tf_idf . '), ';

		}

		if ( !empty($insertEntry) ) {		
			$sql = 'INSERT INTO ' . $table_name . '(doc_id, keyword, tf, idf, tf_idf) VALUES ' . $insertEntry;
			$sql = mb_substr($sql, 0, (mb_strlen($sql)-2));
			$results = $wpdb->query( $sql );
		}

	//UPDATE
	} else {
		$updateEntry = '';
		foreach ($similarityObj as $keyword => $array) {
			$tf = $array['tf'];
			$idf = $array['idf'];
			$tf_idf = $array['tf-idf'];

			$sql = 'UPDATE ' . $table_name . ' SET tf = ' . $tf . ', idf = ' . $idf . ', tf_idf = ' . $tf_idf . ' WHERE doc_id = ' . $doc_id .' AND keyword = "' . $keyword . '"';

			$results = $wpdb->query( $sql );
		}
	}
}

//COS類似度などをDBに保存・更新
function wix_document_similarity_score_inserts_updates($doc_id) {
	global $wpdb, $similarityObj;

	$table_name = $wpdb->prefix . 'wix_document_similarity';
	$sql = 'SELECT COUNT(*) FROM ' . $table_name . ' WHERE doc_id = ' . $doc_id;
	$entry_check_flag = $wpdb->get_var($sql);

	//INSERT
	// if ( $entry_check_flag == 0 ) {

		//計算対象ドキュメント群
		$sql = 'SELECT ID, post_title, post_type FROM ' . $wpdb->prefix . 'posts' . ' WHERE post_status!="inherit" and post_status!="trash" and post_status!="auto-save" and post_status!="auto-draft" and ID!=' . $doc_id . ' order by ID asc';
		$subjectDocuments = $wpdb->get_results($sql);
		//計算
		foreach ($subjectDocuments as $key => $value) {
			$doc_id2 = $value->ID;
			$sql = 'SELECT keyword, tf_idf FROM ' . $wpdb->prefix . 'wix_keyword_similarity' . ' WHERE doc_id=' . $doc_id2;
		}

	// } else {

	// }

	return $subjectDocuments;
}











?>