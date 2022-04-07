<?php
namespace Ceanro;

use Swoole\Http\Request;

class Router
{

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
        list($path,$var) = self::parseUrlPath($request->server['path_info']);
        if($appMultiModule){
            // 多模块
            $module = $path[0];
        }
        $controller = '';
        if($autoSearch){
            $dir = APP_PATH . DS . $controllerFolder . DS;
            $find = false;
            $item = [];
            $pathList = [];
            foreach($path as $value){
                $item[] = $value;
                $file = $dir.$value;
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