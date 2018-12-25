<?php
/**
 *  stock索引
 */

require_once(LIB_PATH . "TableFileTool.php");

class Refer
{

    const SHZS = [
        'code' => 'sh000001',
        'name' => '上证指数'
    ];

    private static $_file = null;
    private static $_stock_data = null;

    private static function _load(){

        if (empty(self::$_stock_data)) {
            self::$_file = DATA_PATH . 'refer/Stock.json';
            if (file_exists(self::$_file)) {
                $content = file_get_contents(self::$_file);
                self::$_stock_data = json_decode($content, true);
                if (! empty(self::$_stock_data)) return true;
            }
            Log::easyError('Refer Wrong');
            exit(0);
        }
        return true;
    }

    public static function getRefer(){
        return array_merge(self::getMarket(), self::getStock());
    }

    /**
     * 获取股票 的索引
     */
    public static function getStock(){
        self::_load();
        return self::$_stock_data;
    }
    /**
     * 获取市场 的索引
     */
    public static function getMarket(){
        return [
            'sh000001' => self::SHZS
        ];
    }

    /**
     * 获取上证指数 的索引信息
     */
    public static function getBySHZS(){
        return self::SHZS;
    }

    /**
     * 获取单个股票 的索引信息
     */
    public static function getByCode($code){
        $all = self::getRefer();
        if (key_exists($code, $all)) return $all[$code];
        return false;
    }

}