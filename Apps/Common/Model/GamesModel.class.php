<?php
namespace Common\Model;
class GamesModel extends BaseModel 
{
	private $wxpayConfig;
	private $wxpay;
	public function _initialize() 
	{
		header ( "Content-type: text/html; charset=utf-8" );
		vendor ( 'WxPayHelper.WxPayConfig' );
		vendor ( 'WxPayHelper.WxPayHelper' );
		vendor ( 'WxPayHelper.SDKRuntimeException' );
		$m = D ( 'WeChat/Payments' );
		$this->wxpay = $m->getPayment ( "weixin" );
		$this->wxpayConfig = C ( 'WxPayConf' );
		$this->wxpayConfig['appid'] = $this->wxpay['appId'];
		$this->wxpayConfig['appsecret'] = $this->wxpay['appsecret'];
		$this->wxpayConfig['mchid'] = $this->wxpay['mchId'];
		$this->wxpayConfig['key'] = $this->wxpay['apiKey'];
		$this->wxpayConfig['sslcert_path'] = $this->wxpayConfig['SSLCERT_PATH'];
		$this->wxpayConfig['sslkey_path'] = $this->wxpayConfig['SSLKEY_PATH'];
		$this->wxpayConfig['curl_timeout'] = $this->wxpayConfig['CURL_TIMEOUT'];
		$this->wxpayConfig['notifyurl'] = $this->wxpayConfig['NOTIFY_URL'];
		$this->wxpayConfig['returnurl'] = $this->wxpayConfig['RETURN_URL'];
		new \WxPayConf ( $this->wxpayConfig );
	}
	public function Play()
	{
		$m = M('users');
		$rs = array('errcode'=>-1);
		$money = I('lot')*10;
		$type = I('guess_last_digit');
		$user = M('users')->where(array('userId'=>( int ) session ( 'WST_USER.userId' )))->find();
		$userId = (int)session('WST_USER.userId');
		$userStatus = $m->where('userId='.$userId)->getField('userStatus',1);
		if($userStatus == 0)
		{
			$rs['msg']='账号异常！请联系客服！';
			return $rs;
		}
		if (empty($type))
		{
			$rs['msg']='请选择牛';
			return $rs;
		}
		if (!in_array($type, array('1','2','3','4','5','6')))
		{
			$rs['msg']='请不要作弊哈';
			return $rs;
		}
		if ($GLOBALS['CONFIG']['is_open'] == 0)
		{
			$rs['msg']='感谢您的关注,游戏内测中~~~';
			return $rs;
		}
		if ($money > ($user['userMoney']))
		{
			$rs['msg']='下注金额大于可用资金,请充值~~';
			return $rs;
		}
		if($money > $GLOBALS['CONFIG']['max_money'])
		{
			$rs['msg']='下注金额大于'.$GLOBALS['CONFIG']['max_money'];
			return $rs;
		}
		if($money < $GLOBALS['CONFIG']['mix_money'])
		{
			$rs['msg']='下注金额小于'.$GLOBALS['CONFIG']['mix_money'];
			return $rs;
		}
		$data = array();
		$data['user_id'] = $userId;
		$data['trade_sn'] = createOrderSn();
		$data['trade_money'] = $money;
		$data['type'] = $type;
		$data['add_time'] = time();
		$insert_id = M('trade')->add($data);
		if ($insert_id>0)
		{
			$rs=array('errcode'=>'0','msg'=>'生成订单成功','trade_id'=>$insert_id,'url'=>U('WeChat/Games/topay',array('trade_id'=>$true_trade)));
		}
		else
		{
			$rs=array('status'=>'-1','msg'=>'下单失败,请重试~~');
		}
		return $rs;
	}
	function buyNumber()
	{
		$rs = array('status'=>'-1');
		$total_hand = I('total_hand');
		$money = $total_hand * 10;
		$type = I('number');
		$user = M('users')->where(array('userId'=>( int ) session ( 'WST_USER.userId' )))->find();
		$userId = (int)session('WST_USER.userId');
		$userStatus = M('users')->where('userId='.$userId)->getField('userStatus',1);
		if($userStatus == 0)
		{
			$rs['msg']='账号异常！请联系客服！';
			return $rs;
		}
		if (!in_array($type, array('0','1','2','3')))
		{
			$rs['msg'] = '请选择猜的类型';
			return $rs;
		}
		if ($GLOBALS['CONFIG']['is_close_duobao'] == 1) 
		{
			$rs['msg']='八戒夺宝游戏维护中~~~';
			return $rs;
		}
		$bottom_pour = $money*1.1+1;
		if ($bottom_pour > ($user['userMoney'])) 
		{
			$rs['msg']='下注金额大于可用资金,请充值~~';
			return $rs;
		}
		if( $user['play_count'] <= 0 ) 
		{
			$rs['msg']='今日已完100次,请明天再来';
			return $rs;
		}
		if($money > $GLOBALS['CONFIG']['max_money']) 
		{
			$rs['msg']='下注金额大于'.$GLOBALS['CONFIG']['max_money'];
			return $rs;
		}
		if($money < $GLOBALS['CONFIG']['mix_money']) 
		{
			$rs['msg']='下注金额小于'.$GLOBALS['CONFIG']['mix_money'];
			return $rs;
		}
		$rand_num = randomFloat(1,1.5);
		$wxopenid = session ( 'WST_USER.wxOpenId' );
		$commonutil = new \CommonUtil();
		$post_url = "https://api.mch.weixin.qq.com/mmpaymkttransfers/promotion/transfers";
		$post_data = array();
		$post_data['mch_appid'] = $this->wxpayConfig ['appid'];
		$post_data['mchid'] = $this->wxpayConfig ['mchid'];
		$post_data['nonce_str'] = $commonutil->createNoncestr(32);
		;
		$post_data['partner_trade_no'] = createOrderSn();
		$post_data['check_name'] = 'NO_CHECK';
		$post_data['openid'] = $wxopenid;
		$post_data['amount'] = $rand_num*100;
		;
		$post_data['desc'] = '获取单号';
		$post_data['spbill_create_ip'] = get_client_ip();
		$sign = $commonutil->getSign($post_data);
		$post_data['sign'] = $sign;
		$post_xml = $commonutil->arrayToXml($post_data);
		$result = $commonutil->postXmlSSLCurl($post_xml,$post_url);
		$data = $commonutil->xmlToArray($result);
		if (empty($data) || !is_array($data)) 
		{
			$rs['msg']='企业接口有误,请稍候再试';
			return $rs;
		}
		if (isset($data['return_code']) && $data['return_code'] == 'SUCCESS' && isset($data['result_code']) && $data['result_code'] == 'SUCCESS') 
		{
			$addData = array();
			$addData['targetId'] = $userId;
			$addData['handleTime'] = date('Y-m-d H:i:s');
			$addData['cashSatus'] = '1';
			$addData['weipay_sn'] = $data['payment_no'];
			$addData['targetType']='3';
			$addData['money']=$rand_num;
			$addData['change_money']='0';
			$addData['createTime']=date('Y-m-d H:i:s');
			$addData['cashRemarks']='获取单号';
			$addData['draw_sn']=createTxSn();
			M('cash_draws')->add($addData);
			return $data = $this->complate($data['payment_no'],$money,$type,$total_hand);
		}
		elseif(isset($data['return_code']) && $data['return_code'] == 'FAIL' && isset($data['result_code']) && $data['result_code'] == 'FAIL') 
		{
			$msg = isset($data['err_code_des']) ? $data['err_code_des'] : '企业付款失败，请检查接口服务！';
			return array('status'=>'-1','msg'=>$msg);
		}
		else
		{
			return array('status'=>'-1','msg'=>'下单失败，接口:'.$data['err_code_des']);
		}
		$rs=array('success'=>'1','msg'=>'生成订单成功');
		return $rs;
	}
	function complate($weipay_sn='',$trade_money,$cai_type,$total_hand)
	{
		$rate_caida = $GLOBALS['CONFIG']['rate_caida'];
		$rate_caixiao = $GLOBALS['CONFIG']['rate_caixiao'];
		$rate_caidan = $GLOBALS['CONFIG']['rate_caidan'];
		$rate_caishuang = $GLOBALS['CONFIG']['rate_caishuang'];
		$userId = (int)session('WST_USER.userId');
		$trade_sn = createOrderSn();
		$sx_money = $trade_money*0.1;
		$transaction_id = $weipay_sn;
		$weipay_arr = array();
		$weipay_arr[] = $first_num = substr($weipay_sn, 23,1);
		$weipay_arr[] = $second_num = substr($weipay_sn, 24,1);
		$weipay_arr[] = $three_num = substr($weipay_sn, 25,1);
		$weipay_arr[] = $four_num = substr($weipay_sn, 26,1);
		$weipay_arr[] = $five_num = substr($weipay_sn, 27,1);
		$weishu_str = substr($weipay_sn, -5,5);
		$shoushu_str = substr($weipay_sn, 0,23);
		;
		$sum = $first_num + $second_num + $three_num + $four_num + $five_num;
		$userinfo = M('users')->where(array('userId'=>$userId))->field('userMoney,redPacketMoney,distributMoney,recommend_user_id')->find();
		$draw_money = '0';
		if ($cai_type == '0')
		{
			$little = array('0','1','2','3','4');
			if (in_array($five_num, $little))
			{
				$draw_money = $trade_money * $rate_caida;
			}
		}
		elseif ($cai_type == '1')
		{
			$big = array('5','6','7','8','9');
			if (in_array($five_num, $big))
			{
				$draw_money = $trade_money * $rate_caixiao;
			}
		}
		elseif ($cai_type == '2')
		{
			$single = array('1','3','5','7','9');
			if (in_array($five_num, $single))
			{
				$draw_money = $trade_money * $rate_caidan;
			}
		}
		elseif ($cai_type == '3')
		{
			$double = array('0','2','4','6','8');
			if (in_array($five_num, $double))
			{
				$draw_money = $trade_money * $rate_caishuang;
			}
		}
		M('users')->where(array('userId'=>$userId))->setDec('userMoney',$trade_money);
		$moneyRemark = '下单金额减'.$trade_money.'元';
		$userMoney = $userinfo['userMoney'] - $trade_money;
		$redPacketMoney = $userinfo['redPacketMoney'];
		$distributMoney = $userinfo['distributMoney'];
		addMoneylog($userId, $moneyRemark, '5', '0', $trade_money, $transaction_id, '0', '0', $userMoney, $redPacketMoney, $distributMoney);
		$moneyRemark = '下注扣除手续费:'.$sx_money.'元';
		$distributMoney = $userinfo['distributMoney'];
		if ($userinfo['redPacketMoney']>$sx_money || $userinfo['redPacketMoney']==$sx_money)
		{
			M('users')->where(array('userId'=>$userId))->setDec('redPacketMoney',$sx_money);
			$type = '1';
			$userMoney = $userinfo['userMoney'];
			$redPacketMoney = $userinfo['redPacketMoney'] - $sx_money;
		}
		else
		{
			M('users')->where(array('userId'=>$userId))->setDec('userMoney',$sx_money);
			$type = '0';
			$userMoney = $userinfo['userMoney'] - $trade_money - $sx_money;
			$redPacketMoney = $userinfo['redPacketMoney'];
		}
		addMoneylog($userId, $moneyRemark, '4', '0', $sx_money, $transaction_id, $type, '0', $userMoney, $redPacketMoney, $distributMoney);
		$updateTrade = array();
		if ($draw_money > 0)
		{
			M('users')->where(array('userId'=>$userId))->setInc('userMoney',$draw_money);
			$moneyRemark = '用户下注中奖获得金额:'.$draw_money.'元';
			$distributMoney = $userinfo['distributMoney'];
			$userMoney = $userinfo['userMoney'] - $trade_money - $sx_money + $draw_money;
			$redPacketMoney = $userinfo['redPacketMoney'];
			addMoneylog($userId, $moneyRemark, '8', '0', $draw_money, $transaction_id, '0', '1', $userMoney, $redPacketMoney, $distributMoney);
			$updateTrade['draw_money'] = $draw_money;
			$updateTrade['is_draw'] = '1';
		}
		$updateTrade['user_id'] = $userId;
		$updateTrade['trade_sn'] = $trade_sn;
		$updateTrade['trade_money'] = $trade_money;
		$updateTrade['type'] = $cai_type;
		$updateTrade['add_time'] = time();
		$updateTrade['game_type'] = '2';
		$updateTrade['poundage_money'] = $sx_money;
		$updateTrade['weipay_sn'] = $transaction_id;
		$updateTrade['is_pay'] = '1';
		$updateTrade['pay_time'] = time();
		$trade_id = M('trade')->add($updateTrade);
		$distrData = array();
		$distrData['userId']=$userId;
		$distrData['money'] = $trade_money;
		$distrData['dataId'] = $trade_id;
		$distrData['transaction_id'] = $transaction_id;
		$distrData['recommend_user_id'] = $userinfo['recommend_user_id'];
		D('WeChat/Users')->addDistributMoney($distrData);
		M('users')->where(array('userId'=>$userId))->setDec('play_count', 1);
		$cur_user = M('users')->where(array('userId'=>$userId))->find();
		if ($draw_money>0)
		{
			$data = array( 'status' => '1', 'my_money'=>$cur_user['userMoney'], 'money'=>$draw_money, 'wx_order_number'=>$weipay_sn, 'tail'=>$five_num, 'total_hand'=>$total_hand, 'number'=>$cai_type, 'left_cash_time'=>$cur_user['play_count'], 'is_win' => '1', 'record' => array( 'win_money'=>$draw_money, 'status'=>'3', 'number'=>$cai_type, 'total_hand'=>$total_hand, 'buy_money'=>$draw_money-$trade_money, 'tail'=>$five_num, ), 'other_award'=>array( '1'=>$first_num, '2'=>$second_num, '3'=>$three_num, '4'=>$four_num, '5'=>$five_num, 'is_award'=>'0', 'award_type'=>'', 'award_type_cn'=>'', 'award_money'=>'', ), );
		}
		else 
		{
			$data = array( 'status' => '1', 'my_money'=>$cur_user['userMoney'], 'money'=>$trade_money, 'wx_order_number'=>$weipay_sn, 'tail'=>$five_num, 'total_hand'=>$total_hand, 'number'=>$cai_type, 'left_cash_time'=>$cur_user['play_count'], 'is_win' => '0', 'record' => array( 'win_money'=>$trade_money, 'status'=>'2', 'number'=>$cai_type, 'total_hand'=>$total_hand, 'buy_money'=>$trade_money, 'tail'=>$five_num, ), 'other_award'=>array( '1'=>$first_num, '2'=>$second_num, '3'=>$three_num, '4'=>$four_num, '5'=>$five_num, 'is_award'=>'0', 'award_money'=>'', 'award_type'=>'', 'award_type_cn'=>'', ), );
		}
		return $data;
	}
}
?>