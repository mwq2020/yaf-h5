<?php


class StepModel {

    /**
     * 获取部门的参与率
     */
    public static function getDepartmentAttend($activity_info)
    {
        $company_id     = $activity_info['company_id'];
        $activity_start_time    = $activity_info['start_time'];
        $activity_end_time      = $activity_info['end_time'];
        
        //部门的参与率排行 【部门下的人数/有步数的人数】
        if($company_id == 28 && false) { //廊坊银行暂且不使用此处逻辑
            $department_list = DB::table('w_department')
                ->leftJoin('w_company_user','w_department.department_id','=','w_company_user.department_id')
                ->select(
                    DB::raw('count( distinct w_company_user.user_id) AS user_num'),
                    //'w_department.member_num',
                    'w_department.name as department_name',
                    'w_department.department_id'
                )
                ->where(['w_department.company_id' => $company_id,'w_department.status' => 1])
                ->groupBy('w_department.department_id')
                ->get();
        } else {
            $department_list = DB::table('w_department')
                ->select(
                    'w_department.member_num',
                    'w_department.name as department_name',
                    'w_department.department_id'
                )
                ->where(['w_department.company_id' => $company_id,'w_department.status' => 1])
                ->groupBy('w_department.department_id')
                ->get();
        }

        $day_nums = 1;
        if($activity_start_time > 0 && $activity_end_time > 0){
            if(time() >= $activity_start_time && time() <= $activity_end_time){ //活动中
                $day_nums = intval((strtotime(date('Y-m-d')) - strtotime(date('Y-m-d',$activity_start_time)))/86400) +1;
            } elseif(time() > $activity_end_time){ //活动结束
                $day_nums = intval((strtotime(date('Y-m-d',$activity_end_time)) - strtotime(date('Y-m-d',$activity_start_time)))/86400)+1;
            }
        }

        //廊坊银行
        $target_avage_step_num = 3000;
        if($company_id == 28) {
            $target_avage_step_num = 4000;
        }
        $sql = 'select count(*) as attend_num,c.department_id,c.department_name from '.
            '( select sum(a.step_num)/'.$day_nums.' as avage_step_num,b.department_id,b.department_name '.
            'from w_step_log a left join w_company_user b on a.user_id = b.user_id '.
            'where b.company_id = '.$company_id.' and b.is_tested = 0 '.
            ' and a.data_time >= '.$activity_start_time.' and a.data_time <= '.$activity_end_time.
            " group by a.user_id having avage_step_num >= {$target_avage_step_num} ".
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

        if(!empty($department_list)) {
            foreach($department_list as $department_key => &$department_row){
                if(isset($attend_list[$department_row['department_id']])){
                    $department_row['attend_num'] = $attend_list[$department_row['department_id']]['attend_num'];
                    if($company_id == 28 && false) { //廊坊银行暂且不使用此处逻辑
                        $department_row['attend_percent'] = ($department_row['attend_num'] > $department_row['user_num'] ? 100 : round($department_row['attend_num']/$department_row['user_num'],4)*100);
                    } else {
                        $department_row['attend_percent'] = ($department_row['attend_num'] > $department_row['member_num'] ? 100 : round($department_row['attend_num']/$department_row['member_num'],4)*100);
                    }
                } else {
                    $department_row['attend_num'] = 0;
                    $department_row['attend_percent'] = 0;
                }
            }
            $attend_percent_sort = array_column($department_list,'attend_percent');
            array_multisort($attend_percent_sort, SORT_DESC,$department_list);

            $temp_ranking_num = 1;
            foreach($department_list as $key => $department_info) {
                if(isset($temp_attend_percent)){
                    if($department_info['attend_percent'] < $temp_attend_percent){
                        $temp_ranking_num++;
                    }
                    $department_list[$key]['ranking_num'] = $temp_ranking_num;
                    $temp_attend_percent = $department_info['attend_percent'];
                } else {
                    $department_list[$key]['ranking_num'] = $temp_ranking_num;
                    $temp_attend_percent = $department_info['attend_percent'];
                }
            }

        }

        return  $department_list;
    }

    /**
     * 获取部门内人员的排行
     */
    public static function getDepartmentRanking($activity_info,$user_id,&$return_data)
    {
        $page_index     = isset($_REQUEST['page_index']) ? intval($_REQUEST['page_index']) : 1; //当前页码
        $page_size      = isset($_REQUEST['page_size']) ? intval($_REQUEST['page_size']) : 50;    //每页返回数据的条数
        $company_id     = $activity_info['company_id'];
        $activity_start_time    = $activity_info['start_time'];
        $activity_end_time      = $activity_info['end_time'];

        $company_user_info  = DB::table('w_company_user')->where(['user_id'=>$user_id,'company_id'=>$activity_info['company_id']])->first();
        if(empty($company_user_info)) {
            throw new \Exception('服务错误，请重试');
        }
        $department_id = $company_user_info['department_id'];

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
            ->where('w_step_log.user_id','!=',4423) //排除工行的王建红
            ->groupBy('w_step_log.user_id')
            ->orderBy('step_num_count','desc')
            ->offset($offset)
            ->limit($page_size)
            ->get();
        $return_data['ranking_list'] = $user_list; //部门排行列表数据


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
    }

    /**
     * 获取部门平均成绩排行
     */
    public static function getDepartmentAverageRanking($activity_info,&$return_data)
    {
        $company_id     = $activity_info['company_id'];
        $activity_start_time    = $activity_info['start_time'];
        $activity_end_time      = $activity_info['end_time'];

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

            //根据平均步数倒序排
            $average_step_sort = array_column($department_list,'average_step_num');
            array_multisort($average_step_sort, SORT_DESC,$department_list);
            $temp_ranking_num = 1;
            foreach($department_list as $key => $department_info) {
                if(isset($temp_average_step_num)){
                    if($department_info['average_step_num'] < $temp_average_step_num){
                        $temp_ranking_num++;
                    }
                    $department_list[$key]['ranking_num'] = $temp_ranking_num;
                    $temp_average_step_num = $department_info['average_step_num'];
                } else {
                    $department_list[$key]['ranking_num'] = $temp_ranking_num;
                    $temp_average_step_num = $department_info['average_step_num'];
                }
            }
        }

        $return_data['ranking_list'] = $department_list;
        return true;
    }

    /**
     * 获取活动全员的成绩排行
     */
    public static function getAllMemberRanking($activity_info,$user_id,&$return_data)
    {
        $page_index     = isset($_REQUEST['page_index']) ? intval($_REQUEST['page_index']) : 1; //当前页码
        $page_size      = isset($_REQUEST['page_size']) ? intval($_REQUEST['page_size']) : 50;    //每页返回数据的条数
        $company_id     = $activity_info['company_id'];
        $activity_start_time    = $activity_info['start_time'];
        $activity_end_time      = $activity_info['end_time'];

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
            ->where('w_step_log.user_id','!=',4423) //排除工行的王建红
            ->groupBy('w_step_log.user_id')
            ->orderBy('step_num_count','desc')
            //->orderBy('w_company_user.user_id','desc')
            ->offset($offset)
            ->limit($page_size)
            ->get();
        $return_data['ranking_list'] = $user_list;

        //获取全部人员的步数及顺序用于计算个人排名
        $sql =  "select sum(a.step_num) as step_num_count,a.user_id ".
            "from w_step_log a left join w_company_user b on a.user_id = b.user_id ".
            "where b.company_id = {$company_id} and (b.is_tested = 0 or b.user_id={$user_id}) ".
            "and a.data_time >= {$activity_start_time} and a.data_time <= {$activity_end_time} ".
            "and a.step_num <= 80000 ".
            "group by a.user_id order by step_num_count desc";
        //"group by a.user_id order by step_num_count desc,a.user_id desc";
        $user_count_list_res = DB::select($sql);

        /*
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
        */
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
                'w_company_user.is_tested',
                'w_users.avatar'
            )
            ->where(['w_company_user.user_id' => $user_id,'w_company_user.company_id' => $company_id])
            ->first();

        $user_info['user_ranking_num'] = isset($user_count_list[$user_id]) ? $user_count_list[$user_id]['ranking_num'] : count($user_count_list)+1;
        $user_info['user_step_num_count'] = isset($user_count_list[$user_id]) ? $user_count_list[$user_id]['step_num_count'] : 0;
        $return_data['user_info'] = $user_info;
        return true;
    }



}