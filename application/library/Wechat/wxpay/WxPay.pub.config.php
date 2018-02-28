<?php
/**
* 	配置账号信息
*/

class WxPayConf_pub
{
	//=======【基本信息设置】=====================================
	//微信公众号身份的唯一标识。审核通过后，在微信发送的邮件中查看
	const APPID = 'wxfd639922b98b60bb';
	//受理商ID，身份标识
	const MCHID = '1304644501';
	//商户支付密钥Key。审核通过后，在微信发送的邮件中查看
	const KEY = 'asd56dfh5df2sd5g6hu5j6f9yt8rtr74';
	//JSAPI接口中获取openid，审核后在公众平台开启开发模式后可查看
	const APPSECRET = '80d74df3b5345da97a5be75ef88f0538';
	
	//=======【证书路径设置】=====================================
	//证书路径,注意应该填写绝对路径
	const SSLCERT_PATH = '../cert/apiclient_cert.pem';
	const SSLKEY_PATH = '../cert/apiclient_key.pem';
//	const SSLCERT_PATH = '/www/bestdo/golf.club.bestdo.com/framework/plugins/cert/apiclient_cert.pem';
//	const SSLKEY_PATH = '/www/bestdo/golf.club.bestdo.com/framework/plugins/cert/apiclient_key.pem';

    //echo "<br>=====".SSLCERT_PATH.'====='.SSLKEY_PATH.'=====';
	
	//=======【异步通知url设置】===================================
	//异步通知url，商户根据实际开发过程设定
	const NOTIFY_URL = 'http://front.club.bestdo.com/payment/wxpay/wxcallback';

	//=======【curl超时设置】===================================
	//本例程通过curl使用HTTP POST方法，此处可修改其超时时间，默认为30秒
	const CURL_TIMEOUT = 30;
}
	
?>