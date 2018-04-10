<?php
namespace DouNiu\Action;
/**
 * 分销分成记录控制器
 */
class DistributMoneysAction extends BaseAction{
	/**
	 * 分享页
	 */ 
	public function toShare(){
		$this->isLogin();
		$users = D('WeChat/Users');
		$info['user'] = $users->getUserInfo((int)session('WST_USER.userId'));
		$c = D('WeChat/CashConfigs');
		$dm = D('WeChat/DistributMoneys');
		$info['data'] = $dm->dataCount();
		$dm = D('WeChat/DistributUsers');
		$info['totalUser'] = $dm->dataCount();
		//分享信息
		$shareInfo= array(
			'title'=>$GLOBALS['CONFIG']['mallShareTitle'],
			'desc'=>$GLOBALS['CONFIG']['mallName'],
			'link'=>U('WeChat/Index/index',array('shareUserId'=>base64_encode((int)session('WST_USER.userId'))),true,true),
			'imgUrl'=>WSTDomain().'/'.$GLOBALS['CONFIG']['mallLogo']
		);
		$info['share'] = $shareInfo;
		$this->assign('info', $info);
		$this->display("default/users/share/share");
	}
	/**
	 * 佣金信息
	 */
	public function index(){
		$this->isLogin();
		$dm = D('WeChat/DistributMoneys');
		$this->assign('info', $dm->dataCount());
		$this->display("default/users/share/share_commission");
	}
	/**
	 * 获取列表
	 */
	public function queryByList(){
		$this->isLogin();
		$du = D('WeChat/DistributMoneys');
		$list = $du->queryByList();
		$this->ajaxReturn($list);
	}
}
