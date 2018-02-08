<?php
/****************************/
/****** 健步走默认入口 ********/
/****************************/

use Yaf\Controller_Abstract;
//use Illuminate\Database\Capsule\Manager as DB;
class IndexController extends Controller_Abstract 
{

    public function indexAction() {
        echo '我是登录接口';
        return false;
    }

    public function testAction()
    {
        echo "<pre>";
        echo "this is wechatrun test page<br>";
        $run_model = new WechatRunModel();
        $list = $run_model->all()->toArray();
        print_r($list);



        //$users = DB::connection('test');
        $user_info = DB::table('users','test')->get();
        //print_r($user_info);

    
        //$flag = DB::connection('test')->insert('insert into w_users (user_name, mobile, password) values (?, ?, ?)', ['Laravel','18211072317','123456']);
        //var_dump($flag);
        $id = DB::table('users','test')->insertGetId(['user_name' => 'mwqtest', 'mobile' => '18211072317', 'password' => '123456']);
        print_r($id);

        //$id = DB::insertGetId();
        

        //table('users')->get()->toArray();
        // $prefix = DB::getFacadeAccessor();
        // $prefix = Query::getQueryLog();
        //DB::raw('integral * (cart.goods_number)');
        

        exit;
    }

    public function weRunDataAction()
    {
        $return_data = array('step_num'=>0);

        $appid = WELFARE_WECHAT_APPID;
        $sessionKey =  isset($_REQUEST['sessionKey']) ? $_REQUEST['sessionKey'] : '';
        $encryptedData = isset($_REQUEST['encryptedData']) ?  $_REQUEST['encryptedData'] : '';
        $iv = isset($_REQUEST['iv']) ?  $_REQUEST['iv'] : '';
        $uid = isset($_REQUEST['uid']) ?  $_REQUEST['uid'] : '';

        if(empty($sessionKey) || empty($encryptedData) || empty($iv)){
            $return_data['km_txt']      = 0;
            $return_data['joule_txt']   = 0;
            $return_data['food_txt']    = 0;
            $this->ajaxSuccess($return_data);
        }

        //由于某些原因 引入微信官方的类库后输出了部分不明字符 导致返回结果的json格式错误，此处引用缓冲区 屏蔽到多余的输出
        ob_start();
        $obj = new Wxcrypt();
        $res = $obj->decodeCryptData($appid,$sessionKey,$encryptedData,$iv);
        ob_clean();

        //获取微信当日步数
        $res = json_decode($res,true);
        if(isset($res['stepInfoList'])){
            //更新微信运动日志表
            $this->updateStepLog($uid,$res['stepInfoList']);
            $return_data['werun_data'] = $res; // todo 上线隐藏即可

            $today_info = array_pop($res['stepInfoList']);
            $return_data['step_num'] = $today_info['step'];
        }
        $return_data['km_txt'] = WebApi_Team_Card::instance()->getKmByStepnum($return_data['step_num']);
        $return_data['joule_txt'] = WebApi_Team_Card::instance()->getJouleByStepnum($return_data['step_num']);
        $return_data['food_txt'] = WebApi_Team_Card::instance()->getFoodByStepnum($return_data['step_num']);
        $this->ajaxSuccess($return_data);
    }



}