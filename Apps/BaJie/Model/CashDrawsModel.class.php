<?php
namespace BaJie\Model;
/**
 * 提现服务类
 */
class CashDrawsModel extends BaseModel {
	protected $tableName = 'cash_draws';
	/**
	 * 获取提现信息列表
	 */
    public function queryByList(){
    	$currPage = (int)I("currPage",0);
    	$pageSize = ( I('pageSize') ? (int)I('pageSize') : 10 );
    	$userId = (int)session('WST_USER.userId');
		$sql = "select cd.*,b.bankName from __PREFIX__cash_draws cd left join __PREFIX__banks b on cd.accTargetId=b.bankId
				where cd.targetType = 0 and cd.accType=3 and cd.targetId=".$userId." order by createTime desc";
		$rd = $this->pageQuery($sql,$currPage,$pageSize);
		return $rd;
	}	
     /**
	 * 新增用户记录
	 */   
	public function insertByUser(){
		$rd = array('status'=>-1);
		//获取提现账号信息
		$userId = (int)session('WST_USER.userId');   
	    $money = I('drawMoney');
		if($money<=0){
			$rd['msg'] = '提现金额必须大于0!';
			return $rd;
		}
		//判断提现金额是否允许
		$sql = "select userMoney from __PREFIX__users where userId=".$userId;
		$user = $this->queryRow($sql);
		if($user['userMoney']<$money){
			$rd['msg'] = '对不起，您的可提现金额不足!';
			return $rd;
		}
		if($GLOBALS['CONFIG']['cashStartMoney']>$money){
			$rd['msg'] = '对不起，最低提现金额'.$GLOBALS['CONFIG']['cashStartMoney'].'元';
			return $rd;
		}
		 
		/*if (ceil($money)!== $money){
		    $rd['msg'] = '请输入正整数!';
			return $rd;
		}*/;
		$this->targetType = 0;
		$this->targetId = $userId;
		$this->money = $money;
		$this->accType = $config['accType'];
		$this->accTargetId = $config['accTargetId'];
		$this->accNo = $config['accNo'];
		$this->accUser = $config['accUser'];
		$this->areaId2 = $config['areaId2'];
		$this->cashSatus = 0;
		$this->createTime = date('Y-m-d H:i:s');
		$this->cashConfigId = (int)I('configId');
		$id = $this->add();
		if(false !== $id){
			$data = array();
			$m = M('messages');
			$data["msgType"] = 0;
			$data["sendUserId"] = 0;
			$data["receiveUserId"] = $userId;
			$data["msgContent"] = "您申请提现 ¥".$money."已提交，预计明天24点前到帐！";
			$data["msgStatus"] = 0;
			$data["msgFlag"] = 1;
			$data["createTime"] = date('Y-m-d H:i:s');
			$m->add($data);
			$rd['status']= 1;
			$rd['msg'] = '操作成功';
			$sql="update __PREFIX__users set userMoney=userMoney-".$money.",lockMoney=lockMoney+".$money." where userId=".$userId;
			$this->execute($sql);
		}else{
			$rd['msg'] = $this->getError();
		}
		return $rd;
	}
}