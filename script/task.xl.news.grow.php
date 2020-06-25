<?php

/**
 * 挖掘有 新浪资讯利好 任务
 */

ini_set('memory_limit', '800M');

if ($argc > 0 && basename($argv[0]) == 'task.xl.news.grow.php') {
    require_once(dirname(__FILE__). '/../require.php');
    // 同一时间同一机器不允许执行多个
    $file = fopen(__FILE__, "a");
    if (! flock($file, LOCK_EX | LOCK_NB)) {
        Log::info('已经有程序' . basename($argv[0]) . '在运行');
        exit(-1);
    }
    Log::info(basename($argv[0]) . ' 开始运行');

    XLGrow::run();

    Log::info(basename($argv[0]) . ' 运行结束');
}

class XLGrow
{

    public static function run()
    {
//        $pattern = array(
//            array(
//                "/(不可限量|高景气|超预期|爆发|暴增|历史性|机遇期|大时代)/" // AND
//            ), array(  // OR
//                "/(增|翻)/",  // AND
//                "/(倍|番|[0-9]{3,}\.?[0-9]*%|[^0-9\.][5-9][0-9]\.?[0-9]*%)/"
//            )
//        );
//        if (preg_match("/(倍|番|[0-9]{3,}\.?[0-9]*[%％]|[^0-9\.][5-9][0-9]\.?[0-9]*[%％])/", '美诺华2019年扣非后归母净利增134.53％ 制剂业务营收')){
//            echo "yes";
//        } else {
//            echo "no";
//        }


        self::filterNews('2020-06-14', '2020-05-14');

    }

    /**
     * 开始时间$start 结束时间$end
     */
    public static function filterNews($end, $start)
    {
        $pattern = array(
            array(
                "/(不可限量|高景气|超预期|爆发|暴增|历史性|机遇期|大时代)/" // AND
            ), array(  // OR
                "/(增|翻)/",  // AND
                "/(倍|番|[0-9]{3,}\.?[0-9]*[%％]|[^0-9\.][5-9][0-9]\.?[0-9]*[%％])/u"
            )
        );
        $replacement = "<span class='sp'>$1</span>";
        $resultFile = OUTPUT_PATH. $end. '_'. $start. '_XLNews_Growth.html';
        $title_content = 'XL_News 资讯筛选 ('. $start. '~'. $end. ')';
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
            Log::easyDebug('handle', $stk['code'], $stk['name']);
            $xlnd = StockData::genByCodeType($stk['code'], StockData::T_XL_NEWS);
            $xlndd = $xlnd->getDayPeriod($start, $end);
            if (! $xlndd) continue;

            $is_select = false;
            foreach ($xlndd as $nddr) {

                $stkRow = array();
                $is_or_ok = false;
                foreach ($pattern as $p_row) {
                    $is_and_ok = true;
                    foreach ($p_row as $p_node) {
                        if (! preg_match($p_node, $nddr['title'])){
                            $is_and_ok = false;
                            break;
                        }
                    }
                    if ($is_and_ok) {
                        $is_or_ok = true;
                        break;
                    }
                }
                if ($is_or_ok){
                    $is_select = true;
                    foreach ($pattern as $p_row) {
                        foreach ($p_row as $p_node) {
                            $nddr['title'] = preg_replace($p_node, $replacement, $nddr['title']);
                        }
                    }
                    $stkRow[] = $stk['code'].'_'.$stk['name'];
                    $stkRow[] = $nddr['time'];
                    $stkRow[] = $nddr['title'];
                    $stkRow[] = '<a target="_blank" href="' . $nddr['url']. '">打开</a>';
                    array_unshift($stkBlock, $stkRow);
                }

            }
            if ($is_select) {
                $concept = Concept::getByCode($stk['code']);
                if ($concept) {
                    $stkRow = array($stk['code'].'_'.$stk['name'],'',$concept['board'],'');
                    array_unshift($stkBlock, $stkRow);
                }
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
