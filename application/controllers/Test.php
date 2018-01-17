<?php

use Yaf\Controller_Abstract;
class TestController extends Controller_Abstract 
{

    public function testAction()
    {
        \Yaf\Dispatcher::getInstance()->disableView(); 
        echo "this a test page";
    }

}