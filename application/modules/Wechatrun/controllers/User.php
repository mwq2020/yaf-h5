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
        } elseif(time() - $smsInfo['add_time'] > 600){
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

        $return_data = array('user_info'=>array(),'list'=>array(),'count'=>0,'km_count'=>0,'card_date_list'=>array());
        $date_step_list = array();//以日期为key的数组
        $target_num = 10000;

        $ret = DB::table('w_step_log')->where(['user_id'=>$user_id])->get();

        //echo "<pre>";
        if(!empty($ret)) {
            foreach($ret as &$row){
                $row['day_name'] = date('d',$row['data_time']);
                $row['km']      = $row['step_num'] > 0  ? round($row['step_num']*0.7/1000,2) : 0;
                $row['calorie'] = $row['step_num'] > 0  ? intval($row['step_num']/22) : 0;
                $row['status'] = $row['step_num'] >= $target_num ? 1 : 2;
                $return_data['count'] += $row['step_num'];
                $temp_date = date('Y-m-d',$row['data_time']);
                $return_data['card_date_list'][] = $temp_date;
                $date_step_list[$temp_date] = $row;
            }
            $return_data['list'] = $ret;
        }

        //日历日期及打卡标记
        $start_timestamp = strtotime(date('Y-m-01',strtotime($month."-01")));
        $end_timestamp = strtotime("+1 month",$start_timestamp);
        $dateList = array();
        $week_num = 1;
        for($i= $start_timestamp;$i<$end_timestamp;$i+=24*3600){
            $day = date('Y-m-d',$i);
            $tmp_day = array('value'=>$day,'date'=>date('d',$i),'week'=>date('w',$i));
            $tmp_day['is_card']  = in_array($tmp_day['value'], $return_data['card_date_list']) ? 1 :0;
            if(isset($date_step_list[$day])){
                $tmp_day['status'] = $date_step_list[$day]['step_num'] >= $target_num ? 1 : 2;
            } else {
                $tmp_day['status'] = 0;
            }
            $dateList[$week_num][] = $tmp_day;
            if($tmp_day['week'] == 6){
                $week_num++;
            }
        }
        $return_data['dateList'] =  $dateList;
        $return_data['km_count'] = round($return_data['count']*0.7/1000,2);

        //print_r($ret);
        //print_r($return_data);
        //print_r($date_step_list);
        //exit;

        $this->jsonSuccess($return_data);
    }

}