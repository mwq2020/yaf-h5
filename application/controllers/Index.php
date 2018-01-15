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
        echo "this is index page";

        $logger = new Logger('my_logger');
        $logger->pushHandler(new StreamHandler('/tmp/yaf_test.log', Logger::DEBUG));
        $firephp = new FirePHPHandler();
        $logger->pushHandler($firephp);
        $logger->info('monolog test log write success');
        $logger->addWarning('Foo');
        $logger->addError('Bar');

    }
    

}







