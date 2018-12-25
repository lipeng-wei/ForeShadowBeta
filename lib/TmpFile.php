<?php
/**
 * 临时文件
 *
 *
 */

class TmpFile
{
    private $_name = null;
    private $_file = '';
    private static $_box = array();


    private function __construct($name)
    {
        $this->_name = $name;
        $this->_file = TMP_PATH . $name;
    }

    /**
     * 获取临时文件对象
     */
    static public function getIns( $name )
    {

        if ( array_key_exists( $name, self::$_box ) )
        {
            return self::$_box[$name];
        }
        self::$_box[$name] = new TmpFile($name);
        return self::$_box[$name];
    }

    public function bak()
    {
        if (file_exists($this->_file)) {
            $l = filemtime($this->_file);
            $l = date("YmdHis", $l);
            @rename($this->_file, TMP_PATH . $this->_name . '.' . $l);
        }
    }

    public function exist()
    {
        return file_exists($this->_file);
    }

    public function getFileName()
    {
        return $this->_file;
    }

    public function get()
    {
        return file_get_contents($this->_file);
    }

    public function put($content)
    {
        return file_put_contents($this->_file, $content);
    }

    public function append($content)
    {
        file_put_contents($this->_file, $content, FILE_APPEND);
    }

}