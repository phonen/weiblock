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
	    $m = M('users');
		$rs = array('status'=>-1);
		$money = I('money');//投注的金额
		$number = I('number');//选中的号码
		$type = I('type');//类型
		$user = M('users')->where(array('userId'=>( int ) session ( 'WST_USER.userId' )))->find();
		//获取帐号状态
		$userId = (int)session('WST_USER.userId');
		$userStatus = $m->where('userId='.$userId)->getField('userStatus',1);
		if($userStatus == 0){
			$rs['msg']='账号异常！请联系客服！';
			return $rs;
		}

	    if ($GLOBALS['CONFIG']['is_open'] == 0){
		    $rs['msg']='牛牛埋雷9号开放,敬请期待';
		    return $rs;  
		}
		
		if ($money > ($user['userMoney']-$money*0.1)){
			$rs['msg']='下注金额大于可用资金,请充值~~';
		    return $rs;  
		}
		
		if($money > $GLOBALS['CONFIG']['max_money']){
			$rs['msg']='下注金额大于'.$GLOBALS['CONFIG']['max_money'];
		    return $rs;  
		}
		
		if($money < $GLOBALS['CONFIG']['mix_money']){
			$rs['msg']='下注金额小于'.$GLOBALS['CONFIG']['mix_money'];
		    return $rs;
		}
		
		//$userId = (int)session('WST_USER.userId');
		if ($type>0){
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