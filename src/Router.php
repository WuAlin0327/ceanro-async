<?php
namespace Ceanro;

use Swoole\Http\Request;

class Router
{
    /**
     * @var array 路由注册
     */
    protected static $map = [];

    /**
     * @var array 路由前缀绑定模块
     */
    protected static $bind = [];

    /**
     * 绑定路由
     * @param $url string 可以是正则表达式
     * @param $dispatch
     */
    static function register($arr = []){
        self::$map = array_merge(self::$map,$arr);
    }

    static function bind($arr = []){
        self::$bind = array_merge(self::$bind,$arr);
    }

    static function escape($str){
        $str = str_replace('/','\/',$str);
        return $str;
    }

    /**
     * 路由调度
     * @param Request $request
     * @return array
     */
    static function dispatch(Request $request){
        $appMultiModule = Config::get('app_multi_module');
        $controllerFolder = Config::get('controller_folder');
        $autoSearch = Config::get('auto_search');
        $module = '';
        $pathInfo = $request->server['path_info'];
        $routerRegister = array_keys(self::$map);
        $routerRegister = array_merge($routerRegister,array_keys(self::$map));
        $controller = null;
        $action = null;
        // 注册了路由
        foreach($routerRegister as $key){
            preg_match('/'.self::escape($key).'/',$pathInfo,$ret);
            if(!empty($ret)){
                $requestMethod = strtoupper($request->server['request_method']);
                $router = self::$map[$key];
                if(is_array($router)){
                    if(count($router) == 2 && strtoupper($router[1]) != strtoupper($requestMethod)){
                        break;
                    }
                    $routerController = $router[0];
                }else if(is_string($router)){
                    $routerController = $router;
                }
                list($controller,$action) = explode('.',$routerController);
                $controller = str_replace($controllerFolder.'/','',$controller);
                $controller = str_replace('/','.',$controller);
                break;
            }
        }
        if(!$controller && !$action){
            list($path,$var) = self::parseUrlPath($pathInfo);
            if($appMultiModule){
                // 多模块
                $module = $path[0];
                if (isset(self::$bind[$module])){
                    $module = self::$bind[$module];
                }
                array_shift($path);
            }
            $controller = '';
            if($autoSearch){
                if(!$appMultiModule){
                    $dir = APP_PATH . DS . $controllerFolder . DS;
                }else{
                    $dir = APP_PATH . DS . 'Http' . DS . ucfirst($module) . DS . $controllerFolder . DS;
                }
                $find = false;
                $item = [];
                $pathList = [];
                foreach($path as $value){
                    $value = ucfirst($value);
                    $item[] = $value;
                    $file = $dir.$value;
                    $pathList[] = $file;
                    if(file_exists($file.'.'.EXT)){
                        $find = true;
                        break;
                    }else{
                        $dir .= DS . $value . DS;
                    }
                }
                if($find){
                    $path = array_slice($path, count($item));
                    $controller = implode('.', $item);
                }
            }else{
                $controller = !empty($path) ? array_shift($path) : null;
            }
            // 解析操作
            $action = !empty($path) ? array_shift($path) : null;
        }
        return [ 'module' => $module,'controller' => $controller,'action' => $action];
    }

    public function parseClass(){

    }

    /**
     * 解析URL的pathinfo参数和变量
     * @access private
     * @param string $url URL地址
     * @return array
     */
    public static function parseUrlPath($url)
    {
        // 分隔符替换 确保路由定义使用统一的分隔符
        $url = str_replace('|', '/', $url);
        $url = trim($url, '/');
        $var = [];
        if (false !== strpos($url, '?')) {
            // [模块/控制器/操作?]参数1=值1&参数2=值2...
            $info = parse_url($url);
            $path = explode('/', $info['path']);
            parse_str($info['query'], $var);
        } elseif (strpos($url, '/')) {
            // [模块/控制器/操作]
            $path = explode('/', $url);
        } else {
            $path = [$url];
        }
        return [$path, $var];
    }

}