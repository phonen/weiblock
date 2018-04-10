<?php
return array(
	//'配置项'=>'配置值'
	'WxPayConf3'=>array(  
		'STATE' => 'WXWSTMall',
		'JS_API_CALL_URL' => WSTDomain().'/index.php/WeChat/Recharge/toPay',
		'SSLCERT_PATH' => './Apps/WeChat/cert/apiclient_cert.pem',
		'SSLKEY_PATH' => './Apps/WeChat/cert/apiclient_key.pem',  
		'SSLROOTCA_PATH' => '',
		'NOTIFY_URL' =>  WSTDomain().'/index.php/WeChat/Recharge/notify',
		'RETURN_URL' =>  WSTDomain().'/index.php/WeChat/Users/index',	 
		'CURL_TIMEOUT' => 30
	),
);