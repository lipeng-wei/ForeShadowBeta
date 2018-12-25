<?php

/**
 * 日志记录类
 * 提供error, info, debug静态方法，分别记录到error,info,debug文件中
 * 并按照日期分割文件
 * 另外可以自定义文件名，记录日志信息。
 * */

class Log
{

    private $_fp = null;
    private $_logname = '';
    private static $_box = array();
    private static $_log_index;
    private static $_log_level = ['error', 'info', 'debug'];
    private static $_last_day;

    private function __construct($fname)
    {
        $this->_logname = $fname;
        if(!self::$_log_index) {
            $uniqid = Container::get('__request_uniqid__');
            if ($uniqid) {
                self::$_log_index = $uniqid;
            } else {
                self::$_log_index = uniqid();
                Container::register('__request_uniqid__', self::$_log_index);
            }
        }
    }

    /**
     * 获取日志文件对象
     * @param name string 文件名，文件所处目录在配置中设置
     * */
    static public function getIns( $name )
    {
        $day = date("Ymd");
        self::randomGC($day);

        $fname = LOG_PATH .$name.'.log.'.$day;

        if ( array_key_exists( $fname, self::$_box ) )
        {
            return self::$_box[$fname];
        }
        self::$_box[$fname] = new Log($fname);
        return self::$_box[$fname];
    }

    static protected function randomGC($day)
    {
        if (!self::$_last_day) {
            self::$_last_day = $day;
            return;
        }
        if (self::$_last_day != $day && mt_rand(1, 10) <= 2) {
            foreach (self::$_box as $key => $fp) {
                unset(self::$_box[$key]);
            }
        }
        return;
    }

    static public function setLogLevel($level)
    {
        self::$_log_level = explode(',', $level);
        return;
    }

    /**
     * 记录日志到文件
     * @param msg string  日志信息，
     * @param skiplevel number  追溯的层级
     * */
    public function log( $msg, $skipLevel = 1 )
    {
        $logInfo = $this->get_log_info( $skipLevel );
        if ( !is_resource($this->_fp) )
        {
            if(!file_exists($this->_logname)){
                $this->_fp = fopen( $this->_logname, 'a' );
                @chmod($this->_logname,0766);
            }else{
                $this->_fp = fopen( $this->_logname, 'a' );
            }
        }
        /*if(strlen($msg) > 4096 ){
            $msg = substr($msg, 0 , 4096) . '....to long';
        }*/
        $logmsg  = '['.date( 'Y-m-d H:i:s' ).' '.Util::getClientIP().'] ';
        $logmsg .= '['.$logInfo['host'].'] ';
        $logmsg .= '['.$logInfo['func'].'] [LINE:'.$logInfo['line'].'] [' . self::$_log_index . '] '.$msg."\n";
        if( is_resource($this->_fp) )
            fwrite( $this->_fp, $logmsg );
    }

    public static function error( $msg, $skip_level=0 )
    {
        if (in_array('error', self::$_log_level))
            self::getIns('error')->log( $msg, 2+$skip_level );
    }
    public static function info( $msg, $skip_level=0 )
    {
        if (in_array('info', self::$_log_level))
            self::getIns('info')->log( $msg, 2+$skip_level );
    }
    public static function debug( $msg, $skip_level=0 )
    {
        if (in_array('debug', self::$_log_level))
            self::getIns('debug')->log( $msg, 2+$skip_level );
    }

    public static function easyError()
    {
        $log_msg = '';
        $args = func_get_args();
        foreach ($args as $msg) {
            if (is_scalar($msg)) {
                $log_msg .= ' | ' . $msg;
            } else {
                $log_msg .= ' | ' . json_encode($msg);
            }
        }
        self::error($log_msg, 1);
    }
    public static function easyInfo()
    {
        $log_msg = '';
        $args = func_get_args();
        foreach ($args as $msg) {
            if (is_scalar($msg)) {
                $log_msg .= ' | ' . $msg;
            } else {
                $log_msg .= ' | ' . json_encode($msg);
            }
        }
        self::info($log_msg, 1);
    }
    public static function easyDebug()
    {
        $log_msg = '';
        $args = func_get_args();
        foreach ($args as $msg) {
            if (is_scalar($msg)) {
                $log_msg .= ' | ' . $msg;
            } else {
                $log_msg .= ' | ' . json_encode($msg);
            }
        }
        self::debug($log_msg, 1);
    }

    private function get_log_info($skipLevel = 1)
    {
        $trace_arr = debug_backtrace();
        for ($i = 0; $i < $skipLevel; $i++)
        {
            array_shift($trace_arr);
        }

        $tmp_arr1 = array_shift($trace_arr);

        if (! empty($trace_arr))
        {
            $tmp_arr2 = array_shift($trace_arr);
        }
        else
        {
            $tmp_arr2 = array(
                'function' => "MAIN" //主流程 __MAIN__
            );
        }

        if (isset($tmp_arr2['class'])) // 类的方法
        {
            $func = $tmp_arr2['class'] . $tmp_arr2['type'] . $tmp_arr2['function'];
        }
        else
        {
            $func = $tmp_arr2['function'];
        }

        return array(
            'line' => $tmp_arr1['line'] ,
            'file' => $tmp_arr1['file'] ,
            'func' => $func,
            'host' => substr(gethostname(),0,-10),
        );
    }

} 