<?php

/**
 * 在一段时间内 dde飘红比例
 */

if ($argc > 0 && basename($argv[0]) == 'task.dde.php') {
    require_once(dirname(__FILE__). '/../require.php');
    // 同一时间同一机器不允许执行多个
    $file = fopen(__FILE__, "a");
    if (! flock($file, LOCK_EX | LOCK_NB)) {
        Log::info('已经有程序' . basename($argv[0]) . '在运行');
        exit(-1);
    }
    Log::info(basename($argv[0]) . ' 开始运行');

    DDE::run();

    Log::info(basename($argv[0]) . ' 运行结束');
}

class DDE
{
    public static function run()
    {

        //self::calcDDE('2018-12-05', '2019-01-05');
        //self::calcDDE('2020-05-15', '2020-06-01');
        //self::calcDDE('2020-05-23', '2020-06-07');
        //self::calcDDE('2020-05-30', '2020-06-14');
        //self::calcDDE('2020-06-07', '2020-06-21');
        self::calcDDE('2020-06-14', '2020-06-28');

    }

    /**
     * 开始时间$start 结束时间$end
     */
    public static function calcDDE($start, $end)
    {
        $resultFile = OUTPUT_PATH. $end. '_'. $start. '_DDE.html';
        $title_content = 'DDE 筛选 ('. $start. '~'. $end. ')';
        $caption_content = $start. '至'. $end. '日线DDE筛选结果';
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
<th>DDE</th>
<th>DDE_%</th>
<th>DDE_day</th>
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
        $limiter = 7000;



        $stkL = Refer::getStock();
        $stkD = array();
        $maxTotal = 0;
        foreach($stkL as $stk) {
            if (--$limiter < 0 ) break;

            $dde = StockData::genByCodeType($stk['code'], StockData::T_ACG_DDE);
            $dd = $dde->getAll();
            if (count($dd) < 60) continue;
            $dd = $dde->getDayPeriod($start, $end, 3);
            if (! $dd) continue;


            $s_dde = 0;
            $total = 0;
            foreach($dd as $d) {
                if ($d['ddx'] > 0) $s_dde ++;
                $total ++ ;
            }
            if ($total > $maxTotal) $maxTotal = $total;

            $stkR = [];
            $stkR['name'] = $stk['code'].'_'.$stk['name'];
            $stkR['DDE'] = $s_dde;
            $stkR['DDE_per'] = ceil($s_dde / $total * 10000) / 100;
            $stkR['DDE_day'] = $total;

            $stkD[$stk['code']] = $stkR;
        }

        //筛选
        $tmp = [];
        foreach($stkD as $k => $v) {
            if ( $v['DDE_day']/$maxTotal < 0.6 || $v['DDE_per'] < 60) continue;
            $v['total'] = $maxTotal;
            $tmp[$k] = $v;
        }

        array_multisort(array_column($tmp,'DDE_per'), SORT_DESC, $tmp);
        //$stkD = array_slice($tmp, 0, 100);
        $stkD = $tmp;

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