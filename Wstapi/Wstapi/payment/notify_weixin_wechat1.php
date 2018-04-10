<?php

// 绑定模块到当前入口文件
$_GET['m'] = 'WeChat';
$_GET['c'] = 'Recharge';
$_GET['a'] = 'notify';
define('APP_PATH','../../Apps/');

define('NOTIFY_PATH', '../../Apps/Notify/');//定义异步通知目录
   
require '../../ThinkPHP/ThinkPHP.php';

?>