<?php

/**
 * 在一段时间内 量价配合分析（数量、加权值）
 */

if ($argc > 0 && basename($argv[0]) == 'task.v2p.php') {
    require_once(dirname(__FILE__). '/../require.php');
    // 同一时间同一机器不允许执行多个
    $file = fopen(__FILE__, "a");
    if (! flock($file, LOCK_EX | LOCK_NB)) {
        Log::info('已经有程序' . basename($argv[0]) . '在运行');
        exit(-1);
    }
    Log::info(basename($argv[0]) . ' 开始运行');

    V2P::run();

    Log::info(basename($argv[0]) . ' 运行结束');
}

class V2P
{
    public static function run()
    {

        //self::calcV2P('2018-12-05', '2019-01-05');
        self::calcV2P('2020-05-01', '2020-06-01');

    }

    /**
     * 开始时间$start 结束时间$end
     */
    public static function calcV2P($start, $end)
    {
        $resultFile = OUTPUT_PATH. $end. '_'. $start. '_V2P.html';
        $title_content = 'V2P 筛选 ('. $start. '~'. $end. ')';
        $caption_content = $start. '至'. $end. '日线V2P筛选结果';
        $resultContent = '

<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>%%title%%</title>
<style type="text/css">
<!--
    th,td {padding:6px;}
    .thead_tr {background-color:#666; color:#fff;}
    .tr_0 {background-color:#CCCCFF; color:#000; padding:18px;}
    .tr_1 {background-color:#FFFFCC; color:#000; padding:18px;}
    .tr_tr {background-color:#F5F5F5; color:#000; padding:18px; font-weight:lighter}
    .sp {font-weight:bold; color:#FF0000}
-->
</style>
</head>
<body>
<table cellspacing="1" cellpadding="2">
<caption>%%caption%%</caption>
<thead>
<tr class="thead_tr">
<th>编号</th>
<th>代码</th>
<th>NUM</th>
<th>NUM_day</th>
<th>V2P</th>
<th>V2P_day</th>
<th>Total</th>
</tr>
</thead>
<tbody>

%%table%%

</tbody>
</table>
</body>
</html>

        ';
        $limiter = 2;



        $stkL = Refer::getStock();
        $stkD = array();
        $maxTotal = 0;
        foreach($stkL as $stk) {
            //if (--$limiter < 0 ) break;

            $kd = StockData::genByCodeType($stk['code'], StockData::T_K_DAY);
            $dd = $kd->getAll();
            if (count($dd) < 60) continue;
            $dd = $kd->getDayPeriod($start, $end, 3);
            if (! $dd) continue;

            $s_NUM = 0;
            $s_NUM_day = 0;
            $s_V2P = 0;
            $s_V2P_day = 0;

            for ($i = 2; $i < count($dd); $i++) {

                //处理当日涨跌停
                $is_zt = ($dd[$i]["high"] - $dd[$i-1]["close"]) / $dd[$i-1]["close"];
                $is_dt = ($dd[$i-1]["close"] - $dd[$i]["low"]) / $dd[$i-1]["close"];
                $valid_vol = $dd[$i]["volume"] / $dd[$i-1]["volume"];
                //涨跌停 并且 量小于0.3 不参与计算
                if (($is_zt >1.09 || $is_dt < 0.9) && $valid_vol < 0.3) continue;

                //处理前日涨跌停
                $is_zt = ($dd[$i-1]["high"] - $dd[$i-2]["close"]) / $dd[$i-2]["close"];
                $is_dt = ($dd[$i-2]["close"] - $dd[$i-1]["low"]) / $dd[$i-2]["close"];
                $valid_vol = $dd[$i-1]["volume"] / $dd[$i-2]["volume"];
                //涨跌停 并且 量小于0.3 不参与计算
                if (($is_zt >1.09 || $is_dt < 0.9) && $valid_vol < 0.3) continue;



                //----------- 计算V2P_NUM公式
                //计算量的比例
                $q2 = ($dd[$i]["volume"] - $dd[$i-1]["volume"]) / $dd[$i-1]["volume"] * 100;
                //计算价格相关
                $q1 = $dd[$i]["percent"] * 10;

                $s_NUM_day ++;
                if ($q1*$q2 > 0) $s_NUM ++;


                //----------- 计算V2P公式
                //处理量太小不值得计算
                $k1 = ($dd[$i]["volume"] - $dd[$i-1]["volume"]) / $dd[$i-1]["volume"] * 100;
                $k2 = ($dd[$i]["volume"] - $dd[$i-2]["volume"]) / $dd[$i-2]["volume"] * 100;
                if (abs($k1) < 20 && abs($k2) < 20) continue;

                //计算量相关
                $q2 = ($dd[$i]["volume"] - $dd[$i-1]["volume"]) / $dd[$i-1]["volume"] * 100;
                if ($q2 < -100) $q2 = -100;
                if ($q2 > 100) $q2 = 100;

                //计算价格相关
                $perc = $dd[$i]["percent"] * 10;
                $q1 = $perc;
                if ($perc > 60) $q1 = 16;
                if ($perc <= 60 && $perc > 30) $q1 = ($perc - 30) / 15 + 13;
                if ($perc <= 30 && $perc > 10) $q1 = ($perc - 10) / 7 + 10;

                if ($perc < -60) $q1 = -16;
                if ($perc >= -60 && $perc < -30) $q1 = ($perc + 30) / 15 - 13;
                if ($perc >= -30 && $perc < -10) $q1 = ($perc + 10) / 7 - 10;

                $q1 = $q1 > 0 ? ceil($q1 + 100) : ceil($q1 - 100);

                $V2P = $q1 * $q2;
                if ($perc <= 10 && $perc >= -10) $V2P = abs(110 * $q2);

                // 统计
                $s_V2P_day ++;
                $s_V2P += $V2P;

            }

            $stkR = [];
            $stkR['name'] = $stk['code'].'_'.$stk['name'];
            $stkR['NUM'] = $s_NUM_day ?  ceil($s_NUM / $s_NUM_day * 10000) / 100 : 0;
            $stkR['NUM_day'] = $s_NUM_day;
            $stkR['V2P'] = $s_V2P_day ?  ceil($s_V2P / $s_V2P_day) : 0;
            $stkR['V2P_day'] = $s_V2P_day;
            $stkR['total'] = count($dd);

            if($stkR['total'] > $maxTotal) $maxTotal = $stkR['total'];

            $stkD[$stk['code']] = $stkR;
        }

        //筛选
        $tmp = [];
        foreach($stkD as $k => $v) {
            if ( $v['total']/$maxTotal < 0.7 || $v['NUM_day']/$v['total'] < 0.5 ||
                $v['V2P_day']/$v['total'] < 0.3) continue;
            $tmp[$k] = $v;
        }

        $l1 = $tmp;
        $l2 = $tmp;
        array_multisort(array_column($l1,'NUM'), SORT_DESC, $l1);
        array_multisort(array_column($l2,'V2P'), SORT_DESC, $l2);
        $t1 = array_slice($l1, 0, 200);
        $t2 = array_slice($l2, 0, 200);
        $stkD = array_merge($t2, $t1);

        $i = 0;
        $table_content = '';
        foreach($stkD as $stkR) {
            $i ++;
            $table_content .= '<tr class="tr_'. ($i%2). '">';
            $table_content .= '<td>'. $i. '</td>';
            foreach ($stkR as $item) {
                $table_content .= '<td>'. $item. '</td>';
            }
            $table_content .= '</'. 'tr>';
        }

        $resultContent = str_replace("%%title%%", $title_content, $resultContent);
        $resultContent = str_replace("%%caption%%", $caption_content, $resultContent);
        $resultContent = str_replace("%%table%%", $table_content, $resultContent);
        file_put_contents($resultFile, $resultContent);
    }

}