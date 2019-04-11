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
        if($company_id == 28) {
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
                    $department_row['attend_percent'] = ($department_row['attend_num'] > $department_row['member_num'] ? 100 : round($department_row['attend_num']/$department_row['member_num'],4)*100);
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



}