<?php

/**
 * 每周六 凌晨执行 更新 东财资讯 脚本
 *
 * 爬取会封IP 暂时不用了
 */


ini_set('memory_limit', '800M');

if ($argc > 0 && basename($argv[0]) == 'step.dc.news.php') {
    require_once(dirname(__FILE__). '/../require.php');
    require_once(LIB_PATH . 'simplehtmldom_1_5/simple_html_dom.php');
    // 同一时间同一机器不允许执行多个
    $file = fopen(__FILE__, "a");
    if (! flock($file, LOCK_EX | LOCK_NB)) {
        Log::info('已经有程序' . basename($argv[0]) . '在运行');
        exit(-1);
    }
    Log::info(basename($argv[0]) . ' 开始运行');
    DCNewsStep::run();
    Log::info(basename($argv[0]) . ' 运行结束');
}


class DCNewsStep
{

    public static function run()
    {
        self::update();
    }

    public static function update()
    {
        Log::easyInfo('Begin Update DCNews');

        $limit = 6000;

        $cookie = TmpFile::genByName('step.dc.news.cookie');
        $cookie->renew(false);
        //访问data.eastmoney.com 生成cookie
        $url = 'https://www.eastmoney.com/';
        $ret = self::curlSinglePage($url, null, null, $cookie->getFileName());
        //var_dump($ret);

        //校验上次更新时间
        $timeFile = TmpFile::genByName('step.dc.news.time');
        $lastTime = $timeFile->get();
        $pattern = "/[0-9]{4}-[0-9]{2}-[0-9]{2}/";
        preg_match($pattern, $lastTime, $k);
        $lastTime = $k[0];
        if (! $lastTime){
            Log::easyError("Get LastTime Failed");
            return 0;
        }
        Log::easyInfo('Get Get LastTime', $lastTime);
        $thisTime = date("Y-m-d", strtotime('-1 day'));

        //self::updateSingle('sz002459', '晶澳科技', $lastTime);

        $rows = Refer::getStock();
        foreach($rows as $row) {
            if ($row['code']<='sz300408') continue;
            if ($limit-- < 0) break;


            self::updateSingle($row['code'], $row['name'], $lastTime);
        }

//        $timeFile->renew();
//        $timeFile->put($thisTime);
        Log::easyInfo("Finish Update DCNews");

        return 1;
    }

    public static function updateSingle($code, $name, $last_time)
    {
        $pattern = "/(融资|融券|大宗交易|日盘中跌幅|日盘中涨幅|龙虎榜|超大单流入|日快速反弹|日加速下跌|日快速上涨|日快速回调)/";

        $code_num = Util::code2Num($code);
        $cookie = TmpFile::genByName('step.dc.news.cookie');

        Log::easyInfo("Begin", $code, $name);

        $year = date('Y');
        $use_time = date('Y-m-d', strtotime('+1 day'));
        $update_data = array();


        //最多取18页
        $done = false;
        for ($i=1;$i<19;$i++) {

            //http://guba.eastmoney.com/list,002459,1,f_2.html
            $url = "http://guba.eastmoney.com/list,{$code_num},1,f.html";
            if ($i != 1) $url = "http://guba.eastmoney.com/list,{$code_num},1,f_{$i}.html";
            $refer = "http://guba.eastmoney.com/list,{$code_num}.html";
            $host = "guba.eastmoney.com";

            Log::easyInfo("Curl ", $url);
            $ret = self::fetchSinglePage($url, $host, $refer, $cookie->getFileName());
            //var_dump($ret);

            $data = self::parseHtml($ret);
            if (empty($data)) {
                log::easyError('Parse Failed', $code, $name);
                break;
            }
            //var_dump($data);

            foreach ($data as $row) {
                $tmp = $year.'-'.$row['time'];
                if ($tmp>$use_time) {
                    $year--;
                    $tmp = $year.'-'.$row['time'];
                }
                $use_time = date('Y-m-d', strtotime($tmp . '+1 day'));
                if ($use_time < $last_time) {
                    $done = true;
                    break;
                }
                if (preg_match($pattern, $row['title'])){
                    continue;
                }
                $row['time'] = date('Y-m-d', strtotime($tmp));
                //  '/news,600004,'
                if (substr($row['url'],0,13) != "/news,{$code_num},") continue;
                $row['url'] = 'http://guba.eastmoney.com'.$row['url'];

                array_unshift($update_data, $row);
            }
            if ($done) break;
        }
        //var_dump($update_data);
        if (empty($update_data)) {
            Log::easyInfo("Update", $code, $name, "Failed");

        } else {
            $news = StockData::genByCodeType($code, StockData::T_DC_NEWS);
            //$dde->getAll();

            $news->appendSome($update_data);
            Log::easyInfo("Update", $code, $name, "Success With", count($update_data));
        }

    }


    public static function parseHtml($html)
    {

        $rows = $html->find('div[id=articlelistnew] div.articleh');
        //echo $html->find('div[id=articlelistnew] div.articleh', 0)->plaintext;
        $ret = array();
        foreach ($rows as $row) {
            $list = $row->find('span');
            if (! $list[2] || ! $list[4]) continue;
            $res = array();

            //title
            $reu = trim($list[2]->find('a', 0)->title);
            $res['title'] = $reu;

            //url
            $reu = $list[2]->find('a', 0)->href;
            $res['url'] = $reu;

            //time
            $reu = trim($list[4]->plaintext);
            $res['time'] = $reu;

            array_push($ret, $res);
        }
        return $ret;
    }


    public static function fetchSinglePage($url, $host=null, $referer=null, $cookieFile=null) {
        $retry = 6;
        while ($retry-- > 0) {
            $ret = self::curlSinglePage($url, $host, $referer, $cookieFile);
            if ( $ret ){
                $html = str_get_html($ret);
                if ( $html){
                    $content = $html->find('div[id=articlelistnew]', 0);
                    //var_dump($content->plaintext);
                    if (! empty($content)) {
                        Util::successSleep();
                        return $html;
                    }
                }
            }
            Util::failedSleep();
        }
        return false;
    }

    public static function curlSinglePage($url, $host=null, $referer=null, $cookieFile=null){

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
