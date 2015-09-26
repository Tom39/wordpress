<?php

//ドキュメントの投稿ステータスが変わったら、類似度計算
add_action( 'transition_post_status', 'similarity_test', 10, 3 );
function similarity_test( $new_status, $old_status, $post ) {
	global $similarityObj;

	//リビジョンに対するエントリを作らないように
	if ( $new_status != 'inherit' ) {
		$parse = wix_morphological_analysis($post->post_content);
		$wordsArray = wix_compound_noun_extract($parse);
		$words_countArray = array_word_count($wordsArray);
		wix_tf($words_countArray);
		wix_idf();

		//tf-idf計算
		foreach ($similarityObj as $key => $value) {
			$tf_idf = $value['tf'] * $value['idf'];
			$value['tf_idf'] = $tf_idf;
			$similarityObj[$key] = $value;
		}

		//DBに挿入・更新
		wix_keyword_similarity_score_inserts_updates($post->ID);
		wix_document_similarity_score_inserts_updates($post->ID);
		
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
			$tf_idf = $array['tf_idf'];

			if ( empty($insertEntry) )
				$insertEntry = '(' . $doc_id . ', "' . $keyword .'", ' . $tf . ', ' . $idf . ', ' . $tf_idf . '), ';
			else
				$insertEntry = $insertEntry . '(' . $doc_id . ', "' . $keyword .'", ' . $tf . ', ' . $idf . ', ' . $tf_idf . '), ';

		}

		if ( !empty($insertEntry) ) {		
			$sql = 'INSERT INTO ' . $table_name . '(doc_id, keyword, tf, idf, tf_idf) VALUES ' . $insertEntry;
			$sql = mb_substr($sql, 0, (mb_strlen($sql)-2));
			$wpdb->query( $sql );
		}

	//UPDATE
	} else {

		//新たにDBに加わるキーワードはupdateじゃだめ
		$sql = 'SELECT * FROM ' . $table_name . ' WHERE  doc_id = ' . $doc_id;
		$existing_keywordObj = $wpdb->get_results($sql);
		//既にDBに存在するキーワード一覧を取得
		$existing_keywordList = array();
		foreach ($existing_keywordObj as $key => $value) {
			$existing_keywordList[$value->keyword] = '';
		}

		foreach ($similarityObj as $keyword => $array) {
			$tf = $array['tf'];
			$idf = $array['idf'];
			$tf_idf = $array['tf_idf'];

			if ( array_key_exists($keyword, $existing_keywordList) ) {
				$sql = 'UPDATE ' . $table_name . ' SET tf = ' . $tf . ', idf = ' . $idf . ', tf_idf = ' . $tf_idf . ' WHERE doc_id = ' . $doc_id .' AND keyword = "' . $keyword . '"';
				unset($existing_keywordList[$keyword]);
			} else {
				$sql = 'INSERT INTO ' . $table_name . '(doc_id, keyword, tf, idf, tf_idf) VALUES (' . $doc_id . ', "' . $keyword .'", ' . $tf . ', ' . $idf . ', ' . $tf_idf . ')';
			}

			$wpdb->query( $sql );
		}

		//最後は現在ドキュメントに存在しないキーワードをDBから削除
		foreach ($existing_keywordList as $keyword => $value) {
			$sql = 'DELETE FROM ' . $table_name . ' WHERE doc_id = ' . $doc_id . ' and keyword = "' . $keyword . '"';
			$wpdb->query( $sql );
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
	if ( $entry_check_flag == 0 ) {

		//計算対象ドキュメント群の取得(つまりdoc_id2になりうるもの)
		$sql = 'SELECT ID, post_title, post_type FROM ' . $wpdb->posts . ' WHERE post_status!="inherit" and post_status!="trash" and post_status!="auto-save" and post_status!="auto-draft" and ID!=' . $doc_id . ' order by ID asc';
		$subjectDocumentList = $wpdb->get_results($sql);
		
		foreach ($subjectDocumentList as $key => $value) {
			$doc_id2 = $value->ID;
			$sql = 'SELECT keyword, tf_idf FROM ' . $wpdb->prefix . 'wix_keyword_similarity' . ' WHERE doc_id=' . $doc_id2;
			$subjectDocument_info = $wpdb->get_results($sql);

			$tmpArray = array();

			//行列値計算
			foreach ($subjectDocument_info as $key => $array) {
				$subjectDocument_keyword = $array->keyword;
				$subjectDocument_tf_idf = $array->tf_idf;

				if ( array_key_exists($subjectDocument_keyword, $similarityObj) ) {
					array_push($tmpArray, $subjectDocument_tf_idf*$similarityObj[$array->keyword]['tf_idf']);
				}
			}

			$cos_similarity = 0;
			foreach ($tmpArray as $key => $value) {
				$cos_similarity = $cos_similarity + $value;
			}


			//(doc_id,doc_id2) = (○, △)順不同のエントリが存在するなら、挿入じゃなくて更新	
			$sql = 'SELECT COUNT(*) FROM ' . $table_name . ' WHERE (doc_id = ' . $doc_id . ' and doc_id2 = ' . $doc_id2 . ') OR (doc_id = ' . $doc_id2 . ' and doc_id2 = ' . $doc_id . ')';
			$entry_check_flag = $wpdb->get_var($sql);
			if ( $entry_check_flag == 0 ) {
				$insertEntry = '(' . $doc_id . ', ' . $doc_id2 .', ' . $cos_similarity . ')';
				$sql = 'INSERT INTO ' . $table_name . '(doc_id, doc_id2, cos_similarity) VALUES ' . $insertEntry;
				$wpdb->query( $sql );
				
				// if ( empty($insertEntry) )
					// $insertEntry = '(' . $doc_id . ', ' . $doc_id2 .', ' . $cos_similarity . '), ';
				// else
				// 	$insertEntry = $insertEntry . '(' . $doc_id . ', ' . $doc_id2 .', ' . $cos_similarity . '), ';

				// if ( !empty($insertEntry) ) {		
				// 	$sql = 'INSERT INTO ' . $table_name . '(doc_id, doc_id2, cos_similarity) VALUES ' . $insertEntry;
				// 	$sql = mb_substr($sql, 0, (mb_strlen($sql)-2));
				// 	$wpdb->query( $sql );
				// }

			} else {
				$sql = 'UPDATE ' . $table_name . ' SET cos_similarity = ' . $cos_similarity . ' WHERE (doc_id = ' . $doc_id . ' and doc_id2 = ' . $doc_id2 . ') OR (doc_id = ' . $doc_id2 . ' and doc_id2 = ' . $doc_id;
				$wpdb->query( $sql );
			}

		}

		//副問い合わせを使用したクエリ
		// $sql = 'SELECT doc_id, keyword, tf_idf FROM ' . 
		// 		$wpdb->prefix . 'wix_keyword_similarity WHERE tf_idf!=0 and doc_id IN (SELECT ID FROM ' . 
		// 			$wpdb->posts . ' WHERE ID!=' . $doc_id . 
		// 			' and post_status!="inherit" and post_status!="trash" and post_status!="auto-save" and post_status!="auto-draft")';
		// $subjectDocumentList = $wpdb->get_results($sql);

		// $tmpArray = array();
		// foreach ($subjectDocumentList as $key => $value) {
		// 	if ( array_key_exists($value->keyword, $similarityObj) ) {
		// 		array_push($tmpArray, $value->doc_id, $value->keyword);
		// 	}
		// }

	//UPDATE
	} else {

		$sql = 'SELECT * FROM ' . $table_name . ' WHERE  doc_id = ' . $doc_id;
		$existing_keywordObj = $wpdb->get_results($sql);
		//既にDBに存在するキーワード一覧を取得
		$existing_keywordList = array();
		foreach ($existing_keywordObj as $key => $value) {
			$existing_keywordList[$value->keyword] = '';
		}

		foreach ($similarityObj as $keyword => $array) {
			$tf = $array['tf'];
			$idf = $array['idf'];
			$tf_idf = $array['tf_idf'];

			if ( array_key_exists($keyword, $existing_keywordList) ) {
				$sql = 'UPDATE ' . $table_name . ' SET tf = ' . $tf . ', idf = ' . $idf . ', tf_idf = ' . $tf_idf . ' WHERE doc_id = ' . $doc_id .' AND keyword = "' . $keyword . '"';
				unset($existing_keywordList[$keyword]);
			} else {
				$sql = 'INSERT INTO ' . $table_name . '(doc_id, keyword, tf, idf, tf_idf) VALUES (' . $doc_id . ', "' . $keyword .'", ' . $tf . ', ' . $idf . ', ' . $tf_idf . ')';
			}

			$wpdb->query( $sql );
		}

		//最後は現在ドキュメントに存在しないキーワードをDBから削除
		foreach ($existing_keywordList as $keyword => $value) {
			$sql = 'DELETE FROM ' . $table_name . ' WHERE doc_id = ' . $doc_id . ' and keyword = "' . $keyword . '"';
			$wpdb->query( $sql );
		}


	}

}











?>