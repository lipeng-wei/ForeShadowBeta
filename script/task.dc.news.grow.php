<?php

/**
 * 挖掘有 资讯利好 任务
 */

if ($argc > 0 && basename($argv[0]) == 'task.dc.news.grow.php') {
    require_once(dirname(__FILE__). '/../require.php');
    // 同一时间同一机器不允许执行多个
    $file = fopen(__FILE__, "a");
    if (! flock($file, LOCK_EX | LOCK_NB)) {
        Log::info('已经有程序' . basename($argv[0]) . '在运行');
        exit(-1);
    }
    Log::info(basename($argv[0]) . ' 开始运行');

    DCGrow::run();

    Log::info(basename($argv[0]) . ' 运行结束');
}

class DCGrow
{

    public static function run()
    {

        self::filterNews('2020-06-07', '2020-06-01', '2020-03-01');

    }

    /**
     * 开始时间$start 结束时间$end 展现时间$show
     */
    public static function filterNews($end, $start, $show)
    {
        $pattern = "/(不可限量|高景气|翻倍|翻番|加速|超预期|大幅增长|高速增长|高增长|爆发|迅猛增长|暴增|收获期|放量|快速增长|历史性|机遇期|大时代)/";
        $replacement = "<span class='sp'>$1</span>";
        $resultFile = OUTPUT_PATH. $end. '_'. $start. '_DCNews_Growth.html';
        $title_content = 'DC_News 资讯筛选 ('. $start. '~'. $end. ')';
        $caption_content = $start. '至'. $end. '资讯筛选结果';
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
    .tr_1 {background-color:#CCFFCC; color:#000; padding:18px;}
    .tr_2 {background-color:#99CCCC; color:#000; padding:18px;}
    .tr_3 {background-color:#FFFFCC; color:#000; padding:18px;}
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
<th>代码</th>
<th>名称</th>
<th>日期</th>
<th>标题</th>
<th>详情</th>
</tr>
</thead>
<tbody>

%%table%%

</tbody>
</table>
</body>
</html>

        ';

        $limiter = 6000;

        $stkL = Refer::getStock();
        $stkD = array();
        foreach($stkL as $stk){

            if (--$limiter < 0 ) break;

            $stkBlock = array();

            $nd = StockData::genByCodeType($stk['code'], StockData::T_DC_NEWS);
            $ndd = $nd->getDayPeriod($show, $end);
            if (! $ndd) continue;

            $is_select = false;
            foreach ($ndd as $nddr) {
                $stkRow = array();
                $stkRow[] = $stk['code'].'_'.$stk['name'];
                $stkRow[] = $nddr['time'];
                if ($nddr['time'] >= $start && $nddr['time'] <= $end) {
                    if (preg_match($pattern, $nddr['title'])){
                        $is_select = true;
                        $stkRow[] = preg_replace($pattern, $replacement, $nddr['title']);
                    } else {
                        $stkRow[] = $nddr['title'];
                    }

                } else {
                    $stkRow[] = $nddr['title'];
                }
                $stkRow[] = '<a target="_blank" href="' . $nddr['url']. '">打开</a>';
                array_unshift($stkBlock, $stkRow);
            }
            if ($is_select) {
                $stkD = array_merge($stkD, $stkBlock);
            }

        }

        $i = 0;
        $l_code = '';
        $table_content = '';
        foreach($stkD as $stkR) {
            if ($stkR['0'] != $l_code) {
                $i = ($i + 1) % 4;
                $l_code = $stkR['0'];
            }
            if ($stkR[2]  >= $start) {
                $table_content .= '<tr class="tr_'. $i. '">';
            } else {
                $table_content .= '<tr class="tr_tr">';
            }
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
