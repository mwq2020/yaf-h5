<?php
error_reporting(E_ALL & ~E_NOTICE);
ini_set('display_errors', '1');
ini_set('date.timezone','Asia/Shanghai');
ini_set('yaf.use_namespace',1);
ini_set('yaf.use_spl_autoload','on');
ini_set('track_errors', 'on');

use Yaf\Application;
use Yaf\Exception;
define("APP_PATH",  realpath(dirname(__FILE__) . '/../')); /* 指向public的上一级 */
$app  = new Application(APP_PATH . "/conf/application.ini");
$app->getDispatcher()->throwException(true);  
// $app->getDispatcher()->setErrorHandler("myErrorHandler");  
$app->bootstrap()->run();


// function myErrorHandler($errno, $errstr, $errfile, $errline){  
//     switch ($errno) {  
//         case YAF_ERR_NOTFOUND_CONTROLLER:  
//         case YAF_ERR_NOTFOUND_MODULE:  
//         case YAF_ERR_NOTFOUND_ACTION:  
//             header("Not Found");  
//             break;  
  
//         default:  
//             echo 'errno: '.$errno.'<br>';  
//             echo 'errstr: '.str_replace(APP_PATH, '[PATH]', $errstr).'<br>';  
//             echo 'errfile: '.str_replace(APP_PATH, '[PATH]', $errfile).'<br>';  
//             echo 'errline: '.$errline.'<br>';  
  
//             break;  
//     }  
//     return true;  
// } 





