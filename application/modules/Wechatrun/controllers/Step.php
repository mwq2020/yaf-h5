<?php
/****************************/
/****** 健步走活动入口 ********/
/****************************/

class StepController extends Core\Base
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


    //获取排行榜页面数据[中国联通专用]
    public function getRankingListAction() 
    {
        $exclude_user_ids = [4423];//排除用户的user_id

        $activity_id = isset($_REQUEST['activity_id']) ? intval($_REQUEST['activity_id']) : 0;
        $company_id = isset($_REQUEST['company_id']) ? intval($_REQUEST['company_id']) : 0;
        $department_id = isset($_REQUEST['department_id']) ? intval($_REQUEST['department_id']) : 0;
        $user_id = isset($_REQUEST['user_id']) ? intval($_REQUEST['user_id']) : 0;
        $ranking_type = isset($_REQUEST['ranking_type']) ? trim($_REQUEST['ranking_type']) : 'all'; //排行的类型
        $page_index = isset($_REQUEST['page_index']) ? intval($_REQUEST['page_index']) : 1; //当前页码
        $page_size = isset($_REQUEST['page_size']) ? intval($_REQUEST['page_size']) : 50;    //每页返回数据的条数

        $return_data = [
                        'page_index' => $page_index,
                        'page_size' => $page_size ,
                        'ranking_type' => $ranking_type ,
                        'ranking_list' => [],
                        'user_info' => []
                        ];
        try {
            $activity_info = DB::table('w_company_step_activity')->where(['activity_id'=>$activity_id])->first();
            if(empty($activity_info)) {
                throw new \Exception('活动详情为空');
            }
            $company_id = $activity_info['company_id'];
            $activity_start_time = $activity_info['start_time'];
            $activity_end_time = $activity_info['end_time'];

            /*
            $company_user_info  = DB::table('w_company_user')->where(['user_id'=>$user_id,'company_id'=>$activity_info['company_id']])->first();
            if(empty($company_user_info)) {
                throw new \Exception('服务错误，请重试');
            }
            $department_id = $company_user_info['department_id'];
            */

            if($ranking_type == 'department'){
                //部门的平均步数排行
                StepModel::getDepartmentAverageRanking($activity_info,$return_data);
            } elseif($ranking_type == 'attend_percent') {
                //部门的参与率排行 【部门下的人数/有步数的人数】
                $return_data['ranking_list'] = StepModel::getDepartmentAttend($activity_info);
            } elseif($ranking_type == 'department_member'){
                //部门下面的员工的排行
                StepModel::getDepartmentRanking($activity_info,$user_id,$return_data);
            }else {
                StepModel::getAllMemberRanking($activity_info,$user_id,$return_data);
            }

        } catch (\Exception $e) {
            return $this->jsonError($e->getMessage(),$return_data);
        }
        return  $this->jsonSuccess($return_data);
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

            if($current_time < strtotime('2019-04-08 08:00:00')) {
                throw new \Exception('抽奖活动暂未开始');
            }
            if($current_time > strtotime('2019-05-06 20:00:00')) {
                throw new \Exception('抽奖活动已结束');
            }

            $date_list = [
                '一' => strtotime('2019-04-08 08:00:00'), //2019-04-08 08:00:00
                '二' => strtotime('2019-04-15 08:00:00'),
                '三' => strtotime('2019-04-22 08:00:00'),
                '四' => strtotime('2019-04-29 08:00:00'),
                '五' => strtotime('2019-05-06 08:00:00')
            ];

            //计算目标抽奖时间节点
            $target_timestamp = 0;//取的是当前周过后的一周的抽奖开始时间,用于计算倒计时
            $target_draw_num = '';
            foreach($date_list as $key => $row){
                if($current_time <= $row){
                    $target_timestamp = $row;
                    $target_draw_num = $key;
                    break;
                }
            }

            //8点到24点之间才能抽奖 活动日的当天
            if(date('Y-m-d') != date('Y-m-d',$target_timestamp-7*24*3600) || date('H') < 8 || date('H') >= 20){
                throw new \Exception('8-20点为抽奖时间');
            }

            $start_current_week = strtotime(date('Y-m-d')) - (date('N') - 1) * 86400; //重新按照时间戳的方法整理出来的逻辑
            $end_current_week = $start_current_week + 7*86400 - 1;

            $start_last_week  = $start_current_week - 7*86400; //上周一时间戳
            $end_last_week    = $end_current_week - 7*86400; //上周一时间戳

            //查询到当周是否抽过的记录
            $luck_draw_info = DB::table('w_company_step_luck_draw')
                                ->select('*')
                                ->where(['user_id' => $user_id,'activity_id' => $activity_id])
                                ->where('add_time','>=', $start_current_week)
                                ->where('add_time','<=', $end_current_week)
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
                   "step_num >= 6000 ".
                   "group by user_id ";
            $step_count_info = DB::selectOne($sql);
            if(empty($step_count_info)){
                throw new \Exception('您未完成达标步数，谢谢参与，请继续努力！');
            } elseif($step_count_info['step_day_count'] < 5) {
                throw new \Exception('您未完成达标步数，谢谢您的参与，请继续努力！');
            }

            //查询当周已经抽到奖的人数 大于100人强制抽不中
            $count_draw_info = DB::table('w_company_step_luck_draw')
                                ->select(DB::raw('count(id) AS attend_num'))
                                ->where(['activity_id' => $activity_id,'is_selected' => 1])
                                ->where('add_time','>=',$start_current_week)
                                ->where('add_time','<=',$end_current_week)
                                ->first();
            if(!empty($count_draw_info) && $count_draw_info['attend_num'] >= 125) {
                $return_data['is_selected'] = 0;
            } else {
                $probability = 0.01;//概率值
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

            $return_data['current_week'] = date('Y-m-d H:i:s',$start_current_week)."---".date('Y-m-d H:i:s',$end_current_week);
            $return_data['last_week'] = date('Y-m-d H:i:s',$start_last_week)."---".date('Y-m-d H:i:s',$end_last_week);

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
                $return_data['hour_num'] = intval((($target_timestamp - $current_time)%86400)/3600); // 2019-04-15 上线新活动后需要修改为intval
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
            if(date('Y-m-d') == date('Y-m-d',$target_timestamp-7*24*3600) && date('H') >= 8 && date('H') < 20) {
                $return_data['draw_status'] = 1;//标记活动已经开始
            } else {
                $return_data['draw_status'] = 0;//除了以上时间段抽奖都是未开始
            }

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
                    $return_data['user_draw_status'] = 0; //用户已达标标示,设置成未抽奖的状态
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


    //中奖名单
    public function winnerListAction()
    {
        $activity_id = $_REQUEST['activity_id'] ? $_REQUEST['activity_id'] : 0;
        $user_id = $_REQUEST['user_id'] ? $_REQUEST['user_id'] : 0;

        $return_data = ['winner_list' => [],'is_show' => 0];
        try {
            if(empty($user_id)) {
                throw new \Exception('入参错误');
            }

            $activity_info = DB::table('w_company_step_activity')->where(['activity_id'=>$activity_id])->first();
            if(empty($activity_info)) {
                throw new \Exception('活动详情为空');
            }
            $company_id = $activity_info['company_id'];


            $current_time = time();
            if($current_time < strtotime('2019-04-08 08:00:00')) {
                throw new \Exception('抽奖活动暂未开始');
            }
            if($current_time > strtotime('2019-05-07 20:00:00')) {
                throw new \Exception('抽奖活动已结束');
            }

            $start_current_week = strtotime(date('Y-m-d')) - (date('N') - 1) * 86400; //获取当前日期减去当周已经过去的日期
            $end_current_week = $start_current_week + 7*86400 - 1;

            $date_list = [
                '一' => strtotime('2019-04-08 08:00:00'),
                '二' => strtotime('2019-04-15 08:00:00'),
                '三' => strtotime('2019-04-22 08:00:00'),
                '四' => strtotime('2019-04-29 08:00:00'),
                '五' => strtotime('2019-05-06 08:00:00')
            ];
            $date_list = array_reverse($date_list);

            //计算当前抽奖的期限
            $target_draw_num = '';
            foreach($date_list as $key => $row){
                if($start_current_week <= $row && $end_current_week >= $row){
                    $target_draw_num = $key;
                    break;
                }
            }

            $target_draw_num = '二';
            $start_current_week = strtotime('2019-04-15 08:00:00');
            $end_current_week = strtotime('2019-04-21 23:59:59');


            $return_data['draw_num']     = $target_draw_num;//第几期的文字逻辑

            $winner_list = DB::table('w_company_step_luck_draw')
                ->leftJoin('w_company_user','w_company_user.user_id','=','w_company_step_luck_draw.user_id')
                ->select(
                    'w_company_user.real_name',
                    'w_company_user.department_name',
                    'w_company_step_luck_draw.add_time'
                )
                ->where([
                    'w_company_step_luck_draw.activity_id' => $activity_id,
                    'w_company_step_luck_draw.is_selected' => 1,
                    'w_company_user.company_id' => $company_id
                ])
                ->where('w_company_step_luck_draw.add_time','>=',$start_current_week)
                ->where('w_company_step_luck_draw.add_time','<=',$end_current_week)
                ->get();
            $return_data['winner_list'] = $winner_list;
            $return_data['is_show']     = 1;
            //$return_data['test_time'] = date('Y-m-d H:i:s',$start_current_week) . '----'.date('Y-m-d H:i:s',$end_current_week);
        } catch (\Exception $e) {
            $return_data['is_show'] = 0;
            return $this->jsonError($e->getMessage(),$return_data);
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