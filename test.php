<?php


// if(!isset($_POST['param1'])){  // パラメータが設定されてなかったら怒る
//     exit("invalid data");
//   }

//   $response_str="";
//   // $query_str=$_POST['param1'];   // パラメータ取得

//   /* 何かしら処理する */
//   $response_str = $_POST['param2'];

//   echo $response_str;
//   exit();




header("Access-Control-Allow-Origin: *");
header('Content-type: application/javascript; charset=utf-8');

	if ( isset($_GET['param1']) && isset($_GET['param2']) ) {
		$json = array(
			"blog" => $_GET['param1'],
			"author" => $_GET['param2']
		);
	    $callback = $_GET["callback"];
	    echo $callback."(" . json_encode($json) . ")";
	}


    // $response = wp_remote_get( 'http://localhost/wixDirect.html' );
    // echo $response;

?>