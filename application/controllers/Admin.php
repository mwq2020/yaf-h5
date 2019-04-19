<?php
/**
 *
 * ━━━━━━神兽出没━━━━━━
 * 　　　┏┓　　　┏┓
 * 　　┏┛┻━━━┛┻┓
 * 　　┃　　　　　　　┃
 * 　　┃　　　━　　　┃
 * 　　┃　┳┛　┗┳　┃
 * 　　┃　　　　　　　┃
 * 　　┃　　　┻　　　┃
 * 　　┃　　　　　　　┃
 * 　　┗━┓　　　┏━┛Code is far away from bug with the animal protecting
 * 　　　　┃　　　┃    神兽保佑,代码无bug
 * 　　　　┃　　　┃
 * 　　　　┃　　　┗━━━┓
 * 　　　　┃　　　　　　　┣┓
 * 　　　　┃　　　　　　　┏┛
 * 　　　　┗┓┓┏━┳┓┏┛
 * 　　　　　┃┫┫　┃┫┫
 * 　　　　　┗┻┛　┗┻┛
 *
 * ━━━━━━感觉萌萌哒━━━━━━
 */

use Yaf\Controller_Abstract;
use \Monolog\Logger;
use \Monolog\Handler\StreamHandler;
use \Monolog\Handler\FirePHPHandler;
use \Monolog\Formatter\LineFormatter;
class AdminController extends Controller_Abstract
{
    public function init(){
        \Yaf\Dispatcher::getInstance()->autoRender(false);
        //\Yaf\Dispatcher::getInstance()->disableView();
    }

    public function indexAction()
    {
        if(empty($_COOKIE['user_name']) || empty($_COOKIE['secret_code'])){
            $this->redirect("/admin/login");
        }

        if(empty($_POST)){
            return $this->getView()->display('admin/index.phtml');
        }

        $telphone = isset($_POST['telphone']) ? $_POST['telphone'] : '';
        $address_id = isset($_POST['address_id']) ? $_POST['address_id'] : 0;
        try {

            if(empty($telphone)){
                throw new \Exception('手机号不能为空');
            }

            if(empty($address_id)){
                throw new \Exception('打卡点不能为空');
            }

            $user_info = DB::table('w_company_user')->where(['company_id' => 31,'telphone' => $telphone])->first();
            if(empty($user_info)){
                throw new \Exception('用户手机不存在');
            }

            $card_info = DB::table('w_company_activity_hitcard_log')->where(['user_id' => $user_info['user_id'],'address_id'=>$address_id])->first();
            if(!empty($card_info)) {
                throw new \Exception('该点已经打过卡了',500);
            }

            $insert_data = [];
            $insert_data['user_id']     = $user_info['user_id'];
            $insert_data['activity_id'] = 12;
            $insert_data['address_id']  = $address_id;
            $insert_data['gps_info']    = json_encode(['latitude' => 0,'longitude' => 0]);
            $insert_data['ip']          = '172.0.0.2';
            $insert_data['add_time']    = time();
            $insert_data['update_time'] = time();
            $flag = DB::table('w_company_activity_hitcard_log')->insertGetId($insert_data);
            if(empty($flag)){
                throw new \Exception('数据入库失败');
            }

        } catch (\Exception $e){
            echo $e->getMessage();
            exit;
        }

        echo '打卡成功';exit;
        //$this->getView()->assign('user','lvtao'); //模板文件中直接用php语法输出

    }


    public function loginAction()
    {
        //\Yaf\Dispatcher::getInstance()->autoRender(false);
        if(empty($_POST)){
            $this->getView()->assign('user','lvtao'); //模板文件中直接用php语法输出
            return $this->getView()->display('admin/login.phtml');
        }

        try {
            if(empty($_POST['user_name'])){
                throw new \Exception('用户名字不能为空');
            }
            if(empty($_POST['password'])){
                throw new \Exception('密码不能为空');
            }

            $config = \Yaf\Registry::get('config');

            if($_POST['user_name'] != $config->admin->user_name){
                throw new \Exception('用户名字错误');
            }

            if($_POST['password'] != $config->admin->password){
                throw new \Exception('密码错误');
            }

            setcookie("user_name", $_POST['user_name'], time()+7*24*3600,'/');
            setcookie("secret_code", base64_encode($_POST['user_name'].$_POST['password'].rand(100,999)), time()+7*24*3600,'/');

        } catch (\Exception $e) {
            echo $e->getMessage();exit;

        }
        $this->redirect("/admin/index");
    }

}








