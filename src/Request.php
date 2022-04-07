<?php

namespace Ceanro;

class Request
{

    protected static $instance = null;
    protected $input = [];
    public $server = [];
    public $header = null;
    public $cookie = null;
    public $get = null;
    public $files = null;
    public $post = null;
    public $tmpfiles = null;
    public static $dispatch = [];
    public $module = '';
    public static $query = [];

    /**
     * 构造函数
     * @access protected
     * @param array $options 参数
     */
    protected function __construct($options = [])
    {
        foreach ($options as $name => $item) {
            if (property_exists($this, $name)) {
                $this->$name = $item;
            }
        }
        // 保存 php://input
        $this->input = file_get_contents('php://input');
    }


    /**
     * 析构
     */
    public static function destruct(){
        self::$instance = null;
        self::$query = [];
    }

    /**
     * 初始化
     * @access public
     * @param array $options 参数
     */
    public static function instance($options = [])
    {
        if (is_null(self::$instance)) {
            self::$instance = new static($options);
        }
        return self::$instance;
    }

    public function query(){
        if(!self::$query){
            $queryParams = [];
            if($this->get){
                $queryParams = array_merge($queryParams,$this->get);
            }
            if($this->post){
                $queryParams = array_merge($queryParams,$this->post);
            }
            self::$query = $queryParams;
        }
        return self::$query;
    }

    public function module($module = null){
        if (!is_null($module)) {
            $this->module = $module;
            return $this;
        } else {
            return $this->module ?: '';
        }
    }
}