<?php
require './vendor/lastguest/murmurhash/murmurhash3.php';

$arr_a = array('巨人', '中井', '左膝', '靭帯', '損傷', '登録', '抹消');
$arr_b = array('中井', '左膝', '登録', '抹消', '歩行', '問題');

$sum = 0;

for ($i = 0; $i < 10; $i++) {
	$jaccard = calc_jaccard($arr_a, $arr_b);
	$sum += $jaccard;
	print $jaccard . PHP_EOL;
}
print 'avg = ' . $sum/10 . PHP_EOL;


function calc_jaccard($arr_a, $arr_b) {
    $k = 128;
    //k個分の乱数
    $seeds = init_seeds($k);

    $correct = 0.0;
    foreach ($seeds as $key => $seed) {
    	//引数: 単語配列, ある乱数
    	//返り値: 単語配列にハッシュ関数を適用した際の、最小ハッシュ値
    	$minhash_a = calc_minhash($arr_a, $seed);
    	$minhash_b = calc_minhash($arr_b, $seed);
    	if ( $minhash_a == $minhash_b) $correct +=1;
// var_dump($minhash_b);
    }

    return $correct / $k;
}

function init_seeds($num) {
	$seeds = array();
	while ( $num > 0 ) {
		array_push($seeds, mt_rand(0, 999999999));
		$num--;
	}

	return $seeds;
}

function calc_minhash($targets, $seed) {
	$hash_values = array();
	foreach ($targets as $key => $str) {
		array_push( $hash_values, murmurhash3($str, $seed) );
		//array_push( $hash_values, hexdec(hash('md5', $str)) );
	}

	sort( $hash_values );
	return $hash_values[0];
}

?>
