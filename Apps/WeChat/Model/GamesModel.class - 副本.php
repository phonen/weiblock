<?php
namespace WeChat\Model;
/**
 * 游戏控制器
 */
class GamesModel extends BaseModel {
	
	/**
	 * 玩游戏
	 */
	public function play(){
		$rs = array('status'=>-1);
		$num = I('num');//投注数量
		$number = I('user_val');//选中的号码
		$type = I('jc_type');//类型
		$userId = (int)session('WST_USER.userId');
		$money = $num * 10;
		$user = M('users')->where(array('userId'=>$userId))->find();
		if($user['userStatus'] == 0){
			$rs['msg']='账号异常！请联系客服！';
			return $rs;
		}

	    if ($GLOBALS['CONFIG']['is_open'] == 0){
		    $rs['msg']='牛牛埋雷暂未开放,敬请期待';
		    return $rs;  
		}
		
		if (!in_array($type, array('1','2','3','4','5','6'))){
		    $rs['msg']='请不要作弊哈';
		    return $rs;  
		}
		
		if ($money > ($user['userMoney']-$money*0.1)){
			$rs['msgcode']='NOT_SUFFICIENT_FUNDS';
			$rs['msg']='可用资金不足,请先充值';
		    return $rs;  
		}
		
		if($num > 20){
			$rs['msg']='下注数量大于20手';
		    return $rs;  
		}
		
		if($num < 1){
			$rs['msg']='下注金额小于1手';
		    return $rs;
		}
		
		if ($type == 3){
			$number = str_split($number,1);
			$number = json_encode($number);
		}
		
		$data = array();
		$data['user_id'] = $userId;
		$data['trade_sn'] = createOrderSn();
		$data['trade_money'] = $money;
		$data['selectd_num'] = $number;
		$data['type'] = $type;
		$data['add_time'] = time();
		$data['game_type'] = '0';
		$insert_id = M('trade')->add($data);
		$true_trade = base64_encode($insert_id);//
		if ($insert_id>0){ 
		   $rs=array('status'=>'1','msg'=>'下单成功,正在跳转到支付页面','url'=>U('WeChat/Games/topay',array('trade_id'=>$true_trade))); 
		}else{
		   $rs=array('status'=>'-1','msg'=>'下单失败,请重试~~');
		}
		return $rs;
	} 
}