<?php

/**
 * 收盘后 抓取 雪球K线 脚本
 *
 */


ini_set('memory_limit', '800M');
global $cookie;

if ($argc > 0 && basename($argv[0]) == 'step.xq.k.day.php') {
    require_once(dirname(__FILE__). '/../require.php');
    // 同一时间同一机器不允许执行多个
    $file = fopen(__FILE__, "a");
    if (! flock($file, LOCK_EX | LOCK_NB)) {
        Log::info('已经有程序' . basename($argv[0]) . '在运行');
        exit(-1);
    }
    Log::info(basename($argv[0]) . ' 开始运行');

    global $cookie;
    XQKtStep::refreshCookie();
    //var_dump($cookie);


    XQKtStep::run();

    Log::info(basename($argv[0]) . ' 运行结束');
}



class XQKtStep
{

    public static function run()
    {
        self::update();
    }

    public static function update()
    {
        Log::easyInfo('Begin Update k day');

        $row = Refer::getBySHZS();
        self::updateSingle($row['code'], $row['name']);

        $limit = 6000;

        $rows = Refer::getStock();
        foreach($rows as $row) {
            if ($limit-- < 0) break;

            self::updateSingle($row['code'], $row['name']);
        }
        Log::easyInfo("Finish Update k day");

        return true;
    }

    public static function updateSingle($code, $name)
    {
        $re_url = 'https://xueqiu.com/S/'. strtoupper($code);
        $t_time = time() * 1000 - rand(0,999);

        $url = "https://stock.xueqiu.com/v5/stock/chart/kline.json?symbol=". strtoupper($code).
            "&begin=". $t_time. "&period=day&type=before&count=-142&indicator=kline,ma,macd";

        $kd = StockData::genByCodeType($code, StockData::T_K_DAY);
        //$kd->getAll();

        Log::easyDebug('Get Url', $code, $name, $url);
        $ret = self::fetchSinglePage($url, 'stock.xueqiu.com', $re_url);
        if ($ret === false) {
            log::easyError('Fetch Failed', $code, $name, $url);
            return false;
        }
        $data = self::parse($ret['data']);
        if (empty($data)) {
            log::easyError('Parse Failed', $code, $name);
            return false;
        }
        //var_dump($data);
        //exit(0);
        $kd->putAll($data);
        Log::easyInfo("Update", $code, $name, "Success");
        return true;
    }

    public static function parse($json)
    {
        $col = $json['column'];
        $ret = array( );
        foreach ($json['item'] as $row) {

            $reu = array_combine($col, $row);

            $res = [
                'time' => date("Y-m-d", round($reu['timestamp']/1000)),
                "volume" => $reu['volume'],
                "open" => $reu['open'],
                "high" => $reu['high'],
                "low" => $reu['low'],
                "close" => $reu['close'],
                "chg" => $reu['chg'],
                "percent" => $reu['percent'],
                "turnoverrate" => $reu['turnoverrate'],
                "ma5" => $reu['ma5'],
                "ma10" => $reu['ma10'],
                "ma20" => $reu['ma20'],
                "ma30" => $reu['ma30'],
                "dea" => $reu['dea'],
                "dif" => $reu['dif'],
                "macd" => $reu['macd']
            ];
            $ret[$res['time']] = $res;
        }
        ksort($ret);
        return array_values($ret);
    }

    public static function curlSinglePage($url, $host=null, $referer=null)
    {

        //初始化
        $ch = curl_init();

        //设置选项参数
        $header[]= 'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8 ';
        $header[]= 'Accept-Language: zh-CN,zh;q=0.8,zh-TW;q=0.7,zh-HK;q=0.5,en-US;q=0.3,en;q=0.2 ';
        //$header[]= 'Accept-Encoding: gzip, deflate ';
        $header[]= 'Cache-Control: no-cache ';
        $header[]= 'Connection: keep-alive ';
        if ($host) $header[]= 'Host: '. $host. ' ';
        $header[]= 'Pragma: no-cache ';
        if ($referer) $header[]= 'Referer: '. $referer.' ';
        $header[]= 'Upgrade-Insecure-Requests: 1 ';
        $header[]= 'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:64.0) Gecko/20100101 Firefox/64.0 ';
        //$header[]= 'Cookie: Hm_lvt_1db88642e346389874251b5a1eded6e3=1545819613,1545820898,1545820901,1545820912; bid=f47f45ddf81ffc5244394dbbae7a9bd7_jbx3bldi; _ga=GA1.2.86774281.1508467571; device_id=292d53301abbebe64f38ee4718079a32; s=e419ggu7zd; _gid=GA1.2.519558385.1545620780; xq_a_token=77558d457753417e6d204c3c194af3b8be6d159b; xq_a_token.sig=6vYUkYxPfp54csZymplW5bCPODE; xq_r_token=906ae7bb3c63a6e63cca8212303e7d4151693188; xq_r_token.sig=aKD-9IKtPgyogiXsU8XCdFMevnY; Hm_lpvt_1db88642e346389874251b5a1eded6e3=1545827875; u=161545819613874';

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);//设置返回数据

        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);   // 只信任CA颁布的证书
        //curl_setopt($ch, CURLOPT_CAINFO, DATA_PATH . 'xueqiu.com.cacert.pem'); // CA根证书（用来验证的网站证书是否是CA颁布）
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false); // 检查证书中是否设置域名，

        //用来告诉 PHP 在成功连接服务器前等待多久（连接成功之后就会开始缓冲输出）
        //这个参数是为了应对目标服务器的过载，下线，或者崩溃等可能状况。
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 8);
        //用来告诉成功 PHP 从服务器接收缓冲完成前需要等待多长时间，
        //如果目标是个巨大的文件，生成内容速度过慢或者链路速度过慢，这个参数就会很有用。
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        //在屏幕打印请求连接过程和返回http数据
        //curl_setopt($ch, CURLOPT_VERBOSE, 1);

        //curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);

        // 使用 cookie 全局变量
        // 跨域的问题 导致用文件记录cookie不行 自己过滤cookie
        global $cookie;
        curl_setopt($ch,CURLOPT_COOKIE,$cookie);

        //执行
        $contents = curl_exec($ch);
        //释放curl句柄
        curl_close($ch);
        return $contents;
    }

    public static function fetchSinglePage($url, $host=null, $referer=null)
    {
        $retry = 8;
        while ($retry-- > 0) {
            $ret = self::curlSinglePage($url, $host, $referer);

            if ( $ret ){
                $json = json_decode($ret, true);
                if ($json['error_code'] > 0) {
                    sleep(30);
                    self::refreshCookie();
                }
                if ($json && $json['data'] && $json['data']['item'] && $json['data']['item'][0]
                    && $json['data']['item'][0][0]
                ) {
                    Util::successSleep();
                    return $json;
                }
            }
            Util::failedSleep();
        }
        return false;
    }

    public static function refreshCookie()
    {
        $url = 'https://xueqiu.com/';
        global $cookie;
        // 初始化CURL
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        // 获取头部信息
        curl_setopt($ch, CURLOPT_HEADER, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $content = curl_exec($ch);
        //var_dump($content);
        curl_close($ch);
        // 解析http数据流
        list($header, $body) = explode("\r\n\r\n", $content);
        // 解析cookie
        preg_match("/set\-cookie: (xq_a_token=[^;]*;)/i", $header, $matches);
        $cookie = $matches[1];
        Log::easyDebug('Refresh Cookie', $cookie);
        return true;
    }

}
