<?php
 namespace Admin\Action;
/**
 * 交易控制器
 */
class TradeAction extends BaseAction{
	/**
	 * 获取流水记录
	 */
	public function index(){
		$this->isLogin();
		$page = D('Admin/Trade')->queryByPage();
		$pager = new \Think\Page($page['total'],$page['pageSize'],I());
		$page['pager'] = $pager->show();
		$this->assign('Page',$page);
		$this->assign('targetType',(int)I('targetType',-1));
		$this->assign('key',I('key'));
		$this->assign('moneyType',(int)I('moneyType',-1));
		$this->assign('dataSrc',(int)I('dataSrc',-1));
		$this->assign('startDate',I('startDate'));
		$this->assign('endDate',I('endDate'));
		$this->view->display('/moneys/list_trade');
	}
};
?>