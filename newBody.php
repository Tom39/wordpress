<?php

/*
This is Return NewBody Function & Filter. NewBody is Linked soon.
*/


require_once( dirname( __FILE__ ) . '/patternMatching.php' );


add_filter( 'the_excerpt', 'new_body' );
add_filter( 'the_content', 'new_body' );
// remove_all_filters( 'the_content' );
remove_filter('the_content', 'wptexturize'); //-などの特殊文字への変換を停止

function new_body( $content, $decideFileArray = '') {
	global $start;
	if( is_preview() == false ) {

		//プレビューと違ってEnterキーによる空白(空行)を1文字としてカウントしてしまって、preview_attachとattachが噛み合わなくなってたからこうしている。
		//これよくない！
		// $content = preg_replace('/(\s|　)/','',$content);

		$patternMatching = new patternMatching;
		$WixID = $patternMatching -> returnWixID();

		// if ( $WixID != 0 ) {
			if ( !empty($decideFileArray) ) {
				/* Decide処理なら */
				$attachURL = 'http://trezia.db.ics.keio.ac.jp/sakusa_WIXServer_0.3.5/PreviewAttach';
				
				// 新しい cURL リソースを作成
				$ch = curl_init();
				// パラメータ	
				$data = array(
				    'minLength' => 3,
				    'rewriteAnchorText' => 'false',
				    'bookmarkedWIX' => $WixID,
				    'body' => mb_convert_encoding($content, 'UTF-8'),
				    'innerLinkArray' => $decideFileArray
				);
				$data = http_build_query($data, "", "&");

			} else {

				$DecideFileInfo = '';

				if ( $pointer = opendir(WixDecideFiles) ) {
					global $id;
					while ( ($file = readdir($pointer)) !== false ) {
						if ( $file === $id.'.txt' ) {
							$DecideFileInfo = json_encode(DecideFileInfo(WixDecideFiles.$file), JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
							break;
						}
					}
					closedir($pointer);
				}

				$attachURL = 'http://trezia.db.ics.keio.ac.jp/sakusa_WIXServer_0.3.5/attach';
				$ch = curl_init();
				$data = array(
				    'minLength' => 3,
				    'rewriteAnchorText' => 'false',
				    'bookmarkedWIX' => $WixID,
				    'body' => mb_convert_encoding($content, 'UTF-8'),
				    'decideFileInfo' => $DecideFileInfo,
				);
				$data = http_build_query($data, "", "&");
			}

			try {
				//送信
				curl_setopt( $ch, CURLOPT_URL, $attachURL );
				curl_setopt( $ch, CURLOPT_HTTPHEADER, array('Content-Type: application/x-www-form-urlencoded') );
				curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
				curl_setopt( $ch, CURLOPT_POST, true );
				curl_setopt( $ch, CURLOPT_POSTFIELDS, $data );

				$response = curl_exec($ch);

				if ( $response === false ) {
				    // エラー文字列を出力する
				    $response = 'エラーです. newBody.php-> ' .curl_error( $ch );
				}
			} catch ( Exception $e ) {
				$response = '捕捉した例外: ' . $e -> getMessage() . "\n";
			} finally {
				curl_close($ch);
			}

			// $response = $content;
			return $response;

		// } else {

		// 	return $content;

		// }
		
	} else {
		
		return $content;
	
	}
}

//Decideファイル情報を連想配列に整形
function DecideFileInfo($filename) {
	$returnValue = array();

	$file = fopen($filename, 'r');

	if ( flock($file, LOCK_SH) ){
		while ( !feof($file) ) {
			$line = fgets($file);

			if ( $line === false ) break;

			$pieces = explode(',', $line);

			$start = explode(':' ,$pieces[0])[1];
			$end = explode(':' ,$pieces[1])[1];
			$nextStart = explode(':' ,$pieces[2])[1];
			$keyword = explode(':' ,$pieces[3])[1];
			$target = trim( substr($pieces[4], strpos($pieces[4], ':')+1) );

			$returnValue[$start] = array('end'=>$end, 'nextStart'=>$nextStart, 'keyword'=>$keyword, 'target'=>$target);

		}
	}

	fclose($file);

	return $returnValue;

}




?>