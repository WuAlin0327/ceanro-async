<?php
namespace Ceanro;

/**
 * 配置
 * @author wualin 2022-04-05
 * @email 1032298871@qq.com
 */
class Config
{

    private static $config = [];

    private static $range = 'system';

    public static function load($file = '', $name = '', $range = ''){
        $config = require_once $file;
        $range = $range ?: self::$range;
        if(!isset(self::$config[$range])){
            self::$config[$range] = [];
        }
        self::$config[$range] = array_merge(self::$config[$range], $config);
    }

    public static function get($name = '', $range = '' , $default = ''){
        $range = $range ?: self::$range;
        if(!$name){
            return self::$config[$range];
        }
        return isset(self::$config[$range][$name]) ? self::$config[$range][$name] : $default;
    }


}