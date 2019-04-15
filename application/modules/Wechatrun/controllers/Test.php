<?php
/****************************/
/****** 健步走活动入口 ********/
/****************************/

class TestController extends Core\Base
{
    //初始化方法，每个控制器都会先执行这个
    // protected function init()
    // {
    //     \Yaf\Dispatcher::getInstance()->autoRender(false); 
    // }


    public function indexAction()
    {
        return false;
    }

    public function testAction()
    {
        $obj = new StepModel();
        $obj->getDepartmentAttend();
        echo "<br>";
        echo "test---";
    }


    /**
     * 健步走抽奖
     */
    public function luckDrawAction()
    {
        $return_data = ['is_selected' => 0,'has_selected' => 0];  //is_selected 是否抽中  has_selected 当周是否已抽过
        $activity_id = $_REQUEST['activity_id'] ? $_REQUEST['activity_id'] : 0;
        $user_id = $_REQUEST['user_id'] ? $_REQUEST['user_id'] : 0;
        $current_time = time();
        try {
            if(empty($user_id)) {
                throw new \Exception('入参错误');
            }

            $activity_info = DB::table('w_company_step_activity')->where(['activity_id'=>$activity_id])->first();
            if(empty($activity_info)) {
                throw new \Exception('活动详情为空');
            }

            $activity_start_time    = $activity_info['start_time'];
            $activity_end_time      = $activity_info['end_time'];
            if($current_time < strtotime('2019-04-07 08:00:00')) {
                throw new \Exception('抽奖活动暂未开始');
            }
            if($current_time > strtotime('2019-05-06 20:00:00')) {
                throw new \Exception('抽奖活动已结束');
            }

            //8点到24点之间才能抽奖
            if(date('H') < 8) {
                throw new \Exception('抽奖开始时间还没到');
            } elseif(date('H') >= 23){
                throw new \Exception('抽奖时间已过');
            }

            $start_last_week    = mktime(0,0,0,date('m'),date('d')-date('w')+1-7,date('Y'))-7*24*3600;
            $end_last_week      = mktime(23,59,59,date('m'),date('d')-date('w')+7-7,date('Y'))-7*24*3600;

            //查询到当周是否抽过的记录
            $luck_draw_info = DB::table('w_company_step_luck_draw')
                ->select('*')
                ->where(['user_id' => $user_id,'activity_id' => $activity_id])
                ->where('add_time','>=',$start_last_week+7*24*3600)
                ->where('add_time','<=',$end_last_week+7*24*3600)
                ->first();
            if(!empty($luck_draw_info)){
                $return_data['has_selected'] = 1;
                throw new \Exception('当周您已抽过奖了',400);
            }

            //检查用户是否达标
            $sql = "select count(step_num) as step_day_count,user_id ".
                "from w_step_log ".
                "where user_id = {$user_id} and ".
                "data_time >= {$start_last_week} and ".
                "data_time <= {$end_last_week} and ".
                "step_num >= 500 ".
                "group by user_id ";
            $step_count_info = DB::selectOne($sql);
            if(empty($step_count_info)){
                throw new \Exception('您未完成达标步数，谢谢参与，请继续努力！');
            } elseif($step_count_info['step_day_count'] < 5) {
                throw new \Exception('您未完成达标步数，谢谢您的参与，请继续努力！');
            }

            //查询到当周是否抽过的记录 todo 时间点需要再准确点或者再考虑下是否周全
            $count_draw_info = DB::table('w_company_step_luck_draw')
                ->select(DB::raw('count(id) AS attend_num'))
                ->where(['activity_id' => $activity_id,'is_selected' => 1])
                ->where('add_time','>=',$start_last_week+7*24*3600)
                ->where('add_time','<=',$end_last_week+7*24*3600)
                ->first();
            if(!empty($count_draw_info) && $count_draw_info['attend_num'] >= 100) {
                $return_data['is_selected'] = 0;
            } else {
                $probability = 0.3;//概率值
                $rand_list = range(1, 100);//随机数的数组
                shuffle($rand_list);
                $rand_key = array_rand($rand_list,1);//随机取出随机值里面的key
                $current_rand_num = $rand_list[$rand_key];//获取抽到随机数
                if($current_rand_num <= $probability*100){
                    $return_data['is_selected'] = 1;
                } else {
                    $return_data['is_selected'] = 0;
                }
            }
            Log::info('健步走抽奖：用户id:'.$user_id.",活动id:".$activity_id.",抽中状态:".$return_data['is_selected']);

            $insertData = array();
            $insertData['user_id']      = $user_id;
            $insertData['activity_id']  = $activity_id;
            $insertData['is_selected']  = $return_data['is_selected'] >= 1 ? 1 : 0;
            $insertData['add_time']     = $current_time;
            $draw_log_id = DB::table('w_company_step_luck_draw')->insertGetId($insertData);
            if(empty($draw_log_id)) {
                $return_data['is_selected'] = 0;
                throw new \Exception('插入抽奖记录失败');
            }
            Log::info('健步走抽奖：用户id:'.$user_id.",活动id:".$activity_id.",插入数据id:".$draw_log_id);
        } catch(\Exception $e) {
            $code = $e->getCode() == 400 ? 400 : 500;
            return $this->jsonError($e->getMessage(),$return_data,$code);
        }
        return $this->jsonSuccess($return_data);
    }

    /**
     * 获取抽奖详情
     */
    public function luckDrawInfoAction()
    {
        $return_data = ['attend_num' => 0, 'user_draw_status' => 0 ,'draw_status' => 0, 'notice_txt' => '', 'day_num'=>0, 'hour_num' => 0];  //is_selected 是否抽中  has_selected 当周是否已抽过
        $activity_id = $_REQUEST['activity_id'] ? $_REQUEST['activity_id'] : 0;
        $user_id = $_REQUEST['user_id'] ? $_REQUEST['user_id'] : 0;
        $current_time = time();
        //$current_time = strtotime('2019-03-09');
        try {
            $activity_info = DB::table('w_company_step_activity')->where(['activity_id'=>$activity_id])->first();
            if(empty($activity_info)) {
                throw new \Exception('活动详情为空');
            }
            $return_data['activity_name'] = $activity_info['activity_name'];
            $return_data['activity_id'] = $activity_info['activity_id'];
            //$activity_start_time    = $activity_info['start_time'];
            //$activity_end_time      = $activity_info['end_time'];

            $date_list = [
                '一' => strtotime('2019-04-08 08:00:00'), //2019-04-08 08:00:00
                '二' => strtotime('2019-04-15 08:00:00'),
                '三' => strtotime('2019-04-22 08:00:00'),
                '四' => strtotime('2019-04-29 08:00:00'),
                '五' => strtotime('2019-05-06 08:00:00')
            ];

            //计算目标抽奖时间节点
            $target_timestamp = 0; //取的是当前周过后的一周的抽奖开始时间,用于计算倒计时
            $target_draw_num = '';
            foreach($date_list as $key => $row){
                if($current_time <= $row){
                    $target_timestamp = $row;
                    $target_draw_num = $key;
                    break;
                }
            }

            //计算时间当前节点
            if($target_timestamp > 0) {
                $return_data['target_draw_num'] = $target_draw_num;
                $return_data['day_num'] = intval(($target_timestamp - $current_time)/86400);
                $return_data['hour_num'] = ceil((($target_timestamp - $current_time)%86400)/3600);
                $return_data['minute_num'] = ceil(((($target_timestamp - $current_time)%86400)%3600)/60);
            }
            //判断活动状态
            if($current_time < strtotime('2019-04-08 08:00:00')) { //
                $return_data['notice_txt'] = '抽奖活动暂未开始';
                $return_data['draw_status'] = 0;
                throw new \Exception('抽奖活动暂未开始',200);
            }
            if($current_time > strtotime('2019-05-06 20:00:00')) {
                $return_data['draw_status'] = 2;
                $return_data['notice_txt'] = '抽奖活动已结束';
                throw new \Exception('抽奖活动已结束',200);
            }

            //8点到24点之间才能抽奖
            //if(date('Y-m-d') == date('Y-m-d',$target_timestamp-7*24*3600) && date('H') >= 8 && date('H') < 20) {
                $return_data['draw_status'] = 1;//标记活动已经开始
            //} else {
            //    $return_data['draw_status'] = 0;//除了以上时间段抽奖都是未开始
            //}

            $start_current_week = strtotime(date('Y-m-d')) - (date('N') - 1) * 86400; //获取当前日期减去当周已经过去的日期
            $end_current_week = $start_current_week + 7*86400 - 1;

            $start_last_week    = $start_current_week - 7*86400; //上周一时间戳
            $end_last_week    = $end_current_week - 7*86400; //上周一时间戳

            //查询到当周是否抽过的记录
            $luck_draw_info = DB::table('w_company_step_luck_draw')
                ->select('*')
                ->where(['user_id' => $user_id,'activity_id' => $activity_id])
                ->where('add_time','>=',$start_current_week)
                ->where('add_time','<=',$end_current_week)
                ->first();
            if(!empty($luck_draw_info)){
                $return_data['is_selected'] = $luck_draw_info['is_selected'];//用户的抽奖状态设置
                $return_data['user_draw_status'] = 1;//用户的抽奖状态设置
            } else {
                //检查用户达标情况
                $sql = "select count(step_num) as step_day_count,user_id ".
                    "from w_step_log ".
                    "where user_id = {$user_id} and ".
                    "data_time >= {$start_last_week} and ".
                    "data_time <= {$end_last_week} and ".
                    "step_num >= 6000 ".
                    "group by user_id ";
                $step_count_info = DB::selectOne($sql);

                if(empty($step_count_info)){
                    $return_data['user_draw_status'] = 2;//用户未达标标示
                    //throw new \Exception('您未完成达标步数，谢谢参与，请继续努力！');
                } elseif($step_count_info['step_day_count'] < 5) {
                    //throw new \Exception('您未完成达标步数，谢谢您的参与，请继续努力！');
                    $return_data['user_draw_status'] = 2;//用户未达标标示
                } elseif($step_count_info['step_day_count'] >= 5){
                    $return_data['user_draw_status'] = 0; //用户已达标标示
                }
            }

            $sql = "select count(DISTINCT user_id) as attend_num from w_company_step_luck_draw ".
                "where  activity_id= {$activity_id} ".
                " and add_time >= {$start_current_week} and add_time <= {$end_current_week} ";
            $res = DB::selectOne($sql);
            if(!empty($res)){
                $return_data['attend_num'] = $res['attend_num'];
            }

            $return_data['current_week'] = date('Y-m-d H:i:s',$start_current_week)."---".date('Y-m-d H:i:s',$end_current_week);
            $return_data['last_week'] = date('Y-m-d H:i:s',$start_last_week)."---".date('Y-m-d H:i:s',$end_last_week);

        } catch(\Exception $e) {
            $code = $e->getCode() == 200 ? 200 : 500;
            return $this->jsonError($e->getMessage(),$return_data,$code);
        }
        return $this->jsonSuccess($return_data);
    }

    /**
     * 测试概率
     */
    public function robabilityAction()
    {
        $probability = isset($_REQUEST['probability']) ? $_REQUEST['probability'] : 0.1;//概率值
        $rand_list = range(1, 100);//随机数的数组
        $rand_key = array_rand($rand_list,1);//随机取出随机值里面的key
        $current_rand_num = $rand_list[$rand_key];//获取抽到随机数
        if($current_rand_num <= $probability*100){
            echo "抽中";
        } else {
            echo "没中";
        }
    }

    /**
     * 抽奖测试
     */
    public function drawTestAction()
    {
        $draw_num       = isset($_REQUEST['draw_num']) ? $_REQUEST['draw_num'] : 20;  //抽多少个奖品
        $current_time   = isset($_REQUEST['current_time']) ? $_REQUEST['current_time'] : date('Y-m-d H:i:s');  //抽多少个奖品
        $probability    = isset($_REQUEST['probability']) ? $_REQUEST['probability'] : 0.1;//概率值
        $target_step_num  = isset($_REQUEST['target_step_num']) ? $_REQUEST['target_step_num'] : 2000;//达标步数
//        if(empty($_POST)){
//            $this->getView()->assign("draw_num", $draw_num);
//            $this->getView()->assign("current_time", $current_time);
//            $this->getView()->assign("probability", $probability);
//            $this->getView()->assign("target_step_num", $target_step_num);
//            return $this->display("drawtest", []);
//        }

        $start_last_week    = mktime(0,0,0,date('m'),date('d')-date('w')+1-7,date('Y'));
        $end_last_week      = mktime(23,59,59,date('m'),date('d')-date('w')+7-7,date('Y'));

        //检查用户达标情况
        $sql = "select count(step_num) as step_day_count,user_id ".
            "from w_step_log ".
            "where ".
            "data_time >= {$start_last_week} and ".
            "data_time <= {$end_last_week} and ".
            "step_num >= {$target_step_num} ".
            "group by user_id ";
        $attend_user_list = DB::select($sql);

        $this->getView()->assign("draw_num", $draw_num);
        $this->getView()->assign("current_time", $current_time);
        $this->getView()->assign("probability", $probability);
        $this->getView()->assign("target_step_num", $target_step_num);
        $this->getView()->assign("attend_user_list", $attend_user_list);
        return $this->display("drawtest", []);
    }


    public function testtimeAction() 
    {
        echo date('N',time());
        echo "<hr>";

        echo date('N',strtotime('2019-04-09'));
        echo "<hr>";

        echo date('N',strtotime('2019-04-10'));
        echo "<hr>";
        echo date('N',strtotime('2019-04-11'));
        echo "<hr>";
        echo date('N',strtotime('2019-04-12'));
        echo "<hr>";
        echo date('N',strtotime('2019-04-13'));
        echo "<hr>";
        echo date('N',strtotime('2019-04-14'));
        echo "<hr>";
        echo date('N',strtotime('2019-04-15'));
        echo "<hr>";


        $current_week_start = strtotime(date('Y-m-d'))-(date('N') - 1)*86400;
        $current_week_end = $current_week_start + 7*86400 - 1;
        echo date('Y-m-d H:i:s',$current_week_start);
        echo "<hr>";
        echo date('Y-m-d H:i:s',$current_week_end);
    }

}