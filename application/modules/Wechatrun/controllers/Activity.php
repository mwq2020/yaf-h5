<?php
/****************************/
/****** 健步走活动入口 ********/
/****************************/

class ActivityController extends Core\Base
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
     * 活动列表
     */
    public function listAction()
    {
        $company_id = isset($_REQUEST['company_id']) ? intval($_REQUEST['company_id']) : 0;
        if(empty($company_id)){
            $this->jsonError('企业id不能为空');
        }
        $activity_list = DB::table('w_step_activity')->where(['company_id'=>$company_id])->get();
        $this->jsonSuccess($activity_list);
    }

    public function activeActivityInfoAction()
    {
        $user_id = isset($_REQUEST['user_id']) ? intval($_REQUEST['user_id']) : 0;
        $company_id = isset($_REQUEST['company_id']) ? intval($_REQUEST['company_id']) : 0;
        $department_id = isset($_REQUEST['department_id']) ? intval($_REQUEST['department_id']) : 0;

        $activity_info  = DB::table('w_company_step_activity')
                          ->leftJoin('w_company_step_activity_user','w_company_step_activity.activity_id','=','w_company_step_activity_user.activity_id')
                          ->select(
                            'w_company_step_activity.company_id',
                            'w_company_step_activity.activity_id',
                            'w_company_step_activity.activity_name'
                            )  
                          ->where(['w_company_step_activity.company_id'=>$company_id,
                                   'w_company_step_activity_user.user_id' => $user_id,
                                   'w_company_step_activity_user.status' => 1,
                                   ])
                          ->orderBy('w_company_step_activity.start_time','desc')
                          ->first();
        if(!empty($activity_info) && in_array($activity_info['company_id'], [1,9])) {
            $activity_info['is_china_unicom'] = 1;
        }

        $this->jsonSuccess($activity_info);
    }

    /**
     * 活动详情
     */
    public function infoAction()
    {
        $company_id = isset($_REQUEST['company_id']) ? intval($_REQUEST['company_id']) : 0;
        if(empty($company_id)){
            $this->jsonError('企业id不能为空');
        }
        $act_id = isset($_REQUEST['act_id']) ? intval($_REQUEST['act_id']) : 0;
        if(empty($act_id)){
            $this->jsonError('活动id不能为空');
        }
        $activity_info = DB::table('w_step_activity')->where(['act_id'=>$act_id])->first();
        $this->jsonSuccess($activity_info); 
    }

    /**
     * 公司所有人排行
     */
    public function rankallAction()
    {
        $exclude_user_ids = [4423];//排除用户的user_id
        $company_id = isset($_REQUEST['company_id']) ? intval($_REQUEST['company_id']) : 0;
        if(empty($company_id)){
            $this->jsonError('企业id不能为空');
        }
        $act_id = isset($_REQUEST['act_id']) ? intval($_REQUEST['act_id']) : 0;
        if(empty($act_id)){
            //$this->jsonError('活动id不能为空');
        }

        $user_id = isset($_REQUEST['user_id']) ? intval($_REQUEST['user_id']) : 0;
        if(empty($user_id)){
            $this->jsonError('用户id不能为空');
        }

        $activity_start_time = strtotime('2018-03-08');
        $activity_end_time = strtotime('2018-03-31');
        $statistics_end_time = strtotime('2018-04-03 23:59:59');//统计数据结束时间，之后上传的数据不算有效数据
        if($company_id == 10){
            $activity_start_time = strtotime('2018-03-26');
            $activity_end_time = strtotime('2018-04-15'); 
            $statistics_end_time = $activity_end_time + (3*24*3600);
        } elseif($company_id == 11){
            $activity_start_time = strtotime('2018-04-21');
            $activity_end_time = strtotime('2018-04-30');
            $statistics_end_time = $activity_end_time + (3*24*3600);
        } elseif($company_id == 12){
            $activity_start_time = strtotime('2018-04-18');
            $activity_end_time = strtotime('2018-06-30');
            $statistics_end_time = $activity_end_time + (3*24*3600);
        } elseif($company_id == 13){
            $activity_start_time = strtotime('2018-05-01');
            $activity_end_time = strtotime('2018-05-10');
            $statistics_end_time = $activity_end_time + (3*24*3600);
        } elseif($company_id == 15){
            $activity_start_time = strtotime('2018-06-26');
            $activity_end_time = strtotime('2018-07-03');
            $statistics_end_time = $activity_end_time + (3*24*3600);
        } elseif($company_id == 16){
            $activity_start_time = strtotime('2018-09-01');
            $activity_end_time = strtotime('2018-09-13');
            $statistics_end_time = $activity_end_time + (3*24*3600);
        } elseif($company_id == 17){
            $activity_start_time = strtotime('2018-09-20');
            $activity_end_time = strtotime('2018-10-19');
            $statistics_end_time = $activity_end_time + (3*24*3600);
        }


        //新的活动都按照表都数据走，覆盖前面写死的活动时间
        $activity_member_info = DB::table('w_company_step_activity_user')
        ->leftJoin('w_company_step_activity','w_company_step_activity_user.activity_id','=','w_company_step_activity.activity_id')
        ->select(
                    'w_company_step_activity_user.activity_id',
                    'w_company_step_activity_user.user_id',
                    'w_company_step_activity.company_id',
                    'w_company_step_activity.activity_name',
                    'w_company_step_activity.start_time',
                    'w_company_step_activity.end_time'
                    )  
        ->where(['w_company_step_activity_user.user_id'=>$user_id,
                 'w_company_step_activity_user.is_tested' => 0,
                 'w_company_step_activity_user.status' => 1,
                 'w_company_step_activity.status' => 1
            ])
        ->where('w_company_step_activity.start_time','<=',time())
        ->orderBy('w_company_step_activity.start_time','desc')
        ->first();
        if($activity_member_info){
            $activity_start_time    = $activity_member_info['start_time'];
            $activity_end_time      = $activity_member_info['end_time'];
            $statistics_end_time    = $activity_end_time + (3*24*3600);
            $company_id             = $activity_member_info['company_id'];//活动的企业id
            $activity_id            = $activity_member_info['activity_id'];//活动id


            $ret = DB::table('w_step_log')  
                ->leftJoin('w_company_step_activity_user','w_step_log.user_id','=','w_company_step_activity_user.user_id')
                ->leftJoin('w_company_user','w_step_log.user_id','=','w_company_user.user_id')
                ->leftJoin('w_users','w_users.user_id','=','w_company_step_activity_user.user_id')
                ->select(
                     DB::raw('SUM(w_step_log.step_num) AS step_num_all'),
                    'w_company_user.real_name',
                    'w_company_user.telphone',
                    'w_company_user.department_id',
                    'w_company_user.department_name',
                    'w_company_user.user_id',
                    'w_users.avatar'
                    )  
                ->groupBy('w_company_user.user_id')
                ->where(['w_company_step_activity_user.activity_id' => $activity_id,'w_company_user.company_id' => $company_id,'w_company_step_activity_user.status' => 1,'w_company_user.status' => 1])
                ->where('w_step_log.data_time','>=',$activity_start_time)
                ->where('w_step_log.data_time','<=',$activity_end_time)
                ->where('w_step_log.add_time','<=',$statistics_end_time)
                ->orderBy('step_num_all','desc')
                ->get();

        } else {
            $ret = DB::table('w_step_log')  
                ->leftJoin('w_company_user','w_step_log.user_id','=','w_company_user.user_id')
                ->leftJoin('w_users','w_users.user_id','=','w_company_user.user_id')
                ->select(
                     DB::raw('SUM(w_step_log.step_num) AS step_num_all'),
                    'w_company_user.real_name',
                    'w_company_user.telphone',
                    'w_company_user.department_id',
                    'w_company_user.department_name',
                    'w_company_user.user_id',
                    'w_users.avatar'
                    )  
                ->groupBy('w_company_user.user_id')
                ->where(['w_company_user.company_id' => $company_id,'w_company_user.status' => 1])
                ->where('w_step_log.data_time','>=',$activity_start_time)
                ->where('w_step_log.data_time','<=',$activity_end_time)
                ->where('w_step_log.add_time','<=',$statistics_end_time)
                ->orderBy('step_num_all','desc')
                ->get();
        }

        $current_user_rank_num = 0;
        $ranking_list = ['info'=>[],'list' => [],'one'=>[],'two'=>[],'three'=>[]];
        $ranking_num = 1;
        foreach($ret as $row){
            $row['ranking_num'] = $ranking_num;
            if($row['user_id'] == $user_id) {
                $ranking_list['info'] = $row;
                $current_user_rank_num = $ranking_num;
            }
            $ranking_list['list'][$ranking_num] = $row;
            if(in_array($row['user_id'], $exclude_user_ids)){
                continue;
            }
            $ranking_num++;
        }

        if(empty($ranking_list['info'])){
            $company_user = DB::table('w_company_user')->where(['company_id' => $company_id,'user_id' => $user_id])->first();
            $user_info = DB::table('w_users')->where(['user_id'=>$user_id])->first();
            $ranking_list['info']['step_num_all']   = 0;
            $ranking_list['info']['real_name']      = $company_user['real_name'];
            $ranking_list['info']['telphone']       = $company_user['telphone'];
            $ranking_list['info']['department_id']  = $company_user['department_id'];
            $ranking_list['info']['department_name'] = $company_user['department_name'];
            $ranking_list['info']['user_id']        = $company_user['user_id'];
            $ranking_list['info']['avatar']          = $user_info['avatar'];//用户头像
            $ranking_list['info']['ranking_num']    = count($ret)+1;
        }

        $list_count = count($ranking_list['list']);
        if($list_count >= 1){
            $ranking_list['one'] = $ranking_list['list'][1];
        }

        if($list_count >= 2){
            $ranking_list['two'] = $ranking_list['list'][2];
        }

        if($list_count >= 3){
            $ranking_list['three'] = $ranking_list['list'][3];
        }


        // 前100名 自己名字附近的前后5名
        $min_display_num = 100;
        $rank_list_new = [];
        if(count($ranking_list['list']) >= $min_display_num){
            if($current_user_rank_num == 0){
                $rank_list_new = array_slice($ranking_list['list'], 0,$min_display_num,true);
            } else {
                $rank_list_new = array_slice($ranking_list['list'], 0,$min_display_num,true);
                $min_ranking_num = $current_user_rank_num-5;
                $max_ranking_num = $current_user_rank_num+5;
                if($max_ranking_num >  $min_display_num){
                    $min_ranking_num = $min_ranking_num > $min_display_num ? $min_ranking_num : $min_display_num;
                    $temp = array_slice($ranking_list['list'], $min_ranking_num,$max_ranking_num-$min_ranking_num,true);
                    if(!empty($temp)){
                        //$rank_list_new = array_merge($rank_list_new,$temp);
                        $rank_list_new = $rank_list_new+$temp;
                    }
                }
            }
            $ranking_list['list'] = $rank_list_new;
        }


        $this->jsonSuccess($ranking_list);
    }

    /**
     * 部门排行
     */
    public function rankdepartmentAction()
    {
        $exclude_user_ids = [2071];//排除用户的user_id
        $company_id = isset($_REQUEST['company_id']) ? intval($_REQUEST['company_id']) : 0;
        if(empty($company_id)){
            $this->jsonError('企业id不能为空');
        }
        $department_id = isset($_REQUEST['department_id']) ? intval($_REQUEST['department_id']) : 0;
        if(empty($department_id)){
            $this->jsonError('部门id不能为空');
        }
        $act_id = isset($_REQUEST['act_id']) ? intval($_REQUEST['act_id']) : 0;
        if(empty($act_id)){
            //$this->jsonError('活动id不能为空');
        }
        $user_id = isset($_REQUEST['user_id']) ? intval($_REQUEST['user_id']) : 0;
        if(empty($user_id)){
            $this->jsonError('用户id不能为空');
        }

        $activity_start_time = strtotime('2018-03-08');
        $activity_end_time = strtotime('2018-03-31');
        $statistics_end_time = strtotime('2018-04-03 23:59:59');//统计数据结束时间，之后上传的数据不算有效数据
        if($company_id == 10){
            $activity_start_time = strtotime('2018-03-26');
            $activity_end_time = strtotime('2018-04-15');
            $statistics_end_time = $activity_end_time + (3*24*3600);
        } elseif($company_id == 11){
            $activity_start_time = strtotime('2018-04-21');
            $activity_end_time = strtotime('2018-04-30');
            $statistics_end_time = $activity_end_time + (3*24*3600);
        } elseif($company_id == 12){
            $activity_start_time = strtotime('2018-04-18');
            $activity_end_time = strtotime('2018-06-30');
            $statistics_end_time = $activity_end_time + (3*24*3600);
        } elseif($company_id == 13){
            $activity_start_time = strtotime('2018-05-01');
            $activity_end_time = strtotime('2018-05-10');
            $statistics_end_time = $activity_end_time + (3*24*3600);
        } elseif($company_id == 15){
            $activity_start_time = strtotime('2018-06-26');
            $activity_end_time = strtotime('2018-07-03');
            $statistics_end_time = $activity_end_time + (3*24*3600);
        } elseif($company_id == 16){
            $activity_start_time = strtotime('2018-09-01');
            $activity_end_time = strtotime('2018-09-13');
            $statistics_end_time = $activity_end_time + (3*24*3600);
        } elseif($company_id == 17){
            $activity_start_time = strtotime('2018-09-20');
            $activity_end_time = strtotime('2018-10-19');
            $statistics_end_time = $activity_end_time + (3*24*3600);
        }

        //新的活动都按照表都数据走，覆盖前面写死的活动时间
        $activity_member_info = DB::table('w_company_step_activity_user')
        ->leftJoin('w_company_step_activity','w_company_step_activity_user.activity_id','=','w_company_step_activity.activity_id')
        ->select(
                    'w_company_step_activity_user.activity_id',
                    'w_company_step_activity_user.user_id',
                    'w_company_step_activity.company_id',
                    'w_company_step_activity.activity_name',
                    'w_company_step_activity.start_time',
                    'w_company_step_activity.end_time'
                    )  
        ->where(['w_company_step_activity_user.user_id'=>$user_id,
                 'w_company_step_activity_user.is_tested' => 0,
                 'w_company_step_activity_user.status' => 1,
                 'w_company_step_activity.status' => 1
            ])
        ->where('w_company_step_activity.start_time','<=',time())
        ->orderBy('w_company_step_activity.start_time','desc')
        ->first();
        if($activity_member_info){
            $activity_start_time    = $activity_member_info['start_time'];
            $activity_end_time      = $activity_member_info['end_time'];
            $statistics_end_time    = $activity_end_time + (3*24*3600);
            $company_id             = $activity_member_info['company_id'];//活动的企业id
            $activity_id             = $activity_member_info['activity_id'];//活动id
            //获取用户的企业员工信息
            $company_user_info = DB::table('w_company_user')->where(['company_id'=>$company_id,'user_id'=>$user_id])->orderBy('add_time','desc')->first();
            if($company_user_info) {
               $department_id =  $company_user_info['department_id'];
            }

            $ret = DB::table('w_step_log')
                ->leftJoin('w_company_step_activity_user','w_step_log.user_id','=','w_company_step_activity_user.user_id')
                ->leftJoin('w_company_user','w_step_log.user_id','=','w_company_user.user_id')
                ->leftJoin('w_users','w_users.user_id','=','w_company_user.user_id')
                ->select(
                     DB::raw('SUM(w_step_log.step_num) AS step_num_all'),
                    'w_company_user.real_name',
                    'w_company_user.telphone',
                    'w_company_user.department_id',
                    'w_company_user.department_name',
                    'w_company_user.user_id',
                    'w_users.avatar'
                    )  
                ->groupBy('w_company_user.user_id')
                ->where(['w_company_step_activity_user.activity_id' => $activity_id,'w_company_user.company_id' => $company_id,'w_company_user.department_id' => $department_id,'w_company_step_activity_user.status' => 1,'w_company_user.status' => 1])
                ->where('w_step_log.data_time','>=',$activity_start_time)
                ->where('w_step_log.data_time','<=',$activity_end_time)
                ->where('w_step_log.add_time','<=',$statistics_end_time)
                ->orderBy('step_num_all','desc')
                ->get();

        } else {
            $ret = DB::table('w_step_log')  
                ->leftJoin('w_company_user','w_step_log.user_id','=','w_company_user.user_id')
                ->leftJoin('w_users','w_users.user_id','=','w_company_user.user_id')
                ->select(
                     DB::raw('SUM(w_step_log.step_num) AS step_num_all'),
                    'w_company_user.real_name',
                    'w_company_user.telphone',
                    'w_company_user.department_id',
                    'w_company_user.department_name',
                    'w_company_user.user_id',
                    'w_users.avatar'
                    )  
                ->groupBy('w_company_user.user_id')
                ->where(['w_company_user.company_id' => $company_id,'w_company_user.department_id' => $department_id,'w_company_user.status' => 1])
                ->where('w_step_log.data_time','>=',$activity_start_time)
                ->where('w_step_log.data_time','<=',$activity_end_time)
                ->where('w_step_log.add_time','<=',$statistics_end_time)
                ->orderBy('step_num_all','desc')
                ->get();
        }

        

        $current_user_rank_num = 0;
        $ranking_list = ['info'=>[],'list' => [],'one'=>[],'two'=>[],'three'=>[]];
        $ranking_num = 1;
        foreach($ret as $row){
            $row['ranking_num'] = $ranking_num;
            if($row['user_id'] == $user_id) {
                $ranking_list['info'] = $row;
                $current_user_rank_num = $ranking_num;
            }
            //if(in_array($row['user_id'], $exclude_user_ids)) {
            //    continue;
            //}
            $ranking_list['list'][$ranking_num] = $row;
            $ranking_num++;
        }

        if(empty($ranking_list['info'])){
            $company_user = DB::table('w_company_user')->where(['company_id' => $company_id,'user_id' => $user_id])->first();
            $user_info = DB::table('w_users')->where(['user_id'=>$user_id])->first();
            $ranking_list['info']['step_num_all']   = 0;
            $ranking_list['info']['real_name']      = $company_user['real_name'];
            $ranking_list['info']['telphone']       = $company_user['telphone'];
            $ranking_list['info']['department_id']  = $company_user['department_id'];
            $ranking_list['info']['department_name'] = $company_user['department_name'];
            $ranking_list['info']['user_id']        = $company_user['user_id'];
            $ranking_list['info']['avatar']          = $user_info['avatar'];//用户头像
            $ranking_list['info']['ranking_num']    = count($ret)+1;
        }


        $list_count = count($ranking_list['list']);
        if($list_count >= 1){
            $ranking_list['one'] = $ranking_list['list'][1];
        }

        if($list_count >= 2){
            $ranking_list['two'] = $ranking_list['list'][2];
        }

        if($list_count >= 3){
            $ranking_list['three'] = $ranking_list['list'][3];
        }

        // 前100名 自己名字附近的前后5名
        $min_display_num = 100;
        $rank_list_new = [];
        if(count($ranking_list['list']) >= $min_display_num){
            if($current_user_rank_num == 0){
                $rank_list_new = array_slice($ranking_list['list'], 0,$min_display_num,true);
            } else {
                $rank_list_new = array_slice($ranking_list['list'], 0,$min_display_num,true);
                $min_ranking_num = $current_user_rank_num-5;
                $max_ranking_num = $current_user_rank_num+5;
                if($max_ranking_num >  $min_display_num){
                    $min_ranking_num = $min_ranking_num > $min_display_num ? $min_ranking_num : $min_display_num;
                    $temp = array_slice($ranking_list['list'], $min_ranking_num,$max_ranking_num-$min_ranking_num,true);
                    if(!empty($temp)){
                        //$rank_list_new = array_merge($rank_list_new,$temp);
                        $rank_list_new = $rank_list_new+$temp;
                    }
                }
            }
            $ranking_list['list'] = $rank_list_new;
        }

        $this->jsonSuccess($ranking_list);
    }


}