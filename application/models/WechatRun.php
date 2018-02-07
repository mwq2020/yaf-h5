<?php 
use Illuminate\Database\Eloquent\Model as Mymodel; 

class WechatRunModel extends Mymodel 
{ 
    protected $connection = 'test';//当前数据库的链接名
    protected $table      = 'wechat_run_log'; //表名
    protected $primaryKey = 'id';//主键id
    public    $timestamps = false;

}

