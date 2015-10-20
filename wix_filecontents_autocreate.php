<?php

//ドキュメントの投稿ステータスが変わったら、類似度計算
add_action( 'transition_post_status', 'wix_similarity_func', 10, 3 );
function wix_similarity_func( $new_status, $old_status, $post ) {
	global $wpdb, $similarityObj;

	//ゴミ箱行きだったらDELTE.次にリビジョンに対するエントリを作らないように.
	if ( $new_status == 'trash' ) {

		wix_similarity_score_deletes($post->ID, 'wix_keyword_similarity');
		wix_similarity_score_deletes($post->ID, 'wix_document_similarity');

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
			wix_tf($words_countArray);
			wix_idf();

			//tf_idf計算
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
		if ( !empty($similarityObj) ) {
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

			$sql = 'INSERT INTO ' . $table_name . '(doc_id, keyword, tf, idf, tf_idf) VALUES ' . $insertEntry;
			$sql = mb_substr($sql, 0, (mb_strlen($sql)-2));
			$wpdb->query( $sql );
		}

	//UPDATE
	} else {
		//新たにDBに加わるキーワードはupdateじゃだめ
		$sql = 'SELECT keyword FROM ' . $table_name . ' WHERE  doc_id = ' . $doc_id;
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

		//最後は"現在"作成したドキュメントに存在しないキーワードをDBから削除
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

		if ( !empty($subjectDocumentList) ) {
			foreach ($subjectDocumentList as $key => $value) {
				$doc_id2 = $value->ID;

				//ここからCosSimilarityの計算
				$sql = 'SELECT keyword, tf_idf FROM ' . $wpdb->prefix . 'wix_keyword_similarity' . ' WHERE doc_id=' . $doc_id2;
				$subjectDocument_info = $wpdb->get_results($sql);

				//行列値計算
				$bunsi = 0;
				$bunbo1 = 0;
				$bunbo2 = 0;
				foreach ($subjectDocument_info as $key => $array) {
					$subjectDocument_keyword = $array->keyword;
					$subjectDocument_tf_idf = $array->tf_idf;

					if ( array_key_exists($subjectDocument_keyword, $similarityObj) ) {
						$bunsi = $bunsi + $subjectDocument_tf_idf * $similarityObj[$array->keyword]['tf_idf'];
					}
					$bunbo1 = $bunbo1 + $subjectDocument_tf_idf * $subjectDocument_tf_idf;
				}
				foreach ($similarityObj as $keyword => $value) {
					$bunbo2 = $bunbo2 + $value['tf_idf'] * $value['tf_idf'];
				}

				//こうしないとWarningが出ちゃう
				if ( $bunbo1 != 0 && $bunbo2 != 0 ) 
					$cos_similarity = $bunsi / (sqrt($bunbo1) * sqrt($bunbo2));
				else 
					$cos_similarity = 0;


				//(doc_id,doc_id2) = (○, △)順不同のエントリが存在するなら、挿入じゃなくて更新	
				$sql = 'SELECT COUNT(*) FROM ' . $table_name . ' WHERE (doc_id = ' . $doc_id . ' and doc_id2 = ' . $doc_id2 . ') OR (doc_id = ' . $doc_id2 . ' and doc_id2 = ' . $doc_id . ')';
				$entry_check_flag = $wpdb->get_var($sql);
				if ( $entry_check_flag == 0 ) {
					$insertEntry = '(' . $doc_id . ', ' . $doc_id2 .', ' . $cos_similarity . ')';
					$sql = 'INSERT INTO ' . $table_name . '(doc_id, doc_id2, cos_similarity) VALUES ' . $insertEntry;
					$wpdb->query( $sql );
				} else {
					$sql = 'UPDATE ' . $table_name . ' SET cos_similarity = ' . $cos_similarity . ' WHERE (doc_id = ' . $doc_id . ' and doc_id2 = ' . $doc_id2 . ') OR (doc_id = ' . $doc_id2 . ' and doc_id2 = ' . $doc_id . ')';
					$wpdb->query( $sql );
				}

			}
		}

	//UPDATE
	} else {

		$sql = 'SELECT doc_id, doc_id2 FROM ' . $table_name . ' WHERE  doc_id = ' . $doc_id . ' OR doc_id2 = ' . $doc_id;
		$existing_documentList = $wpdb->get_results($sql);

		foreach ($existing_documentList as $key => $value) {
			if ( (int)$value->doc_id == $doc_id )
				$doc_id2 = $value->doc_id2;
			else
				$doc_id2 = $value->doc_id;

			$sql = 'SELECT keyword, tf_idf, tf FROM ' . $wpdb->prefix . 'wix_keyword_similarity' . ' WHERE doc_id=' . $doc_id2;
			$subjectDocument_info = $wpdb->get_results($sql);

			//行列値計算
			$bunsi = 0;
			$bunbo1 = 0;
			$bunbo2 = 0;
			foreach ($subjectDocument_info as $key => $array) {
				$subjectDocument_keyword = $array->keyword;
				$subjectDocument_tf_idf = $array->tf_idf;

				if ( array_key_exists($subjectDocument_keyword, $similarityObj) ) {
					$bunsi = $bunsi + $subjectDocument_tf_idf * $similarityObj[$array->keyword]['tf_idf'];
				}
				$bunbo1 = $bunbo1 + $subjectDocument_tf_idf * $subjectDocument_tf_idf;
			}
			foreach ($similarityObj as $keyword => $value) {
				$bunbo2 = $bunbo2 + $value['tf_idf'] * $value['tf_idf'];
			}

			if ( $bunsi != 0 ) {
				$cos_similarity = $bunsi / (sqrt($bunbo1) * sqrt($bunbo2));

				//更新
				$sql = 'UPDATE ' . $table_name . ' SET cos_similarity = ' . $cos_similarity . ' WHERE (doc_id = ' . $doc_id . ' and doc_id2 = ' . $doc_id2 . ') OR (doc_id = ' . $doc_id2 . ' and doc_id2 = ' . $doc_id . ')';
				$wpdb->query( $sql );
			}

		}

	}

}

//各類似度エントリをDBから削除
function wix_similarity_score_deletes($doc_id, $table) {
	global $wpdb;

	if ( $table == 'wix_keyword_similarity' ) {
		$sql = 'DELETE FROM ' . $wpdb->prefix . $table . ' WHERE doc_id = ' . $doc_id;
	} else if ( $table == 'wix_document_similarity' ) {
		$sql = 'DELETE FROM ' . $wpdb->prefix . $table . ' WHERE doc_id = ' . $doc_id . ' OR doc_id2 = ' . $doc_id;
	}
	$wpdb->query( $sql );
}









?>