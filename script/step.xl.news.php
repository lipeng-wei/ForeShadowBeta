<?php

/**
 * 定期执行 更新 新浪资讯 脚本
 *
 */


ini_set('memory_limit', '800M');

if ($argc > 0 && basename($argv[0]) == 'step.xl.news.php') {
    require_once(dirname(__FILE__). '/../require.php');
    require_once(LIB_PATH . 'simplehtmldom_1_5/simple_html_dom.php');
    // 同一时间同一机器不允许执行多个
    $file = fopen(__FILE__, "a");
    if (! flock($file, LOCK_EX | LOCK_NB)) {
        Log::info('已经有程序' . basename($argv[0]) . '在运行');
        exit(-1);
    }
    Log::info(basename($argv[0]) . ' 开始运行');
    XLNewsStep::run();
    Log::info(basename($argv[0]) . ' 运行结束');
}


class XLNewsStep
{

    public static function run()
    {
        self::update();
    }

    public static function update()
    {
        Log::easyInfo('Begin Update XLNews');

        $limit = 1;

        $cookie = TmpFile::genByName('step.xl.news.cookie');
        $cookie->renew(false);
        $url = 'https://finance.sina.com.cn/stock/';
        $host = 'finance.sina.com.cn';
        $refer = 'https://www.so.com/s?ie=utf-8&fr=so.com&src=home_so.com&nlpv=basesc&q=%E6%96%B0%E6%B5%AA%E8%B4%A2%E7%BB%8F';
        $ret = self::curlSinglePage($url, $host, $refer, $cookie->getFileName());

//        $refer = "http://finance.sina.com.cn/realstock/company/sh600291/nc.shtml";
//        $url = "http://vip.stock.finance.sina.com.cn/corp/go.php/vCB_AllNewsStock/symbol/sh600291.phtml";
//        $host = 'vip.stock.finance.sina.com.cn';
//        $ret = self::curlSinglePage($url, $host, $refer, $cookie->getFileName());

//        $content = iconv('GB2312', 'UTF-8//IGNORE', $ret);
//        var_dump($content);


        //self::updateSingle('sz002459', '晶澳科技');

        $rows = Refer::getStock();
        foreach($rows as $row) {
            //if ($row['code']<='sz300408') continue;
            if ($limit-- < 0) break;

            self::updateSingle($row['code'], $row['name']);
        }

        Log::easyInfo("Finish Update XLNews");

        return 1;
    }

    public static function updateSingle($code, $name)
    {
        $pattern = "/(融资|融券|大宗交易|日盘中跌幅|日盘中涨幅|龙虎榜|超大单流入|日快速反弹|日加速下跌|日快速上涨|日快速回调)/";
        $cookie = TmpFile::genByName('step.xl.news.cookie');
        $update_data = array();
        $url = $host = $refer = '';
        $xln = StockData::genByCodeType($code, StockData::T_XL_NEWS);
        $t = $xln->getLastSolo(0);
        $last_time = date("Y-m-d H:i:s", strtotime('-6 month'));
        if($t && $t['time'] && $t['time']>$last_time) $last_time = $t['time'];
        //echo '$last_time', $last_time;

        Log::easyInfo("Begin", $code, $name);

        //最多取58页
        $done = false;
        for ($i=1;$i<3;$i++) {

            //http://vip.stock.finance.sina.com.cn/corp/go.php/vCB_AllNewsStock/symbol/sh600291.phtml
            //https://vip.stock.finance.sina.com.cn/corp/view/vCB_AllNewsStock.php?symbol=sh603538&Page=3

            if ($i == 1) {
                $refer = "http://finance.sina.com.cn/realstock/company/{$code}/nc.shtml";
                $url = "http://vip.stock.finance.sina.com.cn/corp/go.php/vCB_AllNewsStock/symbol/{$code}.phtml";
                $host = 'vip.stock.finance.sina.com.cn';
            } else {
                $refer = $url;
                $url = "https://vip.stock.finance.sina.com.cn/corp/view/vCB_AllNewsStock.php?symbol={$code}&Page={$i}";
                $host = 'vip.stock.finance.sina.com.cn';
            }

            Log::easyInfo("Curl ", $url);
            $ret = self::fetchSinglePage($url, $host, $refer, $cookie->getFileName());

            $data = self::parseHtml($ret);
            if (empty($data)) {
                log::easyError('Parse Failed', $code, $name);
                break;
            }
            //var_dump($data);

            foreach ($data as $row) {

                if ($row['in_time'] < $last_time) {
                    $done = true;
                    break;
                }
                if (preg_match($pattern, $row['title'])){
                    continue;
                }

                array_unshift($update_data, $row);
            }
            if ($done) break;
        }
        //var_dump($update_data);
        if (empty($update_data)) {
            Log::easyInfo("Update", $code, $name, "Failed");
            return false;
        }
        $xln->appendSome($update_data);
        Log::easyInfo("Update", $code, $name, "Success With", count($update_data));

        return true;
    }


    public static function parseHtml($html)
    {

        $content = $html->find('div.datelist ul', 0);
        //echo str_replace("&nbsp;"," ",$content->plaintext);

        $pattern = "/ [0-9]{4}-[0-9]{2}-[0-9]{2} [0-9]{2}:[0-9]{2} /";
        preg_match_all($pattern, str_replace("&nbsp;"," ",$content->plaintext), $t);
        //var_dump($t);

        $rows = $content->find('a');
        $i = 0;
        $ret = array();
        foreach ($rows as $row) {
            $res = array();

            //title
            $reu = trim($row->plaintext);
            $res['title'] = $reu;

            //url
            $reu = $row->href;
            $res['url'] = $reu;

            //in_time
            $reu = trim($t[0][$i]);
            $res['in_time'] = $reu;
            $i++;

            //time
            $res['time'] = date('Y-m-d', strtotime($reu));

            array_push($ret, $res);
        }
        return $ret;
    }


    public static function fetchSinglePage($url, $host=null, $referer=null, $cookieFile=null) {
        $retry = 6;
        while ($retry-- > 0) {
            $ret = self::curlSinglePage($url, $host, $referer, $cookieFile);
            $ret = iconv('GB2312', 'UTF-8//IGNORE', $ret);
            if ( $ret ){
                $html = str_get_html($ret);
                if ( $html){
                    $content = $html->find('div.datelist ul', 0);
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
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);//设置返回数据

        if (strtolower(substr($url,0,5)) == 'https') {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);   // 只信任CA颁布的证书
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false); // 检查证书中是否设置域名，
        }

        //用来告诉 PHP 在成功连接服务器前等待多久（连接成功之后就会开始缓冲输出）
        //这个参数是为了应对目标服务器的过载，下线，或者崩溃等可能状况。
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        //用来告诉成功 PHP 从服务器接收缓冲完成前需要等待多长时间，
        //如果目标是个巨大的文件，生成内容速度过慢或者链路速度过慢，这个参数就会很有用。
        curl_setopt($ch, CURLOPT_TIMEOUT, 8);

        //在屏幕打印请求连接过程和返回http数据
        //curl_setopt($ch, CURLOPT_VERBOSE, 1);

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
