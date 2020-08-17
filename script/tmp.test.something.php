<?php

/**
 * 测试
 */

if ($argc > 0 && basename($argv[0]) == 'tmp.test.something.php') {
    require_once(dirname(__FILE__). '/../require.php');
    // 同一时间同一机器不允许执行多个
    $file = fopen(__FILE__, "a");
    if (! flock($file, LOCK_EX | LOCK_NB)) {
        Log::info('已经有程序' . basename($argv[0]) . '在运行');
        exit(-1);
    }
    Log::info(basename($argv[0]) . ' 开始运行');

//    $arr = [
//        'ab' => 123,
//        'cd' => 234,
//        'ef' => 345,
//        'gh' => 456,
//        'ij' => 567,
//        'kk' => 678
//    ];
//    var_dump(array_slice($arr,1,2));


    //---------------------------------------------------------------
//    $a = 20200713;
//    $a = pack("I", $a);
//    echo bin2hex($a);
//    $a = hex2bin("093d3401");
//    $a = unpack("I", $a);
//    var_dump($a);
//    exit(0);

//    $a = hex2bin("333030343039");
//    $a = unpack("c*", $a);
//    $str = '';
//    foreach($a as $byte){
//        $str .= chr($byte);
//    }
//    var_dump($str);
//    $a = pack("a*", $str);
//    echo "---".bin2hex($a);
//
//    $str = "测试一下";
//    $str = mb_convert_encoding($str,'CP936', 'UTF-8');
//    $a = pack("a*", $str);
//    echo "---".bin2hex($a);
//    exit(0);


//    $a = hex2bin("f857"); //3a33
//    $a = unpack("n", $a);
//    var_dump($a);
//    echo bin2hex(pack("n", rand(0x0000, 0xffff)));
//    exit(0);

//    $a = '7.32';
//    $dirc = array("-5"=>-3,"-3"=>-2,"-1"=>-1,"1"=>1,"3"=>2,"5"=>3,"7"=>4,"9"=>5,"11"=>6);
//    $dic = [-5,-3,-1,1,3,5,7,9,11];
//    $first = 0;
//    $second = 0;
//    foreach ($dic as $i) {
//        $ib = $i - 2;
//        if($a>=pow(2, $ib) and $a<pow(2, $i)) {
//            $first = $ib;
//            break;
//        }
//    }
//    $chushu = pow(2, $first);
//    if($a >= $chushu*2){
//        $second = $a / $chushu;
//        $second = intval($second*64);
//    } else {
//        $second = $a / $chushu -1;
//        #print(second)
//        $second = intval($second*128);
//    }
//
//    if($first<0)
//        $first = 64 + $dirc[strval($first)];
//    else
//        $first = 63 + $dirc[strval($first)];
//
//    echo $first . "+++" . $second . "\n";
//    $a = pack("C", $first);
//    echo "---".bin2hex($a);
//    $a = pack("C", $second);
//    echo "---".bin2hex($a);
//    exit(0);

//    $str = TdxUtil::getDrawText('sz300001', '20200723', 20.23, "上半年 +66%\r\n增发");
//    echo bin2hex($str);
//
//    $file = dirname(__FILE__). '/../tmp/tdxlineX.eld';
//    file_put_contents($file, $str);

//    $arr = array(
//        array(
//            'code' => 'sz300001',
//            'day' => '20200723',
//            'price' => '20.23',
//            'text' => "上半年 +66%\r\n增发",
//        ),array(
//            'code' => 'sz300001',
//            'day' => '20191223',
//            'price' => '18.23',
//            'text' => "19 +55%\r\n增发",
//        ),
//    );
//    $file = dirname(__FILE__). '/../tmp/tdxlineX.eld';
//    TdxUtil::genDrawText($file, $arr);

    Log::info(basename($argv[0]) . ' 运行结束');
}


class ascii {

    /**
     * 将ascii码转为字符串
     * @param type $str  要解码的字符串
     * @param type $prefix  前缀，默认:&#
     * @return type
     */
    function decode($str, $prefix="&#") {
        $str = str_replace($prefix, "", $str);
        $a = explode(";", $str);
        foreach ($a as $dec) {
            if ($dec < 128) {
                $utf .= chr($dec);
            } else if ($dec < 2048) {
                $utf .= chr(192 + (($dec - ($dec % 64)) / 64));
                $utf .= chr(128 + ($dec % 64));
            } else {
                $utf .= chr(224 + (($dec - ($dec % 4096)) / 4096));
                $utf .= chr(128 + ((($dec % 4096) - ($dec % 64)) / 64));
                $utf .= chr(128 + ($dec % 64));
            }
        }
        return $utf;
    }

    /**
     * 将字符串转换为ascii码
     * @param type $c 要编码的字符串
     * @param type $prefix  前缀，默认：&#
     * @return string
     */
    function encode($c, $prefix="&#") {
        $len = strlen($c);
        $a = 0;
        while ($a < $len) {
            $ud = 0;
            if (ord($c{$a}) >= 0 && ord($c{$a}) <= 127) {
                $ud = ord($c{$a});
                $a += 1;
            } else if (ord($c{$a}) >= 192 && ord($c{$a}) <= 223) {
                $ud = (ord($c{$a}) - 192) * 64 + (ord($c{$a + 1}) - 128);
                $a += 2;
            } else if (ord($c{$a}) >= 224 && ord($c{$a}) <= 239) {
                $ud = (ord($c{$a}) - 224) * 4096 + (ord($c{$a + 1}) - 128) * 64 + (ord($c{$a + 2}) - 128);
                $a += 3;
            } else if (ord($c{$a}) >= 240 && ord($c{$a}) <= 247) {
                $ud = (ord($c{$a}) - 240) * 262144 + (ord($c{$a + 1}) - 128) * 4096 + (ord($c{$a + 2}) - 128) * 64 + (ord($c{$a + 3}) - 128);
                $a += 4;
            } else if (ord($c{$a}) >= 248 && ord($c{$a}) <= 251) {
                $ud = (ord($c{$a}) - 248) * 16777216 + (ord($c{$a + 1}) - 128) * 262144 + (ord($c{$a + 2}) - 128) * 4096 + (ord($c{$a + 3}) - 128) * 64 + (ord($c{$a + 4}) - 128);
                $a += 5;
            } else if (ord($c{$a}) >= 252 && ord($c{$a}) <= 253) {
                $ud = (ord($c{$a}) - 252) * 1073741824 + (ord($c{$a + 1}) - 128) * 16777216 + (ord($c{$a + 2}) - 128) * 262144 + (ord($c{$a + 3}) - 128) * 4096 + (ord($c{$a + 4}) - 128) * 64 + (ord($c{$a + 5}) - 128);
                $a += 6;
            } else if (ord($c{$a}) >= 254 && ord($c{$a}) <= 255) { //error
                $ud = false;
            }
            $scill .= $prefix.$ud.";";
        }
        return $scill;
    }

}