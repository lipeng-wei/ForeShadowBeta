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


//    $file = dirname(__FILE__). '/../tmp/tdxline.dat';
//    echo $file;
//    $hexstr = file_get_contents($file);
//    echo $hexstr;
//    $data = pack('H*', $hexstr);
//    echo $data;


    Log::info(basename($argv[0]) . ' 运行结束');
}


/**
 * 将文件内容转为16进制输出
 * @param String $file 文件路径
 * @return String
 */
function fileToHex($file){
    if(file_exists($file)){
        $data = file_get_contents($file);
        return bin2hex($data);
    }
    return '';
}

/**
 * 将16进制内容转为文件
 * @param String $hexstr 16进制内容
 * @param String $file 保存的文件路径
 */
function hexToFile($hexstr, $file){
    if($hexstr){
        $data = pack('H*', $hexstr);
        file_put_contents($file, $data, true);
    }
}