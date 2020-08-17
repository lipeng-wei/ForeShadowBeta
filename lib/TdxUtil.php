<?php
/**
 *  TDX软件 辅助工具
 */

class TdxUtil
{

    /**
     * 生成 画线导入文件
     *
     * getDrawText('sz300001', '20200723', 20.23, "上半年 +66%\r\n增发");
     *
     */
    static public function genDrawText($path_file, $param)
    {
        $content = "";
        foreach($param as $item) {
            $content .= self::getDrawText($item['code'], $item['day'], $item['price'], $item['text']);
        }
        file_put_contents($path_file, $content);
    }

    static public function getDrawText( $code, $day, $price, $text )
    {
        $content = "";
        $s = substr($code, 0 ,2);
        $s = strtolower($s);
        if ($s == 'sh')
            $content .= hex2bin("01");
        else
            $content .= hex2bin("00");

        $s = substr($code, 2);
        $content .= pack("a*", $s);

        $content .= pack("a17", "");
        $content .= hex2bin("025354414e444b00");
        $content .= pack("a54", "");
        $content .= pack("n", rand(0x0000, 0xffff));


        $a = floatval($price);
        $dirc = array("-5"=>-3,"-3"=>-2,"-1"=>-1,"1"=>1,"3"=>2,"5"=>3,"7"=>4,"9"=>5,"11"=>6);
        $dic = [-5,-3,-1,1,3,5,7,9,11];
        $first = 0;
        $second = 0;
        foreach ($dic as $i) {
            $ib = $i - 2;
            if($a>=pow(2, $ib) and $a<pow(2, $i)) {
                $first = $ib;
                break;
            }
        }
        $chushu = pow(2, $first);
        if($a >= $chushu*2){
            $second = $a / $chushu;
            $second = intval($second*64);
        } else {
            $second = $a / $chushu -1;
            $second = intval($second*128);
        }

        if($first<0)
            $first = 64 + $dirc[strval($first)];
        else
            $first = 63 + $dirc[strval($first)];

        $content .= pack("C", $second) . pack("C", $first);
        $content .= hex2bin("000000000000000000040016100000");
        $content .= pack("I", intval($day));

        $content .= pack("n", rand(0x0000, 0xffff));
        $content .= pack("C", $second) . pack("C", $first);
        $content .= pack("n", rand(0x0000, 0xffff));
        $content .= hex2bin("0000");

        $s = mb_convert_encoding($text,'CP936', 'UTF-8');
        $content .= pack("a154", $s);
        $content .= pack("a60", "");
        $content .= hex2bin("0000");

        $s = date("Ymd");
        $content .= pack("I", intval($s));
        $content .= hex2bin("00000000000000000000000000");
        $content .= hex2bin("00000101000000300000001000000008");
        $content .= hex2bin("000000ffff00002800000000");

        return $content;
    }


}