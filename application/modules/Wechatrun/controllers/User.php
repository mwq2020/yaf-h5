<?php
/****************************/
/*******  健步走用户  ********/
/****************************/


// use Yaf\Controller_Abstract;
class UserController extends Core\Base 
{

    /**
     *  用户的登录操作
     */ 
    public function loginAction() 
    {
        $account = isset($_REQUEST['account']) ? $_REQUEST['account'] : 0;
        if(empty($account)){
            return $this->jsonError('账号不能为空');
        }

        $password = isset($_REQUEST['password']) ? $_REQUEST['password'] : '';
        if(empty($password)){
            return $this->jsonError('密码不能为空');
        }

        $userInfo = DB::table('w_users')->select('user_id','telphone','password','ec_salt')->where(['telphone'=>$account])->first();
        if(empty($userInfo)){
            return $this->jsonError('用户不存在');
        }

        $make_password = '';
        if ($userInfo['ec_salt']) {
            $make_password = md5(md5($password).$userInfo['ec_salt']);
        } else {
            $make_password = md5($password);
        }
        
        if($userInfo['password'] != $make_password) {
            return $this->jsonError('密码错误');
        }

        $company_user = DB::table('w_company_user')->where(['user_id'=>$userInfo['user_id'],'status' => 1])
                        ->orderBy('update_time','desc')->first();
        if(empty($company_user)){
            return $this->jsonError('数据错误！');
        }

        $avatar = isset($_REQUEST['avatar']) ? $_REQUEST['avatar'] : '';
        if(!empty($avatar)){
            DB::table('w_users')->where(['user_id'=>$company_user['user_id']])->update(['avatar' => $avatar]);
        }

        $return_data = [];
        $return_data['user_id']         = $company_user['user_id'];
        $return_data['company_id']      = $company_user['company_id'];
        $return_data['telphone']        = $company_user['telphone'];
        $return_data['real_name']       = $company_user['real_name'];
        $return_data['department_id']   = $company_user['department_id'];
        $return_data['department_name'] = $company_user['department_name'];

        return $this->jsonSuccess($return_data);
    }

    /**
     *  用户的登录操作
     */ 
    public function codeloginAction() 
    {
        
        $account = isset($_REQUEST['account']) ? $_REQUEST['account'] : 0;
        if(empty($account)){
            $this->jsonError('账号不能为空');
        }

        $code = isset($_REQUEST['code']) ? $_REQUEST['code'] : '';
        if(empty($code)){
            $this->jsonError('验证码不能为空');
        }

        $valid_id = isset($_REQUEST['valid_id']) ? $_REQUEST['valid_id'] : '';
        if(empty($valid_id)){
            $this->jsonError('验证码登录失败【1】');
        }

        $smsInfo = DB::table('w_sms_log')->where(['id'=>$valid_id])->first();
        if(empty($smsInfo)){
            $this->jsonError('验证码登录失败【2】');
        } elseif($smsInfo['is_validated'] == 1){
            $this->jsonError('验证码已使用');
        } elseif(time() - $smsInfo['add_time'] > 1200){
            $this->jsonError('验证码已过期');
        }

        if(empty($smsInfo['verification_code']) || $smsInfo['verification_code'] != $code){
            $this->jsonError('验证码登录失败【3】');
        }

        //更新验证码到已使用
        $flag = DB::table('w_sms_log')->where(['id'=>$valid_id])->update(['is_validated' => 1,'update_time' => time()]);
        if(empty($flag)){
            $this->jsonError('验证码登录失败【4】');
        }

        $userInfo = DB::table('w_users')->where(['telphone'=>$account])->first();
        if(empty($userInfo)){
            $this->jsonError('用户不存在');
        }

        $company_user = DB::table('w_company_user')->where(['user_id'=>$userInfo['user_id'],'status' => 1])
                        ->orderBy('update_time','desc')->first();
        if(empty($company_user)){
            return $this->jsonError('数据错误！');
        }

        $avatar = isset($_REQUEST['avatar']) ? $_REQUEST['avatar'] : '';
        if(!empty($avatar)){
            DB::table('w_users')->where(['user_id'=>$company_user['user_id']])->update(['avatar' => $avatar]);
        }

        $return_data = [];
        $return_data['user_id']         = $company_user['user_id'];
        $return_data['company_id']      = $company_user['company_id'];
        $return_data['telphone']        = $company_user['telphone'];
        $return_data['real_name']       = $company_user['real_name'];
        $return_data['department_id']   = $company_user['department_id'];
        $return_data['department_name'] = $company_user['department_name'];

        $this->jsonSuccess($return_data);
    }

    /**
     * 发送验证码
     */
    public function  sendCodeAction()
    {
        $account = isset($_REQUEST['account']) ? $_REQUEST['account'] : 0;
        if(empty($account)){
            return $this->jsonError('账号不能为空');
        }

        //todo 此处需要添加更多的校验 防止空刷短信
        

        $verification_code = rand(100000,999999);
        $valid_id = SmsModel::sendValidSms($account,$verification_code);
        if($valid_id == false){
            return $this->jsonError('验证码发送失败');
        }
        return $this->jsonSuccess(['valid_id' => $valid_id]);
    }

    /**
     * 月度健步走数据
     */
    public function monthstepAction()
    {

        $user_id = isset($_REQUEST['user_id']) ? $_REQUEST['user_id'] : 0;
        if(empty($user_id)){
            $this->jsonError('账号不能为空');
        }
        $company_id = isset($_REQUEST['company_id']) ? $_REQUEST['company_id'] : 0;
        if(empty($company_id)){
            $this->jsonError('企业id不能为空');
        }

        $return_data = array('user_info'=>array(),'list'=>array(),'step_count'=>0,'km_count'=>0,'card_date_list'=>array(),'current_month'=>date('Y年m月'));
        $date_step_list = array();//以日期为key的数组
        $target_num = 6000;

        $start_day = date('Y-m-01');
        $end_day = date('Y-m-d', strtotime("$start_day +1 month -1 day"));

        $ret = DB::table('w_step_log')
               ->where(['user_id'=>$user_id])
               ->where('data_time','>=',strtotime($start_day))
               ->where('data_time','<=',strtotime($end_day))
               ->orderBy('data_time','asc')
               ->get();

        if(!empty($ret)) {
            foreach($ret as &$row){
                $row['day_name'] = date('d',$row['data_time']);
                $row['km']      = $row['real_step_num'] > 0  ? round($row['real_step_num']*0.7/1000,2) : 0;
                $row['calorie'] = $row['real_step_num'] > 0  ? intval($row['real_step_num']/22) : 0;
                $row['status'] = $row['real_step_num'] >= $target_num ? 1 : 2;
                $return_data['step_count'] += $row['real_step_num'];
                $temp_date = date('Y-m-d',$row['data_time']);
                //$return_data['card_date_list'][] = $temp_date;
                //$date_step_list[$temp_date] = $row;
            }
            $return_data['list'] = $ret;
        }
        $return_data['km_count'] = round($return_data['step_count']*0.7/1000,2);


        $company_user = DB::table('w_company_user')->where(['user_id'=>$user_id,'company_id' => $company_id])
                        ->orderBy('update_time','desc')->first();
        if(empty($company_user)){
            return $this->jsonError('数据错误！');
        }
        $user_info = DB::table('w_users')->where(['user_id'=>$user_id])->first();
        $return_data['user_info']['user_id']         = $company_user['user_id'];
        $return_data['user_info']['company_id']      = $company_user['company_id'];
        $return_data['user_info']['telphone']        = $company_user['telphone'];
        $return_data['user_info']['real_name']       = $company_user['real_name'];
        $return_data['user_info']['department_id']   = $company_user['department_id'];
        $return_data['user_info']['department_name'] = $company_user['department_name'];
        $return_data['user_info']['avatar']          = $user_info['avatar'];//用户头像

        $this->jsonSuccess($return_data);
    }

    //用户当前成绩
    public function rankingAction()
    {
        $user_id = isset($_REQUEST['user_id']) ? $_REQUEST['user_id'] : 0;
        if(empty($user_id)){
            $this->jsonError('账号不能为空');
        }
        $company_id = isset($_REQUEST['company_id']) ? $_REQUEST['company_id'] : 0;
        if(empty($company_id)){
            $this->jsonError('企业id不能为空');
        }
        $department_id = isset($_REQUEST['department_id']) ? intval($_REQUEST['department_id']) : 0;
        if(empty($department_id)){
            $this->jsonError('部门id不能为空');
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
        }


        $return_data = [
                        'personal' => ['step_count'=>0,'km_count'=>0,'ranking_num' => 0],
                        'department' =>['step_count'=>0,'average_step'=>0,'department_ranking_num' => 0] 
                       ];
        
        //个人成绩数据汇总
        if($activity_member_info){

            $all_ranking_list = DB::table('w_step_log')
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
                ->where(['w_company_step_activity_user.activity_id' => $activity_id,'w_company_user.company_id' => $company_id,'w_company_step_activity_user.status' => 1,'w_company_user.status' => 1])
                ->where('w_step_log.data_time','>=',$activity_start_time)
                ->where('w_step_log.data_time','<=',$activity_end_time)
                ->where('w_step_log.add_time','<=',$statistics_end_time)
                ->orderBy('step_num_all','desc')
                ->get();

        } else {
            $all_ranking_list = DB::table('w_step_log')  
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

        
        

        $i=1;
        foreach($all_ranking_list as $row) {
            if($row['user_id'] == $user_id){
                $return_data['personal']['step_count'] = $row['step_num_all'];
                $return_data['personal']['km_count'] = round($row['step_num_all']*0.7/1000);
                $return_data['personal']['ranking_num'] = $i;
            }
            $i++;
        }
        if($return_data['personal']['ranking_num'] == 0){
            $return_data['personal']['ranking_num'] == count($all_ranking_list)+1;
        }


        //整理部门人数汇总
        $department_member_ret = DB::table('w_company_user')
                                   ->select(
                                    DB::raw('count(user_id) as user_count'),
                                    'department_id',
                                    'department_name'
                                    )
                                   ->where(['company_id' => $company_id,'status'=>1])
                                   ->groupBy('department_id')
                                   ->get();
        $department_member_list = [];
        foreach($department_member_ret as $row){
            $department_member_list[$row['department_id']] = $row;
        }


        //部门成绩汇总
        if($activity_member_info){
            $department_ranking_list = DB::table('w_step_log')
                ->leftJoin('w_company_step_activity_user','w_step_log.user_id','=','w_company_step_activity_user.user_id')
                ->leftJoin('w_company_user','w_step_log.user_id','=','w_company_user.user_id')
                ->select(
                     DB::raw('SUM(w_step_log.step_num) AS step_num_all'),
                    'w_company_user.department_id',
                    'w_company_user.department_name'
                    )  
                ->groupBy('w_company_user.department_id')
                ->where(['w_company_step_activity_user.activity_id' => $activity_id,'w_company_user.company_id' => $company_id,'w_company_step_activity_user.status' => 1,'w_company_user.status' => 1])
                ->where('w_step_log.data_time','>=',$activity_start_time)
                ->where('w_step_log.data_time','<=',$activity_end_time)
                ->where('w_step_log.add_time','<=',$statistics_end_time)
                ->orderBy('step_num_all','desc')
                ->get();
        } else {
            $department_ranking_list = DB::table('w_step_log')  
                ->leftJoin('w_company_user','w_step_log.user_id','=','w_company_user.user_id')
                ->select(
                     DB::raw('SUM(w_step_log.step_num) AS step_num_all'),
                    'w_company_user.department_id',
                    'w_company_user.department_name'
                    )  
                ->groupBy('w_company_user.department_id')
                ->where(['w_company_user.company_id' => $company_id,'w_company_user.status' => 1])
                ->where('w_step_log.data_time','>=',$activity_start_time)
                ->where('w_step_log.data_time','<=',$activity_end_time)
                ->where('w_step_log.add_time','<=',$statistics_end_time)
                ->orderBy('step_num_all','desc')
                ->get();
        }
        
        //计算部门的平均值
        foreach($department_ranking_list as &$row) {
            $department_user_num = isset($department_member_list[$row['department_id']]) ? $department_member_list[$row['department_id']]['user_count'] : 1;
            $row['department_user_num'] = $department_user_num;
            $row['average_step'] = round($row['step_num_all']/$department_user_num);
        }

        //按照部门的平局值排序
        $sort = array_column($department_ranking_list, 'average_step');
        array_multisort($sort, SORT_DESC, $department_ranking_list);

        $i = 1;
        foreach($department_ranking_list as $rowNew) {
            if($rowNew['department_id'] == $department_id){
                $return_data['department']['department_ranking_num'] = $i;
                $return_data['department']['step_count'] = $rowNew['step_num_all'];
                $return_data['department']['average_step'] = $rowNew['average_step'];
                break;
            }
            $i++;
        }

        if($return_data['department']['department_ranking_num'] == 0) {
            $return_data['department']['department_ranking_num'] == count($department_ranking_list)+1;
        }      
        $this->jsonSuccess($return_data);
    }

    /**
     * 测试用户的企业
     */
    public function testAction()
    {
        $account = $_REQUEST['mobile'];
        if(empty($account)){
            $this->jsonError('手机号不能为空');
        }
         $userInfo = DB::table('w_users')->where(['telphone'=>$account])->first();
        if(empty($userInfo)){
            $this->jsonError('用户不存在');
        }

        $company_user = DB::table('w_company_user')->where(['user_id'=>$userInfo['user_id'],'status' => 1])
                        ->orderBy('update_time','desc')->first();
        $company_info = DB::table('w_company')->where(['company_id'=>$company_user['company_id']])->first();
        
        $return_data = [];
        $return_data['company_name'] = $company_info['company_name'];
        $return_data['mobile'] = $account;
        $return_data['department_name'] = $company_user['department_name'];

        $this->jsonSuccess($return_data);
    }

    public function sampleAction()
    {
        $return_data = [];
        $return_data['user_id']         = 2074;
        $return_data['company_id']      = 5;
        $return_data['telphone']        = '13910423567';
        $return_data['real_name']       = '关键';
        $return_data['department_id']   = 115;
        $return_data['department_name'] = '分行工会';
        return $this->jsonSuccess($return_data);
    }

}