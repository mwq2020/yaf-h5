<?php 
use Illuminate\Database\Eloquent\Model as Mymodel; 

class UserModel extends Mymodel 
{ 
    protected $connection = 'shop';//当前数据库的链接名
    protected $table      = 'w_users'; //表名
    protected $primaryKey = 'user_id';//主键id
    public    $timestamps = false;

    
    /**
     * 获取用户详情
     */
    public function getUserinfo()
    {
        $test = new TestModel();
        $test->mwqTest(); 
    }

}

