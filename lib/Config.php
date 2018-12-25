<?php

class Config
{

    private static $_setting = false;

    //加载配置文件
    public static function load($file) {
        if (self::$_setting) return true;
        if (file_exists($file) == false) return false;
        if (self::$_setting = parse_ini_file($file, true))
            return true;
        else
            return false;
    }

    //获取配置项 参数如： Log.Separator
    static function get($var) {
        $vars = explode('.', $var);
        $result = self::$_setting;
        foreach ($vars as $key) {
            if (!isset($result[$key]))
                return false;
            $result = $result[$key];
        }
        return $result;
    }

    //设置配置项 参数如： Log.Separator
    static function set($var, $val) {
        $vars = explode('.', $var);
        $result = &self::$_setting;
        foreach ($vars as $key) {
            if (!isset($result[$key]))
                return false;
            $result = &$result[$key];
        }
        $result = $val;
        return true;
    }

}