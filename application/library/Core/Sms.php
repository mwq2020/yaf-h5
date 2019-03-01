<?php
namespace Core;

class Sms
{

    /**
     * 发送短信
     * @param $mobile
     * @param $content
     * @param string $signature
     * @return mixed
     */
    public static function send($mobile,$content,$signature='【鑫福利】')
    {
        $content .= $signature;
        $config = \Yaf\Registry::get('config');
        $OperID = $config->sms->OperID;
        $OperPass = $config->sms->OperPass;
        $code = mb_detect_encoding($content,array('UTF-8','ASCII','GB2312','GBK','BIG5'));
        if(strtolower($code) != 'gbk') {
            $content = mb_convert_encoding($content,'gbk', $code);
        }
        $xml_data = "OperID={$OperID}&OperPass={$OperPass}&SendTime=&ValidTime=&AppendID=1234&DesMobile={$mobile}&Content={$content}&ContentType=8";
        //$url = "http://221.179.180.158:9007/QxtSms/QxtFirewall";
        $url = "http://qxtsms.guodulink.net:8000/QxtSms/QxtFirewall";
        //定义content-type为xml
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $xml_data);
        $response = curl_exec($ch);
        if (curl_errno($ch)) {
            \Log::info('error: 国都短信发送失败:'.curl_error($ch));
            return false;
        } else {
            \Log::info('success 国都短信发送成功:'.$response);
        } 
        curl_close($ch);
        return self::xmlToArray($response);
    }

    /**
     * 把xml转成数组
     * @param $xml
     * @return mixed
     */
    public static function xmlToArray($xml)
    {
        /*
        $xml = '<?xml version="1.0" encoding="gbk" ?><response><code>03</code><message><desmobile>18211072317</desmobile><msgid>f913edf0a3b07aa27000</msgid></message></response>';
        */
        libxml_disable_entity_loader(true);
        $values = json_decode(json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
        return $values;
    }

}