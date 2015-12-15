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

	add_action( 'admin_enqueue_scripts', 'wix_admin_settings_scripts' );
	
	add_action('admin_head-toplevel_page_wix-admin-settings', 'created_wixfile_info');

}

//管理画面でのWIX設定ページ
function wix_admin_settings(){
?>
<div class="wrap">
	<ul id="tab">
		<li class="selected"><a href="#tab1">設定一覧</a></li>
		<li><a href="#tab2">WIXファイル操作</a></li>
		<li><a href="#tab3">WIXファイル情報推薦</a></li>
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
						<strong>初期設定</strong>
						<fieldset name="init_option" form="init_option1">
							<legend>サーバホスト名</legend>						
							<input type="text" name="hostName" value="<?php echo get_option('wix_host_name'); ?>" readonly="readonly">
						</fieldset>
						<fieldset name="init_option" form="init_option2">
							<legend>オーサ名</legend>						
							<input type="text" name="authorName" value="<?php echo get_option('wix_author_name'); ?>" readonly="readonly">
						</fieldset>
						<fieldset name="init_option" form="init_option3">
							<legend>URLパターン : WIXファイル名</legend>
							<ol id="pattern_filename">
								<?php
									//作成済みWIXファイルのIDとファイル名の関係抽出
									created_wixfile_info();

									if ( empty($wids_filenames) ) {
										echo '<li>';
											echo '<input type="text" name="pattern[0]" value=' . esc_attr( '/*' ) . '> ';
											echo '<input type="text" name="filename[0]" value=' . esc_attr( $hostName ) . '> ';
										echo '</li>';

									} else {
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
									}
								?>		
							</ol>
							<input type="button" id="add_patternFile" 
									value= "<?php echo esc_attr( __( 'Form Add', 'wix_patternFile_adding' ) ); ?>" 
									class="button button-primary button-small" >
						</fieldset>
						<fieldset name="init_option" form="init_option4">
							<legend>リンク作成最小文字数</legend>
							<label>
								1<input type="range" name="minLength" min="1" max="5" value="<?php echo get_option( 'minLength' ); ?>" list="exlist">5
							</label>
							<datalist id="exlist">
							<option value="1"></option>
							<option value="2"></option>
							<option value="3"></option>
							<option value="4"></option>
							<option value="5"></option>
							</datalist>
						</fieldset>
					</div> <!-- #init_settings -->

				<?php } else { ?> <!-- PatternFileがなければ -->
				<form id="wix_settings_form" method="post" action="">
						<?php 
							wp_nonce_field( 'my-nonce-key', 'nonce_init_settings' ); 
							global $pm;
							$hostName = $pm -> requestURL_part( PHP_URL_HOST );
						?>
					<div id="init_settings">
						<strong>初期設定</strong>
						<fieldset name="init_option" form="init_option1">
							<legend>サーバホスト名</legend>						
							<input type="text" name="hostName" placeholder="<?php echo $hostName ?>">
						</fieldset>
						<fieldset name="init_option" form="init_option2">
							<legend>オーサ名</legend>						
							<input type="text" name="authorName" value="">
						</fieldset>
						<fieldset name="init_option" form="init_option3">
							<legend>URLパターン : WIXファイル名</legend>
							<ol id="pattern_filename">
								<li>
									<input type="text" name="pattern[0]" placeholder="/*">
									<input type="text" name="filename[0]" placeholder="your host name">
								</li>
							</ol>
							<input type="button" id="add_patternFile" 
								value= "<?php echo esc_attr( __( 'Form Add', 'wix_patternFile_adding' ) ); ?>" 
								class="button button-primary button" >
						<fieldset name="init_option" form="init_option4">
							<legend>リンク作成最小文字数</legend>
							<label>
									1<input type="range" name="minLength" min="1" max="5" value="3" list="exlist">5
								</label>
								<datalist id="exlist">
								<option value="1"></option>
								<option value="2"></option>
								<option value="3"></option>
								<option value="4"></option>
								<option value="5"></option>
								</datalist>
						</fieldset>
					</div> <!-- #init_settings -->
				<?php } ?>
				
					<div id="option_settings">
						<div id="wixfile_option_settings" class="contents_option">
							<strong>WIXファイル設定</strong>
							<fieldset name="wixfile_option" form="wixfile_option1">
								<legend>自動生成</legend>
								<?php 
								if ( get_option('wixfile_autocreate') == 'true' ) {
								?>
								<input type="radio" name="wixfile_autocreate" id="wixfile_autocreate_on" value="true" checked>
								<label for="wixfile_autocreate_on" class="switch-on">YES</label>
								<input type="radio" name="wixfile_autocreate" id="wixfile_autocreate_off" value="false">
								<label for="wixfile_autocreate_off" class="switch-off">No</label>
									<?php
									if ( get_option('wixfile_autocreate_wordtype') == 'feature_word' ) {
									?>
									<div id="wixfile_autocreate_setting">
										<br><br>
										<fieldset name="wixfile_autocreate_option" form="wixfile_autocreate_option1">
											<input type="radio" name="wixfile_autocreate_wordtype" id="wixfile_autocreate_wordtype_feature" value="feature_word" checked>
											<label for="wixfile_autocreate_wordtype_feature" class="wixfile_autocreate_option">特徴語</label>
											<input type="radio" name="wixfile_autocreate_wordtype" id="wixfile_autocreate_wordtype_freq" value="freq_word">
											<label for="wixfile_autocreate_wordtype_freq" class="wixfile_autocreate_option">ページ内頻出語</label>
										</fieldset>
									</div>
									<?php
									} else if ( get_option('wixfile_autocreate_wordtype') == 'freq_word' ) {
									?>
									<div id="wixfile_autocreate_setting">
										<br><br>
										<fieldset name="wixfile_autocreate_option" form="wixfile_autocreate_option1">
											<input type="radio" name="wixfile_autocreate_wordtype" id="wixfile_autocreate_wordtype_feature" value="feature_word">
											<label for="wixfile_autocreate_wordtype_feature" class="wixfile_autocreate_option">特徴語</label>
											<input type="radio" name="wixfile_autocreate_wordtype" id="wixfile_autocreate_wordtype_freq" value="freq_word" checked>
											<label for="wixfile_autocreate_wordtype_freq" class="wixfile_autocreate_option">ページ内頻出語</label>
										</fieldset>
									</div>
									<?php
									} else {
									?>
									<div id="wixfile_autocreate_setting">
										<br><br>
										<fieldset name="wixfile_autocreate_option" form="wixfile_autocreate_option1">
											<input type="radio" name="wixfile_autocreate_wordtype" id="wixfile_autocreate_wordtype_feature" value="feature_word">
											<label for="wixfile_autocreate_wordtype_feature" class="wixfile_autocreate_option">特徴語</label>
											<input type="radio" name="wixfile_autocreate_wordtype" id="wixfile_autocreate_wordtype_freq" value="freq_word">
											<label for="wixfile_autocreate_wordtype_freq" class="wixfile_autocreate_option">ページ内頻出語</label>
										</fieldset>
									</div>
									<?php
									}
									?>
								<?php
								} else {
								?>
								<input type="radio" name="wixfile_autocreate" id="wixfile_autocreate_on" value="true">
								<label for="wixfile_autocreate_on" class="switch-on">YES</label>
								<input type="radio" name="wixfile_autocreate" id="wixfile_autocreate_off" value="false" checked>
								<label for="wixfile_autocreate_off" class="switch-off">No</label>
								<div id="wixfile_autocreate_setting" style="display: none">
									<br><br>
									<fieldset name="wixfile_autocreate_option" form="wixfile_autocreate_option1">
										<input type="radio" name="wixfile_autocreate_wordtype" id="wixfile_autocreate_wordtype_feature" value="feature_word">
										<label for="wixfile_autocreate_wordtype_feature" class="wixfile_autocreate_option">特徴語</label>
										<input type="radio" name="wixfile_autocreate_wordtype" id="wixfile_autocreate_wordtype_freq" value="freq_word">
										<label for="wixfile_autocreate_wordtype_freq" class="wixfile_autocreate_option">ページ内頻出語</label>
									</fieldset>
								</div>
								<?php
								}
								?>
							</fieldset>
							<fieldset name="wixfile_option" form="wixfile_option2">
								<legend>ページ作成画面における更新操作</legend>
								<?php 
								if ( get_option('wixfile_manualupdate') == 'true' ) {
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
						<div id="decidefile_option_settings" class="contents_option">
							<strong>リンク先詳細設定</strong>
							<fieldset name="decidefile_option" form="decidefile_option1">
								<legend>詳細設定適用</legend>
								<?php 
								if ( get_option('decidefile_apply') == 'true' ) {
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
							<fieldset name="decidefile_option" form="decidefile_option2">
								<legend>自動詳細設定</legend>
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
								<legend>ページ作成画面における更新操作</legend>
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
						<div id="other_option_settings" class="contents_option">
							<strong>その他</strong>
							<fieldset name="other_option" form="other_option1">
								<legend>形態素解析ツール</legend>
								<?php
								if ( get_option('morphological_analysis') == false ) {
								?>
								<input type="radio" name="morphological_analysis" id="morphological_analysis_yahoo" value="Yahoo">
								<label for="morphological_analysis_yahoo" class="other_option">Yahoo形態素解析</label	><br>
								<input type="radio" name="morphological_analysis" id="morphological_analysis_mecab" value="Mecab">
								<label for="morphological_analysis_mecab" class="other_option">Mecab(インストール要)</label>
								<?php
								} else if ( get_option('morphological_analysis') == 'Yahoo' ) {
								?>
								<input type="radio" name="morphological_analysis" id="morphological_analysis_yahoo" value="Yahoo" checked="">
								<label for="morphological_analysis_yahoo" class="other_option">Yahoo形態素解析</label>
								<input type="text" name="yahoo_id" id="yahoo_id" placeholder="Yahoo Develper ID" value="<?php echo get_option('yahoo_id') ?>">
								<br>
								<input type="radio" name="morphological_analysis" id="morphological_analysis_mecab" value="Mecab">
								<label for="morphological_analysis_mecab" class="other_option">Mecab(インストール要)</label>
								<?php
								} else if ( get_option('morphological_analysis') == 'Mecab' ) {
								?>
								<input type="radio" name="morphological_analysis" id="morphological_analysis_yahoo" value="Yahoo">
								<label for="morphological_analysis_yahoo" class="other_option">Yahoo形態素解析</label><br>
								<input type="radio" name="morphological_analysis" id="morphological_analysis_mecab" value="Mecab" checked>
								<label for="morphological_analysis_mecab" class="other_option">Mecab(インストール要)</label>
								<?php
								}
								?>
								<br>
								<input type="button" id="morphological_analysis_reset" 
										value= "<?php echo esc_attr( __( 'Setting Reset', 'cotents_option_reset' ) ); ?>" 
										class="button button-primary button-small" >
							</fieldset>
							<fieldset name="other_option" form="other_option2">
								<legend>自動生成・推薦支援</legend>
								<?php
								if ( get_option('recommend_support') == false ) {
								?>
								<input type="radio" name="recommend_support" id="recommend_support_docsim" value="ドキュメント類似度">
								<label for="recommend_support_docsim" class="other_option">ドキュメント類似度</label><br>
								<input type="radio" name="recommend_support" id="recommend_support_google" value="Google検索">
								<label for="recommend_support_google" class="other_option">Google検索(アカウント要)</label>
								<?php
								} else if ( get_option('recommend_support') == 'ドキュメント類似度' ) {
								?>
								<input type="radio" name="recommend_support" id="recommend_support_docsim" value="ドキュメント類似度" checked>
								<label for="recommend_support_docsim" class="other_option">ドキュメント類似度</label><br>
								<input type="radio" name="recommend_support" id="recommend_support_google" value="Google検索">
								<label for="recommend_support_google" class="other_option">Google検索(アカウント要)</label>
								<?php
								} else if ( get_option('recommend_support') == 'Google検索' ) {
								?>
								<input type="radio" name="recommend_support" id="recommend_support_docsim" value="ドキュメント類似度">
								<label for="recommend_support_docsim" class="other_option">ドキュメント類似度</label><br>
								<input type="radio" name="recommend_support" id="recommend_support_google" value="Google検索" checked>
								<label for="recommend_support_google" class="other_option">Google検索(アカウント要)</label>
								<input type="text" name="google_api_key" id="google_api_key" placeholder="Google API Key" value="<?php echo get_option('google_api_key') ?>">
								<input type="text" name="google_cx" id="google_cx" placeholder="Custom search engine ID" value="<?php echo get_option('google_cx') ?>">
								<?php
								}
								?>
								<br>
								<input type="button" id="recommend_support_reset" 
										value= "<?php echo esc_attr( __( 'Setting Reset', 'cotents_option_reset' ) ); ?>" 
										class="button button-primary button-small" >
							</fieldset>
						</div>
					</div> <!-- #option_settings -->
					<?php
						if ( file_exists( PatternFile ) && is_readable( PatternFile ) ) {
					?>
					<input type="submit" name="wix_settings" 
						value= "<?php echo esc_attr( __( 'WIX Settings', 'wix_settings' ) ); ?>" 
						id="wix_settings_button" class="button button-primary" >
					<?php } else { ?>
					<input type="submit" name="wix_init_settings" 
						value= "<?php echo esc_attr( __( 'WIX Initial Settings', 'wix_initial_settings' ) ); ?>" 
						id="wix_init_settings_button" class="button button-primary" >
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
													echo '<th id="wixfile_table_heading_blank" colspan="3"></th>';
													echo '<th id="wixfile_table_heading_keyword">単語</th>';
													echo '<th id="wixfile_table_heading_target">リンク先URL</th>';
												echo '</tr>';
											echo '</thead>';
											echo '<tbody>';

												$tmpCount = 0;
												if ( $entryNum == 0 ) {
													echo '<tr id="wixfile_ex_entry' . $tmpCount . '" class="wixfile_even">';
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
															echo '<span>まず右下の欄に入力してください</span>';
														echo '</td>';
														echo '<td id="wixfile_target' . $tmpCount . '" class="wixfile_target">';
															echo '<span><a target="target_page" href=""></a></span>';
														echo '</td>';
													echo '</tr>';
													echo '<tr id="wixfile_ex_entry_hidden' . $tmpCount . '" class="wixfile_even" style="display:none" >';
														echo '<td colspan="1"></td>';
														echo '<td id="wixfile_entry_decide' . $tmpCount . '" class="wixfile_entry_decide" colspan="2">';
															echo '<span><a>決定</a></span>';
														echo '</td>';
														echo '<td id="wixfile_keyword_edit' . $tmpCount . '" class="wixfile_keyword_edit">';
															echo '<input type="text" value="まず右下の欄に入力してください">';
														echo '</td>';
														echo '<td id="wixfile_target_edit' . $tmpCount . '" class="wixfile_target_edit">';
															echo '<input type="text" value="">';
														echo '</td>';
													echo '</tr>';
												} else {
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
														echo '<th colspan="3"></th><th>単語</th><th>リンク先URL</th>';
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
															'<td id="wixfile_keyword' . $tmpIndex . '" class="wixfile_keyword">';
																echo '<span>' . $entrys[$tmpIndex]->keyword . '</span>';
															echo '</td>';
															echo '<td id="wixfile_target' . $tmpIndex . '" class="wixfile_target">';
																echo '<span><a target="target_page" href="' . esc_html($entrys[$tmpCount]->target) . '">'  
																		. mb_strimwidth(esc_html($entrys[$tmpCount]->target), 0, 30, '...') . '</a></span>';
															echo '</td>';
														/*----この中削除ok-----*/
															// echo '<td id="wixfile_keyword' . $tmpIndex . '" class="wixfile_keyword">';
															// 	echo '<span>佐草友也</span>';
															// echo '</td>';
															// echo '<td id="wixfile_target' . $tmpIndex . '" class="wixfile_target">';
															// echo '<span><a target="target_page" href="http://www.db.ics.keio.ac.jp">http://www.db.ics.keio.ac.jp</a></span>';
															// echo '</td>';
														/*---------*/
															if ( ($tmpIndex % 2) == 0 )
															echo '<tr id="wixfile_entry_hidden' . $tmpIndex . '" class="wixfile_even" style="display:none" >';
															else 
															echo '<tr id="wixfile_entry_hidden' . $tmpIndex . '" class="wixfile_odd" style="display:none" >';
																echo '<td colspan="1"></td>';
																echo '<td id="wixfile_entry_decide' . $tmpIndex . '" class="wixfile_entry_decide" colspan="2">';
																	echo '<span><a>決定</a></span>';
																echo '</td>';
																echo '<td id="wixfile_keyword_edit' . $tmpIndex . '" class="wixfile_keyword_edit">';
																	echo '<input type="text" value="' . $entrys[$tmpIndex]->keyword . '">';
																echo '</td>';
																echo '<td id="wixfile_target_edit' . $tmpCount . '" class="wixfile_target_edit">';
																	echo '<input type="text" value="' . esc_html($entrys[$tmpIndex]->target) . '">';
																echo '</td>';
														/*-----この中削除ok----*/
																// echo '<td id="wixfile_keyword_edit' . $tmpIndex . '" class="wixfile_keyword_edit">';
																// 	echo '<input type="text" value="テスト">';
																// echo '</td>';
																// echo '<td id="wixfile_target_edit' . $tmpCount . '" class="wixfile_target_edit">';
																// 	echo '<input type="text" value="http://www.keio.ac.jp/index-jp.html">';
																// echo '</td>';
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
						<legend>新規追加フォーム</legend>
						<input type="text" id="newKeyword_form" class="newKeyword_form" placeholder="リンクを貼りたい単語">
						<input type="text" id="newTarget_form" class="newTarget_form" placeholder="リンク先URL">
					</fieldset>
					<div id="entry_insert_result"></div>
					<input type="button" id="add_wixfile_entry" 
							value="データ追加" 
							class="button button-primary button-small" >
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
						$sql = 'SELECT DISTINCT post_type FROM ' . $wpdb->posts . ' WHERE post_type!="revision" AND (post_type="post" OR post_type="page")';
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
			<div id="second_wixfile" class="wixfile">
				<ul id="second_wixfile_tab" class="wixfile_tab">
					<?php
						echo '<li class="selected"><a href="#second_wixfile_tab1">タブ1</a></li>';
						if ( $entryNum > 20 ) {
							$count = 2;
							while ( $entryNum > 20 ) {
								echo '<li><a href="#second_wixfile_tab' . $count . '">タブ' . $count . '</a></li>';
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
													echo '<th id="wixfile_table_heading_blank" colspan="3"></th>';
													echo '<th id="wixfile_table_heading_keyword">単語</th>';
													echo '<th id="wixfile_table_heading_target">リンク先URL</th>';
												echo '</tr>';
											echo '</thead>';
											echo '<tbody>';

												$tmpCount = 0;
												if ( $entryNum == 0 ) {
													echo '<tr id="wixfile_ex_entry' . $tmpCount . '" class="wixfile_even">';
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
															echo '<span>まず右下の欄に入力してください</span>';
														echo '</td>';
														echo '<td id="wixfile_target' . $tmpCount . '" class="wixfile_target">';
															echo '<span><a target="target_page" href=""></a></span>';
														echo '</td>';
													echo '</tr>';
													echo '<tr id="wixfile_ex_entry_hidden' . $tmpCount . '" class="wixfile_even" style="display:none" >';
														echo '<td colspan="1"></td>';
														echo '<td id="wixfile_entry_decide' . $tmpCount . '" class="wixfile_entry_decide" colspan="2">';
															echo '<span><a>決定</a></span>';
														echo '</td>';
														echo '<td id="wixfile_keyword_edit' . $tmpCount . '" class="wixfile_keyword_edit">';
															echo '<input type="text" value="まず右下の欄に入力してください">';
														echo '</td>';
														echo '<td id="wixfile_target_edit' . $tmpCount . '" class="wixfile_target_edit">';
															echo '<input type="text" value="">';
														echo '</td>';
													echo '</tr>';
												} else {
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
														echo '<th colspan="3"></th><th>単語</th><th>リンク先URL</th>';
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
															echo '<td id="wixfile_keyword' . $tmpIndex . '" class="wixfile_keyword">';
																echo '<span>' . $entrys[$tmpIndex]->keyword . '</span>';
															echo '</td>';
															echo '<td id="wixfile_target' . $tmpIndex . '" class="wixfile_target">';
																echo '<span><a target="doc_page" href="' . esc_html($entrys[$tmpCount]->target) . '">'  
																		. mb_strimwidth(esc_html($entrys[$tmpCount]->target), 0, 30, '...') . '</a></span>';
															echo '</td>';
															/*----この中削除ok-----*/
															// echo '<td id="wixfile_keyword' . $tmpIndex . '" class="wixfile_keyword">';
															// 	echo '<span>佐草友也</span>';
															// echo '</td>';
															// echo '<td id="wixfile_target' . $tmpIndex . '" class="wixfile_target">';
															// echo '<span><a target="doc_page" href="http://www.db.ics.keio.ac.jp">http://www.db.ics.keio.ac.jp</a></span>';
															// echo '</td>';
															/*---------*/
															if ( ($tmpIndex % 2) == 0 )
															echo '<tr id="wixfile_entry_hidden' . $tmpIndex . '" class="wixfile_even" style="display:none" >';
															else 
															echo '<tr id="wixfile_entry_hidden' . $tmpIndex . '" class="wixfile_odd" style="display:none" >';
																echo '<td colspan="1"></td>';
																echo '<td id="wixfile_entry_decide' . $tmpIndex . '" class="wixfile_entry_decide" colspan="2">';
																	echo '<span><a>決定</a></span>';
																echo '</td>';
																echo '<td id="wixfile_keyword_edit' . $tmpIndex . '" class="wixfile_keyword_edit">';
																	echo '<input type="text" value="' . $entrys[$tmpIndex]->keyword . '">';
																echo '</td>';
																echo '<td id="wixfile_target_edit' . $tmpCount . '" class="wixfile_target_edit">';
																	echo '<input type="text" value="' . esc_html($entrys[$tmpIndex]->target) . '">';
																echo '</td>';
																/*-----この中削除ok----*/
																// echo '<td id="wixfile_keyword_edit' . $tmpIndex . '" class="wixfile_keyword_edit">';
																// 	echo '<input type="text" value="テスト">';
																// echo '</td>';
																// echo '<td id="wixfile_target_edit' . $tmpCount . '" class="wixfile_target_edit">';
																// 	echo '<input type="text" value="http://www.keio.ac.jp/index-jp.html">';
																// echo '</td>';
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

			<!-- <div id="default_entry_decision">

			</div> --> 
			<!-- #default_entry_decision -->


			<div id="iframe_exam"><a target="doc_page" href="http://www.db.ics.keio.ac.jp">リンクをクリックすると↓に映ります。</a></div>
			<div id="doc_list_iframe">
				<div id="doc_list_iframe_inline_div">
					<iframe id="doc_iframe" name="doc_page"></iframe>
					<!-- URL:
					<input type="text" id="doc_iframe_text"> -->
				</div>
			</div> <!-- #doc_list_iframe -->

			<div id="second_newEntry" class="newEntry">
				<fieldset name="newEntry_form" form="newEntry_form">
					<legend>新規追加フォーム</legend>
					<input type="text" id="second_newKeyword_form" class="newKeyword_form" placeholder="リンクを貼りたい単語">
					<input type="text" id="second_newTarget_form" class="newTarget_form" placeholder="リンク先URL(入力してEnキー押すと↑に出力)">
				</fieldset>
				<div id="second_entry_insert_result"></div>
				<input type="button" id="second_add_wixfile_entry" 
						value="データ追加" 
						class="button button-primary button-small" >
			</div>


		</div> <!-- #tab2 -->
<?php
/**
			↓タブ３
**/
?>
		<div class="tabbox" id="tab3">
			<div id="third_createdDoc">
				<ul id="third_doc_tab">
					<li class="selected"><a href="#third_doc_tab1">固定ページ</a></li>
					<li><a href="#third_doc_tab2">投稿</a></li>
				</ul>
				<div id="third_doc_list">
					<?php 
						global $wpdb;
						$sql = 'SELECT DISTINCT post_type FROM ' . $wpdb->posts . ' WHERE post_type!="revision" AND (post_type="post" OR post_type="page")';
						$post_typeObj = $wpdb->get_results($sql);
						if ( count($post_typeObj) != 0 ) {
							foreach ($post_typeObj as $index => $value) {
								$type = $value->post_type;
								$sql = 'SELECT ID, post_title, guid FROM ' . $wpdb->posts . 
										' WHERE post_type="' . $type . '" AND post_status!="inherit" and post_status!="trash" and post_status!="auto-save" and post_status!="auto-draft" order by post_type, ID asc';
								$documentsInfo = $wpdb->get_results($sql);

								if ( count($documentsInfo) != 0 ) {
									if ( $type == 'page' ) {
										echo '<div id="third_doc_tab1" class="third_doc_tabbox">';
											echo '<table id="doc_table1" class="doc_table">';
									} else if ( $type == 'post' ) {
										echo '<div id="third_doc_tab2" class="third_doc_tabbox">';
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
													echo '<tr><td><a id=' . $id . ' class="third_doc_page" target="third_doc_page" href="' . $url . '">' . $post_title . '</a></td></tr>';
												}
												echo '</tbody>';
											echo '</table>';
										echo '</div>'; //.third_doc_tabbox
								}
							}

						}
					?>
				</div> <!-- #third_doc_list -->
			</div> <!-- #third_createdDoc -->
			
			<div id="recommend_contents">
				<ul id="recommend_entrys_tab">
					<li class="selected"><a href="#recommend_entrys_tab1">頻出語</a></li>
					<li><a href="#recommend_entrys_tab2">ページ内頻出順</a></li>
					<li><a href="#recommend_entrys_tab3">サイト内頻出順</a></li>
					<li><a href="#recommend_entrys_tab4">特徴的単語</a></li>
				</ul>
				<div id="recommend_entrys">
					<table id="recommend_entrys_table">
						<thead>
							<tr>
								<th id="thead_keywords">単語</th>
								<th id="thead_targets">リンク先URL【推薦ランキング】</th>
							</tr>
						</thead>
						<tbody>
							<tr>
								<td id="recommend_keywords">
									<div id="recommend_entrys_tab1" class="recommend_entrys_tabbox">

									</div>
									<div id="recommend_entrys_tab2" class="recommend_entrys_tabbox">

									</div>
									<div id="recommend_entrys_tab3" class="recommend_entrys_tabbox">

									</div>
									<div id="recommend_entrys_tab4" class="recommend_entrys_tabbox">

									</div>
								</td>
								<td id="recommend_targets">
									<div id="recommend_targets_div">

									</div>
								</td>
							</tr>
						</tbody>
					</table>
				</div> <!-- #recommend_entrys -->
				<input type="button" id="add_wixfile" 
							value= "<?php echo esc_attr( __( 'Add WIXFile', 'wixfile_adding' ) ); ?>" 
							class="button button-primary button-large" >
			</div> <!-- #reccomend_contents -->

			<div id="third_doc_list_iframe">
				<div id="third_doc_list_iframe_inline_div">
					<iframe id="third_doc_iframe" name="third_doc_page"></iframe>
				</div>
			</div> <!-- #third_doc_list_iframe -->

			<div id="existing_wixfile_entrys">

			</div> <!-- #existing_wixfile_entrys -->
		</div> <!-- #tab3 -->

	</div> <!-- #contents -->

</div> <!-- .wrap -->
<?php
}

function wix_detail_settings() {
?>
<div class="wrap">
	<ul id="tab">
		<li class="selected"><a href="#tab1">リンク先詳細設定</a></li>
		<li><a href="#tab2">詳細設定履歴</a></li>
	</ul>
	<div id="contents">
		<div id="tab1" class="tabbox">
			<div id="createdDoc">
				<ul id="doc_tab">
					<li class="selected"><a href="#doc_tab1">固定ページ</a></li>
					<li><a href="#doc_tab2">投稿</a></li>
				</ul>
				<div id="doc_list">
					<?php 
						global $wpdb;
						$sql = 'SELECT DISTINCT post_type FROM ' . $wpdb->posts . ' WHERE post_type!="revision" AND (post_type="post" OR post_type="page")';
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
													echo '<tr><td><a id=' . $id . ' class="doc_page" href="' . $url . '">' . $post_title . '</a></td></tr>';
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

			<div id="wixfile_entry_decide_contents">
				<ul id="decide_entrys_tab">
					<li class="selected"><a href="#decide_entrys_tab1">Default設定</a></li>
					<li><a href="#decide_entrys_tab2">詳細設定</a></li>
				</ul>
				<div id="decide_entrys">
					<table id="decide_entrys_table">
						<tbody>
							<tr id="tbody_tr">
								<td id="tbody_td">
									<div id="decide_entrys_tab1" class="decide_entrys_tabbox">
										<table id="decide_entrys_tab1_table">
											<thead>
												<tr class="thead_tr">
													<th class="thead_keywords">単語</th>
													<th class="thead_targets">Defaultリンク先URL選択</th>
												</tr>
											</thead>

										</table>

									</div>
									<div id="decide_entrys_tab2" class="decide_entrys_tabbox">
										<table id="decide_entrys_tab2_table">
											<thead>
												<tr class="thead_tr">
													<th class="thead_keywords">単語</th>
													<th class="thead_targets">詳細リンク先URL選択</th>
												</tr>
											</thead>

										</table>
									</div>
								</td>
							</tr>
						</tbody>
					</table> <!-- #decide_entrys_table -->
				</div> <!-- #decide_entrys -->

				<input type="button" id="add_decidefile" 
							value= "<?php echo esc_attr( __( 'Save', 'decidefile_adding' ) ); ?>" 
							class="button button-primary button-large" >
			</div> <!-- #wixfile_entry_decide_contents -->


			<div id="decide_iframe">
				<div id="decide_iframe_inline_div">
					<iframe id="doc_iframe" name="doc_page"></iframe>
				</div>
			</div> <!-- #decide_iframe -->

			<div id="existing_latest_decidefile">

			</div> <!-- #existing_latest_decidefile -->

			<form id="default_detail_decideForm" class="default_detail_settings_form" method="post" action="">
				<?php wp_nonce_field( 'my-nonce-key', 'nonce_default_detail_settings' ); ?>
				<input type="submit" name="default_detail_settings" class="default_detail_decideButton" value="Default・詳細設定保存">
			</form>

		</div> <!-- #tab1 -->

		<div id="tab2" class="tabbox">
		<div id="created_DecidefileDoc">
				<ul id="decidefileDoc_tab">
					<li class="selected"><a href="#decidefileDoc_tab1">固定ページ</a></li>
					<li><a href="#decidefileDoc_tab2">投稿</a></li>
				</ul>
				<div id="decidefileDoc_list">
					<?php 
						global $wpdb;
						$sql = 'SELECT DISTINCT post_type FROM ' . $wpdb->posts . ' WHERE post_type!="revision" AND (post_type="post" OR post_type="page")';
						$post_typeObj = $wpdb->get_results($sql);
						if ( count($post_typeObj) != 0 ) {
							$wix_decidefile_index = $wpdb->prefix . 'wix_decidefile_index';

							foreach ($post_typeObj as $index => $value) {
								$type = $value->post_type;
								$sql = 'SELECT ID, post_title, guid FROM ' . $wpdb->posts . ' wp, ' . 
										$wix_decidefile_index . ' wdi WHERE post_type="' . $type . '" AND wp.ID=wdi.doc_id GROUP BY ID ORDER BY post_type, ID ASC';
								$decidefileDocInfo = $wpdb->get_results($sql);

								if ( count($decidefileDocInfo) != 0 ) {
									if ( $type == 'page' ) {
										echo '<div id="decidefileDoc_tab1" class="decidefileDoc_tabbox">';
											echo '<table id="decidefileDoc_table1" class="decidefileDoc_table">';
									} else if ( $type == 'post' ) {
										echo '<div id="decidefileDoc_tab2" class="decidefileDoc_tabbox">';
											echo '<table id="decidefileDoc_table2" class="decidefileDoc_table">';
									}
										echo '<thead>';
													echo '<tr class="decidefileDoc_table_heading">';
														echo '<th>タイトル</th>';
													echo '</tr>';
												echo '</thead>';
												echo '<tbody>';
												foreach ($decidefileDocInfo as $index => $value) {
													$post_title = $value->post_title;
													$url = $value->guid;
													$id = $value->ID;
													echo '<tr><td><a id=' . $id . ' class="decidefileDoc_page" target="decidefileDoc_page" href="' . $url . '">' . $post_title . '</a></td></tr>';
												}
												echo '</tbody>';
											echo '</table>';
										echo '</div>'; //.decidefileDoc_tabbox
								}
							}
						}
					?>
				</div> <!-- #decidefileDoc_list -->
			</div> <!-- #created_DecidefileDoc -->

			<div id="decidefile_latest_contents">
				<table id="decidefile_latest_table">
					<thead>
						<tr id="decidefile_latest_table_thead_tr">
							<th id="decidefile_latest_table_thead_th">
								最新リンク先詳細設定
							</th>
						</tr>
					</thead>
					<tbody>
						<tr id="decidefile_latest_table_tbody_tr">
							<td id="decidefile_latest_table_tbody_td">

							</td>
						</tr>
					</tbody>
				</table> <!-- #"decidefile_latest_table -->
			</div> <!-- #decidefile_latest_contents -->

			<div id="decidefile_iframe">
				<div id="decidefile_iframe_inline_div">
					<iframe id="decidefileDoc_iframe" name="decidefileDoc_page"></iframe>
				</div>
			</div> <!-- #decidefile_iframe -->

			<div id="decidefile_history_contents">

			</div> <!-- #decidefile_history_contents -->

			<form id="update_decidefileForm" class="decidefile_settings_form" method="post" action="">
				<?php wp_nonce_field( 'my-nonce-key', 'nonce_decidefile_settings' ); ?>
				<input type="submit" name="decidefile_settings" class="update_decidefileButton" value="Decideファイル更新">
			</form>
		</div>

	</div> <!-- #contents -->
</div> <!-- .wrap -->
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

?>