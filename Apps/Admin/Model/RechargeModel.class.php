<?php
namespace Admin\Model;
/**
 * 会员充值类服务类
 */
class RechargeModel extends BaseModel {
	/**
	 * 查询一条充值
	 */
	public function fetchRow($condition = array()) {
		$rs = $this->where ( $condition )->find ();
		return $rs;
	}

	/**
	 * 分页列表
	 */
	public function queryByPage() {
		$map = array ();
		$userId = (int)I('userId');
		$userName = WSTAddslashes(I('userName'));
		$time_type = ( int ) I ( 'time_type', -1 );//时间类型
		
		$startDate = strtotime(I ( 'startDate' ));//开始时间
		$endDate = strtotime(I ( 'endDate' ))+60*60*24;//结束时间

		$sql = "select r.*,u.loginName, u.userName,u.userId from __PREFIX__recharge r left join __PREFIX__users u on r.user_id = u.userId where 1 ";
		if(!empty($userId))
		{
			
			$sql.="and r.user_id=".$userId;
		}
		if ($time_type == 1) {
			if ($startDate != '') {
				$sql .= " and r.add_time >='" . $startDate . "'";
			}
			if ($endDate != '') {
				$sql .= " and r.add_time <='" . $endDate . "'";
			}
		} elseif ($time_type == 2) {
			if ($startDate != '') {
				$sql .= " and r.pay_time >='" . $startDate . "'";
			}
			if ($endDate != '') {
				$sql .= " and r.pay_time <='" . $endDate . "'";
			}
		}
		
		if($userName!=''){$sql.=" and  u.userName like '%".$userName."%' or u.loginName like '%".$userName."%'";}
		//$sql .= " and r.recharge_status != '0'";
		$sql .= "  order by r.recharge_id desc"; 
		$rs = $this->pageQuery ( $sql );
		
		return $rs;
	}
}