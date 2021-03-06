<?php

/**
 * 在一段时间内 存在dde连续飘红
 */

if ($argc > 0 && basename($argv[0]) == 'task.dde.continue.php') {
    require_once(dirname(__FILE__). '/../require.php');
    // 同一时间同一机器不允许执行多个
    $file = fopen(__FILE__, "a");
    if (! flock($file, LOCK_EX | LOCK_NB)) {
        Log::info('已经有程序' . basename($argv[0]) . '在运行');
        exit(-1);
    }
    Log::info(basename($argv[0]) . ' 开始运行');

    DDE_CONTINUE::run();

    Log::info(basename($argv[0]) . ' 运行结束');
}

class DDE_CONTINUE
{
    public static function run()
    {

        self::calcDDE('2020-12-07', '2020-12-18', 5);

    }

    /**
     * 开始时间$start 结束时间$end 连续次数$times
     */
    public static function calcDDE($start, $end, $times)
    {
        $resultFile = OUTPUT_PATH. $end. '_'. $start. '_DDE_CONTINUE.html';
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
<th>编号</th>
<th>名称</th>
<th>DDE连续飘红次数</th>
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
        $limiter = 7000;



        $stkL = Refer::getStock();
        $stkD = array();
        foreach($stkL as $stk) {
            if (--$limiter < 0 ) break;

            $dde = StockData::genByCodeType($stk['code'], StockData::T_ACG_DDE);
            $dd = $dde->getAll();
            if (count($dd) < 60) continue;
            $dd = $dde->getDayPeriod($start, $end, $times);
            if (! $dd) continue;


            $s_dde = 0;
            $l_dde = 0;
            foreach($dd as $d) {
                if ($d['ddx'] >= 0) $s_dde ++;
                if ($d['ddx'] < -0.02) $s_dde = 0;
                if ($s_dde >= $l_dde) $l_dde = $s_dde;
            }
            if ($l_dde < $times) continue;

            $stkR = [];
            $stkR['name'] = $stk['code'].'_'.$stk['name'];
            $stkR['DDE_day'] = $l_dde;

            $stkD[$stk['code']] = $stkR;
        }

        array_multisort(array_column($stkD,'DDE_day'), SORT_DESC, $stkD);

        $i = 0;
        $table_content = '';
        foreach($stkD as $stkR) {
            $i ++;
            $table_content .= "\n".'<tr class="tr_'. ($i%2). '">';
            $table_content .= '<td><input type="checkbox" name="sss" value="'.$stkR['name'].'"></td>';
            $table_content .= '<td>'. $i. '</td>';
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