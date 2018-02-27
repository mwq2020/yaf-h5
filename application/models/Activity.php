<?php 
use Illuminate\Database\Eloquent\Model as Mymodel; 

class ActivityModel extends Mymodel 
{ 
    protected $connection = 'shop';//当前数据库的链接名
    protected $table      = 'w_step_activity'; //表名
    protected $primaryKey = 'act_id';//主键id
    public    $timestamps = false;

}

