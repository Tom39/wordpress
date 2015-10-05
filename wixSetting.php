<?php

require_once( dirname( __FILE__ ) . '/patternMatching.php' );
//登録済みWIXファイル
$wids_filenames = array();
$wixfile_table_version = 0.1;

//--------------------------------------------------------------------------
//
//  プラグイン有効の際に行うオプションの追加
//
//--------------------------------------------------------------------------
register_activation_hook( __FILE__, 'wix_manual_decide_init' );
function wix_manual_decide_init() {
	update_option( 'manual_decideFlag', 'true' );
}

//--------------------------------------------------------------------------
//
//  プラグイン有効の際に行うオプションの追加
//
//--------------------------------------------------------------------------
register_activation_hook( __FILE__, 'wix_table_create' );
function wix_table_create() {
	global $wpdb;
	require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

	$table_name = $wpdb->prefix . 'wix_keyword_similarity';
	$is_db_exists = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table_name));

	if ( $is_db_exists == $table_name ) return;
	$sql = "CREATE TABLE " . $table_name . " (
	         doc_id bigint(20) UNSIGNED,
		     keyword tinytext NOT NULL,
		     tf float NOT NULL,
		     idf float NOT NULL,
		     tf_idf float NOT NULL,
		     UNIQUE(doc_id,keyword(20)),
		     FOREIGN KEY (doc_id) REFERENCES " . $wpdb->prefix . 'posts' . "(ID)
		     ON UPDATE CASCADE ON DELETE CASCADE
	        );";

	require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
	dbDelta($sql);


	$table_name = $wpdb->prefix . 'wix_document_similarity';
	$is_db_exists = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table_name));

	if ( $is_db_exists == $table_name ) return;
	$sql = "CREATE TABLE " . $table_name . " (
	         doc_id bigint(20) UNSIGNED,
		     doc_id2 bigint(20) UNSIGNED,
		     cos_similarity float NOT NULL,
		     UNIQUE(doc_id,doc_id2),
		     FOREIGN KEY (doc_id) REFERENCES " . $wpdb->prefix . 'posts' . "(ID)
		      ON UPDATE CASCADE ON DELETE CASCADE,
		     FOREIGN KEY (doc_id2) REFERENCES " . $wpdb->prefix . 'posts' . "(ID)
		      ON UPDATE CASCADE ON DELETE CASCADE
	        );";
	dbDelta($sql);


	$table_name = $wpdb->prefix . 'wixfilemeta';
	$is_db_exists = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table_name));

	if ( $is_db_exists == $table_name ) return;
	$sql = "CREATE TABLE " . $table_name . " (
			id bigint(20) NOT NULL, 
			keyword tinytext NOT NULL, 
			target_num mediumint(9) NOT NULL, 
			doc_num mediumint(9) NOT NULL, 
			UNIQUE KEY id (id)
			);";
	dbDelta($sql);


	$table_name = $wpdb->prefix . 'wixfile_targets';
	$is_db_exists = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table_name));

	if ( $is_db_exists == $table_name ) return;
	$sql = "CREATE TABLE " . $table_name . " (
			keyword_id bigint(20) NOT NULL, 
			target tinytext NOT NULL, 
			UNIQUE(keyword_id, target(255)), 
			FOREIGN KEY (keyword_id) REFERENCES wp_wixfilemeta(id) ON UPDATE CASCADE ON DELETE CASCADE
			);";
	dbDelta($sql);


	$table_name = $wpdb->prefix . 'posts';
	if ( $is_db_exists == $table_name ) return;
	$sql = "ALTER TABLE " . $table_name . " ADD COLUMN doc_length bigint DEFAULT 0 NOT NULL;";
	dbDelta($sql);
}

//--------------------------------------------------------------------------
//
//  プラグイン削除の際に行うオプションの削除
//
//--------------------------------------------------------------------------
register_deactivation_hook(__FILE__, 'wix_uninstall_hook');
function wix_uninstall_hook () {
    delete_option('manual_decideFlag');
}




add_action( 'admin_menu', 'wix_admin_menu' );

//メニュー画面の作成
function wix_admin_menu() {

	add_menu_page(
		__('WIX Admin Settings', 'wix-settings'),
		__('WIX Admin Settings', 'wix-settings'),
		'administrator',
		'wix-admin-settings',
		'wix_admin_settings' 
	);

	add_submenu_page(
		'wix-admin-settings',
		__('WIX Similarity', 'wix-similarity'),
		__('WIX Similarity', 'wix-similarity'),
		'administrator',
		'wix-admin-similarity',
		'wix_admin_similarity'
	);

	add_action( 'admin_enqueue_scripts', 'wix_admin_settings_scripts' );
	
	add_action('admin_head-toplevel_page_wix-admin-settings', 'created_wixfile_info');

}

//管理画面でのWIX設定ページ
function wix_admin_settings(){
?>
<div class="wrap">
<?php
	global $wids_filenames;

	if ( file_exists( PatternFile ) && is_readable( PatternFile ) ) {
		global $pm;
		$patternFile = $pm -> returnCandidates();
		$hostName = $pm -> requestURL_part( PHP_URL_HOST );
?>
	<?php echo '<h2>' . __( 'WIX Settings', 'wix_settings' ) . '</h2>'; ?>

	<div>
		<form id="wix_settings_form" method="post" action="">
			<input type="submit" name="wix_settings" 
				value= "<?php echo esc_attr( __( 'WIX Settings', 'wix_settings' ) ); ?>" 
				class="button button-primary button-large" >

			<?php wp_nonce_field( 'my-nonce-key', 'nonce_settings' ); ?>
			<p>
				サーバホスト名: 
				<input type="text" name="hostName" value="<?php echo get_option('wix_host_name'); ?>" readonly="readonly">
			</p>
			<p>
				オーサ名: 
				<input type="text" name="authorName" value="<?php echo get_option('wix_author_name'); ?>" readonly="readonly">
			</p>

			<p>
				URLパターン : WIXファイル名 
					<input type="button" id="add_patternFile" 
					value= "<?php echo esc_attr( __( 'Form Add', 'wix_patternFile_adding' ) ); ?>" 
					class="button button-primary button-large" >
			</p>	

			<ol id="pattern_filename">
<?php
		$roop = 0;
		foreach ( $patternFile as $key => $value ) {
			if ( strpos( $value, '-only' ) !== false ) $value = str_replace( '-', ':', $value );
			else $value = str_replace( '-', ',', $value );

			foreach ($wids_filenames as $wid => $filename) {
				if ( $wid == $value ) {
					echo '<li>';
					echo '<input type="text" name="pattern[' . $roop . ']" value=' . esc_attr( $key ) . '> ';
					echo '<input type="text" name="filename[' . $roop . ']" value=' . esc_attr( $filename ) . '> ';
					//フォームが１個なら削除ボタンは生成しない
					if ( count($patternFile) != 1 ) {
					echo '<input type="button" value="' . 
							esc_attr( __( 'Delete', 'wix_patternFile_adding' ) ) . 
							'" class="button button-primary button-large">';
					}
					echo '</li>';
				}	
			}
			
			$roop++;
		}
?>		
			</ol>
		</form>
	</div>

<?php } else { ?> <!-- PatternFileがなければ -->
	<?php echo '<h2>' . __( 'WIX Init Settings', 'wix_init_settings' ) . '</h2>'; ?>

	<div>
		<form id="wix_settings_form" method="post" action="">
			<input type="submit" name="wix_init_settings" 
				value= "<?php echo esc_attr( __( 'WIX Initial Settings', 'wix_initial_settings' ) ); ?>" 
				class="button button-primary button-large" >

			<?php 
				wp_nonce_field( 'my-nonce-key', 'nonce_init_settings' ); 
				global $pm;
				$hostName = $pm -> requestURL_part( PHP_URL_HOST );
			?>

			<p>
				サーバホスト名: 
				<input type="text" name="hostName" placeholder="<?php echo $hostName ?>">
			</p>
			<p>
				オーサ名: 
				<input type="text" name="authorName" value="">
			</p>


			<?php echo '<h3>' . __( 'WIX PatternFile Options', 'wix_patternfile_options' ) . '</h3>'; ?>	
			<p>
				URLパターン : WIXファイル名 
					<input type="button" id="add_patternFile" 
					value= "<?php echo esc_attr( __( 'Form Add', 'wix_patternFile_adding' ) ); ?>" 
					class="button button-primary button-large" >

				<ol id="pattern_filename">
					<li>
						<input type="text" name="pattern[0]" placeholder="/test.html">
						<input type="text" name="filename[0]" placeholder="Wikipedia">
					</li>
				</ol>
			</p>
		</form>
	</div>

<?php } ?>

<?php created_wixfiles(); ?>

<?php decide_management(); ?>

<?php wix_admin_wixfile_settings(); ?>

</div>
<?php
}


function wix_admin_wixfile_settings() {
?>
<div class="wrap">
<?php 
	global $wpdb;
	echo '<h2>' . __( 'WIX File Settings', 'wixfile_settings' ) . '</h2>'; 
?>
	<div>
		<form id="wixfile_settings_form" method="post" action="">

			<?php wp_nonce_field( 'my-nonce-key', 'nonce_wixfile_settings' ); ?>

			<table id="wixfile_contents">
				<tr>
					<th>Keyword</th>
					<th>Targets</th>
					<!-- <th>Any Settings</th> -->
				</tr>
				 
			<?php 
				$table_name = $wpdb->prefix . 'wixfilemeta';
				$sql = 'SELECT COUNT(*) FROM ' . $table_name;
				if ( $wpdb->get_var($sql) == 0 ) {
			?>
				<tr>
					<th>
						<input type="text" placeholder="<?php echo '慶應義塾大学' ?>">
					</th>
					<th>
						<input type="text" placeholder="<?php echo esc_html('http://www.keio.ac.jp') ?>">
					</th>
				</tr>
			<?php 
				} else { 
					$sql = 'SELECT keyword, target FROM wp_wixfilemeta wm, wp_wixfile_targets wt WHERE wm.id = wt.keyword_id';
					$results = $wpdb->get_results($sql);
					$count = 0;
					foreach ($results as $value) {
						// $keyword = '<input type="text" name="keywords[' . $count . ']" value="' . esc_html($value->keyword) . '">';
						// $target = '<input type="text" name="targets[' . $count . ']" value="' . esc_html($value->target) . '">';
						$keyword = esc_html($value->keyword);
						$target = mb_strimwidth(esc_html($value->target), 0, 30, '...');
						echo '<tr>';
						echo '<th width="100">' . $keyword . '</th>';
						echo '<th width="100">' . $target . '</th>';
						echo '</tr>';
						$count++;
					}
				} ?>
			</table>


			<br><br>
			<span id='newWIXFiles'>
				<table class='newEntry'>
					<tr>
						<td><label for=keyword>Keyword</label></td>
						<td><input type="text" name="keywords[0]" /></td>
					</tr>
					<tr>
						<td><label for=target>Targets</label></td>
						<td><input type="text" size=30  name="targets[0]" /></td>
					</tr>
					<!-- オプション設置予定 -->
					<!-- <tr>
						<td></td>
						<td>
							<input type=checkbox id=firstonly name=firstonly value="1" /><label for=firstonly>First Match Only</label>
							<input type=checkbox id=case name=case value="1" /><label for=case>Case Sensitivity</label>
							<input type=checkbox id=filter name=filter value="1" /><label for=filter>Filter in comments?</label>
						</td>
					</tr> -->
				</table>
			</span>

			<table>
				<tr>
					<td>
						<input type="submit" name="wixfile_settings" 
							value= "<?php echo esc_attr( __( 'WIX File Save', 'wixfile_settings' ) ); ?>" 
							class="button button-primary button-large" >
					</td>
					<td>
						<input type="button" id="add_wixfile" 
							value= "<?php echo esc_attr( __( 'Form Add', 'wixfile_adding' ) ); ?>" 
							class="button button-primary button-large" >
					</td>
				</tr>
			</table>

		</form>
	</div>
	
</div>
<?php
}


function wix_admin_similarity() {
?>
<div class="wrap">
<?php 
	global $wpdb;
	echo '<h2>' . __( 'WIX Similarity', 'wix_similarity' ) . '</h2>'; 
?>

<!-- ---------- -->
	<div class="left">
		<form id="wix_similarity_form" method="post" action="">

			<?php wp_nonce_field( 'my-nonce-key', 'nonce_wix_similarity' ); ?>

			<table id="document_list">
				<tr>
					<th>Document Type</th>
					<th>Document Title</th>
				</tr>
				<?php
					$sql = 'SELECT ID, post_title, post_type, guid FROM ' . $wpdb->posts .
					 ' WHERE post_status!="inherit" and post_status!="trash" and post_status!="auto-save" and post_status!="auto-draft" order by post_type, ID asc';
					$documentsInfo = $wpdb->get_results($sql);

					$post_type_page_flag = false;
					$post_type_post_flag = false;

					foreach ($documentsInfo as $key => $value) {
						$post_title = $value->post_title;
						$post_type = $value->post_type;
						$url = $value->guid;
						$id = $value->ID;

						if ( $post_type == 'page' ) {
							echo '<tr>';
							if ( $post_type_page_flag === false ) {
								echo '<th>' . $post_type . '</th>';
								$post_type_page_flag = true;
							} else {
								echo '<th></th>';
							}
							echo '<td><a id= '. $id . ' class="wix_similarity_entry" target="target_page" href="' . $url . '">' . $post_title . '</a></td>';
							echo '</tr>';
						} else {
							if ( $post_type_post_flag === false ) {
								if ( $post_type_page_flag === true  ) echo '</tr>';
								echo '<tr>';
								echo '<th>' . $post_type . '</th>';
								$post_type_post_flag = true;
							} else {
								echo '<tr>';
								echo '<th></th>';
							}
							echo '<td><a id= '. $id . ' class="wix_similarity_entry" target="target_page" href="' . $url . '">' . $post_title . '</a></td>';
							echo '</tr>';
						}

						// echo '<tr>';
						// echo '<th>' . $post_type . '</th>';
						// echo '<td>' . $post_title . '</td>';
						// echo '</tr>';
					}
				?>
			</table>

		</form>
	</div>
	<div class="top">
		<div id="frame-box">
			<iframe id="frame-page" name="target_page"></iframe>
		</div>
	</div>
	<div class="bottom">
		<div id="bottom_contents">
			<div id="similarity_info">
				<table id="similarity_entrys">
					<tr>
						<th>Keyword in Doc</th>
						<th>Document Title</th>
					</tr>
				</table>
			</div>

			<div id="wixfile_info">
				<table id="wixfile_contents">
					<tr>
						<th>Keyword</th>
						<th>Targets</th>
					</tr>
				<?php 
					$table_name = $wpdb->prefix . 'wixfilemeta';
					$sql = 'SELECT COUNT(*) FROM ' . $table_name;
					if ( $wpdb->get_var($sql) != 0 ) {
						$sql = 'SELECT keyword, target FROM wp_wixfilemeta wm, wp_wixfile_targets wt WHERE wm.id = wt.keyword_id';
						$results = $wpdb->get_results($sql);
						$count = 0;
						foreach ($results as $value) {
							// $keyword = '<input type="text" name="keywords[' . $count . ']" value="' . esc_html($value->keyword) . '">';
							// $target = '<input type="text" name="targets[' . $count . ']" value="' . esc_html($value->target) . '">';
							$keyword = esc_html($value->keyword);
							$target = mb_strimwidth(esc_html($value->target), 0, 30, '...');
							echo '<tr>';
							echo '<th width="100">' . $keyword . '</th>';
							echo '<th width="100">' . $target . '</th>';
							echo '</tr>';
							$count++;
						}
					} 
				?>
				</table>
			</div>
		</div>
	</div>


<!-- ---------- -->



</div>
<?php
}






add_action( 'admin_init', 'wix_settings_core' );
function wix_settings_core() {
	global $wids_filenames;
	//nonceの値の✔
	if ( isset( $_POST['nonce_init_settings'] ) && $_POST['nonce_init_settings'] ) {

		if ( check_admin_referer( 'my-nonce-key', 'nonce_init_settings' ) ) {

			$e = new WP_Error();

			if ( isset( $_POST['wix_init_settings'] ) && $_POST['wix_init_settings'] ) {

				if ( isset( $_POST['authorName'] ) && $_POST['authorName'] ) {

					if ( !isset( $_POST['hostName'] ) || empty($_POST['hostName']) ) {
						update_option( 'wix_host_name', DB_HOST );
						$hostName = '<' . DB_HOST . '>' . "\n";
					} else {
						update_option( 'wix_host_name', $_POST['hostName'] );
						$hostName = '<' . $_POST['hostName'] . '>' . "\n";
					}

					//オーサ名のDB登録
					update_option( 'wix_author_name', $_POST['authorName'] );


					if ( isset( $_POST['pattern'] ) && $_POST['pattern'] && isset( $_POST['filename'] ) && $_POST['filename'] ) {
						// FILE_APPEND フラグはファイルの最後に追記することを表し、
						// LOCK_EX フラグは他の人が同時にファイルに書き込めないことを表します。
						// stripslashesでアンエスケープ
						file_put_contents( PatternFile, $hostName, FILE_USE_INCLUDE_PATH | LOCK_EX );

						foreach ( $_POST['pattern'] as $key => $pattern ) {
							foreach ($wids_filenames as $wid => $filename) {
								if ( $filename == $_POST['filename'][$key] ) {
									$pattern_filename = "\t" . stripslashes($pattern) . ' : ' . $wid . "\n";
									file_put_contents( PatternFile, $pattern_filename, FILE_APPEND | LOCK_EX );
									break;
								}
							}
						}
					} else {
						file_put_contents( PatternFile, $hostName, FILE_USE_INCLUDE_PATH | LOCK_EX );
						$pattern_filename = "\t" . stripslashes('/*') . ' : 0' . "\n";
						file_put_contents( PatternFile, $pattern_filename, FILE_APPEND | LOCK_EX );
					}
					set_transient( 'wix_init_settings', '初期設定完了しました', 10 );

				} else {
					$e -> add('error', __( 'Please entry author name', 'wix_init_settings' ) );
					set_transient( 'wix_init_settings_errors', $e->get_error_message(), 10 );
				}
			}
		} else {
			$e -> add('error', __( 'Please entry one more', 'wix_init_settings' ) );
			set_transient( 'wix_init_settings_errors', $e->get_error_message(), 10 );
		}

	} else if ( isset( $_POST['nonce_settings'] ) && $_POST['nonce_settings'] ) {

		if ( check_admin_referer( 'my-nonce-key', 'nonce_settings' ) ) {

			$e = new WP_Error();

			if ( isset( $_POST['wix_settings'] ) && $_POST['wix_settings'] ) {

				if ( isset( $_POST['pattern'] ) && $_POST['pattern'] && isset( $_POST['filename'] ) && $_POST['filename'] ) {

					$hostName = '<' . get_option( 'wix_host_name' ) . '>' . "\n";

					file_put_contents( PatternFile, $hostName, FILE_USE_INCLUDE_PATH | LOCK_EX );

					foreach ( $_POST['pattern'] as $key => $pattern ) {
						foreach ($wids_filenames as $wid => $filename) {
							if ( $filename == $_POST['filename'][$key] ) {
								$pattern_filename = "\t" . stripslashes($pattern) . ' : ' . $wid . "\n";
								file_put_contents( PatternFile, $pattern_filename, FILE_APPEND | LOCK_EX );
								break;
							}
						}
					}

					set_transient( 'wix_settings', '設定更新しました', 10 );
				}
			}
		} else {
			$e -> add('error', __( 'Please check various form', 'wix_settings' ) );
			set_transient( 'wix_settings_errors', $e->get_error_message(), 10 );
		}
	}

}

add_action( 'admin_init', 'wixfile_settings_core' );
function wixfile_settings_core() {
	global $wpdb;
	//nonceの値の✔
	if ( isset( $_POST['nonce_wixfile_settings'] ) && $_POST['nonce_wixfile_settings'] ) {

		if ( check_admin_referer( 'my-nonce-key', 'nonce_wixfile_settings' ) ) {

			$e = new WP_Error();

			if ( isset( $_POST['wixfile_settings'] ) && $_POST['wixfile_settings'] ) {

				if ( isset( $_POST['keywords'] ) && $_POST['keywords'] && isset( $_POST['targets'] ) && $_POST['targets'] ) {
					
					$wixfilemeta = $wpdb->prefix . 'wixfilemeta';
					$wixfile_targets = $wpdb->prefix . 'wixfile_targets';
					$target_checker = array();
					$insertKeywordArray = array();
					$insertTargetArray = array();
					$latest_id = 0;

					foreach ($_POST['keywords'] as $index => $keyword) {
						if ( !empty($keyword) ) {
							$target = $_POST['targets'][$index];
							$keyword_flag = false;
							$target_flag = false;

							//二重挿入しないようにフラグ立てる
							foreach ($target_checker as $key => $valueArray) {
								if ( $key == $keyword ) {
									$keyword_flag = true;
									foreach ($valueArray as $i => $value) {
										if ( $value == $target ) {
											$target_flag = true;
											break;
										}
									}
								}
							}
// var_dump($keyword_flag);
// var_dump($target_flag);
							if ( $target_flag == false ) {
								$sql = 'SELECT COUNT(*) FROM ' . $wixfilemeta . ' WHERE keyword="' . $keyword . '"';
								//まだwixfilemetaテーブルに存在しないキーワードの場合(つまりこれから挿入しなければならない)
								if ( $wpdb->get_var($sql) == 0 ) {
									//キーワードが既に挿入用Arrayにセットされていたら今回はいれない.そしてidを合わせる
									if ( $keyword_flag == false ) {
										$sql = 'SELECT MAX(id) FROM ' . $wixfilemeta;
										if ( $wpdb->get_var($sql) == NULL ) {
											$latest_id = count($insertKeywordArray); //DBにまだなんの行もない時
										} else {
											$tmp = (int) $wpdb->get_var($sql);
											$latest_id = $tmp + count($insertKeywordArray) + 1;
										}
										
										array_push($insertKeywordArray, 
													array(
														'id' => $latest_id,
														'keyword' => $keyword
														)
													);
									} else {
										foreach ($insertKeywordArray as $key => $value) {
											if ( $value['keyword'] == $keyword ) $latest_id = $value['id'];
										}
									}
									array_push($insertTargetArray, 
												array(
													'keyword_id' => $latest_id,
													'target' => $target
													)
												);

								} else {
									//wixfile_targetsテーブルに該当ターゲットが既に存在しなければ、キーワードのidを返す
									$sql = 'SELECT wm.id FROM ' . $wixfilemeta . ' wm WHERE wm.keyword="' . $keyword . 
												'" AND wm.id NOT IN (SELECT wt.keyword_id FROM ' . 
													$wixfile_targets . ' wt WHERE wt.target="' . $target . '")';
									$keyword_idObj = $wpdb->get_results($sql);
// var_dump($keyword_idObj);
									if ( !empty($keyword_idObj) ) {
										array_push($insertTargetArray, 
													array(
														'keyword_id' => (int)$keyword_idObj[0]->id,
														'target' => $target
														)
													);
									}
// var_dump($insertTargetArray);
								}

								//二重挿入チェッカーに追加
								if ( empty($target_checker) ) {
									$target_checker[$keyword] = array($target);
								} else {
									if ( array_key_exists($keyword, $target_checker) ) {
										$valueArray = $target_checker[$keyword];
										array_push($valueArray, $target);
										$target_checker[$keyword] = $valueArray;
									} else {
										$target_checker[$keyword] = array($target);
									}
								}
// var_dump($target_checker);
							}
						}
					}

// var_dump($insertKeywordArray);
// var_dump($insertTargetArray);
					if ( !empty($insertKeywordArray) ) {
						$insertKeyword = '';

						foreach ($insertKeywordArray as $index => $valueArray) {
							$keyword = $valueArray['keyword'];
							$id = $valueArray['id'];
							if ( $index == 0 ) {
								$insertKeyword = '(' . $id . ', "' . $keyword .'"), ';
							} else {
								$insertKeyword = $insertKeyword . '(' . $id . ', "' . $keyword .'"), ';
							}
						}

						$sql = 'INSERT INTO ' . $wixfilemeta . '(id, keyword) VALUES ' . $insertKeyword;
						$sql = mb_substr($sql, 0, (mb_strlen($sql)-2));
						$result = $wpdb->query( $sql );					

						if ( $result != 0 ) set_transient( 'wix_settings', 'WIX FILE 更新しました', 1 );
						else set_transient( 'wix_settings', 'WIX FILE 更新に失敗しました', 1 );

					} else {
						set_transient( 'wix_settings', '既にある情報、もしくはフォームに値がなかったため更新しませんでした', 1 );
					}

					if ( !empty($insertTargetArray) ) {
						$insertTarget = '';

						foreach ($insertTargetArray as $index => $valueArray) {
							$target = $valueArray['target'];
							$id = $valueArray['keyword_id'];
							if ( $index == 0 ) {
								$insertTarget = '(' . $id . ', "' . $target . '"), ';
							} else {
								$insertTarget = $insertTarget . '(' . $id . ', "' . $target . '"), ';
							}
						}

						$sql = 'INSERT INTO ' . $wixfile_targets . '(keyword_id, target) VALUES ' . $insertTarget;
						$sql = mb_substr($sql, 0, (mb_strlen($sql)-2));
						$result = $wpdb->query( $sql );					

						if ( $result != 0 ) set_transient( 'wix_settings', 'WIX FILE 更新しました', 1 );
						else set_transient( 'wix_settings', 'WIX FILE 更新に失敗しました', 1 );

					} else {
						set_transient( 'wix_settings', '既にある情報、もしくはフォームに値がなかったため更新しませんでした', 1 );
					}







					// if ( !empty($insertArray) ) {
					// 	$insertKeyword = '';
					// 	$insertTarget = '';

					// 	foreach ($insertArray as $index => $valueArray) {
					// 		$keyword = $valueArray['keyword'];
					// 		$target = $valueArray['target'];
					// 		$id = $valueArray['id'];
					// 		if ( $index == 0 ) {
					// 			$insertKeyword = '(' . $id . ', "' . $keyword .'"), ';
					// 			$insertTarget = '(' . $id . ', "' . $target . '"), ';
					// 		} else {
					// 			$insertKeyword = $insertKeyword . '(' . $id . ', "' . $keyword .'"), ';
					// 			$insertTarget = $insertTarget . '(' . $id . ', "' . $target . '"), ';
					// 		}
					// 	}

					// 	$sql = 'INSERT INTO ' . $wixfilemeta . '(id, keyword) VALUES ' . $insertKeyword;
					// 	$sql = mb_substr($sql, 0, (mb_strlen($sql)-2));
					// 	$result = $wpdb->query( $sql );

					// 	$sql = 'INSERT INTO ' . $wixfile_targets . '(keyword_id, target) VALUES ' . $insertTarget;
					// 	$sql = mb_substr($sql, 0, (mb_strlen($sql)-2));
					// 	$result2 = $wpdb->query( $sql );						

					// 	if ( $result !=0 && $result2 != 0 ) 
					// 		set_transient( 'wix_settings', 'WIX FILE 更新しました', 1 );
					// 	else
					// 		set_transient( 'wix_settings', 'WIX FILE 更新に失敗しました', 1 );
					// } else {
					// 	set_transient( 'wix_settings', '既にある情報、もしくはフォームに値がなかったため更新しませんでした', 1 );
					// }

				}

			}
		} else {
			$e -> add('error', __( 'Please check various WIX FIle form', 'wixfile_settings' ) );
			set_transient( 'wixfile_settings_errors', $e->get_error_message(), 10 );
		}
	}
}



//更新・エラーメッセージを表示する
add_action( 'admin_notices', 'wix_settings_notices' );	

function wix_settings_notices() {
?>
	<?php if ( $messages = get_transient( 'wix_init_settings_errors' ) ): ?>
	<div class="error">
		<ul>
			<?php foreach( (array)$messages as $message ): ?>
				<li><?php echo esc_html($message); ?></li>
			<?php endforeach; ?>
		</ul>
	</div>
	<?php elseif ( $messages = get_transient( 'wix_init_settings' ) ): ?>
	<div class="updated">
		<ul>
			<?php foreach( (array)$messages as $message ): ?>
				<li><?php echo esc_html($message); ?></li>
			<?php endforeach; ?>
		</ul>
	</div>
	<?php elseif ( $messages = get_transient( 'wix_settings_errors' ) ): ?>
	<div class="error">
		<ul>
			<?php foreach( (array)$messages as $message ): ?>
				<li><?php echo esc_html($message); ?></li>
			<?php endforeach; ?>
		</ul>
	</div>
	<?php elseif ( $messages = get_transient( 'wix_settings' ) ): ?>
	<div class="updated">
		<ul>
			<?php foreach( (array)$messages as $message ): ?>
				<li><?php echo esc_html($message); ?></li>
			<?php endforeach; ?>
		</ul>
	</div>
	<?php elseif ( $messages = get_transient( 'wixfile_settings_errors' ) ): ?>
	<div class="error">
		<ul>
			<?php foreach( (array)$messages as $message ): ?>
				<li><?php echo esc_html($message); ?></li>
			<?php endforeach; ?>
		</ul>
	</div>
	<?php elseif ( $messages = get_transient( 'wixfile_settings' ) ): ?>
	<div class="updated">
		<ul>
			<?php foreach( (array)$messages as $message ): ?>
				<li><?php echo esc_html($message); ?></li>
			<?php endforeach; ?>
		</ul>
	</div>
	<?php endif; ?>
<?php
}





//Library登録済みWIXファイル一覧
function created_wixfiles() {
?>
<?php echo '<h3>' . __( 'Created WIXFile List', 'created_wixfile_list' ) . '</h3>'; ?>

	<div id="created_wixfiles">
<?php
	global $wids_filenames;

	if ( isset($wids_filenames) ) {
		echo '<p>';
		foreach ($wids_filenames as $wid => $filename) {
			echo $filename . '<br>';
		}
		echo '</p>';
	} else {
		echo '<p>';
		echo __( '登録済みのWIXファイルがありません', 'Not Exist your submitted WIXFiles' );
		echo '</p>';
	}
?>
	</div>
<?php
}


//decide_management欄
function decide_management() {
?>
<?php echo '<h3>' . __( 'WIX Decide Management', 'wix_decide_management' ) . '</h3>'; ?>
<?php echo '<h4>' . __( '・Manual Decide', 'manual_decide' ) . '</h4>'; ?>

	<div class="decide_management" id="manual_decide">
<?php if ( get_option( 'manual_decideFlag' ) == 'true' ) { ?>
	    <input type="checkbox" name="decide_management" class="decide_management-checkbox" id="myonoffswitch" checked>
<?php } else { ?>
	    <input type="checkbox" name="decide_management" class="decide_management-checkbox" id="myonoffswitch">
<?php } ?>
	    <label class="decide_management-label" for="myonoffswitch">
	        <span class="decide_management-inner"></span>
	        <span class="decide_management-switch"></span>
	    </label>
	</div>
<?php
}


function created_wixfile_info() {
	$URL = 'http://trezia.db.ics.keio.ac.jp/WIXAuthorEditor_0.0.1/GetCreatedWIXFileNames';
	
	$ch = curl_init();
	$data = array(
	    'author' => 'sakusa'
	);
	$data = http_build_query($data, "", "&");

	try {
		//送信
		curl_setopt( $ch, CURLOPT_URL, $URL );
		curl_setopt( $ch, CURLOPT_HTTPHEADER, array('Content-Type: application/x-www-form-urlencoded') );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
		curl_setopt( $ch, CURLOPT_POST, true );
		curl_setopt( $ch, CURLOPT_POSTFIELDS, $data );

		$response = curl_exec($ch);

		if ( $response === false ) {

		    // エラー文字列を出力する
		    echo 'エラーです.';
	    	echo curl_error( $ch );

		} else {
		
			global $wids_filenames;

			if ( strlen($response) != 0 ) {

				$tmp = explode(",", $response);

				foreach ($tmp as $key => $value) {
					$wid = explode(":", $value)[0];
					$filename = explode(":", $value)[1];

					$wids_filenames[$wid] = $filename;
				}

			}

		}

	} catch ( Exception $e ) {
	
		echo '捕捉した例外: ',  $e -> getMessage(), "\n";
	
	} finally {

		curl_close($ch);
	
	}

}


//manual_decideFlagを返す
add_action( 'wp_ajax_wix_manual_decide', 'wix_manual_decide' );
add_action( 'wp_ajax_nopriv_wix_manual_decide', 'wix_manual_decide' );
function wix_manual_decide() {

	header("Access-Control-Allow-Origin: *");
	header('Content-type: application/javascript; charset=utf-8');
		
	$manual_decideFlag = (string)$_POST['manual_decideFlag'];
	update_option( 'manual_decideFlag', $manual_decideFlag );
	$json = array(
		"data" => $manual_decideFlag
	);

	echo json_encode( $json );

	
    die();
}

//WIXFileのエントリ候補をwix_document_similarityテーブルから推薦
add_action( 'wp_ajax_wix_similarity_entry_recommend', 'wix_similarity_entry_recommend' );
add_action( 'wp_ajax_nopriv_wix_similarity_entry_recommend', 'wix_similarity_entry_recommend' );
function wix_similarity_entry_recommend() {
	global $wpdb;

	header("Access-Control-Allow-Origin: *");
	header('Content-type: application/javascript; charset=utf-8');

	$doc_id = $_POST['doc_id'];

	//候補キーワード群
	$sql = 'SELECT * FROM ' . $wpdb->prefix . 'wix_keyword_similarity' .
			 ' WHERE doc_id = ' . $doc_id . ' AND tf_idf != 0 order by tf_idf desc';
	$candidate_keywords = $wpdb->get_results($sql);

	//クリックされたドキュメントとの関連度を持つドキュメント群
	$sql = 'SELECT * FROM ' . $wpdb->prefix . 'wix_document_similarity' .
	 ' WHERE cos_similarity != 0 AND (doc_id=' . $doc_id . ' OR doc_id2=' . $doc_id . ') order by cos_similarity desc';
	$similar_documents = $wpdb->get_results($sql);


	if ( !empty($candidate_keywords) && !empty($similar_documents) ) {
		//候補ターゲット群をDBから持ってくる
		$selectQuery = '';
		foreach ($similar_documents as $key => $value) {
			if ( (int)$value->doc_id == $doc_id )
				$candidate_doc_id = $value->doc_id2;
			else
				$candidate_doc_id = $value->doc_id;


			if ( empty($selectQuery) )
				$selectQuery = 'ID=' . $candidate_doc_id . ' ';
			else
				$selectQuery = $selectQuery . 'OR ID=' . $candidate_doc_id . ' ';
		}
		$sql = 'SELECT ID, post_title, guid FROM ' . $wpdb->posts . ' WHERE ' . $selectQuery;
		$candidate_targets = $wpdb->get_results($sql);

		$returnValue = array();
		foreach ($candidate_keywords as $key => $value) {
			$returnValue[$value->keyword] = $candidate_targets;
		}


	} else {
		$returnValue = array();
	}

	$json = array(
		"entrys" => $returnValue,
		// "entrys" => $similar_documents,
	);

	echo json_encode( $json );

	
    die();
}


?>