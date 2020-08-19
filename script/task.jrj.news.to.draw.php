<?php

/**
 * 挖掘有 金融界资讯 利好 并导入画线
 */

ini_set('memory_limit', '800M');

if ($argc > 0 && basename($argv[0]) == 'task.jrj.news.to.draw.php') {
    require_once(dirname(__FILE__). '/../require.php');
    // 同一时间同一机器不允许执行多个
    $file = fopen(__FILE__, "a");
    if (! flock($file, LOCK_EX | LOCK_NB)) {
        Log::info('已经有程序' . basename($argv[0]) . '在运行');
        exit(-1);
    }
    Log::info(basename($argv[0]) . ' 开始运行');

    JRJDraw::run();

    Log::info(basename($argv[0]) . ' 运行结束');
}

class JRJDraw
{

    public static function run()
    {
        self::filterNews('2020-08-16', '2020-07-01', '2020-07-05');
    }

    /**
     * 开始时间$start 结束时间$end
     */
    public static function filterNews($end, $start, $pos)
    {
        $pattern = "/(二季度|半年).*利.*(增|翻).*(倍|番|[0-9]{3,}\.?[0-9]*[%％]|[^0-9\.][5-9][0-9]\.?[0-9]*[%％])/u";
        $limiter = 6000;
        $pos_time = date("Ymd", strtotime($pos));
        $resultFile = OUTPUT_PATH. $end. '_'. $start. '_JRJNews_TdxLineX.eld';

        $stkL = Refer::getStock();
        $stkDict = array();

        foreach($stkL as $stk){

            if (--$limiter < 0 ) break;

            Log::easyDebug('handle', $stk['code'], $stk['name']);
            $jrjnd = StockData::genByCodeType($stk['code'], StockData::T_JRJ_NEWS);
            $jrjndd = $jrjnd->getDayPeriod($start, $end);
            if (! $jrjndd) continue;

            $txt = "";
            foreach ($jrjndd as $nddr) {

                preg_match($pattern, $nddr['title'], $matches);
                if (! empty($matches[0])) {
                    $txt = $matches[0];
                }

            }
            if ($txt) {
                $kd = StockData::genByCodeType($stk['code'], StockData::T_K_DAY);
                $dd = $kd->getDaySolo($pos);
                $stkDict[$stk['code'].'_'.$stk['name']] = array(
                    "code" => $stk['code'],
                    "day" => $pos_time,
                    "price" => $dd['ma20'],
                    "text" => $txt,
                );
            }
        }

        TdxUtil::genDrawText($resultFile, $stkDict);
        print_r(array_keys($stkDict));

    }

}
