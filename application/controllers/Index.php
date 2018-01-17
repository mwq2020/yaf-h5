<?php

use Yaf\Controller_Abstract;
use \Monolog\Logger;
use \Monolog\Handler\StreamHandler;
use \Monolog\Handler\FirePHPHandler;
use \Monolog\Formatter\LineFormatter;
class IndexController extends Controller_Abstract 
{

    public function indexAction()
    {
        echo "<pre>this is index page<br>";

        //写日志模拟
        $logger = new Logger('my_logger');
        $logger->pushHandler(new StreamHandler('/tmp/yaf_test.log', Logger::DEBUG));
        $firephp = new FirePHPHandler();
        $logger->pushHandler($firephp);
        $logger->info('monolog test log write success');
        $logger->addWarning('Foo');
        $logger->addError('Bar');
        
        //\Yaf\Dispatcher::getInstance()->disableView(); 

        //数据存取模拟
        $mod = new UserModel(); 
        // $data = $mod->find(1)->toArray(); 
        // print_r($data);

        $mod->getUserinfo();

    }

    public function testAction()
    {
        \Yaf\Dispatcher::getInstance()->disableView(); 
        echo "this a index test page";
    }
    
}








