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
        echo "hello";
        return false;    
    }

    //微信授权
    public function authorizeAction()
    {
        $appid = '';

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








