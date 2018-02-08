<?php
/** 
 * 
 * ━━━━━━神兽出没━━━━━━ 
 * 　　　┏┓　　　┏┓ 
 * 　　┏┛┻━━━┛┻┓ 
 * 　　┃　　　　　　　┃ 
 * 　　┃　　　━　　　┃ 
 * 　　┃　┳┛　┗┳　┃ 
 * 　　┃　　　　　　　┃ 
 * 　　┃　　　┻　　　┃ 
 * 　　┃　　　　　　　┃ 
 * 　　┗━┓　　　┏━┛Code is far away from bug with the animal protecting 
 * 　　　　┃　　　┃    神兽保佑,代码无bug 
 * 　　　　┃　　　┃ 
 * 　　　　┃　　　┗━━━┓ 
 * 　　　　┃　　　　　　　┣┓ 
 * 　　　　┃　　　　　　　┏┛ 
 * 　　　　┗┓┓┏━┳┓┏┛ 
 * 　　　　　┃┫┫　┃┫┫ 
 * 　　　　　┗┻┛　┗┻┛ 
 * 
 * ━━━━━━感觉萌萌哒━━━━━━ 
 */ 

use Yaf\Controller_Abstract;
use \Monolog\Logger;
use \Monolog\Handler\StreamHandler;
use \Monolog\Handler\FirePHPHandler;
use \Monolog\Formatter\LineFormatter;
class IndexController extends Controller_Abstract 
{

    public function indexAction()
    {
        //echo "<pre>this is index page<br>";

        //写日志模拟
        // $logger = new Logger('my_logger');
        // $logger->pushHandler(new StreamHandler('/tmp/yaf_test.log', Logger::DEBUG));
        // $firephp = new FirePHPHandler();
        // $logger->pushHandler($firephp);
        // $logger->info('monolog test log write success');
        // $logger->addWarning('Foo');
        // $logger->addError('Bar');
        
        //\Yaf\Dispatcher::getInstance()->disableView(); 

        //数据存取模拟
        echo "<pre>";
        $mod = new UserModel(); 
        $data = $mod->find(2)->toArray(); 
        print_r($data);


        $list = $mod->all()->toArray();
        print_r($list);
        //exit;


        $run_model = new WechatRunModel();
        $list = $run_model->all()->toArray();
        print_r($list);
        exit;


        //$mod->getUserinfo();
    }

    public function testAction()
    {
        \Yaf\Dispatcher::getInstance()->disableView(); 
        echo "this a index test page";
    }

    public function loginAction()
    {

    }
    
}








