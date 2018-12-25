<?php
/**
 * 容器类，
 * 实例化对象后，可注册到容器中，在程序其他逻辑中可重复获取该实例。
 * 提供注册，获取，删除，判断是否存在方法，
 * find方法，可以获取对象，并在对象不存在的时候，实例化并注册到容器。
 * */
class Container
{
    private static $_pool=array();

    /**
     * 获取 key为 cls 的对象，并在对象不存在时，实例化对象并注册到容器
     * @param string cls 类名，并且作为对象的key
     * @param ...args 变长参数列表，cls对象构造函数的参数
     * */
    public static function find( $cls )
    {
        $obj = self::get($cls);
        if( $obj ) {
            return $obj;
        }

        $obj = new $cls( );
        self::register($cls, $obj);

        return $obj;
    }


    /**
     * 注册对象到容器
     * @param string ley 对象键值
     * @param mixed obj 对象
     * */
    public static function register($key, $obj)
    {
        if( isset(self::$_pool[$key]) ) {
            return false;
        }
        self::$_pool[$key] = $obj;
        return true;
    }

    /**
     * 获取容器内的对象
     * @param string key 键值
     * */
    public static function &get($key)
    {
        if( isset(self::$_pool[$key]) ) {
            return self::$_pool[$key];
        }
        return null;
    }

    /**
     * 设置对象到容器
     * @param string ley 对象键值
     * @param mixed obj 对象
     * */
    public static function set($key, $obj)
    {
        self::$_pool[$key] = $obj;
        return true;
    }

    /**
     * 销毁容器内的对象
     * @param string key 键值
     * */
    public static function del( $key )
    {
        unset(self::$_pool[$key]);
        return true;
    }

    /**
     * 判断容器内是否有键值为key的对象
     * @param string key 键值
     * */
    public static function has( $key )
    {
        if( self::$_pool[$key]) {
            return true;
        }
        return false;
    }

}
