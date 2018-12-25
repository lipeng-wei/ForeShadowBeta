<?php
/**
 *
 * 类 表 格式的 数据文件 处理工具
 *
 * 包含表头和数据
 * 列数据以 "|" 分割
 * 行数据以 "\r\n" 分割
 *
 */

class TableFileTool
{

    /**
     * 读取文件的数据，并按照表头来组合成数组
     */
    public static function get($file)
    {
        if (file_exists($file) == false) return false;

        $content = file_get_contents($file);
        if ($content === false) return false;

        $rows = explode("\r\n", $content);
        $title = false;
        $data = array();
        foreach($rows as $row){
            if (empty($row)) continue;
            $items = explode("|", $row);
            $adata = array();
            if (! $title){
                //处理表头
                $title = $items;
                continue;
            } else {
                //处理数据
                for($i = 0; $i<sizeof($items); $i++){
                    $adata[$title[$i]] = $items[$i];
                }
            }
            $data[] = $adata;
        }
        return $data;

    }

    /**
     * 写入数据，并生成表头
     */
    public static function put($file, $data)
    {
        $rows = array();
        $title = false;
        $special = array("|", "\r\n");
        foreach($data as $adata){
            if (empty($adata)) continue;
            if (! $title){
                //处理表头
                $title = array_keys($adata);
                $rows []= join("|", $title);
            }
            $row = array();
            foreach($title as $item){
                $row []= str_replace($special, " ", $adata[$item]);
            }
            $rows []= join("|", $row);

        }
        $content = join("\r\n", $rows);
        return file_put_contents($file, $content);
    }


    /**
     * 增加若干条数据，并按照表头顺序写入
     */
    public static function append($file, $data)
    {

        if (empty($data)) return true;

        if (! file_exists($file))
            return self::put($file, $data);

        $content = file_get_contents($file);
        if ($content === false) return false;

        //得到表头数据
        $rows = explode("\r\n", $content);
        $title = explode("|", $rows[0]);

        $rows = array();
        $special = array("|", "\r\n");

        //按照表头数据 来整合新数据
        foreach($data as $adata){
            if (! $adata) continue;
            $row = array();
            foreach($title as $item){
                $row[] = str_replace($special, " ", $adata[$item]);
            }
            $rows[] = join("|", $row);

        }
        $content = "\r\n" . join("\r\n", $rows);
        return file_put_contents($file, $content, FILE_APPEND);
    }
}