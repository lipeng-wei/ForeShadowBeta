<?php

/**
 * 每周六 凌晨执行 更新 东财研报 脚本
 *
 *  step.dc.report.time 2017-08-09
 */


ini_set('memory_limit', '800M');

if ($argc > 0 && basename($argv[0]) == 'step.dc.report.php') {
    require_once(dirname(__FILE__). '/../require.php');
    // 同一时间同一机器不允许执行多个
    $file = fopen(__FILE__, "a");
    if (! flock($file, LOCK_EX | LOCK_NB)) {
        Log::info('已经有程序' . basename($argv[0]) . '在运行');
        exit(-1);
    }
    Log::info(basename($argv[0]) . ' 开始运行');
    DCReportStep::run();
    Log::info(basename($argv[0]) . ' 运行结束');
}


class DCReportStep
{

    public static function run()
    {
        self::updateReport();
    }

    public static function updateReport()
    {
        $cookie = TmpFile::genByName('step.dc.report.cookie');
        $cookie->renew(false);
        //访问data.eastmoney.com 生成cookie
        $url = 'http://data.eastmoney.com/report/';
        $ret = self::curlSinglePage($url, 'data.eastmoney.com', 'www.baidu.com',
            $cookie->getFileName());
        //var_dump($ret);

        Log::easyInfo('Begin Update report');

        //校验上次更新时间
        $timeFile = TmpFile::genByName('step.dc.report.time');
        $lastTime = $timeFile->get();
        $pattern = "/[0-9]{4}-[0-9]{2}-[0-9]{2}/";
        preg_match($pattern, $lastTime, $k);
        $lastTime = $k[0];
        if (! $lastTime){
            Log::easyError("Get LastTime Failed");
            return 0;
        }
        $thisTime = date("Y-m-d", strtotime('-1 day'));

        $page = 0;
        $limit = 8000;
        $list = array();
        while($page < $limit) {

            $page++;

            $t_rt = 50072929 + $page;
            $t_var = "pUXlYkcE";
            $t_page = $page;
            $url = "http://datainterface.eastmoney.com//EM_DataCenter/js.aspx?type=SR&sty=GGSR&js=var%20".$t_var.
                "={%22data%22:[(x)],%22pages%22:%22(pc)%22,%22update%22:%22(ud)%22,%22count%22:%22(count)%22}&".
                "ps=50&p=".$t_page."&mkt=0&stat=0&cmd=4&code=&rt=".$t_rt;

            Log::easyDebug('Get page', $t_page);
            Log::easyDebug('Get Url', $url);
            $ret = self::fetchSinglePage($url, 'datainterface.eastmoney.com', 'http://data.eastmoney.com/report/',
                $cookie->getFileName());
            $updata = self::parseReport($ret['data']);

            if ($updata) {
                $list = array_merge($updata, $list);
            }
            if ($list[0][1]['time'] <= $lastTime) break;
        }
        if ($list) {
            //var_dump($list);
            foreach($list as $new){

                if ($new[0]) {
                    $rd = StockData::genByCodeType($new[0],StockData::T_DC_REPORT);
                    $data = $rd->getAll();
                    if (! $data) {
                        $rd->appendSome(array($new[1]));
                        Log::easyInfo("Update", $new[0], $new[1]['time'], $new[1]['title'], "Success");
                        continue;
                    }

                    if ($new[1]['time'] > $lastTime && $new[1]['time'] <= $thisTime ){
                        $rd->appendSome(array($new[1]));
                        Log::easyInfo("Update", $new[0], $new[1]['time'], $new[1]['title'], "Success");
                    }
                }
            }
        }else {
            Log::easyError("Parse DCReport Failed");
        }
        $timeFile->renew();
        $timeFile->put($thisTime);
        Log::easyInfo("Finish Update DCReport");

        return 1;
    }

    public static function parseReport($json){

        $ret = array();

        foreach($json as $row){

            //code
            $reu = $row['secuFullCode'];
            $reu = substr($reu,0,6);
            $code = Util::num2Code($reu);


            //time
            $reu = $row['datetime'];
            $reu = substr($reu,0,10);
            $res['time'] = $reu;

            //rate
            $reu = $row['sratingName'];
            $res['rate'] = $reu;

            //change
            $reu = $row['change'];
            $res['change'] = $reu;

            //aim
            $res['aim'] = 0;

            //institute
            $reu = $row['insName'];
            $res['institute'] = $reu;

            //title
            $reu = $row['title'];
            $res['title'] = $reu;

            //url
            $reu = $res['time'];
            $reu = str_replace("-", "", $reu);
            $reu = 'http://data.eastmoney.com/report/'.$reu.'/'.$row['infoCode'].'.html';
            $res['url'] = $reu;

            array_unshift($ret , array($code, $res));

        }
        //var_dump($ret);
        return $ret;
    }

    public static function curlSinglePage($url, $host, $referer = null, $cookieFile = null){

        //初始化
        $ch = curl_init();

        //设置选项参数
        $header[]= 'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8 ';
        $header[]= 'Accept-Language: zh-CN,zh;q=0.8,en-US;q=0.5,en;q=0.3 ';
        //$header[]= 'Accept-Encoding: gzip, deflate ';
        $header[]= 'Cache-Control:	max-age=0 ';
        $header[]= 'Connection: Keep-Alive ';
        if ($host) $header[]= 'Host: '. $host. ' ';
        if ($referer) $header[]= 'Referer: '. $referer.' ';
        $header[]= 'User-Agent: Mozilla/5.0 (Windows NT 6.1; WOW64; rv:41.0) Gecko/20100101 Firefox/41.0 ';

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);//设置返回数据

        //用来告诉 PHP 在成功连接服务器前等待多久（连接成功之后就会开始缓冲输出）
        //这个参数是为了应对目标服务器的过载，下线，或者崩溃等可能状况。
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 8);
        //用来告诉成功 PHP 从服务器接收缓冲完成前需要等待多长时间，
        //如果目标是个巨大的文件，生成内容速度过慢或者链路速度过慢，这个参数就会很有用。
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        if ($cookieFile) {
            curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile); //保存
            curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile); //读取
        }

        //curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_HTTPHEADER,$header);

        //执行
        $contents = curl_exec($ch);
        //释放curl句柄
        curl_close($ch);
        return $contents;
    }

    public static function fetchSinglePage($url, $host=null, $referer=null, $cookieFile=null) {
        $retry = 18;
        while ($retry-- > 0) {
            $ret = self::curlSinglePage($url, $host, $referer, $cookieFile);
            if ( $ret ){
                $t = strpos($ret, '=');
                $content = substr($ret, $t + 1);
                $json = json_decode($content, true);
                if ($json) {
                    Util::successSleep();
                    return $json;
                }
            }
            Util::failedSleep();
        }
        return false;
    }

}
