<?php
namespace WeChat\Model;
/**
 * ============================================================================

 * ============================================================================
 * 会员充值类服务类
 */
class RechargeModel extends BaseModel {
	
	/**
	 * 生成充值订单
	 */
	public function create() {
		$data = array ();
		$data ["user_id"] = ( int ) session ( 'WST_USER.userId' ); //用户id
		$data ["recharge_sn"] = createRecharge (); //充值单号 
		$data ["recharge_money"] = I ('recharge_money'); //充值金额
		$data ["add_time"] = time (); //充值时间
		$rv = array ('status' => - 1 );
		$rs = M ( 'recharge' )->add ( $data );
		if (false !== $rs) {
		    $rv = array ('status' => 1, 'msg' => '充值订单生成成功,正在跳转到支付页面', 'url' => U ( 'WeChat/Recharge/topay', array ('recharge_id' => $rs ) ));
		} else {
			$rv = array ('status' => - 1, 'msg' => '充值订单生成失败,请重试');
		}
		return $rv;
	}
	 
   public function complete($check) {
        $rechargeM = M ( 'Recharge' );
		//修改本次的记录
		$data = array ();
		$data ['recharge_status'] = '1';                    //
		$data ['pay_time'] = time ();                       //支付时间
		$data ['receive_money'] = $check['recharge_money']; //到账金额  
		$data ['weipay_sn'] = $check['weipay_sn']; 
		$rechargeM->where ( array ('recharge_sn' => $check['recharge_sn'] ) )->save ( $data );
		
		
		$userInfo = M('Users')->where(array('userId'=>$check['user_id']))->field('redPacketMoney,distributMoney,userMoney')->find();

		//资金流水表
	   $lm = M('log_moneys');
	   $data = array();
	   $data["targetType"] = 0;
	   $data["targetId"] = $check['user_id'];
	   $data["moneyRemark"] = '通过微信支付成功充值:'.$check ["recharge_money"];
	   $data["dataSrc"] = 2;//充值订单
	   $data["dataId"] =  $check['recharge_id'];
	   $data["money"] = $check["recharge_money"];
	   $data["transactionId"] = $check['recharge_sn'];
	   $data["type"] = 0;
	   $data["moneyType"] = 1;
	   $data["createTime"] = time();
	   $data["userMoney"] = $userInfo['userMoney']+$check['recharge_money'];
	   $data["redPacketMoney"] = $userInfo['redPacketMoney'];
	   $data["distributMoney"] = $userInfo['distributMoney'];
	   $rd = $lm->add($data);
	   
	   M ( 'Users' )->where ( array('userId'=>$check['user_id']) )->setInc ( 'userMoney', $check ["recharge_money"]);
	} 
}