<?php
/** 
 *　　　　　　　　┏┓　　　┏┓+ + 
 *　　　　　　　┏┛┻━━━┛┻┓ + + 
 *　　　　　　　┃　　　　　　　┃ 　 
 *　　　　　　　┃　　　━　　　┃ ++ + + + 
 *　　　　　　 ████━████ ┃+ 
 *　　　　　　　┃　　　　　　　┃ + 
 *　　　　　　　┃　　　┻　　　┃ 
 *　　　　　　　┃　　　　　　　┃ + + 
 *　　　　　　　┗━┓　　　┏━┛ 
 *　　　　　　　　　┃　　　┃　　　　　　　　　　　 
 *　　　　　　　　　┃　　　┃ + + + + 
 *　　　　　　　　　┃　　　┃　　　　Code is far away from bug with the animal protecting　　　　　　　 
 *　　　　　　　　　┃　　　┃ + 　　　　神兽保佑,代码无bug　　 
 *　　　　　　　　　┃　　　┃ 
 *　　　　　　　　　┃　　　┃　　+　　　　　　　　　 
 *　　　　　　　　　┃　 　　┗━━━┓ + + 
 *　　　　　　　　　┃ 　　　　　　　┣┓ 
 *　　　　　　　　　┃ 　　　　　　　┏┛ 
 *　　　　　　　　　┗┓┓┏━┳┓┏┛ + + + + 
 *　　　　　　　　　　┃┫┫　┃┫┫ 
 *　　　　　　　　　　┗┻┛　┗┻┛+ + + + 
 */

use Yaf\Controller_Abstract;
use Monolog\Logger;
//use Illuminate\Database\Capsule\Manager as DB;

use Illuminate\Database\Capsule\Manager as Capsule;

class TestController extends Controller_Abstract 
{

    public function testAction()
    {
        \Yaf\Dispatcher::getInstance()->disableView(); 
        echo "this a test page";
        $session = \Yaf\Session::getInstance();
        //$session->start();
        echo "<hr>";
        echo $session->mwq;

        echo "<br>";
        echo getmypid();
        return false;

        //写日志模拟
        // $logger = new Logger('my_logger');
        // $logger->pushHandler(new StreamHandler('/tmp/yaf_test.log', Logger::DEBUG));
        // $firephp = new FirePHPHandler();
        // $logger->pushHandler($firephp);
        // $logger->info('monolog test log write success');
        // $logger->addWarning('Foo');
        // $logger->addError('Bar');
        
        //\Yaf\Dispatcher::getInstance()->disableView(); 

        //数据存取模拟
        // echo "<pre>";
        // $mod = new UserModel(); 
        // $data = $mod->find(2)->toArray(); 
        // print_r($data);


        // $list = $mod->all()->toArray();
        // print_r($list);
        // //exit;


        // $run_model = new WechatRunModel();
        // $list = $run_model->all()->toArray();
        // print_r($list);
        // exit;
    }

    public function sessionAction()
    {
        \Yaf\Dispatcher::getInstance()->disableView(); 

        //Yaf_Application,  Yaf_Loader,  Yaf_Dispatcher, Yaf_Registry, Yaf_Session 都是单例模式 
        //你可以通过它们的getInstance() 来获取它们的单例，也可以通过Yaf_dispatcher::getXXX方法来获取实例
        $session = \Yaf\Session::getInstance();
        //$session->start();
        $session->mwq = 'mwq test session';
        echo "session success";
        // \Yaf\Session::getInstance()->set('name', "alex")->set('sex',"男")；
        return false;
    }

    public function logAction()
    {
        Log::debug('debug------aaaaaaaaaa',['aaa'=>'ccccc','mwq'=>2020]);
        Log::info('info------aaaaaaaaaa');
        Log::notice('notice------aaaaaaaaaa');
        Log::warn('debug------aaaaaaaaaa');
        Log::err('err------aaaaaaaaaa');
        Log::crit('crit------aaaaaaaaaa');
        Log::alert('alert------aaaaaaaaaa');
        Log::emerg('emerg------aaaaaaaaaa');
        return false;
    }

    //获取一条记录
    public function rowAction()
    {


        echo "<pre>----";
        $user_info = DB::table('user','test')->where(['user_id'=>1])->get();
        print_r($user_info);

        $user_info = DB::table('user','test')->where(['user_id'=>1])->first();
        print_r($user_info);


        //


        return false;
    }

    //获取多条记录
    public function allAction()
    {
        echo "<pre>";
        $user_list = DB::table('users','test')->get();
        print_r($user_list);
        return false;
    }

    //插入操作
    public function insertAction()
    {
        echo "<pre>";
        // 插入记录 只获取结果（成功或者失败）
        //$flag = DB::connection('test')->insert('insert into w_users (user_name, mobile, password) values (?, ?, ?)', ['Laravel','18211072317','123456']);
        //echo "插入记录结果:";
        //print_r($flag);
        //echo "<br>";

        // 插入记录并获取记录id
        $id = DB::table('users','test')->insertGetId(['user_name' => 'mwqtest', 'mobile' => '18211072317', 'password' => '123456']);
        echo "插入记录结果id:".$id;
        echo "<br>";
        return false;
    }

    //更新操作
    public function updateAction()
    {
        $updateData = ['user_name' => 'mwq_'.rand(500,999)];
        $where = ['user_name' => 'mwq_518'];
        echo "<pre>";
        $flag = DB::table('users','test')->where($where)->update($updateData);
        echo "更新记录结果:".$flag;
        echo "<br>";
        print_r($updateData);
        print_r($where);
        return false;
    }

    //删除操作
    public function deleteAction()
    {
        $where = ['user_id' => '7'];
        echo "<pre>";
        $flag = DB::table('users','test')->where($where)->delete();
        echo "删除记录结果:".$flag;
        echo "<br>";
        print_r($where);
        return false;
    }

    //表的关联操作
    public function joinAction()
    {
        echo "<pre>";
        $run_list = DB::table('wechat_run_log','test')  
                ->leftJoin('users','wechat_run_log.user_id','=','users.user_id')  
                ->select(  
                    'wechat_run_log.user_id',  
                    'users.user_name',  
                    'users.mobile'
                    )  
                ->groupBy('wechat_run_log.user_id')
                ->whereIn('users.user_id', array(1,2,3))  
                //->where('orders.created_at', 'like', Input::get('year') . '-' . Input::get('month') . '-%')  
                ->get();
        print_r($run_list);
        return false;
    }

    //事务测试
    public function transctionAction()
    {
        echo "<pre>";
        echo "事务开始<br>";
        try {
            DB::connection('test')->beginTransaction();

            $updateData = ['user_name' => 'mwq_'.rand(500,999)];
            $where = ['user_id' => 1];
            $flag = DB::table('users','test')->where($where)->update($updateData);
            echo "数据更新结果：".$flag;
            echo "<br>";

            $id = DB::table('users','test')->insertGetId(['user_name' => 'mwqtest', 'mobile' => '18211072317', 'password' => '123456']);
            echo "数据插入结果：".$id;
            echo "<br>";

            throw new \Exception('test transction');
            
            DB::connection('test')->commit();
            echo "事务提交成功<br>";
        } catch (\Exception $e) {
            DB::connection('test')->rollBack();
            echo "事务回滚<br>";
        }
        echo "事务结束<br>";
        return false;

        //事务的闭包方式处理
        DB::connection('test')->transaction(function(){
                $article = DB::table('users','test')->insert([
                    'user_name' => 'test name',
                    'mobile' => 18211072316,
                ]);
                DB::table('users','test')->delete(6);//数据库中没有该表
            }
        );
    }

    public function sampleAction()
    {
        $users = DB::table('users')->count();
        $price = DB::table('orders')->max('price');
        $price = DB::table('orders')->min('price');
        $price = DB::table('orders')->avg('price');
        $total = DB::table('users')->sum('votes');

        $users = DB::table('users')
                     ->select(DB::raw('count(*) as user_count, status'))
                     ->where('status', '<>', 1)
                     ->groupBy('status')
                     ->get();

        $objects = DB::table('orders')  
                ->join('order_products','orders.id','=','order_products.order_id')  
                ->join('products','order_products.product_id','=','products.id')  
                ->leftJoin('categories','products.category_id','=','categories.id')  
                ->select(  
                    'orders.external_id',  
                    'orders.address_billing_name',  
                    'categories.customs_code',  
                    'orders.external_sale_date',  
                    'orders.invoice_external_id',  
                    'orders.payment_method',  
                    'orders.address_shipping_country',  
                    DB::raw('  
                        SUM(order_products.amount_base) AS amount_base,  
                        SUM(order_products.amount_tax) AS amount_tax,  
                        SUM(order_products.amount_total) AS amount_total  
                        ')  
                    )  
                ->groupBy('orders.external_id','categories.customs_code')  
                ->whereIn('orders.object_state_id', array(2,3))  
                ->where('orders.created_at', 'like', Input::get('year') . '-' . Input::get('month') . '-%')  
                ->get();  

        return false;
    }

}