<?php
/****************************/
/*******  健步走数据  ********/
/****************************/

use Yaf\Controller_Abstract;
class WalkController extends Controller_Abstract 
{

    /**
     *  获取个人健步走的月份数据
     */ 
    public function monthAction() 
    {
        echo '我是获取当月步数';
        return false;
    }

    /**
     * 公司健步走的数据排行[当月]
     */
    public function companyRankingAction()
    {
        echo "我是获取公司当月的数据排行";
        return false;
    }

    /**
     * 部门健步走的当月数据排行[当月]
     */
    public function departmentRankingAction()
    {
        echo "我是获取部门当月的数据排行";
        return false;
    }

}