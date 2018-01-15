<?php

use Yaf\Bootstrap_Abstract;
//use Illuminate\Container\Container;
//use Illuminate\Database\Capsule\Manager as Capsule;//如果你不喜欢这个名称，as DB;就好

class Bootstrap extends Bootstrap_Abstract
{

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




}
