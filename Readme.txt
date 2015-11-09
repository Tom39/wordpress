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

	wix_table_create()
		//WIX用テーブルの作成

	wix_uninstall_hook ()
		//アンインストール時の挙動

	wix_settings_core()
		//wix_admin_settings()の設定項目部の実挙動部

	wixfile_settings_core()
		//wix_admin_settings()のWIXファイル部の実挙動部

	wixfilemeta_posts_insert( $array )
		//WIXファイルに挿入・更新・削除が行われた際、wixfilemeta_postsテーブルをアップデート
			//$array: wixfilemeta_postsテーブルの新規キーワード分挿入用Array [doc_id => keyword]
			//wixfilemeta_posts: doc_id, keyword_id（主キーかつ外部キー）

	wix_keyword_appearance_in_doc( $new_status, $old_status, $post )
		//ドキュメントの作成・更新・削除の際、wixfilemeta_postsテーブルをアップデート
		//wixfilemeta_posts_insertと対。こちらは「ドキュメントの更新」時に呼び出し。

	wix_correspond_keywords( $body )
		//WIXファイル内の、どのキーワードが「その」ドキュメント上に出現するか

	created_wixfile_info()
		//Library登録済みWIXファイル情報

	wix_similarity_entry_recommend()
		//WIXファイルのエントリ候補をwix_document_similarityテーブルから推薦
		//wix_admin_similarity()で使用


[wixAutocreate.php]




/***************************************************************************************/

		JavaScript関数一覧

/***************************************************************************************/