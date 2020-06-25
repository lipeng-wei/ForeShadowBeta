<?php
/**
 *  stock concept
 *
 * board 板块
 * detail 概念详情 暂时无数据
 */

class Concept
{

    private static $_file = null;
    private static $_stock_data = null;

    private static function _load()
    {
        if (empty(self::$_stock_data)) {
            self::$_file = DATA_PATH . 'concept/Concept.json';
            if (file_exists(self::$_file)) {
                $content = file_get_contents(self::$_file);
                self::$_stock_data = json_decode($content, true);
                if (! empty(self::$_stock_data)) return true;
            }
            Log::easyError('Concept Wrong');
            exit(0);
        }
        return true;
    }

    public static function getConcept()
    {
        self::_load();
        return self::$_stock_data;
    }

    /**
     * 获取单个股票 的索引信息
     */
    public static function getByCode($code)
    {
        $all = self::getConcept();
        if (key_exists($code, $all)) return $all[$code];
        return false;
    }

}