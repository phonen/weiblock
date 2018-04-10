<?php
 return array(
		'URL_MODEL' => 0,
	    'URL_CASE_INSENSITIVE'  =>  true, 
	    'VAR_PAGE'=>'p',
	    'PAGE_SIZE'=>20,
		'DB_TYPE'=>'mysql',
	    'DB_HOST'=>'127.0.0.1',
	    'DB_NAME'=>'chiji', 
	    'DB_USER'=>'root',
	    'DB_PWD'=>'root',
	    'DB_PREFIX'=>'xz_',
	    'DEFAULT_C_LAYER' =>  'Action',
	    'DEFAULT_CITY' => '440100',
	    'DATA_CACHE_SUBDIR'=>true,
        'DATA_PATH_LEVEL'=>2, 
	    'SESSION_PREFIX' => 'XZMALL',
        'COOKIE_PREFIX'  => 'XZMALL',
		'LOAD_EXT_CONFIG' => 'xz_config',
		'DEFAULT_MODULE' => 'BaJie',
        'DEFAULT_CONTROLLER' => 'Users',
         
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
	);
?>