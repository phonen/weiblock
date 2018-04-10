<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2014 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: Zye.cc
// +----------------------------------------------------------------------
// 检测PHP环境
header('Content-Type: text/html; charset=utf-8');
//exit('系统维护中,敬请谅解...'); 
if(version_compare(PHP_VERSION,'5.3.0','<'))  die('require PHP > 5.3.0 !');
// 开启调试模式 建议开发阶段开启 部署阶段注释或者设为false
define('APP_DEBUG',true);
// 定义应用目录
define('APP_PATH','../Apps/');

define('NOTIFY_PATH', '../Apps/Notify/');

define('CERT_PATH', '../Apps/Common/cert/');

/* 扩展目录*/
define('EXTEND_PATH', APP_PATH.'Library/');

// 引入ThinkPHP入口文件
require '../ThinkPHP/ThinkPHP.php';
