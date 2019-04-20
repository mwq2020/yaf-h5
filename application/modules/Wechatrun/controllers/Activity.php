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
     * 活动列表接口
     */
    public function listAction()
    {
        $user_id = isset($_REQUEST['user_id']) ? intval($_REQUEST['user_id']) : 0;
        if(empty($user_id)){
            $this->jsonError('参数不能为空');
        }
        $activity_list = DB::table('w_company_step_activity_user')
                        ->leftJoin('w_company_step_activity','w_company_step_activity_user.activity_id','=','w_company_step_activity.activity_id')
                        ->select(
                            'w_company_step_activity.activity_id',
                            'w_company_step_activity.company_id',
                            'w_company_step_activity.activity_name',
                            'w_company_step_activity.start_time',
                            'w_company_step_activity.end_time',
                            'w_company_step_activity.status as activity_status',
                            'w_company_step_activity_user.user_id',
                            'w_company_step_activity_user.is_tested'
                        )
                        ->where([
                                 'w_company_step_activity_user.user_id' => $user_id,
                                 'w_company_step_activity.status'       => 1
                              ])->get();
        if(!empty($activity_list)) {
            foreach($activity_list as &$row) {
                $temp_activity_info = DB::table('w_company_step_activity_user')->select(DB::raw('count(*) AS attend_num'))->where(['activity_id' => $row['activity_id'],'status' => 1])->first();
                $row['attend_num'] = isset($temp_activity_info['attend_num']) ? $temp_activity_info['attend_num'] : 0;
            }
        }
        $this->jsonSuccess($activity_list);
    }

    /**
     * 活动详情接口
     */
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
                          ->where([
                                   'w_company_step_activity_user.user_id' => $user_id,
                                   'w_company_step_activity_user.status' => 1,
                                   ])
                          ->orderBy('w_company_step_activity.start_time','desc')
                          ->first();
        if(empty($activity_info)){
            $activity_info = [];
            $activity_info['activity_id'] = 0;
            $activity_info['is_china_unicom'] = 0;
        } elseif(in_array($activity_info['company_id'], [1,9,22])) {
            $activity_info['is_china_unicom'] = 1;
        } else {
            $activity_info['is_china_unicom'] = 0;
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

    /**
     * 用户打卡
     */
    public function hitcardAction()
    {
        $address_id     = isset($_REQUEST['address_id']) ? $_REQUEST['address_id'] : 0;
        $user_id        = isset($_REQUEST['user_id']) ? $_REQUEST['user_id'] : 0;
        $activity_id    = isset($_REQUEST['activity_id']) ? $_REQUEST['activity_id'] : 0;
        $latitude       = isset($_REQUEST['latitude']) ? $_REQUEST['latitude'] : 0;
        $longitude      = isset($_REQUEST['longitude']) ? $_REQUEST['longitude'] : 0;
        //$ip             = isset($_REQUEST['ip']) ? $_REQUEST['ip'] : 0;

        $return_data = ['success' => 1];
        try {
            if(empty($address_id)){
                throw new \Exception('地址id为空');
            }
            if(empty($user_id)){
                throw new \Exception('用户id为空');
            }
            if(empty($activity_id)){
                throw new \Exception('活动id为空');
            }
            if(empty($latitude) || empty($longitude)){
                throw new \Exception('GPS信息为空');
            }


            $card_info = DB::table('w_company_activity_hitcard_log')->where(['user_id' => $user_id,'address_id'=>$address_id])->first();
            if(!empty($card_info)) {
                throw new \Exception('该点已经打过卡了',500);
            }

            $user_ip = $this->getUserRealIp();
            $card_info = DB::table('w_company_activity_hitcard_log')->where(['ip' => $user_ip,'address_id'=>$address_id])->first();
            if(!empty($card_info)) {
                Log::info('用户打卡ip多次打卡:'.$user_id."|".$user_ip);
                //throw new \Exception('一个手机只能打卡一次',500);
            }

            $insert_data = [];
            $insert_data['user_id']     = $user_id;
            $insert_data['activity_id'] = $activity_id;
            $insert_data['address_id']  = $address_id;
            $insert_data['gps_info']    = json_encode(['latitude' => $latitude,'longitude' => $longitude]);
            $insert_data['ip']          = $user_ip;
            $insert_data['add_time']    = time();
            $insert_data['update_time'] = time();
            $flag = DB::table('w_company_activity_hitcard_log')->insertGetId($insert_data);
            if(empty($flag)){
                throw new \Exception('数据入库失败');
            }
        } catch (\Exception $e) {
            $return_data['success'] = 0;
            $error_message = $e->getCode() > 0 ? $e->getMessage() : '打卡失败';
            return $this->jsonError($error_message,$return_data);
        }
        $this->jsonSuccess($return_data);
    }

    /**
     * 获取打卡的点
     */
    public function getHitCardInfoAction()
    {
        $user_id        = isset($_REQUEST['user_id']) ? $_REQUEST['user_id'] : 0;
        $activity_id    = isset($_REQUEST['activity_id']) ? $_REQUEST['activity_id'] : 0;
        $return_data = ['current_address_id' => 0,'start_timestamp' => 0,'end_timestamp' => 0 ,'card_list' => []];

        $hitcard_log_list = DB::table('w_company_activity_hitcard_log')
            ->select('user_id','activity_id','address_id','add_time')
            ->where(['activity_id' => $activity_id,'user_id' => $user_id])
            ->orderBy('address_id','asc')
            ->get();
        if(!empty($hitcard_log_list)) {
            foreach($hitcard_log_list as $key => $row) {
                //记录第一打卡的时间用于时间统计
                if($key == 0) {
                    $return_data['start_timestamp'] = $row['add_time'];
                }
                //记录最后一次打卡的地址
                if($key == count($hitcard_log_list) - 1) {
                    $return_data['current_address_id'] = $row['address_id'];
                }

                if($row['address_id'] == 2) {
                    $return_data['end_timestamp'] = $row['add_time'];
                }
            }
        }
        $return_data['card_list'] = $hitcard_log_list;


        /*
        $return_data = [];
        $return_data['address_list'][] = ['address_id' => 1, 'latitude' => 39.787706,'longitude'=>116.329733,'address_name' => '测试地址1']; //39.787706,116.329733 二区西北叫
        $return_data['address_list'][] = ['address_id' => 2, 'latitude' => 39.787525,'longitude'=>116.333563,'address_name' => '测试地址2'];  //39.787525,116.333563 二区东北角
        $return_data['address_list'][] = ['address_id' => 3, 'latitude' => 39.785554,'longitude'=>116.330130,'address_name' => '测试地址3'];  //39.785554,116.330130 二区西南角
        $return_data['address_list'][] = ['address_id' => 4, 'latitude' => 39.785546,'longitude'=>116.333756,'address_name' => '测试地址4'];  //39.785546,116.333756  二区东南角
        echo json_encode($return_data['address_list']);
        exit;
        */
        $this->jsonSuccess($return_data);
    }

    /**
     * 获取上海银行的用户的个人步数详情
     */
    public function getShanghaiBocUserStepAction()
    {
        $return_data = ['current_day_num' => 0,'activity_day_num' => 0,'step_num_count' => 0,'map_flag' => 0];
        $user_id        = isset($_REQUEST['user_id']) ? $_REQUEST['user_id'] : 0;
        $activity_id    = isset($_REQUEST['activity_id']) ? $_REQUEST['activity_id'] : 0;
        try {
            if(empty($user_id)) {
                throw new \Exception('入参错误');
            }
            if(empty($activity_id)) {
                throw new \Exception('入参错误');
            }
            $activity_info = DB::table('w_company_step_activity')->where(['activity_id'=>$activity_id])->first();
            if(empty($activity_info)) {
                throw new \Exception('活动详情为空');
            }

            $start_time = $activity_info['start_time'];
            $end_time = $activity_info['end_time'];

            if(time() < $start_time){
                throw new \Exception('活动还没开始');
            }

            //检查用户达标情况
            $sql = "select sum(step_num) as step_num_count,user_id ".
                "from w_step_log ".
                "where user_id = {$user_id} and ".
                "data_time >= {$start_time} and ".
                "data_time <= {$end_time} ";
            $step_count_info = DB::selectOne($sql);
            $return_data['step_num_count'] = $step_count_info['step_num_count'];

            if(time() < $start_time){
                $day_num = 0;
            } else if(time() > $end_time){
                $day_num = ceil(($end_time - $start_time)/86400);
            } else {
                $day_num = ceil((time()-$start_time)/86400);
            }
            $return_data['current_day_num']     = $day_num;//活动开始到现在经过了几天,不超过活动总天数
            $return_data['activity_day_num']    = ceil(($end_time - $start_time)/86400); //活动期间的总天数

            if($step_count_info['step_num_count'] > 0){
                $return_data['map_flag'] = intval($step_count_info['step_num_count']/6000);
            }
            $return_data['map_flag'] = $return_data['map_flag'] > $day_num ? $day_num : $return_data['map_flag'];//活动点亮图标个数

        } catch(\Exception $e) {
            $error_message = $e->getCode() > 0 ? $e->getMessage() : '服务错误';
            return $this->jsonError($error_message,$return_data);
        }
        $this->jsonSuccess($return_data);
    }


}