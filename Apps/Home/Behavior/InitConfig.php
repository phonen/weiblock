<?php
namespace Home\Behavior;
/**
 * ============================================================================
 * WSTMall开源商城
 * 官网地址:http://www.wstmall.net
 * 联系QQ:707563272
 * ============================================================================
 * 初始化基础数据
 */
class InitConfig 
{
    public function run(&$params){
        WSTConf('CONF',WSTConfig());
    }
}