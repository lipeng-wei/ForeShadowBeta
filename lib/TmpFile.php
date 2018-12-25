<?php
/**
 * 临时文件
 */

class TmpFile
{
    private $_file = '';
    private static $_box = array();


    private function __construct()
    { }

    /**
     * 获取临时文件对象
     */
    static public function genByName( $name )
    {
        $filename = TMP_PATH . $name;
        if ( array_key_exists( $filename, self::$_box ) )
        {
            return self::$_box[$filename];
        }
        $file = new TmpFile();
        $file->_file = $filename;
        self::$_box[$filename] = $file;
        return $file;
    }
    static public function genByFilePath( $filePath )
    {
        if ( array_key_exists( $filePath, self::$_box ) )
        {
            return self::$_box[$filePath];
        }
        $file = new TmpFile();
        $file->_file = $filePath;
        self::$_box[$filePath] = $file;

        return $file;
    }

    public function renew($bak=true)
    {
        if (file_exists($this->_file)) {
            if ($bak === true) {
                $l = filemtime($this->_file);
                $l = date("YmdHis", $l);
                @rename($this->_file, $this->_file . '.' . $l);
            } else {
                @unlink($this->_file);
            }
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