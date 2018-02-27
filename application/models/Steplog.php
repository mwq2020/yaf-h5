<?php 
use Illuminate\Database\Eloquent\Model as Mymodel; 

class SteplogModel extends Mymodel 
{ 
    protected $connection = 'shop';//当前数据库的链接名
    protected $table      = 'w_step_log'; //表名
    protected $primaryKey = 'id';//主键id
    public    $timestamps = false;


     //把步数转化成千米
    public static function getKmByStepnum($step_num)
    {
        return round($step_num/1000*0.7,2);
    }

    // 步数转化成千焦
    public static function getJouleByStepnum($step_num)
    {
        return intval($step_num/22);
    }

    // 步数转化成师傅
    public static function getFoodByStepnum($step_num)
    {
        $str = '';
        if($step_num > 0  && $step_num<=1000){
            $str = '1杯咖啡';
        } elseif($step_num > 1000  && $step_num<=2000) {
            $str = '1根玉米';
        }elseif($step_num > 2000  && $step_num<=3000) {
            $str = '1听可乐';  
        }elseif($step_num > 3000  && $step_num<=4000) {
            $str = '1个冰淇淋';
        }elseif($step_num > 4000  && $step_num<=5000) {
            $str = '1包小薯条';
        }elseif($step_num > 5000  && $step_num<=6000) {
            $str = '1瓶可乐';
        }elseif($step_num > 6000  && $step_num<=7000) {
            $str = '1包中薯条';
        }elseif($step_num > 7000  && $step_num<=8000) {
            $str = '1个鸡腿';
        }elseif($step_num > 8000  && $step_num<=9000) {
            $str = '1包大薯条';
        }elseif($step_num > 9000  && $step_num<20000) {
            $str = '1个汉堡包';
        } elseif($step_num > 20000){
            $str = '汉堡套餐';
        }
        return $str;
    }

}

