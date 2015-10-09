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


?>