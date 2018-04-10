<?php
namespace WeChat\Model;
/**
 * 分销用户服务类
 */
class DistributUsersModel extends BaseModel {
	/**
	 * 获取指定对象的资料
	 */
	public function getInfo($buyerId){
		$rs = $this->where("buyerId=".$buyerId)->field("userId,buyerId")->find();
		return $rs;
	}
	/**
	 * 统计数据
	 */
	public function dataCount(){
		$userId = (int)session('WST_USER.userId');
		$rs = $this->where('userId='.$userId)->field('buyerId')->select();
		return count($rs);
	}
	/**
	 * 用户列表
	 */
	public function queryByList(){
		$currPage = (int)I("currPage",0);
		$pageSize = (int)I('pageSize',10);
		$userId = (int)session('WST_USER.userId');
		$sql="SELECT du.buyerId,u.userName,u.loginName,u.userPhoto,u.createTime FROM __PREFIX__distribut_users du left join __PREFIX__users u on u.userId=du.buyerId
			  WHERE du.userId=".$userId." order by du.distributId desc";
		$rs = $this->pageQuery($sql,$currPage,$pageSize);
		return $rs;
	}
}