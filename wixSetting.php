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
	echo '<h2>' . __( 'WIX PatternFile Options', 'wix_patternfile_options' ) . '</h2>';

	if ( file_exists( PatternFile ) && is_readable( PatternFile ) ) {
		global $pm;
		$patternFile = $pm -> returnCandidates();
		$hostName = $pm -> requestURL_part( PHP_URL_HOST );
?>
	<div id="patternFile_options">
		<form id="patternFile_form" method="post" action="">
			<?php wp_nonce_field( 'my-nonce-key', 'nonce_patternFile' ); ?>

			<p>
				パターンファイルの更新: <input type="submit" name="update_patternFile" 
										value= "<?php echo esc_attr( __( 'Update', 'admin-wix-patternFile' ) ); ?>" 
										class="button button-primary button-large" >
				
				フォームの追加: <input type="button" id="add_patternFile" 
				value= "<?php echo esc_attr( __( 'Add', 'admin-wix-patternFile' ) ); ?>" 
				class="button button-primary button-large" >
			</p>	

			<p>
				<input type="text" name="hostName" value="<?php echo $pm -> matchingHostName; ?>">
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

	<div>
		<form id="patternFile_form" method="post" action="">
			<?php wp_nonce_field( 'my-nonce-key', 'nonce_patternFile' ); 
				global $pm;
				$hostName = $pm -> requestURL_part( PHP_URL_HOST );
			?>

			<p>
				パターンファイルの作成: <input type="submit" name="update_patternFile" 
										value= "<?php echo esc_attr( __( 'Create', 'admin-wix-patternFile' ) ); ?>" 
										class="button button-primary button-large" >
				
				フォームの追加: <input type="button" id="add_patternFile" 
				value= "<?php echo esc_attr( __( 'Add', 'admin-wix-patternFile' ) ); ?>" 
				class="button button-primary button-large" >
			</p>	

			<p>
				サーバホスト名: 
				<input type="text" name="hostName" value="<?php echo $hostName ?>">
			</p>

			<ol id="pattern_filename">
				<li>
					<input type="text" name="pattern[0]" placeholder="/test.html">
					<input type="text" name="filename[0]" placeholder="128">
				</li>
			</ol>
		</form>
	</div>

<?php } ?>


<?php echo '<h2>' . __( 'Created WIXFile List', 'created_wixfile_list' ) . '</h2>'; ?>

	<div id="created_wixfiles">
<?php
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

<?php decide_management(); ?>

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



//decide_management欄
function decide_management() {
?>
<?php echo '<h2>' . __( 'WIX Decide Management', 'wix_decide_management' ) . '</h2>'; ?>
<?php echo '<h3>' . __( 'Manual Decide', 'manual_decide' ) . '</h3>'; ?>

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







//パターンファイルの更新
add_action( 'admin_init', 'update_patternFile' );

function update_patternFile() {
	global $wids_filenames;
	//nonceの値の✔
	if ( isset( $_POST['nonce_patternFile'] ) && $_POST['nonce_patternFile'] ) {

		if ( check_admin_referer( 'my-nonce-key', 'nonce_patternFile' ) ) {

			$e = new WP_Error();

			if ( isset( $_POST['update_patternFile'] ) && $_POST['update_patternFile'] ) {
				//更新処理
				if ( isset( $_POST['pattern'] ) && $_POST['pattern'] && isset( $_POST['filename'] ) && $_POST['filename'] ) {

					// FILE_APPEND フラグはファイルの最後に追記することを表し、
					// LOCK_EX フラグは他の人が同時にファイルに書き込めないことを表します。
					// stripslashesでアンエスケープ
					if ( !isset( $_POST['hostName'] ) || empty($_POST['hostName']) )
						$hostName = '<' . DB_HOST . '>' . "\n";
					else
						$hostName = '<' . $_POST['hostName'] . '>' . "\n";

					//ここから続々とファイルへ書き込み
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

					set_transient( 'update_WIX', 'パターンファイルを更新しました', 10 );
				}
			}
		} else {
			$e -> add('error', __( 'Please enter valid patterns&filenames.', 'update_patternFile' ) );
			set_transient( 'update_WIX_errors', $e->get_error_message(), 10 );
		}
	}

}

//更新・エラーメッセージを表示する
add_action( 'admin_notices', 'patternFile_notices' );	

function patternFile_notices() {
	?>
	<?php if ( $messages = get_transient( 'update_WIX_errors' ) ): ?>
	<div class="error">
		<ul>
			<?php foreach( $messages as $message ): ?>
				<li><?php echo esc_html($message); ?></li>
			<?php endforeach; ?>
		</ul>
	</div>
	<?php elseif ( $messages = get_transient( 'update_WIX' ) ): ?>
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