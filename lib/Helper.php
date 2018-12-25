<?php

/**
 * 判断是否为命令行模式
 */
if(!function_exists('is_cli')){
    function is_cli()
    {
        return (PHP_SAPI === 'cli' OR defined('STDIN'));
    }
}