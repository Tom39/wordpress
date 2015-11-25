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
	wix_manual_decide_init()
		//設定項目のON/OFF の初期設定(add_option)
		//register_deactivation_hook

	wix_table_create()
		//WIX用テーブルの作成
		//register_deactivation_hook

	wix_uninstall_hook ()
		//アンインストール時の挙動
		//register_deactivation_hook

	wix_settings_core()
		//wix_admin_settings()の設定項目部の実挙動部
		//add_action -> 'admin_init'

	wixfile_settings_core()
		//wix_admin_settings()のWIXファイル部の実挙動部
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

	created_wixfile_info()
		//Library登録済みWIXファイル情報

	wix_similarity_entry_recommend()
		//WIXファイルのエントリ候補をwix_document_similarityテーブルから推薦
		//wix_admin_similarity()で使用


[wixAutocreate.php]
	wix_documnt_length( $new_status, $old_status, $post )
		//ドキュメント長の計算
		//add_action -> 'transition_post_status'

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

	
[wixSimilarity.php]
	
			


/***************************************************************************************/

		JavaScript関数一覧

/***************************************************************************************/