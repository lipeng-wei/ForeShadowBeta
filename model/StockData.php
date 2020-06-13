<?php
/**
 * Stock时序(time) 的数据模型
 */
require_once(LIB_PATH . "TableFileTool.php");

class StockData
{

    //日线
    const T_K_DAY = 'kday';
    //东财研报
    const T_DC_REPORT = 'dcreport';
    //东财公告
    const T_DC_PUBLIC = 'dcpublic';
    //东财资讯
    const T_DC_NEWS = 'dcnews';
    //爱查股DDE
    const T_ACG_DDE = 'acgdde';
    //新浪资讯
    const T_XL_NEWS = 'xlnews';



    public static $TYPE_CONF = [
        self::T_K_DAY => [
            'PATH' => 'kday',
            'NAME' => '日线'
        ],
        self::T_DC_REPORT => [
            'PATH' => 'dcreport',
            'NAME' => '东财研报'
        ],
        self::T_DC_PUBLIC => [
            'PATH' => 'dcpublic',
            'NAME' => '东财公告'
        ],
        self::T_DC_NEWS => [
            'PATH' => 'dcnews',
            'NAME' => '东财资讯'
        ],
        self::T_ACG_DDE => [
            'PATH' => 'acgdde',
            'NAME' => '爱查股DDE'
        ],
        self::T_XL_NEWS => [
            'PATH' => 'xlnews',
            'NAME' => '新浪资讯'
        ],
    ];

    protected $_data;
    protected $_filename;

    public static function desc($type, $config)
    {
        if (array_key_exists($type, $config)) {
            return $config[$type]['NAME'];
        }
        return $type;
    }

    public static function genByCodeType($stockCode, $dataType)
    {
        if (! array_key_exists($dataType, self::$TYPE_CONF))
            return false;


        $ins = new StockData();
        $ins->_filename = DATA_PATH . self::$TYPE_CONF[$dataType]['PATH'] . '/' . $stockCode;

        return $ins;
    }

    public static function genByFileName($filename)
    {
        $ins = new StockData();
        $ins->_filename = $filename;

        return $ins;
    }

    /*
     * 读取文件 准备数据
     */
    protected function _prepare()
    {
        if ($this->_data) return true;
        $this->_data = TableFileTool::get($this->_filename);
        return true;
    }

    /**
     * 获取完整数据
     */
    public function getAll()
    {
        $this->_prepare();
        return $this->_data;
    }

    /**
     * 放入完整数据
     */
    public function putAll($data)
    {
        $list = [];
        foreach($data as $item) {
            if ($item['time'])  $list[] = $item;
        }
        return TableFileTool::put($this->_filename, $list);
    }

    /**
     * 追加部分数据
     */
    public function appendSome($data)
    {
        $list = [];
        foreach($data as $item) {
            if ($item['time'])  $list[] = $item;
        }
        return TableFileTool::append($this->_filename, $list);
    }

    /**
     * 获取数据存储文件路径
     */
    public function getFileName()
    {
        return $this->_filename;
    }

    /**
     * 二分查找日期索引
     * [ 日期日存在 true，日期的索引值 ] / [ 日期不存在 false，离日期最近的索引值 ]
     */
    protected function _locateDayIndex($day)
    {
        $s = 0;
        $e = sizeof($this->_data);
        while ($s + 1 < $e) {
            $i = intval ( ($s + $e) / 2 );
            if ($this->_data[$i]['time'] == $day) return array(true, $i);
            if ($this->_data[$i]['time'] > $day){
                $e = $i;
            } else {
                $s = $i;
            }
        }
        return array(false, $s);
    }


    /**
     * 获取某一天的数据
     * @param $day string 为 日期 例如 2015-11-06
     */
    public function getDaySolo($day)
    {
        $this->_prepare();

        $d = $this->_locateDayIndex($day);
        return $d[0] ? $this->_data[$d[1]]: false;
    }

    /**
     * 获取最新某天数据
     * $pos  为 离最新的周期数 0：最新一个周期（最新一天）
     */
    function getLastSolo($pos)
    {
        $this->_prepare();

        return sizeof($this->_data) > $pos ?
            $this->_data[sizeof($this->_data) - $pos - 1] : false;
    }

    /**
     * 获取某一段时间的数据
     *
     * @param $start  string 起始日期 true 则从头开始
     * @param $end string 为 截止日期 true 则到结尾
     * @param int $min 为 要求最短周期数
     * @return array|bool
     */
    function getDayPeriod($start, $end, $min = 1)
    {
        $this->_prepare();

        $s = $start === true ? 0 : $this->_locateDayIndex($start)[1];
        $e = $end === true ? sizeof($this->_data) - 1 : $this->_locateDayIndex($end)[1];
        return $s + $min <= $e + 1? array_slice($this->_data, $s, $e - $s + 1) : false;
    }

    /**
     * 获取最新若干周期的数据
     * @param $pos int 为 起始 距离 最新的周期数  true 则从头开始  0 则是最新一天
     * @param $num int 为 读取周期数 true 则到结尾
     * @param int $min 为 要求最短周期数
     * @return array|bool
     */
    function getLastPeriod($pos, $num, $min = 1)
    {
        $this->_prepare();

        $s = $pos === true ? 0 : sizeof($this->_data)- $pos - 1;
        $s = $s < 0 ? 0 : $s;
        $n = $num === true ? null : $num;
        return $s >= 0 && $s + $min <= sizeof($this->_data) ? array_slice($this->_data, $s, $n) : false;

    }


}