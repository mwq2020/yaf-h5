<?php

use Yaf\Bootstrap_Abstract;
use Illuminate\Container\Container; 
use Illuminate\Database\Capsule\Manager as Capsule;

use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\FirePHPHandler;
use Monolog\Formatter\LineFormatter;
use Monolog\Processor\WebProcessor;

class Bootstrap extends Bootstrap_Abstract
{
    private $config;

    /**
     * 初始化系统错误日志处理
     */
    public function _initError()
    {
        register_shutdown_function([$this,"handleErrorLog"]);
    }

    /**
     * 处理系统的错误日志
     */
    public static function handleErrorLog()
    {
        $error = error_get_last();
        $errorCodes = [E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_PARSE];
        if (defined('FATAL_ERROR')) {
            $errorCodes[] = FATAL_ERROR;
        }
        if($error === NULL || !in_array($error['type'], $errorCodes)){
            return false;
        }
        
        $log = new Logger('system');
        $log->pushHandler(new StreamHandler('/tmp/yaf.log', Logger::WARNING));
        $log->pushProcessor(new WebProcessor());
        $log->err($error['message'].PHP_EOL."#".$error['line']." ".$error['file']);
    }

    /**
     * 加载vendor下的文件
     */
    public function _initLoader()
    {
        \Yaf\Loader::import(APP_PATH . '/vendor/autoload.php');
    }


    /**
     * 配置
     */
    public function _initConfig()
    {
        $this->config = \Yaf\Application::app()->getConfig();//把配置保存起来
        \Yaf\Registry::set('config', $this->config);
    }

    /**
     * 初始化数据库
     */
    public function _initDatabase()
    {
        $capsule = new Capsule; 
        foreach($this->config->db as $database_name => $database) {
            $database_info = array( 
                'driver' => $database->type, 
                'host' => $database->host, 
                'database' => $database->database, 
                'username' => $database->username, 
                'password' => $database->password, 
                'charset' => $database->charset, 
                'collation' => $database->collation, 
                'prefix' => $database->prefix, 
            );
            // 创建链接 
            $capsule->addConnection($database_info,$database_name); 
        }
        // 设置全局静态可访问 
        $capsule->setAsGlobal(); 
        // 启动Eloquent 
        $capsule->bootEloquent();
    }


}



