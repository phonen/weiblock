<?php
return array(
	'LAYOUT_ON'=>true,//开启模版布局
	'LAYOUT_NAME'=>'layout',//布局文件名称
	'WxPayConf'=>array(
		'STATE' => 'MALL',
		'JS_API_CALL_URL' => WSTDomain().'/index.php/WeChat/Games/toPay',
		'SSLCERT_PATH' => './Apps/DouNiu/cert/apiclient_cert.pem',
		'SSLKEY_PATH' => './Apps/DouNiu/cert/apiclient_key.pem',  
		'SSLROOTCA_PATH' => '',
		'NOTIFY_URL' =>  WSTDomain().'/index.php/DouNiu/Games/notify', 
		'RETURN_URL' =>  WSTDomain().'/index.php/DouNiu/Orders/index',	
		'CURL_TIMEOUT' => 30
	),
	
	'WxPayConf2'=>array(  
		'STATE' => 'WXWSTMall',
		'JS_API_CALL_URL' => WSTDomain().'/index.php/DouNiu/Recharge/toPay',
		'SSLCERT_PATH' => '',
		'SSLKEY_PATH' => '',
		'SSLROOTCA_PATH' => '',
		'NOTIFY_URL' =>  WSTDomain().'/index.php/DouNiu/Recharge/notify',
		'RETURN_URL' =>  WSTDomain().'/index.php/DouNiu/Users/index',	 
		'CURL_TIMEOUT' => 30
	),
	 
	'WxPayConf3'=>array(  
		'STATE' => 'WXWSTMall',
		'JS_API_CALL_URL' => WSTDomain().'/index.php/DouNiu/Recharge/toPay',
		'SSLCERT_PATH' => './Apps/DouNiu/cert/apiclient_cert.pem',
		'SSLKEY_PATH' => './Apps/DouNiu/cert/apiclient_key.pem',  
		'SSLROOTCA_PATH' => '',
		'NOTIFY_URL' =>  WSTDomain().'/index.php/DouNiu/Recharge/notify',
		'RETURN_URL' =>  WSTDomain().'/index.php/DouNiu/Users/index',	 
		'CURL_TIMEOUT' => 30
	),
);