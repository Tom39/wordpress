<?php

/*
This is Return NewBody Function & Filter. NewBody is Linked soon.
*/


require_once( dirname( __FILE__ ) . '/patternMatching.php' );


add_filter( 'the_excerpt', 'new_body' );
add_filter( 'the_content', 'new_body' );
// remove_all_filters( 'the_content' );

function new_body( $content ) {
	
	if( is_preview() == false ) {

		$patternMatching = new patternMatching;
		$WixID = $patternMatching -> returnWixID();

		$attachURL = 'http://wixdev.db.ics.keio.ac.jp/sakusa_WIXServer_0.3.5/attach';
		
		// 新しい cURL リソースを作成
		$ch = curl_init();

		// パラメータ	
		$data = array(
		    'minLength' => 3,
		    'rewriteAnchorText' => 'false',
		    'bookmarkedWIX' => $WixID,
		    'body' => mb_convert_encoding($content, 'UTF-8'),
		);
		$data = http_build_query($data, "", "&");

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
			    echo 'エラーです. http_test.php';
		    	echo curl_error( $ch );

			}

		} catch ( Exception $e ) {
		
			echo '捕捉した例外: ',  $e -> getMessage(), "\n";
		
		} finally {

			curl_close($ch);
		
		}

		// return $content;
		return $response;
		
	} else {
		return $content;
	
	}
}	
?>