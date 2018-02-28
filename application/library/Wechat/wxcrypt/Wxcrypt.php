<?php
namespace Wechat\wxcrypt;

class Wxcrypt 
{

    /**
     * 解密微信运动数据.
     */
    public function decodeCryptData($appid,$sessionKey,$encryptedData,$iv)
    {
        include_once __DIR__.'/wxcrypt/wxBizDataCrypt.php';
        $pc = new WXBizDataCrypt($appid, $sessionKey);
        $errCode = $pc->decryptData($encryptedData, $iv, $data);
        if ($errCode == 0) {
            return $data;
        } else {
            return false;
        }
    }

}
