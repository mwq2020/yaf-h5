<?php
error_reporting(E_ALL & ~E_NOTICE);
ini_set('display_errors', '1');
ini_set('date.timezone','Asia/Shanghai');



use Yaf\Application;
use Yaf\Exception;
define("APP_PATH",  realpath(dirname(__FILE__) . '/../')); /* 指向public的上一级 */
$app  = new Application(APP_PATH . "/conf/application.ini");
$app->bootstrap()->run();





