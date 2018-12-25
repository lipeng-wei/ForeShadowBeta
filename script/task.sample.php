<?php

/**
 * 定时任务 样例
 */

if ($argc > 0 && basename($argv[0]) == 'task.sample.php') {
    require_once(dirname(__FILE__). '/../require.php');
    // 同一时间同一机器不允许执行多个
    $file = fopen(__FILE__, "a");
    if (! flock($file, LOCK_EX | LOCK_NB)) {
        Log::info('已经有程序' . basename($argv[0]) . '在运行');
        exit(-1);
    }
    Log::info(basename($argv[0]) . ' 开始运行');
    // TO-DO

    Log::info(basename($argv[0]) . ' 运行结束');
}