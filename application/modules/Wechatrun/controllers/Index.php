<?php
/****************************/
/****** 健步走默认入口 ********/
/****************************/

// use Yaf\Controller_Abstract;
//use Illuminate\Database\Capsule\Manager as DB;
class IndexController extends Core\Base 
{

    public function indexAction() 
    {
        echo 'hello world!';
        return false;
    }

    /**
     * 测试接口
     */
    public function testAction()
    {
        $today_info = ['mwq' => 20000000];
        $user_id = 323333;
        Log::info('用户上传步数user_id:'.$user_id."|".json_encode($today_info));
        

        echo "<pre>";
        //echo "this is wechatrun test page<br>";

        $ranking_list = ['list'=>[]];
        for($i=1;$i<=250;$i++){
            $ranking_list['list'][$i] = $i."--aa";
        }
        
        $current_user_rank_num = 190;

        $min_display_num = 100;
        $rank_list_new = [];
        if(count($ranking_list['list']) >= $min_display_num){
            if($current_user_rank_num == 0){
                $rank_list_new = array_slice($ranking_list['list'], 0,$min_display_num,true);
            } else {
                $rank_list_new = array_slice($ranking_list['list'], 0,$min_display_num,true);
                $min_ranking_num = $current_user_rank_num-5;
                $max_ranking_num = $current_user_rank_num+5;

                // echo "<pre>";
                // print_r($rank_list_new);
                // exit;

                echo "<br><br>{$min_ranking_num}<br><br>{$max_ranking_num}<br>";
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

        print_r($ranking_list['list']);
        exit;

        

        // $current_ranking_num = 107;
        // $last_num = 100;
        // $list_new = [];
        // if(count($list) >= 100){
        //     $list_new = array_slice($list, 0,$last_num);
        //     $min_ranking_num = $current_ranking_num-5;
        //     $max_ranking_num = $current_ranking_num+5;

        //     if($max_ranking_num >  $last_num){
        //         $min_ranking_num = $min_ranking_num > $last_num ? $min_ranking_num : $last_num;
        //         echo "<br>{$min_ranking_num}<br>{$max_ranking_num}<br>";
        //         //print_r($list);
        //         echo "<hr>";
        //         $temp = array_slice($list, $min_ranking_num,$max_ranking_num-$min_ranking_num);
        //         print_r($temp);
        //         $list_new = array_merge($list_new,$temp);
        //     }
        // }

        // echo "<hr>";
        // print_r($list_new);
        // echo "<hr>";
        // print_r($list);
        exit;




        //$run_model = new WechatRunModel();
        //$list = $run_model->all()->toArray();
        //print_r($list);

        //$users = DB::connection('test');
        //$user_info = DB::table('users','test')->get();
        //print_r($user_info);

    
        //$flag = DB::connection('test')->insert('insert into w_users (user_name, mobile, password) values (?, ?, ?)', ['Laravel','18211072317','123456']);
        //var_dump($flag);
        //$id = DB::table('users','test')->insertGetId(['user_name' => 'mwqtest', 'mobile' => '18211072317', 'password' => '123456']);
        //print_r($id);

        //$id = DB::insertGetId();
        
        //table('users')->get()->toArray();
        // $prefix = DB::getFacadeAccessor();
        // $prefix = Query::getQueryLog();
        //DB::raw('integral * (cart.goods_number)');
        exit;
    }


    /**
     * 小程序的微信登录
     */
    public function loginAction()
    {
        $returnData = array();
        try {
            if(empty($_REQUEST['code'])){
                throw new exception('参数错误');
            }

            //$appid  = WELFARE_WECHAT_APPID;
            //$secret = WELFARE_WECHAT_APPSECRET;

            $config = \Yaf\Registry::get('config');
            $appid = $config->wechat->APPID;
            $appsecret = $config->wechat->APPSECRET;

            $code   = $_REQUEST['code'];
            $auth_url = "https://api.weixin.qq.com/sns/jscode2session?appid={$appid}&secret={$appsecret}&js_code={$code}&grant_type=authorization_code";
            $authInfoJson = file_get_contents($auth_url);
            //Logs::debug('wechat auth data ',$authInfoJson);
            if(empty($authInfoJson)){
                throw new exception('微信授权失败');
            }

            $authInfo = json_decode($authInfoJson,true);
            //Logs::debug('return data',$returnData);
            if(isset($authInfo['errcode']) || empty($authInfo['openid'])) {
                throw new exception('微信登录失败');
            }

            $returnData['openid']       = $authInfo['openid'];
            $returnData['unionid']      = isset($authInfo['unionid']) ? $authInfo['unionid'] : '';
            $returnData['session_key']  = $authInfo['session_key'];
        } catch (exception $e){
            return $this->jsonError($e->getMessage());
        }
        return  $this->jsonSuccess($returnData);
    }


    public function weRunDataAction()
    {
        $return_data = array('step_num'=>0);

        $config = \Yaf\Registry::get('config');
        $appid = $config->wechat->APPID;
        $appsecret = $config->wechat->APPSECRET;

        $sessionKey =  isset($_REQUEST['sessionKey']) ? $_REQUEST['sessionKey'] : '';
        $encryptedData = isset($_REQUEST['encryptedData']) ?  $_REQUEST['encryptedData'] : '';
        $iv = isset($_REQUEST['iv']) ?  $_REQUEST['iv'] : '';
        $user_id = isset($_REQUEST['user_id']) ?  $_REQUEST['user_id'] : '';
        $is_encode = isset($_REQUEST['is_encode']) ?  $_REQUEST['is_encode'] : '';
        if(!empty($is_encode)) {
            $sessionKey = urldecode($sessionKey);
            $encryptedData = urldecode($encryptedData);
            $iv = urldecode($iv); 
        }

        if(empty($sessionKey) || empty($encryptedData) || empty($iv)){
            $return_data['km_txt']      = 0;
            $return_data['joule_txt']   = 0;
            $return_data['food_txt']    = 0;
            $this->jsonSuccess($return_data);
        }

        //由于某些原因 引入微信官方的类库后输出了部分不明字符 导致返回结果的json格式错误，此处引用缓冲区 屏蔽到多余的输出
        ob_start();
        $obj = new Wechat\wxcrypt\Wxcrypt();
        $res = $obj->decodeCryptData($appid,$sessionKey,$encryptedData,$iv);
        ob_clean();

        if($res === false || empty($res)) {
            $return_data['step_num']    = 0;
            $return_data['km_txt']      = 0;
            $return_data['joule_txt']   = 0;
            $return_data['food_txt']    = 0;
            // $this->jsonSuccess($return_data);
            $this->jsonSuccess($return_data,301,'授权失败或session_key失效');
        }


        //获取微信当日步数
        $res = json_decode($res,true);
        if(isset($res['stepInfoList'])){
            //更新微信运动日志表
            $this->updateStepLog($user_id,$res['stepInfoList']);
            $return_data['werun_data'] = $res; // todo 上线隐藏即可

            $today_info = array_pop($res['stepInfoList']);
            $return_data['step_num'] = $today_info['step'];
        }

        Log::info('用户上传步数user_id:'.$user_id."|".json_encode($today_info));

        $return_data['km_txt'] = SteplogModel::getKmByStepnum($return_data['step_num']);
        $return_data['joule_txt'] = SteplogModel::getJouleByStepnum($return_data['step_num']);
        $return_data['food_txt'] = SteplogModel::getFoodByStepnum($return_data['step_num']);
        $this->jsonSuccess($return_data);
    }

    /** 
     * 更新微信运动返回的当月数据到数据库
     */
    private function updateStepLog($user_id,$stepInfoList)
    {
        if(empty($user_id) || empty($stepInfoList)){
            return false;
        }

        foreach ($stepInfoList as $row) {
            if($row['step'] <= 0){
                continue;
            }
            $info = DB::table('w_step_log')->where(['user_id'=>$user_id,'data_time' => $row['timestamp']])->first();
            if (empty($info)) {
                $insertData = array();
                $insertData['user_id']      = $user_id;
                $insertData['step_num']     = $row['step'];
                $insertData['data_time']    = $row['timestamp'];
                $insertData['add_time']     = time();
                $insertData['update_time']  = time();
                $flag = DB::table('w_step_log')->insertGetId($insertData);
            } else {
                if($info['step_num'] < $row['step']){
                    $updateData = array();
                    $updateData['step_num']     = $row['step'];
                    $updateData['update_time']  = time();
                    $flag = DB::table('w_step_log')->where(['id'=>$info['id']])->update($updateData);
                }
            }
        }
    }


    /**
     * 微信小程序推送的服务器检查校验方法。
     */
    public function checkSignatureAction()
    {
        $signature = $_GET["signature"];
        $timestamp = $_GET["timestamp"];
        $nonce = $_GET["nonce"];

        $token = 'xindong';
        $tmpArr = array($token, $timestamp, $nonce);
        sort($tmpArr, SORT_STRING);
        $tmpStr = implode( $tmpArr );
        $tmpStr = sha1( $tmpStr );

        if( $tmpStr == $signature ){
            echo $_REQUEST['echostr'];
            return true;
        }else{
            // echo "faile";
            //echo $_REQUEST['echostr'];
            return false;
        }

    }


}