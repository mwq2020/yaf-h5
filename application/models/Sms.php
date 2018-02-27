<?php 
use Illuminate\Database\Eloquent\Model as Mymodel; 

class SmsModel extends Mymodel 
{ 
    protected $connection = 'shop';//当前数据库的链接名
    protected $table      = 'w_sms_log'; //表名
    protected $primaryKey = 'id';//主键id
    public    $timestamps = false;

    public static function sendSms($mobile,$content)
    {
        $ret = Core\Sms::send($mobile,$content);
        if($ret === false){
            return false;
        }
        
        if(!isset($ret['code']) || !in_array($ret['code'], ['01','03'])){
            return false;
        }

        if(isset($ret['message']['msgid'])){
            $sms_data = [];
            $sms_data['mobile'] = $mobile;
            $sms_data['content'] = $content;
            $sms_data['verification_code'] = '';
            $sms_data['type'] = 2;
            $sms_data['status'] = 1;
            $sms_data['fail_num'] = 0;
            $sms_data['is_validated'] = 0;
            $sms_data['transtract_id'] = $ret['message']['msgid'];
            $sms_data['add_time'] = time();
            $sms_data['update_time'] = time();
            $valid_id = DB::table('w_sms_log','shop')->insertGetId($sms_data);
        } else{
            foreach($ret['message'] as $message){
                $sms_data = [];
                $sms_data['mobile'] = $mobile;
                $sms_data['content'] = $content;
                $sms_data['verification_code'] = '';
                $sms_data['type'] = 2;
                $sms_data['status'] = 1;
                $sms_data['fail_num'] = 0;
                $sms_data['is_validated'] = 0;
                $sms_data['transtract_id'] = $message['msgid'];
                $sms_data['add_time'] = time();
                $sms_data['update_time'] = time();
                $valid_id = DB::table('w_sms_log','shop')->insertGetId($sms_data);
            }
        }

       return true; 
    }

    public static function sendValidSms($mobile,$verification_code)
    {
        $content = '验证码'.$verification_code.',10分钟内有效！';
        $ret = Core\Sms::send($mobile,$content);
        if($ret === false){
            return false;
        }
        
        if(!isset($ret['code']) || !in_array($ret['code'], ['01','03']) || empty($ret['message']['msgid'])){
            return false;
        }

        $sms_data = [];
        $sms_data['mobile'] = $mobile;
        $sms_data['content'] = $content;
        $sms_data['verification_code'] = $verification_code;
        $sms_data['type'] = 1;
        $sms_data['status'] = 1;
        $sms_data['fail_num'] = 0;
        $sms_data['is_validated'] = 0;
        $sms_data['transtract_id'] = $ret['message']['msgid'];
        $sms_data['add_time'] = time();
        $sms_data['update_time'] = time();
        $valid_id = DB::table('w_sms_log','shop')->insertGetId($sms_data);
        return $valid_id; 
    }

}

