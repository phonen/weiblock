<?php
namespace DouNiu\Action;
/**
 * ============================================================================

 * ============================================================================
 * 分销用户控制器
 */
class DistributUsersAction extends BaseAction{ 
	/**
	 * 用户列表
	 */
	public function index(){
		$this->isLogin();
		$du = D('WeChat/DistributUsers');
		$this->assign('totalUser', $du->dataCount());
		$this->display("default/users/share/share_users");
	}
	/**
	 * 获取列表
	 */
	public function queryByList(){
		$this->isLogin();
		$du = D('WeChat/DistributUsers');
		$list = $du->queryByList();
		$this->ajaxReturn($list);
	}
}
