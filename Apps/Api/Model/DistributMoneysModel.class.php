<?php
namespace WeChat\Model;
/**
 * 分销服务类
 */
class DistributMoneysModel extends BaseModel {
	/**
	 * 统计数据
	 */
	public function dataCount($from){ 
		$userId = (int)session('WST_USER.userId');
		$promoterMoney = 0;
		if ($from>0){
			$condition = array();
			$condition['from']=$from;
			$condition['userId']=$userId;
			$urs = $this->where($condition)->field('distributMoney')->select();
			for ($i = 0; $i < count($urs); $i++) {
				$promoterMoney += $urs[$i]['distributMoney'];
			}
		}
		return $promoterMoney;
	}
	/**
	 * 佣金列表
	 */
	public function queryByList(){
		$currPage = (int)I("currPage",0);
		$pageSize = ( I('pageSize') ? (int)I('pageSize') : 10 );
		$userId = (int)session('WST_USER.userId');
		$sql="SELECT buyerId,moneyRemark,money,distributMoney,createTime FROM __PREFIX__distribut_moneys  WHERE userId=".$userId;
		if((int)I('type')==0){
			$sql.=" and userId=promoterId ";
		}else{
			$sql.=" and userId=buyerId ";
		}
		$sql.=" order by moneyId desc";
		$rs = $this->pageQuery($sql,$currPage,$pageSize);
		return $rs;
	}
	
	/**
	 * 添加分成记录
	 */
	public function addDistributMoneys($obj){
		$data = array();
		$dm = M('distribut_moneys');
		$data["shopId"] = $obj['shopId'];
		$data["orderId"] = $obj['orderId'];
		$data["userId"] = $obj['userId'];
		$data["promoterId"] = $obj["promoterId"];
		$data["buyerId"] = $obj["buyerId"];
		$data["moneyRemark"] = $obj["moneyRemark"];
		$data["distributType"] = $obj["distributType"];
		$data["dataId"] = $obj["dataId"];
		$data["money"] = $obj["money"];
		$data["distributMoney"] = $obj["distributMoney"];
		$data["createTime"] = date('Y-m-d H:i:s');
		$data["from"] = $obj['from'];
		$data["orderId"] = $obj['order_id'];
		$dm->add($data);
		$rd = array('status'=>1);
		return $rd;
	}
}