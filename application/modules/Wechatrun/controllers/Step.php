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

            $company_user_info  = DB::table('w_company_user')->where(['user_id'=>$user_id,'company_id'=>$activity_info['company_id']])->first();
            if(empty($company_user_info)) {
                throw new \Exception('服务错误，请重试');
            }
            $department_id = $company_user_info['department_id'];

            if($ranking_type == 'department'){
                $department_step_list_res = DB::table('w_step_log')
                                ->leftJoin('w_company_user','w_step_log.user_id','=','w_company_user.user_id')
                                ->leftJoin('w_department','w_department.department_id','=','w_company_user.department_id')
                                ->select(
                                    DB::raw('sum(w_step_log.step_num) AS step_num_count'),
                                    'w_company_user.department_id',
                                    'w_department.name as department_name'
                                    )
                                ->where(['w_company_user.company_id' => $company_id,'w_company_user.is_tested' => 0])
                                ->where('w_step_log.data_time','>=',$activity_start_time)
                                ->where('w_step_log.data_time','<=',$activity_end_time)
                                ->groupBy('w_company_user.department_id')
                                ->orderBy('step_num_count','desc')
                                ->get();
                $department_step_list = [];
                if(!empty($department_step_list_res)){
                    foreach($department_step_list_res as $department_row) {
                        $department_step_list[$department_row['department_id']] = $department_row;
                    }
                }

                //部门的参与率排行 【部门下的人数/有步数的人数】
                $department_list = DB::table('w_department')
                                  ->leftJoin('w_company_user','w_department.department_id','=','w_company_user.department_id')
                                    ->select(
                                     DB::raw('count( distinct w_company_user.user_id) AS user_num'),
                                    'w_department.name as department_name',
                                    'w_department.department_id'
                                    ) 
                                  ->where(['w_company_user.company_id' => $company_id,'w_company_user.is_tested' => 0])
                                  ->groupBy('w_department.department_id')
                                  ->get();
                if(!empty($department_list)){
                    foreach($department_list as $department_key => &$row){
                        $row['step_num_count'] = isset($department_step_list[$row['department_id']]) ? $department_step_list[$row['department_id']]['step_num_count'] : 0;
                        $row['average_step_num'] = !empty($row['user_num']) ? intval($row['step_num_count']/$row['user_num']) : 0;
                    }
                }
                
                //根据平均步数倒序排
                $average_step_sort = array_column($department_list,'average_step_num');
                array_multisort($average_step_sort, SORT_DESC,$department_list);
                $return_data['ranking_list'] = $department_list;
            } elseif($ranking_type == 'attend_percent') {
                //部门的参与率排行 【部门下的人数/有步数的人数】
                $department_list = DB::table('w_department')
                                  //->leftJoin('w_company_user','w_department.department_id','=','w_company_user.department_id')
                                    ->select(
                                    //DB::raw('count( distinct w_company_user.user_id) AS user_num'),
                                    'w_department.member_num',
                                    'w_department.name as department_name',
                                    'w_department.department_id'
                                    ) 
                                  ->where(['w_department.company_id' => $company_id,'w_department.status' => 1])
                                  ->groupBy('w_department.department_id')
                                  ->get();

                $day_nums = 1;
                if($activity_start_time > 0 && $activity_end_time > 0){
                    if(time() >= $activity_start_time && time() <= $activity_end_time){ //活动中
                        $day_nums = intval((strtotime(date('Y-m-d')) - strtotime(date('Y-m-d',$activity_start_time)))/86400) +1;
                    } elseif(time() > $activity_end_time){ //活动结束
                        $day_nums = intval((strtotime(date('Y-m-d',$activity_end_time)) - strtotime(date('Y-m-d',$activity_start_time)))/86400)+1;
                    }
                }
                $sql = 'select count(*) as attend_num,c.department_id,c.department_name from '.
                '( select sum(a.step_num)/'.$day_nums.' as avage_step_num,b.department_id,b.department_name '.
                    'from w_step_log a left join w_company_user b on a.user_id = b.user_id '.
                    'where b.company_id = '.$company_id.' and b.is_tested = 0 '.
                    ' and a.data_time >= '.$activity_start_time.' and a.data_time <= '.$activity_end_time.
                    ' group by a.user_id having avage_step_num >= 3000 '.
                ') c group by c.department_id';
                $attend_list_res = DB::select($sql);
                                       
                /*
                $attend_list_res = DB::table('w_step_log')
                                ->leftJoin('w_company_user','w_step_log.user_id','=','w_company_user.user_id')
                                ->select(
                                    DB::raw('count( distinct w_step_log.user_id) AS attend_num'),
                                    'w_company_user.department_id'
                                    )
                                ->where(['w_company_user.company_id' => $company_id,'w_company_user.is_tested' => 0])
                                ->where('w_step_log.data_time','>=',$activity_start_time)
                                ->where('w_step_log.data_time','<=',$activity_end_time)
                                ->groupBy('w_company_user.department_id')
                                ->get();
                */
                
                $attend_list = [];
                if(!empty($attend_list_res)) {
                    foreach($attend_list_res as $row) {
                        $attend_list[$row['department_id']] = $row;
                    }
                }

                if(!empty($department_list)){
                    foreach($department_list as $department_key => &$department_row){
                        if(isset($attend_list[$department_row['department_id']])){
                            $department_row['attend_num'] = $attend_list[$department_row['department_id']]['attend_num'];
                            $department_row['attend_percent'] = ($department_row['attend_num'] > $department_row['member_num'] ? 100 : round($department_row['attend_num']/$department_row['member_num'],4)*100);
                        } else {
                            $department_row['attend_num'] = 0;
                            $department_row['attend_percent'] = 0;
                        }
                    }
                    $attend_percent_sort = array_column($department_list,'attend_percent');
                    array_multisort($attend_percent_sort, SORT_DESC,$department_list);
                }
                // todo 根据参与率倒序排行
                $return_data['ranking_list'] = $department_list;

            } elseif($ranking_type == 'department_member'){
                $offset = $page_index > 1 ? ($page_index-1)*$page_size : 0;
                //个人员工排名
                $user_list = DB::table('w_step_log')
                                ->leftJoin('w_company_user','w_step_log.user_id','=','w_company_user.user_id')
                                ->leftJoin('w_users','w_users.user_id','=','w_company_user.user_id')
                                ->select(
                                    DB::raw('sum(w_step_log.step_num) AS step_num_count'),
                                    'w_company_user.real_name',
                                    'w_company_user.telphone',
                                    'w_company_user.department_id',
                                    'w_company_user.department_name',
                                    'w_company_user.user_id',
                                    'w_users.avatar'
                                    )
                                ->where(['w_company_user.company_id' => $company_id,'w_company_user.is_tested' => 0,'w_company_user.department_id' => $department_id])
                                ->where('w_step_log.data_time','>=',$activity_start_time)
                                ->where('w_step_log.data_time','<=',$activity_end_time)
                                ->where('w_step_log.step_num','<=',80000)
                                ->groupBy('w_step_log.user_id')
                                ->orderBy('step_num_count','desc')
                                ->offset($offset)
                                ->limit($page_size)
                                ->get();
                $return_data['ranking_list'] = $user_list;

                //查询并计算当前用户的排名
                $user_count_list_res = DB::table('w_step_log')
                                ->leftJoin('w_company_user','w_step_log.user_id','=','w_company_user.user_id')
                                ->select(
                                    DB::raw('sum(w_step_log.step_num) AS step_num_count'),
                                    'w_step_log.user_id'
                                    )
                                ->where(['w_company_user.company_id' => $company_id,'w_company_user.is_tested' => 1,'w_company_user.department_id' => $department_id])  
                                ->where('w_step_log.data_time','>=',$activity_start_time)
                                ->where('w_step_log.data_time','<=',$activity_end_time)
                                ->groupBy('w_step_log.user_id')
                                ->orderBy('step_num_count','desc')
                                ->get();
                $user_count_list = [];
                if(!empty($user_count_list_res)){
                    $ranking_num = 1;
                    foreach($user_count_list_res as $row) {
                        $row['ranking_num'] = $ranking_num;
                        $user_count_list[$row['user_id']] = $row;
                        $ranking_num++;
                    }
                }

                $user_info = DB::table('w_company_user')
                            ->leftJoin('w_users','w_company_user.user_id','=','w_users.user_id')
                            ->select(
                                'w_company_user.real_name',
                                'w_company_user.department_name',
                                'w_users.avatar'
                                )
                            ->where(['w_company_user.user_id' => $user_id,'w_company_user.company_id' => $company_id])->first();
                $user_info['user_ranking_num'] = isset($user_count_list[$user_id]) ? $user_count_list[$user_id]['ranking_num'] : count($user_count_list)+1; 
                $user_info['user_step_num_count'] = isset($user_count_list[$user_id]) ? $user_count_list[$user_id]['step_num_count'] : 0; 
                $return_data['user_info'] = $user_info;
            }else {
                $offset = $page_index > 1 ? ($page_index-1)*$page_size : 0;
                //个人员工排名
                $user_list = DB::table('w_step_log')
                                ->leftJoin('w_company_user','w_step_log.user_id','=','w_company_user.user_id')
                                ->leftJoin('w_users','w_users.user_id','=','w_company_user.user_id')
                                ->select(
                                    DB::raw('sum(w_step_log.step_num) AS step_num_count'),
                                    'w_company_user.real_name',
                                    'w_company_user.telphone',
                                    'w_company_user.department_id',
                                    'w_company_user.department_name',
                                    'w_company_user.user_id',
                                    'w_users.avatar'
                                    )
                                ->where(['w_company_user.company_id' => $company_id,'w_company_user.is_tested' => 0])
                                ->where('w_step_log.data_time','>=',$activity_start_time)
                                ->where('w_step_log.data_time','<=',$activity_end_time)
                                ->where('w_step_log.step_num','<=',80000)
                                ->groupBy('w_step_log.user_id')
                                ->orderBy('step_num_count','desc')
                                ->offset($offset)
                                ->limit($page_size)
                                ->get();
                $return_data['ranking_list'] = $user_list;

                //查询并计算当前用户的排名
                $user_count_list_res = DB::table('w_step_log')
                                ->leftJoin('w_company_user','w_step_log.user_id','=','w_company_user.user_id')
                                ->select(
                                    DB::raw('sum(w_step_log.step_num) AS step_num_count'),
                                    'w_step_log.user_id'
                                    )
                                ->where(['w_company_user.company_id' => $company_id,'w_company_user.is_tested' => 0])
                                ->where('w_step_log.data_time','>=',$activity_start_time)
                                ->where('w_step_log.data_time','<=',$activity_end_time)
                                ->groupBy('w_step_log.user_id')
                                ->orderBy('step_num_count','desc')
                                ->get();
                $user_count_list = [];
                if(!empty($user_count_list_res)){
                    $ranking_num = 1;
                    foreach($user_count_list_res as $row) {
                        $row['ranking_num'] = $ranking_num;
                        $user_count_list[$row['user_id']] = $row;
                        $ranking_num++;
                    }
                }

                $user_info = DB::table('w_company_user')
                            ->leftJoin('w_users','w_company_user.user_id','=','w_users.user_id')
                            ->select(
                                'w_company_user.real_name',
                                'w_company_user.department_name',
                                'w_users.avatar'
                                )
                            ->where(['w_company_user.user_id' => $user_id,'w_company_user.company_id' => $company_id])->first();
                $user_info['user_ranking_num'] = isset($user_count_list[$user_id]) ? $user_count_list[$user_id]['ranking_num'] : count($user_count_list)+1; 
                $user_info['user_step_num_count'] = isset($user_count_list[$user_id]) ? $user_count_list[$user_id]['step_num_count'] : 0; 
                $return_data['user_info'] = $user_info;
            }

        } catch (\Exception $e) {
            return $this->jsonError($e->getMessage(),$return_data);
        }
        return  $this->jsonSuccess($return_data);
    }


    /**
     * 肩部走抽奖
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
            if($current_time < strtotime('2019-03-29 08:00:00')) { //2019-04-08 08:00:00
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

            //查询到当周是否抽过的记录 todo 时间点需要再准确点或者再考虑下是否周全
            $count_draw_info = DB::table('w_company_step_luck_draw')
                                ->select(DB::raw('count(id) AS attend_num'))
                                ->where(['activity_id' => $activity_id,'is_selected' => 1])
                                ->where('add_time','>=',$start_last_week+7*24*3600)
                                ->where('add_time','<=',$end_last_week+7*24*3600)
                                ->first();
            if(!empty($count_draw_info) && $count_draw_info['attend_num'] >= 100) {
                //throw new \Exception('抽奖人数已满',400);
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

            if(!empty($count_draw_info) && $count_draw_info['attend_num'] >= 100) {
                $return_data['is_selected'] = 0;
            } else {
                $probability = 0.3;//概率值
                $rand_list = range(1, 100);//随机数的数组
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
                '一' => strtotime('2019-03-29 08:00:00'), //'一' => strtotime('2019-04-08 08:00:00'),
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
            if($current_time < strtotime('2019-03-29 08:00:00')) { //2019-04-08 08:00:00
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

    //测试活动数据
    public function testAction() 
    {
        
        $return_data = ['attend_num' => 0, 'user_draw_status' => 0 ,'draw_status' => 0, 'notice_txt' => '', 'day_num'=>0, 'hour_num' => 0];  //is_selected 是否抽中  has_selected 当周是否已抽过
        $activity_id = $_REQUEST['activity_id'] ? $_REQUEST['activity_id'] : 0;
        $user_id = $_REQUEST['user_id'] ? $_REQUEST['user_id'] : 0;
        $current_time = time();
        //$current_time = strtotime('2019-03-08 12:00:00');
        try {
            $activity_info = [];
            $activity_info['activity_name'] = '测试活动';
            $activity_info['activity_id'] = 1;
            $activity_info['start_time'] = strtotime('2019-03-01');
            $activity_info['end_time'] = strtotime('2020-01-01');
            
            $return_data['activity_name'] = $activity_info['activity_name'];
            $return_data['activity_id'] = $activity_info['activity_id'];

            $activity_start_time    = $activity_info['start_time'];
            $activity_end_time      = $activity_info['end_time'];
            if($current_time < $activity_start_time+7*24*3600) {
                $return_data['notice_txt'] = '抽奖活动暂未开始';
                throw new \Exception('抽奖活动暂未开始',200);
            }
            if($current_time > $activity_end_time+7*24*3600) {
                $return_data['notice_txt'] = '抽奖活动已结束';
                throw new \Exception('抽奖活动已结束',200);
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
                $return_data['user_draw_status'] = 1;//用户的抽奖状态设置
                $return_data['day_num'] = intval(($start_last_week+2*7*24*3600 - $current_time)/86400);
                $return_data['hour_num'] = ceil((($start_last_week+2*7*24*3600 - $current_time)%86400)/3600);
            }
            $return_data['draw_status'] = 1;//标记活动已经开始

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

}