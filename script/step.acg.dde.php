<?php

/**
 * 收盘后 抓取 爱查股 DDE 脚本
 *
 */


ini_set('memory_limit', '800M');

if ($argc > 0 && basename($argv[0]) == 'step.acg.dde.php') {
    require_once(dirname(__FILE__). '/../require.php');
    // 同一时间同一机器不允许执行多个
    $file = fopen(__FILE__, "a");
    if (! flock($file, LOCK_EX | LOCK_NB)) {
        Log::info('已经有程序' . basename($argv[0]) . '在运行');
        exit(-1);
    }
    Log::info(basename($argv[0]) . ' 开始运行');

    ACGDDEStep::run();

    Log::info(basename($argv[0]) . ' 运行结束');
}



class ACGDDEStep
{

    public static function run()
    {
        self::update();
    }

    public static function update()
    {
        Log::easyInfo('Begin Update ACG dde');

        $limit = 6000;

        $cookie = TmpFile::genByName('step.acg.dde.cookie');
        $cookie->renew(false);

        $rows = Refer::getStock();
        //Util::code2Num()
        foreach($rows as $row) {
            if ($limit-- < 0) break;


            self::updateSingle($row['code'], $row['name']);
        }
        Log::easyInfo("Finish Update ACG dde");

        return true;
    }
    public static function updateSingle($code, $name)
    {
        $code_num = Util::code2Num($code);
        $cookie = TmpFile::genByName('step.acg.dde.cookie');

        $url = "http://www.aichagu.com/ddx/{$code_num}.html";
        Log::easyInfo("Curl ", $url);

        $ret = self::curlSinglePage($url, '', '',
            $cookie->getFileName());
        //var_dump($ret);

        $url = "http://www.aichagu.com/ddx/data/data2.html?code={$code_num}";
        $refer = "http://www.aichagu.com/ddx/data/ddx_day.html?code={$code_num}";
        Log::easyDebug("Curl Data", $url);
        $ret = self::fetchSinglePage($url, '', $refer,
            $cookie->getFileName());
        //var_dump($ret);

        $data = self::parse($ret);
        if (empty($data)) {
            log::easyInfo('Parse Failed', $code, $name);
            return false;
        }
        //var_dump($data);

        $dde = StockData::genByCodeType($code, StockData::T_ACG_DDE);
        //$dde->getAll();

        $dde->putAll($data);
        Log::easyInfo("Update", $code, $name, "Success");

    }

    public static function parse($contents)
    {
        $ret = array();
        if ($contents){
            $rows = explode('|', $contents);
            //去重
            $lastime = 0;
            foreach($rows as $row){
                if (!$row) continue;
                $list = explode(',', $row);

                if (trim(end($list)) == $lastime) continue;
                else $lastime = trim(end($list));

                /*
                    ddx大单动向 大单买入净量占流通盘的百分比，越大越好。
                    ddy涨跌动因 每日卖出单数和买入单数的差占持仓人数的比例，越大越好。
                    ddz大单差分/tddz特大单差分 大资金买入强度，越高表示买入强度越大，越大越好。
                 */
                $res = array(
                    'time' => trim(end($list)),
                    'ddx' => trim($list[10]),
                    'ddy' => trim($list[3]),
                    'ddz' => trim($list[7]),
                    'tddz' => trim($list[8])
                );
                $ret []= $res;
            }
        }
        return $ret;
    }


    public static function fetchSinglePage($url, $host=null, $referer=null, $cookieFile=null) {
        $retry = 8;
        while ($retry-- > 0) {
            $ret = self::curlSinglePage($url, $host, $referer, $cookieFile);
            if ( $ret ){

                $cuts = explode('==', $ret);
                if (sizeof($cuts) == 3) {
                    $k = explode('<=>', $cuts[1]);
                    $k = end($k);
                    $k = explode(' ', $k);
                    $new = $k[0];


                    $k = explode('<=>', $cuts[2]);
                    $k = end($k);
                    if (substr($k,strlen($k)-1) != '|') $k .='|';
                    $new = $k . $new;

                    Util::successSleep();
                    return $new;

                } else {
                    Log::easyError("Data Wrong", $ret);
                }
            }
            Util::failedSleep();
        }
        return false;
    }


    public static function curlSinglePage($url, $host, $referer, $cookieFile){

        //初始化
        $ch = curl_init();

        //设置选项参数
        $header[]= 'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8 ';
        $header[]= 'Accept-Language: zh-CN,zh;q=0.8,zh-TW;q=0.7,zh-HK;q=0.5,en-US;q=0.3,en;q=0.2 ';
        $header[]= 'Cache-Control: no-cache ';
        $header[]= 'Pragma: no-cache ';
        $header[]= 'Connection: Keep-Alive ';
        $header[]= 'Upgrade-Insecure-Requests: 1 ';
        if ($host) $header[]= 'Host: '. $host. ' ';
        if ($referer) $header[]= 'Referer: '. $referer.' ';
        $header[]= 'User-Agent: Mozilla/5.0 (Windows NT 6.1; Win64; x64; rv:76.0) Gecko/20100101 Firefox/76.0 ';


        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);//设置返回数据

        if ($cookieFile) {
            curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile); //保存
            curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile); //读取
        }

        //curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);

        curl_setopt($ch, CURLOPT_ENCODING, 'gzip, deflate');

        //执行
        $contents = curl_exec($ch);

        //打印错误
        //var_dump(curl_error($ch));

        //释放curl句柄
        curl_close($ch);
        return $contents;
    }

}
