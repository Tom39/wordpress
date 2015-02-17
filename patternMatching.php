<?php

$pm = new patternMatching;

class patternMatching {
	private $WixID = '0';
	private $RequestPath = '';
	public $matchingHostName = '';

	function returnWixID() {

		global $pm;

		$firstCandidates = $pm -> returnCandidates();
		$pm -> RequestPath = $pm -> subjectPath();

		//ソート後
		$secondCandidates = array();
		//Selection後
		$finalCandidates = array();
		//既入wid除外用Array
		$finalCandidates_wids = array();

		$no_attachFlag = false;


		$pm -> RequestPath = '/vaio/products/fa1/special.html';
		// var_dump( $pm -> RequestPath );

		try{
			foreach ( $firstCandidates as $key => $value ) {
				$pattern = $key;
				$wids = $value;

				//正規表現パス
				if ( strpos( $pattern, '"' ) !== false ) {

					$pattern = $pm -> removeSpace( $pattern );
					$pattern = substr( $pattern, 1, strlen($pattern) - 2 );

				} else {
					//パスパターン
					if ( strpos( substr( $pattern, 0, 1), '/' ) !== false ) 
						$pattern = '^' . $pattern;
					else 
						$pattern = '^/' . $pattern;
					
					if ( strpos( $pattern, ' ' ) !== false ) $pattern = $pm -> removeSpace( $pattern );

					if ( strpos( $pattern, '*' ) !== false ) $pattern = preg_replace('/\\*/', '.*', $pattern );

					if ( strpos( $pattern, '?' ) !== false ) {
						//元々ある.をエスケープしてから?を.に。
						$pattern = preg_replace('/\\./', '\\\\.', $pattern );
						$pattern = preg_replace('/\\?/', '.', $pattern );
					}
				}

				//URLパスとマッチしたら$secondCandidatesに。
				if ( preg_match( '{' . $pattern . '}', $pm -> RequestPath, $matches, PREG_OFFSET_CAPTURE ) == 1 ) {

					if ( mb_strtolower( $wids ) !== 'no_attach'  ) {

						//$matches[0][0]にマッチング文字列, [0][1]にマッチング開始位置
						array_push( $secondCandidates, 
									array(
											'pattern' => $pattern,
											'wids' => $wids,
											'endLocation' => (strlen( $matches[0][0] ) + $matches[0][1]),
											'patternLength' => strlen( $matches[0][0] )
										)
								);

					} else {

						//最長一致でない限りフラグは立てない
						if ( (strlen( $matches[0][0] ) + $matches[0][1]) == strlen( $pm -> RequestPath ) ) {

							$no_attachFlag = true;
							break;

						}

					}
				
				}
			}

			if ( $no_attachFlag == false ) {
				$endLocation = array();
				$patternLength = array();

				foreach ( $secondCandidates as $key => $row ){

					//endLocation:マッチング終端位置, patternLength:マッチング文字列長
					$endLocation[$key] = $row['endLocation'];
					$patternLength[$key] = $row['patternLength'];

				}
				//ソート
				array_multisort( $endLocation, SORT_DESC, $patternLength, SORT_DESC, $secondCandidates );

				//オプションの判定
				$finalCandidates = $pm -> selectCandidates( $secondCandidates );

				$decided_WixID = '';


				//wids決定
				foreach ( $finalCandidates as $key => $value ) {
					$tmpArray = $value;

					//既にあるwidは返さない
					if ( empty( $finalCandidates_wids ) === true ) {

						$decided_WixID = $tmpArray['wids'];

						$finalCandidates_wids = explode( '-', $tmpArray['wids'] );

					} else {

						$flag = false;

						foreach ( $finalCandidates_wids as $key => $value ) {
							$tmpWid = $value;

							if ( strpos( $tmpArray['wids'], $tmpWid ) !== false ) {

								$flag = true;
								break;
							}

						}

						if ( $flag == false ) {
							
							$decided_WixID = $decided_WixID . '-' . $tmpArray['wids'];
							
							foreach ( explode( '-', $tmpArray['wids'] ) as $key => $value ) {
								array_push( $finalCandidates_wids, $value );
							}
							
						}

					}

				}

				$pm -> WixID = $decided_WixID;

			} else {

				$pm -> WixID = '0';
			
			}


		} catch ( Exception $e ) {
	
			echo '捕捉した例外: ',  $e -> getMessage(), "\n";

		}


		return $pm -> WixID;
	}


	function returnCandidates() {
		global $pm;
		global $matchingHostName;
		$candidates = array();

		try{
			if ( file_exists( PatternFile ) && is_readable( PatternFile ) ) {

				$requestHost = $pm -> requestURL_part( PHP_URL_HOST );

				//ファイル内容
				$fileContents = file_get_contents( PatternFile, FILE_USE_INCLUDE_PATH );

				//ホスト名探索($matches[1]にホスト名群)
				preg_match_all('/<(.*)>/', $fileContents, $matches);

				$subContents_num = array();

				//パターンファイル内該当箇所
				$subContents = '';

				foreach ( $matches[1] as $key => $value ) {

					if ( !empty( $value ) && preg_match( '/' . $value . '/', $requestHost ) ) {
						
						$pm -> matchingHostName = $value;
						
						//該当ホスト名の次に< >がある場合
						if ( isset( $matches[0][$key + 1] ) ) {
							array_push( 
										$subContents_num, 
										strpos( $fileContents, $matches[0][$key] ) + strlen($matches[0][$key] ),
										strpos( $fileContents, $matches[0][$key + 1] ) 
										);
							//該当ホスト名 ~ 次の<ホスト名>までを抽出。substrの第３引数は文字数
							$subContents = substr( $fileContents, $subContents_num[0], $subContents_num[1] - $subContents_num[0] );
						} else {
							array_push( 
										$subContents_num, 
										strpos( $fileContents, $matches[0][$key] ) + strlen($matches[0][$key] )
										);
							$subContents = substr( $fileContents, $subContents_num[0] );
						}
						break;
					}

				}

				try{
					$patterns = array();
					$wids = array();
					$tmp = '';
					$flag = false;

					$candidates = $pm -> splitSpace( $subContents );

					/* 空白と:を除いた要素を$candidatesから取り除く*/
					foreach ( $candidates as $key => $value ) {
						if ( ($key = array_search(':', $candidates)) !== false 
								|| ($key = array_search('', $candidates)) !== false  ) {

							unset( $candidates[$key] );

						}
					}
					$candidates = array_merge( $candidates );


					//patternとwidへの分離
					foreach ( $candidates as $key => $value ) {

						if ( (strpos( $value, '/' ) !== false) || (strpos( $value, '"' ) !== false) ) {

							array_push( $patterns, $value );

							if ( $flag == true ) {
								array_push( $wids, $tmp );
								$tmp = '';
							}

						} else {

							$flag = true;

							if ( $tmp === '' ) $tmp = $value;
							else $tmp = $tmp . '-' . $value;

							if ( $key ===  count($candidates) - 1 )
								array_push( $wids, $tmp );

						}

					}

					$candidates = array();

					//連想配列作成
					for ( $i = 0; $i < count($patterns); $i++ ) {
						$candidates += array( $patterns[$i] => $wids[$i] );
					}


				} catch ( Exception $e ) {
		
					echo '捕捉した例外: ',  $e -> getMessage(), "\n";
		
				}

			} else {
				echo 'パターンファイルがありません。';
			}

		} catch ( Exception $e ) {
	
			echo '捕捉した例外: ',  $e -> getMessage(), "\n";

		}

		return $candidates;
	}


	function selectCandidates( $array ) {
		global $pm;

		foreach ( $array as $key => $value ) {
			$tmpArray = $value;

			//widにonlyが付いてたら、そのパターンが最長一致した時のみ適用(卒論時のoffと同意)
			if ( strpos($tmpArray['wids'], 'only') !== false ) {

				//一致しなかったら候補から除外
				if ( $tmpArray['endLocation'] != strlen( $pm -> RequestPath ) ) {

					var_dump( 'ではない' );
					unset( $array[$key] );
				
				} else {

					$array[$key]['wids'] = str_replace( '-only', '', $tmpArray['wids'] );

				}

			}
		}

		return $array;
	}



	function startsWith( $haystack, $needle )	{
    	return $needle === "" || strpos( $haystack, $needle ) === 0;
	}
	
	function endsWith( $haystack, $needle ) {
    	return $needle === "" || substr( $haystack, -strlen($needle) ) === $needle;
	}

	function removeSpace( $str ) {
		$str = preg_replace('/(\s|　)/','',$str);
		return $str;
	}

	function splitSpace( $str ) {
		$str = preg_split("/[\s,]+/", $str);
		return $str;
	}

	function requestURL_part( $option ) {

		if ( get_permalink() != '' )
			return parse_url( urldecode( get_permalink() ), $option );
		else
			return parse_url( urldecode( get_admin_url() ), $option );
	}

	function subjectPath() {
		global $pm;

		$requestPath = $pm -> requestURL_part( PHP_URL_PATH );
		$requestQuery = $pm -> requestURL_part( PHP_URL_QUERY );

		$subjectPath;

		if ( isset( $requestQuery ) ) {
			$subjectPath = $requestPath . '?' . $requestQuery;
		} else {
			$subjectPath = $requestPath;
		}

		return $subjectPath;
	}

}



?>