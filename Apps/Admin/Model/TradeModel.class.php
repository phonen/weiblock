<?php
namespace Admin\Model;
/**
 * 交易服务类
 */
class TradeModel extends BaseModel { 
	/**
	 * 资金流水
	 */
    public function queryByPage(){
		$key = WSTAddslashes(I('key'));
		$targetType = (int)I('targetType',-1);
		$moneyType = (int)I('moneyType',-1);
		$dataSrc = (int)I('dataSrc',-1);
		$startDate = I('startDate');
		$endDate = I('endDate');
		$sql = "select t.*,u.userName,u.loginName from __PREFIX__trade t left join __PREFIX__users u on t.user_id = u.userId where 1=1 ";
		
		if($moneyType!=-1)$sql.=" and l.moneyType=".$moneyType;

		if($key!='')$sql.=" and u.userId  = ".$key ;
		if($startDate!=''){
			$startDate = date('Y-m-d',strtotime($startDate))." 00:00:00";
			if($startDate!='')$sql.=" and l.createTime >='".$startDate."'";
		}
        if($endDate!=''){
			$endDate = date('Y-m-d',strtotime($endDate))." 23:59:59";
			if($endDate!='')$sql.=" and l.createTime <='".$endDate."'";
		}
		$sql.=" order by t.add_time desc";
		$page = $this->pageQuery($sql,(int)I('2'),20);  
		if(!empty($page['root'])){
			foreach ($page['root'] as $key =>$v){
				$page['root'][$key]['targetName'] = ($v['targetType']==1)?$v['shopName']:("【".$v['userName']."】");
			}
		}
		return $page;
	}
	
	/**
	 * 获取商家资金信息
	 */
	public function getShopMoneys(){
		$shopId = (int)session('WST_USER.shopId');
		$sql = "select shopMoney,lockMoney from __PREFIX__shops where shopId=".$shopId;
		$rs = $this->queryRow($sql);
		$rs['status'] = 1;
		return $rs;
	}
}