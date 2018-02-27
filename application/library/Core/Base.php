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

}