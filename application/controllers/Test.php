<?php

use Yaf\Controller_Abstract;
class TestController extends Controller_Abstract 
{

    public function testAction()
    {
        \Yaf\Dispatcher::getInstance()->disableView(); 
        echo "this a test page";
        $session = \Yaf\Session::getInstance();
        //$session->start();
        echo "<hr>";
        echo $session->mwq;
    }

    public function sessionAction()
    {
        \Yaf\Dispatcher::getInstance()->disableView(); 

        //Yaf_Application,  Yaf_Loader,  Yaf_Dispatcher, Yaf_Registry, Yaf_Session 都是单例模式 
        //你可以通过它们的getInstance() 来获取它们的单例，也可以通过Yaf_dispatcher::getXXX方法来获取实例
        $session = \Yaf\Session::getInstance();
        //$session->start();
        $session->mwq = 'mwq test session';
        echo "session success";
        // \Yaf\Session::getInstance()->set('name', "alex")->set('sex',"男")；
    }

}