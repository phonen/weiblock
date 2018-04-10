<?php
header('Content-Type: text/html; charset=utf-8');
if('/'==DIRECTORY_SEPARATOR)
{
$server_ip=$_SERVER['SERVER_ADDR'];
}
else
{
$server_ip=@gethostbyname($_SERVER['SERVER_NAME']);
}
$sq = $server_ip;
if($sq == "127.0.0.1")
{
if(version_compare(PHP_VERSION,'5.3.0','<')) die('require PHP > 5.3.0 !');
define('APP_DEBUG',true);
define('APP_PATH','./Apps/');
define('NOTIFY_PATH', './Apps/Notify/');
define('CERT_PATH', './Apps/Common/cert/');
define('EXTEND_PATH', APP_PATH.'Library/');
require './ThinkPHP/ThinkPHP.php';
}
else
{
echo "资源e站（Zye.cc）友情提示：请在index.php填写您的服务器IP地址！本文件加密后可作授权使用！";
}
?>