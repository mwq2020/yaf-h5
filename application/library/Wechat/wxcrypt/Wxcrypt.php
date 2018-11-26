<?php
namespace Wechat\wxcrypt;
use \Log;

class Wxcrypt 
{

    /**
     * 解密微信运动数据.
     */
    public function decodeCryptData($appid,$sessionKey,$encryptedData,$iv)
    {
        $pc = new wxBizDataCrypt($appid, $sessionKey);
        $errCode = $pc->decryptData($encryptedData, $iv, $data);
        if ($errCode == 0) {
            return $data;
        } else {
            Log::info('用户上传步数解密失败:errCode='.$errCode."|".json_encode($_REQUEST));
            return false;
        }
    }

}
