<?php
/****************************/
/*******  健步走用户  ********/
/****************************/


use Yaf\Controller_Abstract;
class UserController extends Controller_Abstract 
{

    /**
     *  用户的登录操作
     */ 
    public function loginAction() 
    {
        echo '我是登录接口';
        return false;
    }

    /**
     * 发送验证码
     */
    public function  sendCodeAction()
    {

    }

    /**
     * 获取用户详情接口
     */
    public function userInfoAction()
    {

    }

}