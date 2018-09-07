<?php
namespace App\Core;

/**
 * 单例模式
 */
abstract class Service
{
    protected static $_instance = null;
    protected $error_no = 100;
    protected $error_msg = '';
    /**
     * @return static
     */
    public static function getInstance()
    {
        $class_name =  get_called_class();
        if (!isset(static::$_instance[$class_name])) {
            if (static::$_instance && count(static::$_instance) >= 20) {
                static::$_instance = null;
            }
            static::$_instance[$class_name] = new static();
        }
        return static::$_instance[$class_name];
    }

    protected function __construct()
    {
    }

    public function getError()
    {
        return [
            'error_no' => $this->error_no,
            'error_msg' => $this->error_msg
        ];
    }
}
