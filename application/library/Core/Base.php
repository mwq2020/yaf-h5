<?php

namespace Core;
use Yaf\Controller_Abstract;
class Base extends Controller_Abstract 
{

    /**
     * 控制器的初始化方法
     */
    public function init()
    {
        \Yaf\Dispatcher::getInstance()->autoRender(false); 
    }

    /**
     * 公共返回json数据方法
     */
    public function jsonError($msg,$data=[],$code=500)
    {
        exit(json_encode(['code'=>$code,'data'=>$data,'message'=>$msg]));
    }

    /**
     * 公共返回json数据方法
     */
    public function jsonSuccess($data,$code=200,$msg='')
    {
        exit(json_encode(['code'=>$code,'data'=>$data,'message'=>$msg]));
    }

    /**
     * 获取用户的真实ip
     * @return string
     */
    public function getUserRealIp()
    {
        if (getenv("HTTP_CLIENT_IP") && strcasecmp(getenv("HTTP_CLIENT_IP"), "unknown"))
            $ip = getenv("HTTP_CLIENT_IP");
        else if (getenv("HTTP_X_FORWARDED_FOR") && strcasecmp(getenv("HTTP_X_FORWARDED_FOR"), "unknown"))
            $ip = getenv("HTTP_X_FORWARDED_FOR");
        else if (getenv("REMOTE_ADDR") && strcasecmp(getenv("REMOTE_ADDR"), "unknown"))
            $ip = getenv("REMOTE_ADDR");
        else if (isset($_SERVER['REMOTE_ADDR']) && $_SERVER['REMOTE_ADDR'] && strcasecmp($_SERVER['REMOTE_ADDR'], "unknown"))
            $ip = $_SERVER['REMOTE_ADDR'];
        else
            $ip = "unknown";
        return($ip);
    }

}