<?php

/**
 * 偶尔更新 Concept索引 脚本
 *
 */


ini_set('memory_limit', '100M');

if ($argc > 0 && basename($argv[0]) == 'step.concept.php') {
    require_once(dirname(__FILE__). '/../require.php');
    // 同一时间同一机器不允许执行多个
    $file = fopen(__FILE__, "a");
    if (! flock($file, LOCK_EX | LOCK_NB)) {
        Log::info('已经有程序' . basename($argv[0]) . '在运行');
        exit(-1);
    }
    Log::info(basename($argv[0]) . ' 开始运行');

    ConceptStep::run();

    Log::info(basename($argv[0]) . ' 运行结束');
}


class ConceptStep
{

    public static function run()
    {
        self::update();
    }

    public static function update()
    {

        $limit = 6000;
        Log::easyInfo('Begin Update Concept');

        $cookie = TmpFile::genByName('step.dc.concept.cookie');
        $cookie->renew(false);

//        $url = "http://f10.eastmoney.com/CoreConception/CoreConceptionAjax?code=SZ000063";
//        $refer = "http://f10.eastmoney.com/CoreConception/Index?type=web&code=SZ000063";
//        $host = "f10.eastmoney.com";
//        $ret = self::curlSinglePage($url, $host, $refer, $cookie->getFileName());
//        echo json_encode($ret, JSON_UNESCAPED_UNICODE);
//        exit(0);

        $rows = Refer::getStock();
        $update_data = array();

        $conceptFile = TmpFile::genByFilePath(DATA_PATH . 'concept/Concept.json');
        $conceptJson = json_decode($conceptFile->get(), true);
        if (empty($conceptJson)) $conceptJson = array();
        $conceptFile->renew(true);

        foreach($rows as $row) {
            if ($limit-- < 0) break;

            $url = "http://f10.eastmoney.com/CoreConception/CoreConceptionAjax?code={$row['code']}";
            $refer = "http://f10.eastmoney.com/CoreConception/Index?type=web&code={$row['code']}";
            $host = "f10.eastmoney.com";
            Log::easyInfo('Get Url', $url);

            $ret = self::fetchSinglePage($url, $host, $refer, $cookie->getFileName());
            if (empty($ret)) {
                Log::easyError("Update {$row['code']} {$row['name']} Concept Failed");
                continue;
            }
            $res = array(
                $row['code'] => array(
                    'board'=> $ret['hxtc'][0]['ydnr']
                )
            );

            //详情介绍
            // 暂时先不用了
//            $str = '';
//            foreach ($ret['hxtc'] as $vv) {
//                if ($vv['gjc'] == '所属板块') continue;
//                $str .= $vv['gjc']."\n".$vv['ydnr']."\n\n";
//            }
//            $res[$row['code']]['detail'] = trim($str);
            //var_dump($res);

            $update_data = array_merge($update_data, $res);
            if ($limit % 60 == 0) {
                $conceptJson = array_merge($conceptJson, $update_data);
                $content = json_encode($conceptJson, JSON_UNESCAPED_UNICODE);
                $conceptFile->put($content);
            }

        }
        $content = json_encode($conceptJson, JSON_UNESCAPED_UNICODE);
        $conceptFile->put($content);
        Log::easyInfo("Update Concept Finish");
        return true;
    }

    public static function parse($json){

        $ret = array();
        Container::set('total', $json['total']);

        foreach($json['data'] as $row){

            $res = [];
            $reu = explode(',', $row);

            //code
            if (substr($reu[1],0,2) == '68') continue; //去除科创板
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

    public static function fetchSinglePage($url, $host=null, $referer=null, $cookieFile=null){
        $retry = 6;
        while ($retry-- > 0) {
            $ret = self::curlSinglePage($url, $host, $referer, $cookieFile);
            if ( $ret ){
                $json = json_decode($ret, true);
                //echo $json['hxtc'][0]['gjc'];
                if (is_array($json) && $json['hxtc'] && $json['hxtc'][0]
                    && $json['hxtc'][0]['gjc'] == '所属板块')
                {
                    Util::successSleep();
                    return $json;
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
