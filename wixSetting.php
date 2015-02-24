<?php

require_once( dirname( __FILE__ ) . '/patternMatching.php' );
$wids_filenames = array();

//--------------------------------------------------------------------------
//
//  プラグイン有効の際に行うオプションの追加
//
//--------------------------------------------------------------------------
register_activation_hook(__FILE__, 'wix_manual_decide_init' );
function wix_manual_decide_init() {
	update_option( 'manual_decideFlag', 'true' );
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
		__('WIX Settings', 'wix-settings'),
		__('WIX Settings', 'wix-settings'),
		'administrator',
		'wix-admin-settings',
		'wix_admin_settings' 
	);

	add_action( 'admin_enqueue_scripts', 'wix_admin_settings_scripts' );
	created_wixfile_info();
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
					value= "<?php echo esc_attr( __( 'Form Add', 'admin-wix-patternFile' ) ); ?>" 
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
							esc_attr( __( 'Delete', 'admin-wix-patternFile' ) ) . 
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
					value= "<?php echo esc_attr( __( 'Form Add', 'admin-wix-patternFile' ) ); ?>" 
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
<?php echo '<h4>' . __( 'Manual Decide', 'manual_decide' ) . '</h4>'; ?>

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
	$URL = 'http://wixdev.db.ics.keio.ac.jp/WIXAuthorEditor_0.0.1/GetCreatedWIXFileNames';
	
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




add_action( 'wp_ajax_wix_manual_decide', 'wix_manual_decide' );
add_action( 'wp_ajax_nopriv_wix_manual_decide', 'wix_manual_decide' );

//manual_decideFlagを返す
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


add_action( 'admin_init', 'wix_settings_core' );
function wix_settings_core() {
	global $wids_filenames;
	//nonceの値の✔
	if ( isset( $_POST['nonce_init_settings'] ) && $_POST['nonce_init_settings'] ) {

		if ( check_admin_referer( 'my-nonce-key', 'nonce_init_settings' ) ) {

			$e = new WP_Error();

			if ( isset( $_POST['wix_init_settings'] ) && $_POST['wix_init_settings'] ) {

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

					set_transient( 'wix_init_settings', '初期設定完了しました', 10 );
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
	<?php endif; ?>
<?php
}


  



// function custom_gettext( $translated_text, $text, $domain ) {
  	
//  	switch ( $text ) {
//  		case 'Dashboard':
//  			$translated_text = __('Home',$domain);
//  			break;
//  	}

// 	return $translated_text;
// }
// add_filter( 'gettext', 'custom_gettext', 20, 3 );



// function patternFileContents() {
// 	global $pm;
// 	$patternFile = array();

// 	try {
// 		$file = fopen( PatternFile, 'r' );
 
// 		/* ファイルを1行ずつ出力 */
// 		if( $file ){
// 			$host_name = '';
// 			$pattern_array = array();
// 			$flag = false;

// 			while ( $line = fgets($file) ) {
// 				if ( $pm -> startsWith( $line, '<' ) ) {
// 					if ( $flag == true ) {
// 						$patternFile += array( $host_name => $pattern_array );
// 						$pattern_array = array();
// 					}
// 					$host_name = $line;
// 				} else {
// 					$flag = true;
// 					array_push( $pattern_array, $line );
// 				}
// 			}
// 			$patternFile += array( $host_name => $pattern_array );
// 		}
// 	} catch ( Exception $e ) {
// 		echo '捕捉した例外: ',  $e -> getMessage(), "\n";
// 	} finally {
// 		fclose( $file );
// 	}

// 	return $patternFile;
// }

?>