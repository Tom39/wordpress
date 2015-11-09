<?php

require_once( dirname( __FILE__ ) . '/patternMatching.php' );
require_once( dirname( __FILE__ ) . '/wixSetting_core.php' );
//登録済みWIXファイル
$wids_filenames = array();


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
		__('WIX Detail Settings', 'wix-detail-settings'),
		__('WIX Detail Settings', 'wix-detail-settings'),
		'administrator',
		'wix-detail-settings',
		'wix_detail_settings'
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
	<ul id="tab">
		<li class="selected"><a href="#tab1">タブ1</a></li>
		<li><a href="#tab2">タブ2</a></li>
	</ul>
	<div id="contents">
		<div class="tabbox" id="tab1">
			<div id="wix_settings">
				<?php
					global $wids_filenames;
					if ( file_exists( PatternFile ) && is_readable( PatternFile ) ) {
						global $pm;
						$patternFile = $pm -> returnCandidates();
						$hostName = $pm -> requestURL_part( PHP_URL_HOST );
				?>
				<form id="wix_settings_form" method="post" action="">
						<?php wp_nonce_field( 'my-nonce-key', 'nonce_settings' ); ?>
					<div id="init_settings">
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
					</div> <!-- #init_settings -->

				<?php } else { ?> <!-- PatternFileがなければ -->
				<form id="wix_settings_form" method="post" action="">
						<?php 
							wp_nonce_field( 'my-nonce-key', 'nonce_init_settings' ); 
							global $pm;
							$hostName = $pm -> requestURL_part( PHP_URL_HOST );
						?>
					<div id="init_settings">
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
					</div> <!-- #init_settings -->
				<?php } ?>
	
					<div id="option_settings">
						<div class="contents_option" id="wixfile_option_settings">
							<strong>WIXファイル</strong>
							<fieldset name="wixfile_option" form="wixfile_option1">
								<legend>[自動生成]</legend>
								<?php 
								if ( get_option('wixfile_autocreate') == 'true' ) {
								?>
								<input type="radio" name="wixfile_autocreate" id="wixfile_autocreate_on" value="true" checked>
								<label for="wixfile_autocreate_on" class="switch-on">YES</label>
								<input type="radio" name="wixfile_autocreate" id="wixfile_autocreate_off" value="false">
								<label for="wixfile_autocreate_off" class="switch-off">No</label>
								<?php
								} else {
								?>
								<input type="radio" name="wixfile_autocreate" id="wixfile_autocreate_on" value="true">
								<label for="wixfile_autocreate_on" class="switch-on">YES</label>
								<input type="radio" name="wixfile_autocreate" id="wixfile_autocreate_off" value="false" checked>
								<label for="wixfile_autocreate_off" class="switch-off">No</label>
								<?php
								}
								?>
							</fieldset>
							<fieldset name="wixfile_option" form="wixfile_option2">
								<legend>[ページ作成画面における更新操作]</legend>
								<?php 
								if ( get_option('decidefile_autocreate') == 'true' ) {
								?>
								<input type="radio" name="wixfile_manualupdate" id="wixfile_manualupdate_on" value="true" checked>
								<label for="wixfile_manualupdate_on" class="switch-on">YES</label>
								<input type="radio" name="wixfile_manualupdate" id="wixfile_manualupdate_off" value="false">
								<label for="wixfile_manualupdate_off" class="switch-off">No</label>
								<?php
								} else {
								?>
								<input type="radio" name="wixfile_manualupdate" id="wixfile_manualupdate_on" value="true">
								<label for="wixfile_manualupdate_on" class="switch-on">YES</label>
								<input type="radio" name="wixfile_manualupdate" id="wixfile_manualupdate_off" value="false" checked>
								<label for="wixfile_manualupdate_off" class="switch-off">No</label>
								<?php
								}
								?>
							</fieldset>
						</div>
						<div class="contents_option" id="decidefile_option_settings">
							<strong>Decideファイル</strong>
							<fieldset name="decidefile_option" form="decidefile_option1">
								<legend>[Decideファイル適用]</legend>
								<?php 
								if ( get_option('decidefile_autocreate') == 'true' ) {
								?>
								<input type="radio" name="decidefile_apply" id="decidefile_apply_on" value="true" checked>
								<label for="decidefile_apply_on" class="switch-on">YES</label>
								<input type="radio" name="decidefile_apply" id="decidefile_apply_off" value="false">
								<label for="decidefile_apply_off" class="switch-off">No</label>
								<?php
								} else {
								?>
								<input type="radio" name="decidefile_apply" id="decidefile_apply_on" value="true">
								<label for="decidefile_apply_on" class="switch-on">YES</label>
								<input type="radio" name="decidefile_apply" id="decidefile_apply_off" value="false" checked>
								<label for="decidefile_apply_off" class="switch-off">No</label>
								<?php
								}
								?>
							</fieldset>
							<fieldset name="decidefile_option" form="decidefile_option3">
								<legend>[自動生成]</legend>
								<?php 
								if ( get_option('decidefile_autocreate') == 'true' ) {
								?>
								<input type="radio" name="decidefile_autocreate" id="decidefile_autocreate_on" value="true" checked>
								<label for="decidefile_autocreate_on" class="switch-on">YES</label>
								<input type="radio" name="decidefile_autocreate" id="decidefile_autocreate_off" value="false">
								<label for="decidefile_autocreate_off" class="switch-off">No</label>
								<?php
								} else {
								?>
								<input type="radio" name="decidefile_autocreate" id="decidefile_autocreate_on" value="true">
								<label for="decidefile_autocreate_on" class="switch-on">YES</label>
								<input type="radio" name="decidefile_autocreate" id="decidefile_autocreate_off" value="false" checked>
								<label for="decidefile_autocreate_off" class="switch-off">No</label>
								<?php
								}
								?>
							</fieldset>
							<fieldset name="decidefile_option" form="decidefile_option3">
								<legend>[ページ作成画面における更新操作]</legend>
								<?php 
								if ( get_option('manual_decide') == 'true' ) {
								?>
								<input type="radio" name="decidefile_manualupdate" id="decidefile_manualupdate_on" value="true" checked>
								<label for="decidefile_manualupdate_on" class="switch-on">YES</label>
								<input type="radio" name="decidefile_manualupdate" id="decidefile_manualupdate_off" value="false">
								<label for="decidefile_manualupdate_off" class="switch-off">No</label>
								<?php
								} else {
								?>
								<input type="radio" name="decidefile_manualupdate" id="decidefile_manualupdate_on" value="true">
								<label for="decidefile_manualupdate_on" class="switch-on">YES</label>
								<input type="radio" name="decidefile_manualupdate" id="decidefile_manualupdate_off" value="false" checked>
								<label for="decidefile_manualupdate_off" class="switch-off">No</label>
								<?php
								}
								?>
							</fieldset>
						</div>
					</div> <!-- #option_settings -->
					<?php
						if ( file_exists( PatternFile ) && is_readable( PatternFile ) ) {
					?>
					<input type="submit" name="wix_settings" 
						value= "<?php echo esc_attr( __( 'WIX Settings', 'wix_settings' ) ); ?>" 
						id="wix_settings_button" class="button button-primary button-large" >
					<?php } else { ?>
					<input type="submit" name="wix_init_settings" 
						value= "<?php echo esc_attr( __( 'WIX Initial Settings', 'wix_initial_settings' ) ); ?>" 
						id="wix_init_settings_button" class="button button-primary button-large" >
					<?php } ?>
				</form>
			</div> <!-- #wix_settings -->

			<div id="wixfile" class="wixfile">
				<ul id="wixfile_tab" class="wixfile_tab">
					<?php
						global $wpdb;
						$sql = 'SELECT keyword, target FROM wp_wixfilemeta wm, wp_wixfile_targets wt WHERE wm.id = wt.keyword_id';
						$entrys = $wpdb->get_results($sql);
						$entryNum = count($entrys);
						// $entryNum = 80;
						echo '<li class="selected"><a href="#wixfile_tab1">タブ1</a></li>';
						if ( $entryNum > 20 ) {
							$count = 2;
							while ( $entryNum > 20 ) {
								echo '<li><a href="#wixfile_tab' . $count . '">タブ' . $count . '</a></li>';
								$count++;
								$entryNum = $entryNum - 20;
							}
						}
					?>
				</ul>
				<div id="wixfile_operation_contents" class="wixfile_operation_contents">
					<form id="wixfile_settings_form" class="wixfile_settings_form" method="post" action="">
						<?php wp_nonce_field( 'my-nonce-key', 'nonce_wixfile_settings' ); ?>

						<div id="wixfile_contents">
							<?php
								$entryNum = count($entrys);
								// $entryNum = 80;
								if ( $entryNum < 20 ) {
									echo '<div id="wixfile_tab1" class="wixfile_tabbox">';
										echo '<table id="wixfile_table1" class="wixfile_table">';
											echo '<thead>';
												echo '<tr class="wixfile_table_heading">';
													echo '<th colspan="3"></th><th>キーワード</th><th>ターゲット</th>';
												echo '</tr>';
											echo '</thead>';
											echo '<tbody>';

												$tmpCount = 0;
												while ( $tmpCount < $entryNum ) {
												if ( ($tmpCount % 2) == 0 )
												echo '<tr id="wixfile_entry' . $tmpCount . '" class="wixfile_even">';
												else 
												echo '<tr id="wixfile_entry' . $tmpCount . '" class="wixfile_odd">';
													echo '<td class="wixfile_entry_select">';
														echo '<span>';
														echo '<input type="checkbox" id="wixfile_entry_checkbox' . $tmpCount . '" class="wixfile_entry_checkbox">';
														echo '</span>';
													echo '</td>';
													echo '<td id="wixfile_entry_edit' . $tmpCount . '" class="wixfile_entry_edit">';
														echo '<span><a>編集</a></span>';
													echo '</td>';
													echo '<td id="wixfile_entry_delete' . $tmpCount . '" class="wixfile_entry_delete">';
														echo '<span><a>削除</a></span>';
													echo '</td>';
													echo '<td id="wixfile_keyword' . $tmpCount . '" class="wixfile_keyword">';
														echo '<span>' . $entrys[$tmpCount]->keyword . '</span>';
													echo '</td>';
													echo '<td id="wixfile_target' . $tmpCount . '" class="wixfile_target">';
														echo '<span><a target="target_page" href="' . esc_html($entrys[$tmpCount]->target) . '">'  
																	. mb_strimwidth(esc_html($entrys[$tmpCount]->target), 0, 30, '...') . '</a></span>';
													echo '</td>';
												echo '</tr>';
												if ( ($tmpCount % 2) == 0 )
												echo '<tr id="wixfile_entry_hidden' . $tmpCount . '" class="wixfile_even" style="display:none" >';
												else 
												echo '<tr id="wixfile_entry_hidden' . $tmpCount . '" class="wixfile_odd" style="display:none" >';
													echo '<td colspan="1"></td>';
													echo '<td id="wixfile_entry_decide' . $tmpCount . '" class="wixfile_entry_decide" colspan="2">';
														echo '<span><a>決定</a></span>';
													echo '</td>';
													echo '<td id="wixfile_keyword_edit' . $tmpCount . '" class="wixfile_keyword_edit">';
														echo '<input type="text" value="' . $entrys[$tmpCount]->keyword . '">';
													echo '</td>';
													echo '<td id="wixfile_target_edit' . $tmpCount . '" class="wixfile_target_edit">';
														echo '<input type="text" value="' . esc_html($entrys[$tmpCount]->target) . '">';
													echo '</td>';
												echo '</tr>';
													$tmpCount++;
												}
											echo '</tbody>';
										echo '</table>';
									echo '</div>';
								} else {
									$tmpEntryNum = 0;
									$tmpIndex = 0;
									$count = 1;
									while ( $tmpEntryNum < $entryNum ) {
										echo '<div id="wixfile_tab' . $count . '" class="wixfile_tabbox">';
											echo '<table id="wixfile_table' . $count . '" class="wixfile_table">';
												echo '<thead>';
													echo '<tr class="wixfile_table_heading">';
														echo '<th colspan="3"></th><th>キーワード</th><th>ターゲット</th>';
													echo '</tr>';
												echo '</thead>';
												echo '<tbody>';

													$tmpCount = 0;
													while ( $tmpCount < 20 ) {
														if ( $tmpIndex < $entryNum ) {
														if ( ($tmpIndex % 2) == 0 )
														echo '<tr id="wixfile_entry' . $tmpIndex . '" class="wixfile_even">';
														else 
														echo '<tr id="wixfile_entry' . $tmpIndex . '" class="wixfile_odd">';
															echo '<td class="wixfile_entry_select">';
																echo '<span>';
																echo '<input type="checkbox" id="wixfile_entry_checkbox' . $tmpIndex . '" class="wixfile_entry_checkbox">';
																echo '</span>';
															echo '</td>';
															echo '<td id="wixfile_entry_edit' . $tmpIndex . '" class="wixfile_entry_edit">';
																echo '<span><a>編集</a></span>';
															echo '</td>';
															echo '<td id="wixfile_entry_delete' . $tmpIndex . '" class="wixfile_entry_delete">';
																echo '<span><a>削除</a></span>';
															echo '</td>';
															// '<td id="wixfile_keyword' . $tmpIndex . '" class="wixfile_keyword">';
															// 	echo '<span>' . $entrys[$tmpIndex]->keyword . '</span>';
															// echo '</td>';
															// echo '<td id="wixfile_target' . $tmpIndex . '" class="wixfile_target">';
															// 	echo '<span><a target="target_page" href="' . esc_html($entrys[$tmpCount]->target) . '">'  
															//			. mb_strimwidth(esc_html($entrys[$tmpCount]->target), 0, 30, '...') . '</a></span>';
															// echo '</td>';
														/*----この中削除ok-----*/
															echo '<td id="wixfile_keyword' . $tmpIndex . '" class="wixfile_keyword">';
																echo '<span>佐草友也</span>';
															echo '</td>';
															echo '<td id="wixfile_target' . $tmpIndex . '" class="wixfile_target">';
															echo '<span><a target="target_page" href="http://www.db.ics.keio.ac.jp">http://www.db.ics.keio.ac.jp</a></span>';
															echo '</td>';
														/*---------*/
															if ( ($tmpIndex % 2) == 0 )
															echo '<tr id="wixfile_entry_hidden' . $tmpIndex . '" class="wixfile_even" style="display:none" >';
															else 
															echo '<tr id="wixfile_entry_hidden' . $tmpIndex . '" class="wixfile_odd" style="display:none" >';
																echo '<td colspan="1"></td>';
																echo '<td id="wixfile_entry_decide' . $tmpIndex . '" class="wixfile_entry_decide" colspan="2">';
																	echo '<span><a>決定</a></span>';
																echo '</td>';
																// echo '<td id="wixfile_keyword_edit' . $tmpIndex . '" class="wixfile_keyword_edit">';
																// 	echo '<input type="text" value="' . $entrys[$tmpIndex]->keyword . '">';
																// echo '</td>';
																// echo '<td id="wixfile_target_edit' . $tmpCount . '" class="wixfile_target_edit">';
																// 	echo '<input type="text" value="' . esc_html($entrys[$tmpIndex]->target) . '">';
																// echo '</td>';
														/*-----この中削除ok----*/
																echo '<td id="wixfile_keyword_edit' . $tmpIndex . '" class="wixfile_keyword_edit">';
																	echo '<input type="text" value="テスト">';
																echo '</td>';
																echo '<td id="wixfile_target_edit' . $tmpCount . '" class="wixfile_target_edit">';
																	echo '<input type="text" value="http://www.keio.ac.jp/index-jp.html">';
																echo '</td>';
														/*---------*/
															echo '</tr>';
														echo '</tr>';
															$tmpIndex++;
															$tmpCount++;
														} else {
															break;
														}
													}
												echo '</tbody>';
											echo '</table>';
										echo '</div>';
										$count++;
										$tmpEntryNum += 20;
									}
								}
							?>
						</div>	<!-- #wixfile_contents -->
						<div id="wixfile_entry_operation">
							<img class="selectallarrow" src="../wp-content/plugins/WIX/css/images/arrow_ltr.png" alt="with" selected>
							<input type="checkbox" id="wixfile_entry_allcheck" class="wixfile_entry_allcheck">
							<label for="wixfile_entry_allcheck">Check All</label>
							<span id="wixfile_each_operation">
								<i>With Selected: </i>
								<span id="wixfile_entry_batch_edit" class="wixfile_entry_batch_edit">
									<img src="../wp-content/plugins/WIX/css/images/b_edit.png" alt="change">
									<a>編集</a>
								</span>
								<span id="wixfile_entry_batch_delete" class="wixfile_entry_batch_delete">
									<img src="../wp-content/plugins/WIX/css/images/b_drop.png" alt="delete">
									<a>削除</a>
								</span>
							</span>
						</div>
						<div id="wixfile_entry_import">
							<!-- エントリ挿入(未実装) -->

						</div>
						<input type="submit" name="wixfile_settings" 
							value= "<?php echo esc_attr( __( 'WIXFIle Setting', 'wixfile_setting' ) ); ?>" 
							id="wixfile_settings_button" class="button button-primary" >
					</form>
				</div> <!-- #wixfile_operation_contents -->
				<div id="wixfile_iframe">
					<div id="wixfile_iframe_inline_div">
						<iframe id="wixfile_entry_iframe" name="target_page"></iframe>
					</div>
				</div>
				<div id="newEntry" class="newEntry">
					<fieldset name="newEntry_form" form="newEntry_form">
						<legend>New Entry</legend>
						<input type="text" id="newKeyword_form" class="newKeyword_form" placeholder="リンクを貼りたい単語">
						<input type="text" id="newTarget_form" class="newTarget_form" placeholder="リンク先URL">
					</fieldset>
					<div id="entry_insert_result"></div>
					<input type="button" id="add_wixfile_entry" 
							value="データ追加" 
							class="button button-primary button-large" >
				</div>
			</div> <!-- #wixfile -->

		</div> <!-- #tab1 -->
<?php
/**
			↓タブ２
**/
?>
		<div class="tabbox" id="tab2">
			<div id="createdDoc">
				<ul id="doc_tab">
					<li class="selected"><a href="#doc_tab1">固定ページ</a></li>
					<li><a href="#doc_tab2">投稿</a></li>
				</ul>
				<div id="doc_list">
					<?php 
						$sql = 'SELECT DISTINCT post_type FROM ' . $wpdb->posts . ' WHERE post_type!="revision"';
						$post_typeObj = $wpdb->get_results($sql);
						if ( count($post_typeObj) != 0 ) {
							foreach ($post_typeObj as $index => $value) {
								$type = $value->post_type;
								$sql = 'SELECT ID, post_title, guid FROM ' . $wpdb->posts . 
										' WHERE post_type="' . $type . '" AND post_status!="inherit" and post_status!="trash" and post_status!="auto-save" and post_status!="auto-draft" order by post_type, ID asc';
								$documentsInfo = $wpdb->get_results($sql);

								if ( count($documentsInfo) != 0 ) {
									if ( $type == 'page' ) {
										echo '<div id="doc_tab1" class="doc_tabbox">';
											echo '<table id="doc_table1" class="doc_table">';
									} else if ( $type == 'post' ) {
										echo '<div id="doc_tab2" class="doc_tabbox">';
											echo '<table id="doc_table2" class="doc_table">';
									}
												echo '<thead>';
													echo '<tr class="doc_table_heading">';
														echo '<th>タイトル</th>';
													echo '</tr>';
												echo '</thead>';
												echo '<tbody>';
												foreach ($documentsInfo as $index => $value) {
													$post_title = $value->post_title;
													$url = $value->guid;
													$id = $value->ID;
													echo '<tr><td><a id=' . $id . ' class="doc_page" target="doc_page" href="' . $url . '">' . $post_title . '</a></td></tr>';
												}
												echo '</tbody>';
											echo '</table>';
										echo '</div>'; //.doc_tabbox
								}
							}


						}
					?>
				</div> <!-- #doc_list -->
			</div> <!-- #createdDoc -->
<!-- ---------------------------------------------------------- tab2 の WIXファイル部分 ------------------------------ -->
			<div id="second_wixfile" class="wixfile">
				<ul id="second_wixfile_tab" class="wixfile_tab">
					<?php
						echo '<li class="selected"><a href="#second_wixfile_tab1">タブ2のタブ1</a></li>';
						if ( $entryNum > 20 ) {
							$count = 2;
							while ( $entryNum > 20 ) {
								echo '<li><a href="#second_wixfile_tab' . $count . '">タブ2のタブ' . $count . '</a></li>';
								$count++;
								$entryNum = $entryNum - 20;
							}
						}
					?>
				</ul>

				<div id="second_wixfile_operation_contents" class="wixfile_operation_contents">
					<form id="second_wixfile_settings_form" class="wixfile_settings_form" method="post" action="">
						<?php wp_nonce_field( 'my-nonce-key', 'nonce_wixfile_settings' ); ?>

						<div id="second_wixfile_contents">
							<?php
								$entryNum = count($entrys);
								// $entryNum = 80;
								if ( $entryNum < 20 ) {
									echo '<div id="second_wixfile_tab1" class="second_wixfile_tabbox">';
										echo '<table id="second_wixfile_table1" class="second_wixfile_table">';
											echo '<thead>';
												echo '<tr class="second_wixfile_table_heading">';
													echo '<th colspan="3"></th><th>キーワード</th><th>ターゲット</th>';
												echo '</tr>';
											echo '</thead>';
											echo '<tbody>';

												$tmpCount = 0;
												while ( $tmpCount < $entryNum ) {
												if ( ($tmpCount % 2) == 0 )
												echo '<tr id="wixfile_entry' . $tmpCount . '" class="wixfile_even">';
												else 
												echo '<tr id="wixfile_entry' . $tmpCount . '" class="wixfile_odd">';
													echo '<td class="wixfile_entry_select">';
														echo '<span>';
														echo '<input type="checkbox" id="wixfile_entry_checkbox' . $tmpCount . '" class="wixfile_entry_checkbox">';
														echo '</span>';
													echo '</td>';
													echo '<td id="wixfile_entry_edit' . $tmpCount . '" class="wixfile_entry_edit">';
														echo '<span><a>編集</a></span>';
													echo '</td>';
													echo '<td id="wixfile_entry_delete' . $tmpCount . '" class="wixfile_entry_delete">';
														echo '<span><a>削除</a></span>';
													echo '</td>';
													echo '<td id="wixfile_keyword' . $tmpCount . '" class="wixfile_keyword">';
														echo '<span>' . $entrys[$tmpCount]->keyword . '</span>';
													echo '</td>';
													echo '<td id="wixfile_target' . $tmpCount . '" class="wixfile_target">';
														echo '<span><a target="doc_page" href="' . esc_html($entrys[$tmpCount]->target) . '">'  
																	. mb_strimwidth(esc_html($entrys[$tmpCount]->target), 0, 30, '...') . '</a></span>';
													echo '</td>';
												echo '</tr>';
												if ( ($tmpCount % 2) == 0 )
												echo '<tr id="wixfile_entry_hidden' . $tmpCount . '" class="wixfile_even" style="display:none" >';
												else 
												echo '<tr id="wixfile_entry_hidden' . $tmpCount . '" class="wixfile_odd" style="display:none" >';
													echo '<td colspan="1"></td>';
													echo '<td id="wixfile_entry_decide' . $tmpCount . '" class="wixfile_entry_decide" colspan="2">';
														echo '<span><a>決定</a></span>';
													echo '</td>';
													echo '<td id="wixfile_keyword_edit' . $tmpCount . '" class="wixfile_keyword_edit">';
														echo '<input type="text" value="' . $entrys[$tmpCount]->keyword . '">';
													echo '</td>';
													echo '<td id="wixfile_target_edit' . $tmpCount . '" class="wixfile_target_edit">';
														echo '<input type="text" value="' . esc_html($entrys[$tmpCount]->target) . '">';
													echo '</td>';
												echo '</tr>';
													$tmpCount++;
												}
											echo '</tbody>';
										echo '</table>';
									echo '</div>';
								} else {
									$tmpEntryNum = 0;
									$tmpIndex = 0;
									$count = 1;
									while ( $tmpEntryNum < $entryNum ) {
										echo '<div id="second_wixfile_tab' . $count . '" class="second_wixfile_tabbox">';
											echo '<table id="second_wixfile_table' . $count . '" class="second_wixfile_table">';
												echo '<thead>';
													echo '<tr class="second_wixfile_table_heading">';
														echo '<th colspan="3"></th><th>キーワード</th><th>ターゲット</th>';
													echo '</tr>';
												echo '</thead>';
												echo '<tbody>';

													$tmpCount = 0;
													while ( $tmpCount < 20 ) {
														if ( $tmpIndex < $entryNum ) {
														if ( ($tmpIndex % 2) == 0 )
														echo '<tr id="wixfile_entry' . $tmpIndex . '" class="wixfile_even">';
														else 
														echo '<tr id="wixfile_entry' . $tmpIndex . '" class="wixfile_odd">';
															echo '<td class="wixfile_entry_select">';
																echo '<span>';
																echo '<input type="checkbox" id="wixfile_entry_checkbox' . $tmpIndex . '" class="wixfile_entry_checkbox">';
																echo '</span>';
															echo '</td>';
															echo '<td id="wixfile_entry_edit' . $tmpIndex . '" class="wixfile_entry_edit">';
																echo '<span><a>編集</a></span>';
															echo '</td>';
															echo '<td id="wixfile_entry_delete' . $tmpIndex . '" class="wixfile_entry_delete">';
																echo '<span><a>削除</a></span>';
															echo '</td>';
															// echo '<td id="wixfile_keyword' . $tmpIndex . '" class="wixfile_keyword">';
															// 	echo '<span>' . $entrys[$tmpIndex]->keyword . '</span>';
															// echo '</td>';
															// echo '<td id="wixfile_target' . $tmpIndex . '" class="wixfile_target">';
															// 	echo '<span><a target="doc_page" href="' . esc_html($entrys[$tmpCount]->target) . '">'  
															// 			. mb_strimwidth(esc_html($entrys[$tmpCount]->target), 0, 30, '...') . '</a></span>';
															// echo '</td>';
															/*----この中削除ok-----*/
															echo '<td id="wixfile_keyword' . $tmpIndex . '" class="wixfile_keyword">';
																echo '<span>佐草友也</span>';
															echo '</td>';
															echo '<td id="wixfile_target' . $tmpIndex . '" class="wixfile_target">';
															echo '<span><a target="doc_page" href="http://www.db.ics.keio.ac.jp">http://www.db.ics.keio.ac.jp</a></span>';
															echo '</td>';
															/*---------*/
															if ( ($tmpIndex % 2) == 0 )
															echo '<tr id="wixfile_entry_hidden' . $tmpIndex . '" class="wixfile_even" style="display:none" >';
															else 
															echo '<tr id="wixfile_entry_hidden' . $tmpIndex . '" class="wixfile_odd" style="display:none" >';
																echo '<td colspan="1"></td>';
																echo '<td id="wixfile_entry_decide' . $tmpIndex . '" class="wixfile_entry_decide" colspan="2">';
																	echo '<span><a>決定</a></span>';
																echo '</td>';
																// echo '<td id="wixfile_keyword_edit' . $tmpIndex . '" class="wixfile_keyword_edit">';
																// 	echo '<input type="text" value="' . $entrys[$tmpIndex]->keyword . '">';
																// echo '</td>';
																// echo '<td id="wixfile_target_edit' . $tmpCount . '" class="wixfile_target_edit">';
																// 	echo '<input type="text" value="' . esc_html($entrys[$tmpIndex]->target) . '">';
																// echo '</td>';
																/*-----この中削除ok----*/
																echo '<td id="wixfile_keyword_edit' . $tmpIndex . '" class="wixfile_keyword_edit">';
																	echo '<input type="text" value="テスト">';
																echo '</td>';
																echo '<td id="wixfile_target_edit' . $tmpCount . '" class="wixfile_target_edit">';
																	echo '<input type="text" value="http://www.keio.ac.jp/index-jp.html">';
																echo '</td>';
																/*---------*/
															echo '</tr>';
														echo '</tr>';
															$tmpIndex++;
															$tmpCount++;
														} else {
															break;
														}
													}
												echo '</tbody>';
											echo '</table>';
										echo '</div>';
										$count++;
										$tmpEntryNum += 20;
									}
								}
							?>
						</div>	<!-- #second_wixfile_contents -->
						<div id="second_wixfile_entry_operation">
							<img class="selectallarrow" src="../wp-content/plugins/WIX/css/images/arrow_ltr.png" alt="with" selected>
							<input type="checkbox" id="second_wixfile_entry_allcheck" class="wixfile_entry_allcheck">
							<label for="second_wixfile_entry_allcheck">Check All</label>
							<span id="wixfile_each_operation">
								<i>With Selected: </i>
								<span id="wixfile_entry_batch_edit" class="wixfile_entry_batch_edit">
									<img src="../wp-content/plugins/WIX/css/images/b_edit.png" alt="change">
									<a>編集</a>
								</span>
								<span id="wixfile_entry_batch_delete" class="wixfile_entry_batch_delete">
									<img src="../wp-content/plugins/WIX/css/images/b_drop.png" alt="delete">
									<a>削除</a>
								</span>
							</span>
						</div>
						<div id="wixfile_entry_import">
							<!-- エントリ挿入(未実装) -->

						</div>
						<input type="submit" name="wixfile_settings" 
							value= "<?php echo esc_attr( __( 'WIXFIle Setting', 'wixfile_setting' ) ); ?>" 
							id="wixfile_settings_button" class="button button-primary" >
					</form>
				</div> <!-- #second_wixfile_operation_contents -->			
			</div> <!-- #second_wixfile -->
			<div id="iframe_exam"><a target="doc_page" href="http://www.db.ics.keio.ac.jp">リンクをクリックすると↓に映ります。</a></div>
			<div id="doc_list_iframe">
				<div id="doc_list_iframe_inline_div">
					<iframe id="doc_iframe" name="doc_page"></iframe>
					URL:
					<input type="text" id="doc_iframe_text">
				</div>
			</div> <!-- #doc_list_iframe -->

			<div id="second_newEntry" class="newEntry">
				<fieldset name="newEntry_form" form="newEntry_form">
					<legend>New Entry</legend>
					<input type="text" id="second_newKeyword_form" class="newKeyword_form" placeholder="リンクを貼りたい単語">
					<input type="text" id="second_newTarget_form" class="newTarget_form" placeholder="リンク先URL">
				</fieldset>
				<div id="second_entry_insert_result"></div>
				<input type="button" id="second_add_wixfile_entry" 
						value="データ追加" 
						class="button button-primary button-large" >
			</div>


		</div> <!-- #tab2 -->
	</div> <!-- #contents -->

</div> <!-- .wrap -->
<?php
}








function wix_detail_settings() {
?>
<?php echo '<h3>' . __( 'Created WIXFile List', 'created_wixfile_list' ) . '</h3>'; ?>


<?php
}













/*
<!-- 作成済みWIXファイル群 -->
<!-- <?php created_wixfiles(); ?> -->
<!-- Decide処理するかオプション -->
<!-- <?php decide_management(); ?> -->
<!-- WIXファイル作成 -->
<!-- <?php wix_admin_wixfile_settings(); ?> -->
*/

//Library登録済みWIXファイル一覧
function created_wixfiles() {
?>
<?php echo '<h3>' . __( 'Created WIXFile List', 'created_wixfile_list' ) . '</h3>'; ?>

	<div id="created_wixfiles">
<?php
	global $wids_filenames;

	if ( !empty($wids_filenames) ) {
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
/* 今使ってない(2015/10/19) */
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


/* 今使ってない(2015/11/05) */
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


?>