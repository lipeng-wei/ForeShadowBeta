<?php

/**
 * 偶尔更新 Refer索引 脚本
 *
 */


ini_set('memory_limit', '100M');

if ($argc > 0 && basename($argv[0]) == 'step.refer.php') {
    require_once(dirname(__FILE__). '/../require.php');
    // 同一时间同一机器不允许执行多个
    $file = fopen(__FILE__, "a");
    if (! flock($file, LOCK_EX | LOCK_NB)) {
        Log::info('已经有程序' . basename($argv[0]) . '在运行');
        exit(-1);
    }
    Log::info(basename($argv[0]) . ' 开始运行');

    ReferStep::run();

    Log::info(basename($argv[0]) . ' 运行结束');
}


class ReferStep
{

    public static function run()
    {
        self::update();
    }

    public static function update()
    {

        $cookie = TmpFile::genByName('step.dc.refer.cookie');
        $cookie->renew(false);

        //访问data.eastmoney.com 生成cookie
        $re_url = 'http://quote.eastmoney.com/center/gridlist.html';
        $ret = self::curlSinglePage($re_url, 'quote.eastmoney.com', 'www.baidu.com',
            $cookie->getFileName());
        //var_dump($ret);

        Log::easyInfo('Begin Update refer');

        $page = 0;
        $limit = 800;
        $list = array();
        while($page < $limit) {

            $page++;

            $t_time = time() * 1000 + rand(100,299);
            $t_token = "4f1862fc3b5e77c150a2b985b12db0fd";
            $t_page = $page;
            $t_ps = 20;
            $url = 'http://nufm.dfcfw.com/EM_Finance2014NumericApplication/JS.aspx?cb=jQuery112407727835151512259_'.
                $t_time. '&type=CT&token='. $t_token.'&sty=FCOIATC&js=(%7Bdata%3A%5B(x)%5D%2CrecordsFiltered%3A(tot)%7D)&cmd=C._A&st=(Code)&sr=1&p='.
                $t_page. '&ps='. $t_ps. '&_='. ($t_time + rand(1,50));

            Log::easyDebug('Get page', $t_page);
            Log::easyDebug('Get Url', $url);
            $ret = self::fetchSinglePage($url, null, $re_url, $cookie->getFileName());
            $updata = self::parse($ret);

            $list = array_merge($updata, $list);

            $total = Container::get('total');
            Log::easyDebug('total', $total);
            if ($total && $t_page * $t_ps >= $total) break;
        }
        if (!empty($list)) {
            //var_dump($list);
            ksort($list);
            $referFile = TmpFile::genByFilePath(DATA_PATH . 'refer/Stock.json');
            $referFile->renew();
            $content = json_encode($list, JSON_UNESCAPED_UNICODE);
            $referFile->put($content);
        }else {
            Log::easyError("Update Refer Failed");
        }
        Log::easyInfo("Update Refer Finish");

        return true;
    }

    public static function parse($json){

        $ret = array();
        Container::set('total', $json['total']);

        foreach($json['data'] as $row){

            $res = [];
            $reu = explode(',', $row);

            //code
            $code = Util::num2Code($reu[1]);
            $res['code'] = Util::num2Code($reu[1]);

            //name
            $res['name'] = $reu[2];

            if (!$code) continue;
            $ret[$code] = $res;
        }
        //var_dump($ret);
        return $ret;
    }

    public static function curlSinglePage($url, $host=null, $referer=null, $cookieFile=null){

        //初始化
        $ch = curl_init();

        //设置选项参数
        $header[]= 'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8 ';
        $header[]= 'Accept-Language: zh-CN,zh;q=0.8,zh-TW;q=0.7,zh-HK;q=0.5,en-US;q=0.3,en;q=0.2 ';
        //$header[]= 'Accept-Encoding: gzip, deflate ';
        $header[]= 'Pragma: no-cache ';
        $header[]= 'Cache-Control: no-cache ';
        if ($host) $header[]= 'Host: '. $host. ' ';
        if ($referer) $header[]= 'Referer: '. $referer.' ';
        $header[]= 'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:64.0) Gecko/20100101 Firefox/64.0 ';

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

    public static function fetchSinglePage($url, $host=null, $referer=null, $cookieFile=null){
        $retry = 18;
        while ($retry-- > 0) {
            $ret = self::curlSinglePage($url, $host, $referer, $cookieFile);
            if ( $ret ){
                $pattern = "/\[.*\]/";
                preg_match($pattern, $ret, $k);
                $content = '{"data":'.$k[0].'}';
                //Log::easyDebug($content);
                $json = json_decode($content, true);
                if ($json && is_array($json)) {
                    $pattern = "/recordsFiltered:[0-9]+/";
                    preg_match($pattern, $ret, $k);
                    $j = explode(':', $k[0]);
                    $json['total'] = intval($j[1]);
                    //Log::easyDebug($content);
                    Util::successSleep();
                    return $json;
                }
            }
            Util::failedSleep();
        }
        return false;
    }

}
