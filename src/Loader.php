<?php

namespace Ceanro;

/**
 * 加载模块
 */
class Loader
{

    /**
     * 初始化控制器
     * @param string $module
     * @param string $controller
     * @param string $layer
     * @return string
     * @throws \Exception
     */
    public static function controller($module = 'index', $controller = 'index', $layer = ''){
        $request = Request::instance();
        if($module){
            $request->module($module);
        }
        $class = self::parseClass($module,$controller,$layer,false);
        if(class_exists($class)){
            return new $class($request);
        }else{
            throw new \Exception('Error: Controller Undefined');
        }
    }

    /**
     * 解析控制器
     * @param string $module
     * @param string $name
     * @param string $layer
     * @param false $appendSuffix
     * @return string
     */
    public static function parseClass($module, $name, $layer, $appendSuffix = false)
    {

        $array = explode('\\', str_replace(['/', '.'], '\\', $name));
        $class = self::parseName(array_pop($array), 1);
        $class = $class . (\Ceanro\App::$suffix || $appendSuffix ? ucfirst($layer) : '');
        $path  = $array ? implode('\\', $array) . '\\' : '';
        $controllers = Config::get('controller_folder');
        $multi = Config::get('app_multi_module') ? 'Http' : '';
        return \Ceanro\App::$namespace . '\\' . ($multi ? $multi .'\\' : '') .
            ($module ? ucfirst($module) . '\\' : '') . $controllers . '\\' .
            ($layer ? $layer . '\\' : '')  . $path . $class;
    }


    /**
     * 字符串命名风格转换
     * type 0 将 Java 风格转换为 C 的风格 1 将 C 风格转换为 Java 的风格
     * @access public
     * @param  string  $name    字符串
     * @param  integer $type    转换类型
     * @param  bool    $ucfirst 首字母是否大写（驼峰规则）
     * @return string
     */
    public static function parseName($name, $type = 0, $ucfirst = true)
    {
        if ($type) {
            $name = preg_replace_callback('/_([a-zA-Z])/', function ($match) {
                return strtoupper($match[1]);
            }, $name);

            return $ucfirst ? ucfirst($name) : lcfirst($name);
        }

        return strtolower(trim(preg_replace("/[A-Z]/", "_\\0", $name), "_"));
    }

}