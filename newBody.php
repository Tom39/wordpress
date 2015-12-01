<?php

/*
This is Return NewBody Function & Filter. NewBody is Linked soon.
*/


require_once( dirname( __FILE__ ) . '/patternMatching.php' );


add_filter( 'the_excerpt', 'new_body' );
add_filter( 'the_content', 'new_body' );
remove_filter('the_content', 'wptexturize'); //-などの特殊文字への変換を停止

function new_body( $content, $decideFileArray = '', $decideFlag = false ) {
	global $start;
	if( is_preview() == false ) {

		$minLength = intval( get_option( 'minLength', '3' ) );
		$patternMatching = new patternMatching;
		$WixID = $patternMatching -> returnWixID();
			if ( $decideFlag == true ) {
				/* Decide処理なら */
				$attachURL = 'http://trezia.db.ics.keio.ac.jp/sakusa_WIXServer_0.3.5/PreviewAttach';
				
				// 新しい cURL リソースを作成
				$ch = curl_init();
				// パラメータ	
				$data = array(
				    'minLength' => $minLength,
				    'rewriteAnchorText' => 'false',
				    'bookmarkedWIX' => $WixID,
				    'body' => mb_convert_encoding($content, 'UTF-8'),
				    'innerLinkArray' => $decideFileArray
				);
				$data = http_build_query($data, "", "&");
				
			} else {
				$DecideFileInfo = '';
				$WIXFileInfo = '';

				//WIXファイル情報を抽出
				$WIXFileInfo = wixFileInfo( strip_tags($content) );

				//Decideファイル情報の抽出
				if ( $pointer = opendir(WixDecideFiles) ) {
					global $id;
					while ( ($file = readdir($pointer)) !== false ) {
						if ( $file === $id.'.txt' ) {
							$DecideFileInfo = decideFileInfo(WixDecideFiles.$file);
							break;
						}
					}
					closedir($pointer);
				}

				if ( !empty($DecideFileInfo) ) {
					$AttachInfo = $DecideFileInfo + $WIXFileInfo;
					if ( !empty($AttachInfo) ) {
						asort($AttachInfo);

						$tmpArray = '';
						$tmp = '';
						$flag = false;
						foreach ($AttachInfo as $start => $value) {
							if ( $flag == false ) {
								$nextStart = $value['nextStart'];
								if ( $nextStart == '0' ) {
									$tmpArray = $value;
									$tmp = $start;
									$flag = true;
								}
							} else {
								$tmpArray['nextStart'] = strval($start);
								$AttachInfo[$tmp] = $tmpArray;
								break;
							}
						}
					}

				} else {
					$AttachInfo = $WIXFileInfo;
				}

				if ( !empty($AttachInfo) ) {

					$AttachInfo = json_encode($AttachInfo, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);

					$attachURL = 'http://trezia.db.ics.keio.ac.jp/sakusa_WIXServer_0.3.5/attach';
					$ch = curl_init();
					$data = array(
					    'minLength' => $minLength,
					    'rewriteAnchorText' => 'false',
					    'bookmarkedWIX' => $WixID,
					    'body' => mb_convert_encoding($content, 'UTF-8'),
					    'attachInfo' => $AttachInfo,
					);
					$data = http_build_query($data, "", "&");
				
				} else {

					$attachURL = 'http://trezia.db.ics.keio.ac.jp/sakusa_WIXServer_0.3.5/attach';
					$ch = curl_init();
					$data = array(
					    'minLength' => $minLength,
					    'rewriteAnchorText' => 'false',
					    'bookmarkedWIX' => $WixID,
					    'body' => mb_convert_encoding($content, 'UTF-8'),
					);
					$data = http_build_query($data, "", "&");

				}

			}

			try {
				//送信
				curl_setopt( $ch, CURLOPT_URL, $attachURL );
				curl_setopt( $ch, CURLOPT_HTTPHEADER, array('Content-Type: application/x-www-form-urlencoded') );
				curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
				curl_setopt( $ch, CURLOPT_POST, true );
				curl_setopt( $ch, CURLOPT_POSTFIELDS, $data );

				$response = curl_exec($ch);

				if ( $response === false ) 
					$response = 'エラーです. newBody.php-> ' .curl_error( $ch );

			} catch ( Exception $e ) {
				$response = '捕捉した例外: ' . $e -> getMessage() . "\n";
			} finally {
				curl_close($ch);
			}

			return $response;
		
	} else {
		
		return $content;
	
	}
}

//Decideファイル情報を連想配列に整形
function decideFileInfo($filename) {
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

//リクエストHTMLに対して、適用可能なWIXファイルエントリ情報を抽出し、連想配列に整形
function wixFileInfo( $body ) {
	global $wpdb, $post;
	$returnValue = array();

	$wixfilemeta = $wpdb->prefix . 'wixfilemeta';
	$wixfile_targets = $wpdb->prefix . 'wixfile_targets';
	$wixfilemeta_posts = $wpdb->prefix . 'wixfilemeta_posts';

	$is_db_exists = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $wixfilemeta));

	if ( $is_db_exists == $wixfilemeta ) {

		$sql = 'SELECT COUNT(*) FROM ' . $wixfilemeta;
		if ( $wpdb->get_var($sql) != 0 ) {

			$sql = 'SELECT keyword FROM ' . $wixfilemeta . ' wm, ' . $wixfilemeta_posts . ' wmp WHERE wmp.doc_id = ' . $post->ID . ' AND wm.id = wmp.keyword_id';
			$distinctKeywords = $wpdb->get_results($sql);

			/* $keyword_sort_array : キーワードを文字列の長い順番にして、キーワード間の部分一致対策*/
			$keyword_sort_array = array();
			foreach ($distinctKeywords as $key => $value) {
				$keyword_sort_array[$key] = strlen($value->keyword);
			}
			array_multisort($keyword_sort_array, SORT_DESC, $distinctKeywords);

			//全位置情報
			$allLocationArray = array();
			$offset = 0;

			//wixfileテーブル内のキーワード毎にループを回す
			foreach ($distinctKeywords as $key => $value) {
				$keyword = $value->keyword;
				$len = mb_strlen($keyword, "UTF-8");
				$offset = 0;
				/* locationArray : 文字列マッチングが成立した位置を保持（start取得） */
				$locationArray = array();
				while ( ($pos = mb_strpos($body, $keyword, $offset, "UTF-8")) !== false ) {
					if ( in_array($pos, $allLocationArray) == false ) {
						array_push($locationArray, $pos);
						array_push($allLocationArray, $pos);
					}
					$offset = $pos + $len;
				}

				//end, keyword, targetの作成
				if ( count($locationArray) != 0 ) {
					$locationArray_len = count($locationArray);
					$sql = 'SELECT wt.target FROM ' . $wixfilemeta . ' wm, ' . $wixfile_targets . ' wt WHERE wm.id = wt.keyword_id AND wm.keyword="' . $keyword . '"';
					$results = $wpdb->get_results($sql);
					$targetArray = array();
					foreach ($results as $key => $value) {
						array_push($targetArray, $value->target);
					}

					foreach ($locationArray as $key => $start) {
						$end = intval($start) + $len;
						if ( $key < $locationArray_len ) 
							$returnValue[intval($start)] = array('end'=>strval($end), 'keyword'=>$keyword, 'target'=>$targetArray[0]);
					}
				}
			}

			sort($allLocationArray);
			asort($returnValue);

			//nextStartの作成
			if ( count($allLocationArray) != 0 ) {
				$returnValue_len = count($returnValue);
				$count = 1;
				foreach ($returnValue as $start => $array) {
					if ( $returnValue_len != $count )
						$array['nextStart'] = strval($allLocationArray[$count]);
					else
						$array['nextStart'] = '0';
					
					$returnValue[$start] = $array;
					$count++;
				}
			}
		}
	}

	return $returnValue;

}



?>