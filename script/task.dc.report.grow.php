<?php

/**
 * 挖掘有基本面利好 任务
 */

if ($argc > 0 && basename($argv[0]) == 'task.dc.report.grow.php') {
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

        //self::filterReport('2018-11-30', '2018-05-09', '2017-12-01');
        //self::filterReport('2019-01-06', '2018-11-30', '2017-12-01');
        //self::filterReport('2019-12-19', '2019-09-20', '2019-06-01');
        //self::filterReport('2020-05-20', '2020-04-01', '2019-10-01');
        //self::filterReport('2020-06-01', '2020-05-20', '2020-03-01');
        //self::filterReport('2020-06-07', '2020-06-01', '2020-03-01');
        //self::filterReport('2020-06-14', '2020-06-07', '2020-03-01');
        //self::filterReport('2020-06-21', '2020-06-14', '2020-03-01');
        //self::filterReport('2020-06-28', '2020-06-21', '2020-03-01');
        //self::filterReport('2020-07-05', '2020-06-28', '2020-03-01');
        //self::filterReport('2020-08-08', '2020-07-08', '2020-03-01');
        //self::filterReport('2020-08-16', '2020-08-08', '2020-06-01');
        self::filterReport('2020-11-23', '2020-09-01', '2020-07-01');

    }

    /**
     * 开始时间$start 结束时间$end 展现时间$show
     */
    public static function filterReport($end, $start, $show)
    {
        $pattern = "/(不可限量|高景气|翻倍|翻番|超预期|大幅增长|高速增长|高增长|爆发|迅猛增长|暴增|收获期|历史性|机遇期|大时代|低估值|估值修复|洼地)/";
        $replacement = "<span class='sp'>$1</span>";
        $resultFile = OUTPUT_PATH. $end. '_'. $start. '_DCReport_Growth.html';
        $title_content = 'DC_Report 研报筛选 ('. $start. '~'. $end. ')';
        $caption_content = $start. '至'. $end. '研报筛选结果';
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
<script type="text/javascript">

window.onload = function () {
    document.getElementById("selectAll").onclick = function() {
	    var checkBoxObject = document.getElementsByName("sss");
	    for (var i = 0; i < checkBoxObject.length; i++) {
	        checkBoxObject[i].checked = true;
	    }
	};
	document.getElementById("unSelectAll").onclick = function() {
	    var checkBoxObject = document.getElementsByName("sss");
	    for (var i = 0; i < checkBoxObject.length; i++) {
	        checkBoxObject[i].checked = false;
	    }
	};
	document.getElementById("selectCopy").onclick = function() {
	    var checkBoxObject = document.getElementsByName("sss");
	    var sssText = " ";
	    for (var i = 0; i < checkBoxObject.length; i++) {
	    	//console.info(checkBoxObject[i].value);
	    	if (checkBoxObject[i].checked) {
	    		sssText = sssText + "\n" + checkBoxObject[i].value;
	    	}
	    }
	    var flag = copyText(sssText);
	};
}
</script>
</head>
<body>
<table cellspacing="1" cellpadding="2">
<caption>%%caption%%</caption>
<thead>
<tr>
	<input type="button" id="selectAll" value="全选">
    <input type="button" id="unSelectAll" value="全不选">
    <input type="button" id="selectCopy" value="获取">
</tr>
<tr class="thead_tr">
<th>勾选</th>
<th>名称</th>
<th>日期</th>
<th>机构</th>
<th>标题</th>
<th>详情</th>
<th>更多</th>
</tr>
</thead>
<tbody>

%%table%%

</tbody>
</table>
<script type="text/javascript">
function copyText(text) {
	//创建input对象
    var textarea = document.createElement("textarea");
    //当前获得焦点的元素
    var currentFocus = document.activeElement;
    //将文本框插入到NewsToolBox这个之后
    var toolBoxwrap = document.getElementById("NewsToolBox");
    //添加元素
    toolBoxwrap.appendChild(textarea);
    textarea.value = text;
    textarea.focus();
    if (textarea.setSelectionRange) {
    	//获取光标起始位置到结束位置
        textarea.setSelectionRange(0, textarea.value.length);
    } else {
        textarea.select();
    }
    try {
    	//执行复制
        var flag = document.execCommand("copy");
    } catch (eo) {
        var flag = false;
    }
    //删除元素
    toolBoxwrap.removeChild(textarea);
    currentFocus.focus();
    return flag;
}
</script>
<div id="NewsToolBox"></div>
</body>
</html>

        ';

        $limiter = 5;

        $stkL = Refer::getStock();
        $stkD = array();
        foreach($stkL as $stk){

            //if (--$limiter < 0 ) break;

            $stkBlock = array();

            $rd = StockData::genByCodeType($stk['code'], StockData::T_DC_REPORT);
            $rdd = $rd->getDayPeriod($show, $end);
            if (! $rdd) continue;

            $is_select = false;
            foreach ($rdd as $rddr) {
                $stkRow = array();
                $stkRow[] = $stk['code'].'_'.$stk['name'];
                $stkRow[] = $rddr['time'];
                $stkRow[] = $rddr['institute'];
                if ($rddr['time'] >= $start && $rddr['time'] <= $end) {
                    if (preg_match($pattern, $rddr['title'])){
                        $is_select = true;
                        $stkRow[] = preg_replace($pattern, $replacement, $rddr['title']);
                    } else {
                        $stkRow[] = $rddr['title'];
                    }

                } else {
                    $stkRow[] = $rddr['title'];
                }
                $stkRow[] = '<a target="_blank" href="' . $rddr['url']. '">打开</a>';
                $stkRow[] = '<a target="_blank" href="http://stock.jrj.com.cn/share,'.
                    Util::code2Num($stk['code']).',stockyanbao.shtml">金融界</a>&nbsp;'.
                    '<a target="_blank" href="http://vip.stock.finance.sina.com.cn/q/go.php/vReport_List/kind/search/index.phtml?t1=2&symbol='.
                    $stk['code'].'">新浪</a>&nbsp;'.'<a target="_blank" href="http://yanbao.stock.hexun.com/yb_'.
                    Util::code2Num($stk['code']).'.shtml">和讯</a>&nbsp;'.
                    '<a target="_blank" href="http://www.iwencai.com/search?typed=0&preParams=&ts=1&f=1&qs=result_tab&tid=report&w='.
                    $stk['name'].'">i问财</a>';
                array_unshift($stkBlock, $stkRow);
            }
            if ($is_select) {
                $concept = Concept::getByCode($stk['code']);
                if ($concept) {
                    $stkRow = array($stk['code'].'_'.$stk['name'],'','',$concept['board'],'','');
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
                $table_content .= "\n".'<tr class="tr_'. $i. '">';
            } else {
                $table_content .= "\n".'<tr class="tr_tr">';
            }
            $table_content .= '<td><input type="checkbox" name="sss" value="'.$stkR[0].'"></td>';
            foreach ($stkR as $item) {
                $table_content .= '<td>'. $item. '</td>';
            }
            $table_content .= '</'. 'tr>'."\n";
        }
        $resultContent = str_replace("%%title%%", $title_content, $resultContent);
        $resultContent = str_replace("%%caption%%", $caption_content, $resultContent);
        $resultContent = str_replace("%%table%%", $table_content, $resultContent);
        file_put_contents($resultFile, $resultContent);

    }

}
