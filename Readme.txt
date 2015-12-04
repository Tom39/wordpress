/***************************************************************************************/

		PHP関数一覧

/***************************************************************************************/

[wixController.php]
	wix_admin_init()
		//初期化

	wix_admin_settings_scripts()
		//jQuery UI スクリプトの読み込み

	wix_admin_decide_scripts($hook_suffix)
		//wixDecide系スクリプトの読み込み
			$hook_suffix: どこのページであるか

	wix_admin_setting_scripts($hook_suffix)
		//wixDecide系スクリプトの読み込み
			$hook_suffix: どこのページであるか

	wix_eternal_link_scripts()
		//wixExternalLink系スクリプトの読み込み

	dump($filename, $obj)
		//dumpなどをファイル書き込み
			$filename: 作成ファイル名
			$obj: 書き込む対象

	wix_manual_decide_init()
		//設定項目のON/OFF の初期設定(add_option)
		//register_deactivation_hook

	wix_table_create()
		//WIX用テーブルの作成
		//register_deactivation_hook

	wix_uninstall_hook ()
		//アンインストール時の挙動
		//register_deactivation_hook


[wixSetting.php]
	wix_admin_menu()
		//メニュー項目の作成

	wix_admin_settings()
		//設定(初期設定含む) & WIXファイルUI

	created_wixfiles()
		//Library登録済みWIXファイル一覧

	wix_admin_similarity()
		//推薦関係ページUI

	wix_settings_notices()
		//WIX用の更新・エラーメッセージを表示


[wixSetting_core.php]
	wix_settings_core()
		//wix_admin_settings()の設定項目部の実挙動部
		//add_action -> 'admin_init'

	wixfile_settings_core()
		//wix_admin_settings()のWIXファイル部の実挙動部
		//add_action -> 'admin_init'

	wix_decidefile_update_core()
		//wix_detail_settings()のDecideファイル更新部の実挙動部
		//add_action -> 'admin_init'

	wixfilemeta_posts_insert( $array )
		//WIXファイルに挿入が行われた際、wixfilemeta_postsテーブルをアップデート
			//$array: wixfilemeta_postsテーブルの新規キーワード分挿入用Array [doc_id => keyword]
			//wixfilemeta_posts: doc_id, keyword_id（主キーかつ外部キー）

	wix_keyword_appearance_in_doc( $new_status, $old_status, $post )
		//ドキュメントの作成・更新・削除の際、wixfilemeta_postsテーブルをアップデート
		//wixfilemeta_posts_insertと対。こちらは「ドキュメントの更新」時に呼び出し。
		//add_action -> 'transition_post_status'

	wix_correspond_keywords( $body )
		//WIXファイル内の、どのキーワードが「その」ドキュメント上に出現するか

	wix_similarity_entry_recommend()
		//エントリ候補をオーサーに提示
		//Ajax

	feature_words_sort( $array1, $array2, $array3 )
		//"特徴語"を重み付計算に基づいたランキング
			//$array1: TF-IDF, $array2: BM25, $array3: TextRank

	candidate_targets_sort( $similar_documents, $doc_id )
		//ターゲット候補(内部リンク)の、ドキュメント間類似度に基づいたランキング
			//$similar_documents: 候補ターゲット群
			//$doc_id: 計算対象ドキュメントID

	wix_exisitng_entry_presentation()
		//既存該当キーワードを提示(Wix Admin Settingsのタブ3で用いる)
		//Ajax

	wix_setting_decideBody()
		//WIX Detail Settingsにおける手動Decide処理用body
		//Ajax

	wix_existing_decidefile_presentation()
		//最新の該当DecideFile情報を提示
		//Ajax

	wix_decidefile_history()
		//該当ドキュメントのDecide情報履歴を提示
		//Ajax

	wix_setting_createDecidefile()
		//WIX Detail Settingsにおける手動Decide処理用body
		//Ajax

	wix_setting_createDecidefile_inDB($doc_id, $object)
		//Decideファイル更新におけるDB側の更新
			//$object: Decide情報

	wix_disambiguation_recommend()
		//DefaultエントリのUIに関して(Entry Disambiguation)
		//Ajax

	wix_entry_disambiuation_with_docSim($doc_id, $doc_idArray, $keyword_innerlinkArray, $entrysArray)
		//ドキュメント間類似度と、disambiguation用のスコアを用いたEntry Disambiguation処理

	wix_documentSim_for_ranking( $doc_id, $doc_idArray )
		//ドキュメント間の類似度計算
			//$doc_id: 計算対象ドキュメントID
			//$doc_idArray: 被計算対象ドキュメントID

	wix_calc_disambiguation_score($keyword_innerlinkArray, $entrysArray)
		//disambiguation用のスコア計算(内部リンクのみ)
		//閲覧されるページのリンクがどの内部ページに飛ぶかを元に計算
			//$keyword_innerlinkArray: 内部リンクを持つエントリ
			//$entrysArray: 対象ドキュメント内に出現する単語を含むエントリ

	wix_calc_disambiguation_score_hard($keyword_innerlinkArray, $entrysArray)
		//disambiguation用のスコア計算(内部リンクのみ)
		//閲覧されるページのリンクがどの内部ページに飛ぶか、またリンクが貼られるキーワードを含むエントリ数に応じて計算

	wix_calc_disambiguation_score_veryhard($keyword_innerlinkArray, $entrysArray)
		//disambiguation用のスコア計算(内部リンクのみ)
		//閲覧されるページのリンクがどの内部ページに飛ぶか、またリンクが貼られるキーワードを含むエントリ数に応じて、さらに出現キーワード数も元に計算

	wix_entry_disambiuation_with_googleSearch($doc_id, $doc_idArray, $keyword_innerlinkArray, $entrysArray)
		//google検索を用いたDisambiguation処理

	wix_get_contextInfo($keyword, $wordsArray)
		//Context情報の取得
			//$keyword: 対象キーワード
			//$wordsArray:  周辺単語検索用配列

	wix_surrounding_words($subjectWord, $wordsArray)
		//対象単語の周辺N単語を返す

	get_snippet_by_google($word, $and_searchArray)
		//Google検索によるスニペット取得

	wix_contents_option()
		//YahooIDとかGoogle IDが既にDBにあったら返り値へ。


	created_wixfile_info()
		//Library登録済みWIXファイル情報

	wix_similarity_entry_recommend()
		//WIXファイルのエントリ候補をwix_document_similarityテーブルから推薦
		//wix_admin_similarity()で使用


[wixAutocreate.php]
	wix_documnt_length( $new_status, $old_status, $post )
		//ドキュメント長の計算
		//add_action -> 'transition_post_status'

	words_for_entry_disambiguation($wordsArray, $doc_id)
		//Entry Disambiguation用に形態素解析結果を保存(正規化無視)

	wix_similarity_func( $new_status, $old_status, $post )
		//ドキュメントの投稿ステータスが変わったら、類似度計算
		//add_action -> 'transition_post_status'

	wix_keyword_similarity_score_inserts_updates($doc_id)
		//単語特徴量をDBに保存・更新・削除
			//$doc_id: 対象ドキュメントID

	wix_document_similarity_score_inserts_updates($doc_id)
		//ドキュメント間類似度をDBに保存・更新・削除
			//$doc_id: 対象ドキュメントID

	wix_similarity_score_deletes($doc_id, $table)
		//各特徴量・類似度エントリをDBから削除
			//$doc_id: 対象ドキュメントID
			//$table: DB内のどのテーブルに対して行うか

	wix_status_update_idf_update( $new_status, $old_status, $post )
		//ドキュメントの変更に応じてIDF値の更新
		//add_action -> 'transition_post_status'

	wix_word_features_update()
		//単語ベクトル(TF-IDF, BM25)の更新

	wix_tfidf_update()
		//TF-IDF値の更新

	wix_bm25_update()
		//BM25値の更新

	wix_textrank_update()
		//TextRank値の更新

	wix_cosSimilarity_update()
		//cosSimilarityの更新

	wix_words_obj_update()
		//postsテーブルのwords_obj列一括更新

	
[wixSimilarity.php]
	wix_morphological_analysis($content)
		//yahoo形態素解析の実行

	wix_morphological_analysis_mecab($content)
		//Mecab(php-Mecab)を使った形態素解析を実行

	wix_compound_noun_extract($parse)
		//yahoo形態素解析結果から複合名詞の作成

	wix_compound_noun_extract_mecab($parse)
		//Mecabを使って、形態素解析結果から複合名詞の作成

	wix_blank_remove($array)
		//複合名詞を持つ配列から空白要素の削除

	wix_stopwords_remove($array)
		//要素が全部数字などの削除

	array_word_count($array)
		//作成ドキュメントにおける各キーワードの出現回数カウンタ

	wix_tfidf( $words_countArray )
		//TF-IDFの計算

	wix_bm25( $words_countArray, $doc_id )
		//BM25の計算

	wix_tf($array)
		//TF値の計算

	wix_tf_ranking($array, $words_num)
		//Entry Disambiuation用のTFランキング計算

	wix_idf()
		//IDF値の計算

	wix_idf_creating_document( $id )
		//ドキュメント作成中におけるキーワード推薦時のidf計算

	wix_textrank($wordsArray)
		//textrankの計算

	wix_cosSimilarity($doc_id)
		//Cosine Similarityの計算

	wix_jaccard($doc_id)
		//Jaccard類似度の計算

	wix_minhash($doc_id)
		//MinHashの計算

	regist_hashscore($doc_id)
		//ハッシュ値のDB登録

	random_number($num)
		//乱数を用意

	calc_minhash($targets, $seed)
		//minhash値計算

	wix_entry_ranking($doc_id)
		//WIXファイルエントリのランキング
		//これ使ってないはず


[wixDecide.php]
	ajaxURL()
		//Javascript→phpへのAjax通信を可能にするための変数定義
		//add_action -> admin_head-admin.php

	wix_meta_box()
		//WIX用meta box
		//add_action -> add_meta_boxes

	wix_manual_decide_check()
		//ManualDecideするか否かのフラグをjs側へ返す
		//Ajax

	wix_entry_recommendation_creating_document()
		//ドキュメント作成中に行うエントリ推薦
		//Ajax

	wix_new_entry_insert()
		//post.phpからのEntry情報をDBに挿入
		//Ajax

	wix_wixfilemeta_posts_insert()
		//WIXファイルに挿入・更新・削除が行われた時の、「WIXファイル内キーワードが出現するドキュメント」を表すテーブルをupdate
		//Ajax

	wix_new_entry_inserts()
		//エントリの推薦からDBに挿入
		//Ajax

	wix_decide_preview()
		//Manula Decideプレビュー画面のBody
		//Ajax

	keyword_location($body)
		//DB内のWIXファイル + パターンファイル記述済みのWIXファイル を元にドキュメント内でのキーワード位置を求める

	wix_create_decidefile()
		//Decideファイル作成
		//Ajax

	wix_create_decidefile_inDB($doc_id, $object)
		//Decide情報をDBに挿入

	wix_decidefile_check()
		//Decideファイルの存在確認
		//Ajax
		//今使ってない




[newBody.php]
	new_body( $content, $decideFileArray = '', $decideFlag = false ) 
		//WIXによるnewBodyの作成
			//$content: body部
			//$decideFileArray: Decide処理時のbody作成時に必要な配列
			//$decideFlag: Decide処理か否か

	decideFileInfo($filename)
		//Decideファイル情報を連想配列に整形

	wixFileInfo( $body )
		//リクエストHTMLに対して、適用可能なWIXファイルエントリ情報を抽出し、連想配列に整形

[patternMatching.php]
	returnWixID()
		//パターンファイルから、条件一致したWixFileIDを返す

	returnCandidates()
		//リクエストURLとURLパターンのマッチング

	selectCandidates( $array )
		//returnCandidatesで得たパターンのソート

	startsWith( $haystack, $needle )

	endsWith( $haystack, $needle )

	removeSpace( $str )

	splitSpace( $str ) 

	requestURL_part( $option )

	subjectPath()

[murmurhash3.php]
	//minhash値計算に使用するAPI




/***************************************************************************************/

		JavaScript関数一覧

/***************************************************************************************/