<?php

use Yaf\Bootstrap_Abstract;
use Illuminate\Container\Container;
use Illuminate\Events\Dispatcher;
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
     * 系统日志初始化
     */
    public function _initLog()
    {
        $log_dir = isset($this->config->application->log) && !empty($this->config->application->log) ? $this->config->application->log : '/tmp/yaf.log';
        $monolog = new Logger('system');
        $monolog->pushHandler(new \Monolog\Handler\RotatingFileHandler($log_dir));
        $monolog->pushProcessor(new WebProcessor());
        YafLog::$mongolog = $monolog;
        class_alias('YafLog', 'Log');
    }

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
        
        //$log = new Logger('system');
        //$log->pushHandler(new StreamHandler('/tmp/yaf.log', Logger::WARNING));
        //$log->pushProcessor(new WebProcessor());
        //$log->err($error['message'].PHP_EOL."#".$error['line']." ".$error['file']);
        Log::err($error['message'].PHP_EOL."#".$error['line']." ".$error['file']);
    }

    /**
     * 初始化数据库
     */
    public function _initDatabase()
    {

        //$container = $app->getContainer();
        //$container->register(new EloquentServiceProvider()); //注意你自己 EloquentServiceProvider() 文件位置


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
            // Capsule::connection($database_name)->enableQueryLog();
            //$capsule::connection($database_name)->enableQueryLog();
        }

        $capsule->setEventDispatcher(new \Illuminate\Events\Dispatcher(new \Illuminate\Container\Container));

        // Set the event dispatcher used by Eloquent models... (optional)
        //se Illuminate\Events\Dispatcher;
        //use Illuminate\Container\Container;
        //$capsule->setEventDispatcher(new Dispatcher(new Container));

        // 设置全局静态可访问 
        $capsule->setAsGlobal(); 
        // 启动Eloquent 
        $capsule->bootEloquent();



        class_alias('Illuminate\Database\Capsule\Manager', 'DB');

//        $capsule->enableQueryLog();
//        $pimple['db'] = function () use ($capsule) {
//            return $capsule;
//        };
    }

}



//框架日志处理类
class YafLog
{
    public static $mongolog;
    public static function __callStatic($method, $args)
    {
        $instance = static::$mongolog;
        if (!$instance) {
            throw new \Exception('monolog 初始化失败');
        }

        switch (count($args)) {
            case 0:
                return $instance->$method();
            case 1:
                return $instance->$method($args[0]);
            case 2:
                return $instance->$method($args[0], $args[1]);
            case 3:
                return $instance->$method($args[0], $args[1], $args[2]);
            case 4:
                return $instance->$method($args[0], $args[1], $args[2], $args[3]);
            default:
                return call_user_func_array([$instance, $method], $args);
        }
    }
}



