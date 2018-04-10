<?php
/**
 * ============================================================================
 * WSTMall开源商城
 * 官网地址:http://www.wstmall.net
 * 联系QQ:707563272
 * ============================================================================
 * 检测系统端口
 */
namespace Home\Behavior;

class CheckVisitBehavior {

    // 行为扩展的执行入口必须是run
    public function run(&$params){
    	session_start();
    	if($_REQUEST['WST_DIRECT']=='PC')session('WST_VIEW','PC');
		if(WSTIsMobile() && session('WST_VIEW')!='PC'){
			header("Location:".U('WebApp/Index/index'));
		}
    }
}
