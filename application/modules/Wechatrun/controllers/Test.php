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
            } elseif(date('H') >= 20){
                throw new \Exception('抽奖时间已过');
            }

            $start_last_week    = mktime(0,0,0,date('m'),date('d')-date('w')+1-7,date('Y'));
            $end_last_week      = mktime(23,59,59,date('m'),date('d')-date('w')+7-7,date('Y'));

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
                "step_num >= 2000 ".
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
            $activity_start_time    = $activity_info['start_time'];
            $activity_end_time      = $activity_info['end_time'];

            $date_list = [
                '一' => strtotime('2019-04-07 08:00:00'), //2019-04-08 08:00:00
                '二' => strtotime('2019-04-15 08:00:00'),
                '三' => strtotime('2019-04-22 08:00:00'),
                '四' => strtotime('2019-04-29 08:00:00'),
                '五' => strtotime('2019-05-06 08:00:00')
            ];

            //计算目标抽奖时间节点
            $target_timestamp = 0;
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
            }
            //判断活动状态
            if($current_time < strtotime('2019-04-07 08:00:00')) { //
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
            if(date('H') >= 8 && date('H') <= 20) {
                $return_data['draw_status'] = 1;//标记活动已经开始
            }

            $start_last_week    = mktime(0,0,0,date('m'),date('d')-date('w')+1-7,date('Y'));
            $end_last_week      = mktime(23,59,59,date('m'),date('d')-date('w')+7-7,date('Y'));

            //查询到当周是否抽过的记录
            $luck_draw_info = DB::table('w_company_step_luck_draw')
                ->select('*')
                ->where(['user_id' => $user_id,'activity_id' => $activity_id])
                ->where('add_time','>=',$start_last_week+7*24*3600)
                ->where('add_time','<=',$end_last_week+7*24*3600)
                ->first();
            if(!empty($luck_draw_info)){
                $return_data['is_selected'] = $luck_draw_info['is_selected'];//用户的抽奖状态设置 
                $return_data['user_draw_status'] = 1;//用户的抽奖状态设置 
            }

            $return_data['start_last_week'] = $start_last_week;//用户未达标标示
            $return_data['end_last_week'] = $end_last_week;//用户未达标标示

            //检查用户达标情况
            $sql = "select count(step_num) as step_day_count,user_id ".
                "from w_step_log ".
                "where user_id = {$user_id} and ".
                "data_time >= {$start_last_week} and ".
                "data_time <= {$end_last_week} and ".
                "step_num >= 2000 ".
                "group by user_id ";
            $step_count_info = DB::selectOne($sql);
            if(empty($step_count_info)){
                $return_data['user_draw_status'] = 2;//用户未达标标示
                //throw new \Exception('您未完成达标步数，谢谢参与，请继续努力！');
            } elseif($step_count_info['step_day_count'] < 5) {
                //throw new \Exception('您未完成达标步数，谢谢您的参与，请继续努力！');
                $return_data['user_draw_status'] = 2;//用户未达标标示
            }

            //时间条件可以在修改的精确点 ？？？？ todo 
            $start_last_week += 7*24*3600;
            $end_last_week += 7*24*3600;
            $sql = "select count(*) as attend_num from w_company_step_luck_draw ".
                "where  activity_id= {$activity_id} ".
                " and add_time >= {$start_last_week} and add_time <= {$end_last_week} ";
            $res = DB::selectOne($sql);
            if(!empty($res)){
                $return_data['attend_num'] = $res['attend_num'];
            }

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

}