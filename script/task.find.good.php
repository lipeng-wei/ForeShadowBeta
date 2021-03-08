<?php

/**
 * 在一段时间内找到 Good个股 并分析
 */

if ($argc > 0 && basename($argv[0]) == 'task.find.good.php') {
    require_once(dirname(__FILE__). '/../require.php');
    // 同一时间同一机器不允许执行多个
    $file = fopen(__FILE__, "a");
    if (! flock($file, LOCK_EX | LOCK_NB)) {
        Log::info('已经有程序' . basename($argv[0]) . '在运行');
        exit(-1);
    }
    Log::info(basename($argv[0]) . ' 开始运行');

    FindGood::run();

    Log::info(basename($argv[0]) . ' 运行结束');
}



class FindGood
{

    public static function run()
    {

        self::calcRange('2020-09-21', '2020-09-29', 3, 20);

    }

    /**
     * 查找：开始时间$start 结束时间$end 存在$n天内 最大涨幅超过$x(%)
     */
    public static function calcRange($start, $end, $n, $x)
    {

        $fileLabel = $end. '_'. $start. '_FindGood';
        $tableCaption = $end . 'FindGood筛选结果 ('. $start. '~'. $end. ')';
        $tableHead = '<th>勾选</th><th>编号</th><th>代码</th><th>涨幅</th>';
        $tableContent = '';

        $limiter = 2;
        $stkL = Refer::getStock();
        $stkD = array();
        foreach($stkL as $stk) {

            //if (--$limiter < 0 ) break;

            $kd = StockData::genByCodeType($stk['code'], StockData::T_K_DAY);
            $dd = $kd->getAll();
            if (count($dd) < 60) continue;
            $dd = $kd->getDayPeriod($start, $end, $n - 1);
            if (! $dd) continue;

            $MaxRange = -2;
            for ($i = 0; $i < count($dd); $i++){
                $daySlice = array_slice($dd, $i, $n);
                if (! $daySlice) continue;
                $dMax = Logic::highValue($daySlice, 'close');
                $dMin= $daySlice[0]['close'];
                $dR = ceil(($dMax - $dMin) / $dMin * 100);
                $MaxRange = $MaxRange < $dR ? $dR : $MaxRange;
            }

            if ($MaxRange < $x) continue;

            $stkD []= [
                'a' => $stk['code'].'_'.$stk['name'],
                'b' => $MaxRange
            ];
        }
        array_multisort(array_column($stkD,'b'), SORT_DESC, $stkD);

        $i = 0;
        foreach($stkD as $stkR) {
            $i ++;
            $tableContent .= "\n".'<tr class="tr_'. ($i%2). '">';
            $tableContent .= '<td><input type="checkbox" name="sss" value="'.$stkR['a'].'"></td>';
            $tableContent .= '<td>'. $i. '</td>';
            $tableContent .= '<td>'. $stkR['a']. '</td>';
            $tableContent .= '<td>'. $stkR['b']. '</td>';
            $tableContent .= '</'. 'tr>'."\n";
        }

        self::outputHtml($fileLabel, $tableCaption, $tableHead, $tableContent);

    }

    public static function outputHtml($fileLabel, $tableCaption, $tableHead, $tableContent)
    {
        $resultFile = OUTPUT_PATH. $fileLabel. '.html';
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

%%head%%

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

        $resultContent = str_replace("%%title%%", $tableCaption, $resultContent);
        $resultContent = str_replace("%%caption%%", $tableCaption, $resultContent);
        $resultContent = str_replace("%%head%%", $tableHead, $resultContent);
        $resultContent = str_replace("%%table%%", $tableContent, $resultContent);
        file_put_contents($resultFile, $resultContent);
    }

}