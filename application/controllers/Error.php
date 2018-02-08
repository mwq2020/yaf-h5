<?php

/** 
 * 　　　　　　　 ┏┓   ┏┓ 
 * 　　　　　　　┏┛┻━━━┛┻┓ 
 * 　　　　　　　┃　　　　　　　┃ 　 
 * 　　　　　　　┃　　　━      ┃ 
 * 　　　　　　　┃　＞　　　＜　┃ 
 * 　　　　　　　┃　　　　　　　┃ 
 * 　　　　　　　┃...　⌒　...　┃ 
 * 　　　　　　　┃　　　　　　　┃ 
 * 　　　　　　　┗━┓        ┏━┛ 
 * 　　　　　　　　　┃　　　┃　Code is far away from bug with the animal protecting　　　　　　　　　　 
 * 　　　　　　　　　┃　　　┃   神兽保佑,代码无bug 
 * 　　　　　　　　　┃　　　┃　　　　　　　　　　　 
 * 　　　　　　　　　┃　　　┃  　　　　　　 
 * 　　　　　　　　　┃　　　┃ 
 * 　　　　　　　　　┃　　　┃　　　　　　　　　　　 
 * 　　　　　　　　　┃　　　┗━━━┓ 
 * 　　　　　　　　　┃         ┣┓ 
 * 　　　　　　　　　┃         ┏┛ 
 * 　　　　　　　　　┗┓┓┏━┳┓  ┏┛ 
 * 　　　　　　　　　　┃┫┫　┃┫┫ 
 * 　　　　　　　　　　┗┻┛　┗┻┛ 
 */ 


/**
 * 当有未捕获的异常, 则控制流会流到这里
 */
use Yaf\Controller_Abstract;
class ErrorController extends Controller_Abstract {

    public function init() 
    {
        \Yaf\Dispatcher::getInstance()->disableView();
    }

    public function errorAction($exception) 
    {
        echo "mwq catch error";
        

        $exception = $this->getRequest()->getException();
        try {
           $this->_view->content = $exception->getMessage();
        } catch (\Yaf\Exception_LoadFailed $e) {
            echo '加载失败';
        } catch (\Yaf\Exception $e) {
            echo 1111;
            //其他错误
        }
        exit;


        if ($exception->getCode() > 100000) {
            //这里可以捕获到应用内抛出的异常
            $code= $exception->getCode();
            $codeConfig  = \Error\CodeConfigModel::getCodeConfig();
           if (empty($codeConfig[$code])) {
                throw new \Exception('错误码' . $code . '的相应提示信息没有设置');
            }
            $message = $codeConfig[$code];
            echo $message;
            /*echo $exception->getCode();
            echo $exception->getMessage();*/
            return;
        }
        switch ($exception->getCode()) {
            case 404://404
            case 515:
            case 516:
            case 517:
                //输出404
                //header(\Our\Common::getHttpStatusCode(404));
                echo '404';
                exit();
                break;
            default :
                break;
        }
        throw $exception;
    }
}