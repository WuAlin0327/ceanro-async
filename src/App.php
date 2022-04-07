<?php

namespace Ceanro;

use Swoole\Http\Request;
use Swoole\Http\Response;
class App
{

    private static $server = null;

    private static $events = ['request'];

    private static $dispatch = [];

    public static $namespace = 'App';

    public static $suffix = '';


    public static function run(){
        Config::load(CONFIG_PATH);
        $config = Config::get();
        self::$server = new \Swoole\Http\Server($config['host'],$config['port']);
        self::$server->set([
            "worker_num"    => $config['worker_num'],
//            "daemonize" => true,
//            "reload_async" => false,
        ]);
        foreach (self::$events as $event){
            self::$server->on($event,function ( Request $request, Response $response) use($config) {
                if($request->server['request_uri'] == '/favicon.ico'){
                    $response->status(500);
                    $response->end();
                    return false;
                }
                \Ceanro\Request::instance($request);
                $dispatch = self::$dispatch ?: [];
                if(empty($dispatch)){
                    $dispatch = Router::dispatch($request);
                }
                $config['debug'] && Log::write('[APP_INIT] ' . __FILE__ . ' LINE:' .__LINE__);
                try {
                    \Ceanro\Request::$dispatch = $dispatch;
                    $res = self::exec($dispatch,$config);
                }catch (\Exception $exception){
                    $response->status(501);
                    $response->end(json_encode([
                        'code'  => 0,
                        'msg'   => $exception->getMessage(),
                        'time'  => time(),
                        'data'  => $dispatch
                    ]));
                    return false;
                }
                $response->end(json_encode($res));
                \Ceanro\Request::destruct();
                return true;
            });
        }
        self::$server->start();
    }

    /**
     * @param $dispatch array
     * @param $config array
     */
    public static function exec($dispatch, $config){
        $request = \Ceanro\Request::instance();
        list($path,$var) = Router::parseUrlPath($request->server['path_info']);
        $object = Loader::controller($dispatch['module'],$dispatch['controller']);
        $action = $dispatch['action'] ?: 'index';
        if(!method_exists($object,$action)){
            throw new \Exception('Method Undefined');
        }
        $call = [$object, $action];
        $vars = $request->query();
        return self::invokeMethod($call,$vars);
    }


    /**
     *  调用反射类，执行控制器方法
     * @access public|static
     * @param array $method
     * @param array $vars
     * @return mixed
     * @throws \ReflectionException
     */
    public static function invokeMethod($method, $vars = []){
        if (is_array($method)) {
            $class   = is_object($method[0]) ? $method[0] : self::invokeClass($method[0]);
            $reflect = new \ReflectionMethod($class, $method[1]);
        } else {
            // 静态方法
            $reflect = new \ReflectionMethod($method);
        }
        $args = self::bindParams($reflect,$vars);
        return $reflect->invokeArgs(isset($class) ? $class : null, $args);
    }

    /**
     * 调用反射执行类的实例化 支持依赖注入
     * @access public
     * @param \ReflectionMethod $class 类名
     * @param array  $vars  变量
     * @return mixed
     */
    public static function invokeClass($class, $vars = [])
    {
        $reflect     = new \ReflectionClass($class);
        $args        = self::bindParams($reflect,$vars);

        return $reflect->newInstanceArgs($args);
    }

    /**
     * 绑定参数
     * @access private
     * @param \ReflectionMethod|\ReflectionFunction $reflect 反射类
     * @param array                                 $vars    变量
     * @return array
     */
    private static function bindParams($reflect, $vars = [])
    {
        // 自动获取请求变量
        if (empty($vars)) {

        }

        $args = [];
        if ($reflect->getNumberOfParameters() > 0) {
            // 判断数组类型 数字数组时按顺序绑定参数
            reset($vars);
            $type = key($vars) === 0 ? 1 : 0;
            foreach ($reflect->getParameters() as $param) {
                $args[] = self::getParamValue($param, $vars, $type);
            }
        }

        return $args;
    }

    /**
     * 获取参数值
     * @access private
     * @param \ReflectionParameter  $param 参数
     * @param array                 $vars  变量
     * @param string                $type  类别
     * @return array
     */
    private static function getParamValue($param, &$vars, $type)
    {
        $name  = $param->getName();
        $class = $param->getClass();

        if ($class) {
            $className = $class->getName();
            $bind      = \Ceanro\Request::instance()->$name;
            if ($bind instanceof $className) {
                $result = $bind;
            } else {
                if (method_exists($className, 'invoke')) {
                    $method = new \ReflectionMethod($className, 'invoke');

                    if ($method->isPublic() && $method->isStatic()) {
                        return $className::invoke(\Ceanro\Request::instance());
                    }
                }

                $result = method_exists($className, 'instance') ?
                    $className::instance() :
                    new $className;
            }
        } elseif (1 == $type && !empty($vars)) {
            $result = array_shift($vars);
        } elseif (0 == $type && isset($vars[$name])) {
            $result = $vars[$name];
        } elseif ($param->isDefaultValueAvailable()) {
            $result = $param->getDefaultValue();
        } else {
            throw new \InvalidArgumentException('method param miss:' . $name);
        }

        return $result;
    }

}