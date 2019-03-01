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
            $activity_start_time = $activity_info['start_time'];
            $activity_end_time = $activity_info['end_time'];

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
                                  ->leftJoin('w_company_user','w_department.department_id','=','w_company_user.department_id')
                                    ->select(
                                     DB::raw('count( distinct w_company_user.user_id) AS user_num'),
                                    'w_department.name as department_name',
                                    'w_department.department_id'
                                    ) 
                                  ->where(['w_company_user.company_id' => $company_id,'w_company_user.is_tested' => 0])
                                  ->groupBy('w_department.department_id')
                                  ->get();


                /*
                $day_nums = 1;
                if($activity_start_time > 0 && $activity_end_time > 0){
                    if(time() >= $activity_start_time && time() <= $activity_end_time){ //活动中
                        $day_nums = intval((strtotime(time('Y-m-d')) - strtotime(date('Y-m-d',$activity_start_time)))/86400) +1;
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
                */                       

                
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
                
                if(!empty($attend_list_res)) {
                    $attend_list = [];
                    foreach($attend_list_res as $row) {
                        $attend_list[$row['department_id']] = $row;
                    }
                }

                if(!empty($department_list)){
                    foreach($department_list as $department_key => &$department_row){
                        if(isset($attend_list[$department_row['department_id']])){
                            $department_row['attend_num'] = $attend_list[$department_row['department_id']]['attend_num'];
                            $department_row['attend_percent'] = ($department_row['attend_num'] > $department_row['user_num'] ? 1 : round($department_row['attend_num']/$department_row['user_num'],4)*100);
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
            } else {
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



}