<?php

/**
 * 在一段时间内 dde飘红比例
 */

if ($argc > 0 && basename($argv[0]) == 'task.dde.percent.php') {
    require_once(dirname(__FILE__). '/../require.php');
    // 同一时间同一机器不允许执行多个
    $file = fopen(__FILE__, "a");
    if (! flock($file, LOCK_EX | LOCK_NB)) {
        Log::info('已经有程序' . basename($argv[0]) . '在运行');
        exit(-1);
    }
    Log::info(basename($argv[0]) . ' 开始运行');

    DDE_PERCENT::run();

    Log::info(basename($argv[0]) . ' 运行结束');
}

class DDE_PERCENT
{
    public static function run()
    {

        //self::calcDDE('2018-12-05', '2019-01-05');
        //self::calcDDE('2020-05-15', '2020-06-01');
        //self::calcDDE('2020-05-23', '2020-06-07');
        //self::calcDDE('2020-05-30', '2020-06-14');
        //self::calcDDE('2020-06-07', '2020-06-21');
        //self::calcDDE('2020-06-14', '2020-06-28');
        //self::calcDDE('2020-06-28', '2020-07-05');
        //self::calcDDE('2020-07-05', '2020-07-12');
        //self::calcDDE('2020-07-12', '2020-07-19');
        //self::calcDDE('2020-07-19', '2020-07-26');
        //self::calcDDE('2020-08-01', '2020-08-09');
        self::calcDDE('2020-08-08', '2020-08-16');

    }

    /**
     * 开始时间$start 结束时间$end
     */
    public static function calcDDE($start, $end)
    {
        $resultFile = OUTPUT_PATH. $end. '_'. $start. '_DDE_PERCENT.html';
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