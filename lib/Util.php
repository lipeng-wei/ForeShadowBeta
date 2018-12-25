<?php

class Util
{

    public static function getClientIP()
    {
        if (getenv('HTTP_CLIENT_IP')) {
            $ip = getenv('HTTP_CLIENT_IP');
        } elseif (getenv('HTTP_X_FORWARDED_FOR')) {
            $ip_list = getenv('HTTP_X_FORWARDED_FOR');
            $ips = explode(',', $ip_list);
            $ip = trim($ips[0]);
        } elseif (getenv('HTTP_X_FORWARDED')) {
            $ip = getenv('HTTP_X_FORWARDED');
        } elseif (getenv('HTTP_FORWARDED_FOR')) {
            $ip = getenv('HTTP_FORWARDED_FOR');
        } elseif (getenv('HTTP_FORWARDED')) {
            $ip = getenv('HTTP_FORWARDED');
        } elseif (getenv('REMOTE_ADDR')) {
            $ip = getenv('REMOTE_ADDR');
        } else {
            $ip = '0.0.0.0';
        }
        return $ip;
    }

    public static function code2Num($code){
        $s = substr($code, 0 ,2);
        $s = strtolower($s);
        if ($s == 'sh' || $s == 'sz')
            return substr($code, 2);
        else
            return false;
    }

    public static function num2Code($num){
        if (strlen($num) != 6 ) return false;
        $s = substr($num, 0 ,1);
        if ($s == '6')
            return 'sh' . $num;
        else if ($s == '0')
            return 'sz' . $num;
        else if ($s == '3')
            return 'sz' . $num;
        else
            return false;
    }

    public static function getSleepTime($idx) {
        static $sleep_arr = array(0, 0, 0, 1, 1, 2, 2,
            10, 1, 1, 1, 2, 2, 30, 1, 1, 1, 2, 2,
            60, 1, 1, 1, 2, 2, 200, 1, 1, 1, 2, 2,
            600, 1, 1, 1, 2, 2, 2, -1, -1, -1);
        return $sleep_arr[$idx];
    }

}