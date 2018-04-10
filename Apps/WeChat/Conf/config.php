<?php
return array(
	'LAYOUT_ON'=>true,//开启模版布局
	'LAYOUT_NAME'=>'layout',//布局文件名称
	'WxPayConf'=>array(
		'STATE' => 'MALL',
		'JS_API_CALL_URL' => WSTDomain().'/index.php/WeChat/Games/toPay',
		'SSLCERT_PATH' => './Apps/WeChat/cert/apiclient_cert.pem',
		'SSLKEY_PATH' => './Apps/WeChat/cert/apiclient_key.pem',  
		'SSLROOTCA_PATH' => '',
		'NOTIFY_URL' =>  WSTDomain().'/index.php/WeChat/Games/notify', 
		'RETURN_URL' =>  WSTDomain().'/index.php/WeChat/Orders/index',	
		'CURL_TIMEOUT' => 30
	),
	
	'WxPayConf2'=>array(  
		'STATE' => 'WXWSTMall',
		'JS_API_CALL_URL' => WSTDomain().'/index.php/WeChat/Recharge/toPay',
		'SSLCERT_PATH' => '',
		'SSLKEY_PATH' => '',
		'SSLROOTCA_PATH' => '',
		'NOTIFY_URL' =>  WSTDomain().'/index.php/WeChat/Recharge/notify',
		'RETURN_URL' =>  WSTDomain().'/index.php/WeChat/Users/index',	 
		'CURL_TIMEOUT' => 30
	),
	 
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