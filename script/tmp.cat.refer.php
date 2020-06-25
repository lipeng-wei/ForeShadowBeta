<?php

/**
 * 查看Refer
 */

if ($argc > 0 && basename($argv[0]) == 'tmp.cat.refer.php') {
    require_once(dirname(__FILE__). '/../require.php');
    // 同一时间同一机器不允许执行多个
    $file = fopen(__FILE__, "a");
    if (! flock($file, LOCK_EX | LOCK_NB)) {
        Log::info('已经有程序' . basename($argv[0]) . '在运行');
        exit(-1);
    }
    Log::info(basename($argv[0]) . ' 开始运行');


//    $refer = Refer::getStock();
//    foreach ($refer as $k => $v) {
//        echo "$k : ( {$v['code']} , {$v['name']} )\n";
//    }

//    $concept = Concept::getConcept();
//    foreach ($concept as $k => $v) {
//        echo "$k : (  {$v['board']} )\n";
//    }

    Log::info(basename($argv[0]) . ' 运行结束');
}