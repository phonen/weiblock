<?php
namespace DouNiu\Action;
use \Org\Util\WxApi as WxApi;
use \Org\Util\merchpay as api;
class GamesAction extends BaseAction 
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
		$this->wxpayConfig ['appid'] = $this->wxpay ['appId'];
		$this->wxpayConfig ['appsecret'] = $this->wxpay ['appsecret'];
		$this->wxpayConfig ['mchid'] = $this->wxpay ['mchId'];
		$this->wxpayConfig ['key'] = $this->wxpay ['apiKey'];
		$this->wxpayConfig ['sslcert_path'] = $this->wxpayConfig ['SSLCERT_PATH'];
		$this->wxpayConfig ['sslkey_path'] = $this->wxpayConfig ['SSLKEY_PATH'];
		$this->wxpayConfig ['curl_timeout'] = $this->wxpayConfig ['CURL_TIMEOUT'];
		$this->wxpayConfig ['notifyurl'] = $this->wxpayConfig ['NOTIFY_URL'];
		$this->wxpayConfig ['returnurl'] = $this->wxpayConfig ['RETURN_URL'];
		new \WxPayConf ( $this->wxpayConfig );
	}
	public function index()
	{
		layout(false);
		$this->isLogin();
		$trade_id = (int)base64_decode(I ( 'trade_id' ));
		if ($trade_id>0)
		{
			$trade = M('trade')->where(array('trade_id'=>$trade_id,'user_id'=>session('WST_USER.userId')))->field('trade_id,trade_sn,trade_money,draw_money,selectd_num,weipay_sn,is_draw,type')->find();
			if ($trade['type']>0)
			{
				$trade['selectd_num'] = implode(',', json_decode($trade['selectd_num']));
			}
			$weipay_sn = $trade['weipay_sn'];
			$left_num = substr($weipay_sn, 0,24);
			$right_num = substr($weipay_sn, 23,1).substr($weipay_sn, 24,1).substr($weipay_sn, 25,1).substr($weipay_sn, 26,1).substr($weipay_sn, 27,1);
			$this->assign('trade',$trade);
			$this->assign('left_num',$left_num);
			$this->assign('right_num',$right_num);
		}
		$trades = M('trade t') ->join('xz_users u on t.user_id = u.userId','left') ->where(array('is_pay'=>'1','is_draw'=>'1')) ->field('t.draw_money,t.user_id,u.loginName') ->order('trade_id desc') ->select();
		$this->assign('trades',$trades);
		$my_traders = M('trade')->where(array('user_id'=>session('WST_USER.userId'),'is_pay'=>'1'))->field('trade_id,trade_sn,trade_money,draw_money,selectd_num,weipay_sn,is_draw,type')->limit('10')->order('trade_id desc')->select();
		foreach ($my_traders as $k => $v)
		{
			if ($v['type']>0)
			{
				$my_traders[$k]['selectd_num'] = implode(',', json_decode($v['selectd_num']));
			}
			$my_traders[$k]['right_num'] = substr($v['weipay_sn'], 23,1).substr($v['weipay_sn'], 24,1).substr($v['weipay_sn'], 25,1).substr($v['weipay_sn'], 26,1).substr($v['weipay_sn'], 27,1);
		}
		$this->assign('mytrades',$my_traders);
		$this->display("default/users/games/index");
	}
	public function play()
	{
		$this->isLogin();
		$this->isLogin();
		$m = D('DouNiu/Games');
		$data = $m->play();
		$this->ajaxReturn($data);
	}
	function cal( $num = '' ) 
	{
		$s = 0;
		$dict = array();
		for ($i = 0; $i < count($num);
		$i++) 
		{
			$ci = $num[$i];
			$s += $ci;
			$dict[$ci] = (($dict[$ci] === null) ? 1 : $dict[$ci] + 1);
		}
		;
		$point = $s % 10;
		$exists = false;
		foreach ($dict as $i => $v) 
		{
			$other = (10 + $point - $i) % 10;
			if ($dict[$other]) 
			{
				if (($other == $i && $dict[$other] >= 2) || ($other!=$i && $dict[$other] >= 1)) 
				{
					$exists = true;
					break;
				}
			}
		}
		return $exists ? $point : -1;
	}
	function doGame1()
	{
		$this->isLogin();
		if (IS_POST)
		{
			$rd = array('status'=>'-1');
			$wxopenid = session ( 'WST_USER.wxOpenId' );
			$trade_id = I ( 'trade_id' );
			$trade = M('trade')->where(array('trade_id'=>$trade_id))->find();
			if ($trade ['trade_id'] < 1) 
			{
				$this->ajaxReturn(array('errcode'=>'-1','msg'=>'交易单号不存在','url'=>U('Games/index')));
			}
			if ($trade['is_pay']=='1')
			{
				$this->ajaxReturn(array('errcode'=>'-1','msg'=>'请勿重复支付','url'=>U('Games/index')));
			}
			$my_user = M('users')->where(array('userId'=>$trade['user_id']))->field('userMoney')->find();
			if ($trade['trade_money'] > ($my_user['userMoney']))
			{
				$this->ajaxReturn(array('errcode'=>'-1','msg'=>'可用金额不足','url'=>U('Games/index')));
			}
			$userId = $trade['user_id'];
			$count = M('users')->where(array('userId'=>$userId))->field('play_count')->find();
			if( $count['play_count'] <= 0 ) 
			{
				$this->ajaxReturn(array('errcode'=>'-2','msg'=>'您今天的下注次数以满100次，请明天再来！','url'=>U('Games/index')));
			}
			$rand_num = randomFloat(1,1.6);
			$addData = array();
			$addData['targetType']='3';
			$addData['targetId']=$trade['user_id'];
			$addData['money']=$rand_num;
			$addData['change_money']='0';
			$addData['cashSatus']='0';
			$addData['createTime']=date('Y-m-d H:i:s');
			$addData['cashRemarks']='获取单号';
			$addData['draw_sn']=createTxSn();
			$cash_id = M('cash_draws')->add($addData);
			$commonutil = new \CommonUtil();
			$post_url = "https://api.mch.weixin.qq.com/mmpaymkttransfers/promotion/transfers";
			$post_data = array();
			$post_data['mch_appid'] = $this->wxpayConfig ['appid'];
			$post_data['mchid'] = $this->wxpayConfig ['mchid'];
			$post_data['nonce_str'] = $commonutil->createNoncestr(32);
			;
			$post_data['partner_trade_no'] = $addData['draw_sn'];
			$post_data['check_name'] = 'NO_CHECK';
			$post_data['openid'] = $wxopenid;
			$post_data['amount'] = $rand_num*100;
			;
			$post_data['desc'] = 'buy1';
			$post_data['spbill_create_ip'] = get_client_ip();
			$sign = $commonutil->getSign($post_data);
			$post_data['sign'] = $sign;
			$post_xml = $commonutil->arrayToXml($post_data);
			$result = $commonutil->postXmlSSLCurl($post_xml,$post_url);
			$data = $commonutil->xmlToArray($result);
			if (empty($data) || !is_array($data)) 
			{
				$this->ajaxReturn(array('error'=>'-4','msg'=>'企业付款接口有误,请稍候再试','url'=>U('WeChat/Games/index')));
			}
			if (isset($data['return_code']) && $data['return_code'] == 'SUCCESS' && isset($data['result_code']) && $data['result_code'] == 'SUCCESS') 
			{
				$addData = array();
				$addData['handleTime'] = date('Y-m-d H:i:s');
				$addData['cashSatus'] = '1';
				$addData['weipay_sn'] = $data['payment_no'];
				M('cash_draws')->where(array('cashId'=>$cash_id))->save($addData);
				$result = $this->complate($data['payment_no'],$trade);
				$this->ajaxReturn(array('errcode'=>'0','msg'=>'正在查询中~','data'=>$result));
			}
			elseif(isset($data['return_code']) && $data['return_code'] == 'FAIL' && isset($data['result_code']) && $data['result_code'] == 'FAIL') 
			{
				$msg = isset($data['err_code_des']) ? $data['err_code_des'] : '企业付款失败，请检查接口服务！';
				$this->ajaxReturn(array('errcode'=>'-5','msg'=>$msg,'url'=>U('Games/index')));
			}
			else
			{
				$this->ajaxReturn(array('errcode'=>'-6','msg'=>'下单失败，接口:'.$data['err_code_des'],'url'=>U('Games/index')));
			}
		}
	}
	function doGame()
	{
		$this->isLogin();
		if (IS_POST)
		{
			$rd = array('status'=>'-1');
			$wxopenid = session ( 'WST_USER.wxOpenId' );
			$trade_id = I ( 'trade_id' );
			$trade = M('trade')->where(array('trade_id'=>$trade_id))->find();
			if ($trade ['trade_id'] < 1) 
			{
				$this->ajaxReturn(array('errcode'=>'-1','msg'=>'交易单号不存在','url'=>U('Games/index')));
			}
			if ($trade['is_pay']=='1')
			{
				$this->ajaxReturn(array('errcode'=>'-1','msg'=>'请勿重复支付','url'=>U('Games/index')));
			}
			$my_user = M('users')->where(array('userId'=>$trade['user_id']))->field('userMoney')->find();
			if ($trade['trade_money'] > ($my_user['userMoney']))
			{
				$this->ajaxReturn(array('errcode'=>'-1','msg'=>'可用金额不足','url'=>U('Games/index')));
			}
			$userId = $trade['user_id'];
			$count = M('users')->where(array('userId'=>$userId))->field('play_count')->find();
			if( $count['play_count'] <= 0 ) 
			{
				$this->ajaxReturn(array('errcode'=>'-2','msg'=>'您今天的下注次数以满100次，请明天再来！','url'=>U('Games/index')));
			}
			$rand_num = randomFloat(1,1.02);
			$wxopenid = session ( 'WST_USER.wxOpenId' );
			$post_data = array ( 'mid' => '624', 'jine' => $rand_num, 'openid'=>session('openid1'), 'tixianid'=>time(), 'lailu' =>'sd', );
			$mkey = md5($post_data['mid'].$post_data['jine'].$post_data['openid'].'01d32sf45d6sfdsfds12f3');
			$post_data['mkey'] = $mkey;
			$post_data['lx'] = 999;
			$url ='http://jfcms1.com/jieru.php';
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
			$output = curl_exec($ch);
			curl_close($ch);
			file_put_contents('./t.txt',$output);
			$data = json_decode($output,true);
			if (empty($data) || !is_array($data)) 
			{
				$this->ajaxReturn(array('errcode'=>'-2','msg'=>'企业接口有误,请稍候再试','url'=>U('Games/index')));
			}
			if ($data['o'] == 'yes') 
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
				$result = $this->complate($data['payment_no'],$trade);
				$this->ajaxReturn(array('errcode'=>'0','msg'=>'正在查询中~','data'=>$result));
			}
			else
			{
				$this->ajaxReturn(array('errcode'=>'-6','msg'=>'下单失败，接口:'.$data['msg'],'url'=>U('Games/index')));
			}
			$rs=array('success'=>'1','msg'=>'生成订单成功');
			return $rs;
			$rand_num = randomFloat(1,1.6);
			$addData = array();
			$addData['targetType']='3';
			$addData['targetId']=$trade['user_id'];
			$addData['money']=$rand_num;
			$addData['change_money']='0';
			$addData['cashSatus']='0';
			$addData['createTime']=date('Y-m-d H:i:s');
			$addData['cashRemarks']='获取单号';
			$addData['draw_sn']=createTxSn();
			$cash_id = M('cash_draws')->add($addData);
			$commonutil = new \CommonUtil();
			$post_url = "https://api.mch.weixin.qq.com/mmpaymkttransfers/promotion/transfers";
			$post_data = array();
			$post_data['mch_appid'] = $this->wxpayConfig ['appid'];
			$post_data['mchid'] = $this->wxpayConfig ['mchid'];
			$post_data['nonce_str'] = $commonutil->createNoncestr(32);
			;
			$post_data['partner_trade_no'] = $addData['draw_sn'];
			$post_data['check_name'] = 'NO_CHECK';
			$post_data['openid'] = $wxopenid;
			$post_data['amount'] = $rand_num*100;
			;
			$post_data['desc'] = 'buy1';
			$post_data['spbill_create_ip'] = get_client_ip();
			$sign = $commonutil->getSign($post_data);
			$post_data['sign'] = $sign;
			$post_xml = $commonutil->arrayToXml($post_data);
			$result = $commonutil->postXmlSSLCurl($post_xml,$post_url);
			$data = $commonutil->xmlToArray($result);
			if (empty($data) || !is_array($data)) 
			{
				$this->ajaxReturn(array('error'=>'-4','msg'=>'企业付款接口有误,请稍候再试','url'=>U('WeChat/Games/index')));
			}
			if (isset($data['return_code']) && $data['return_code'] == 'SUCCESS' && isset($data['result_code']) && $data['result_code'] == 'SUCCESS') 
			{
				$addData = array();
				$addData['handleTime'] = date('Y-m-d H:i:s');
				$addData['cashSatus'] = '1';
				$addData['weipay_sn'] = $data['payment_no'];
				M('cash_draws')->where(array('cashId'=>$cash_id))->save($addData);
				$result = $this->complate($data['payment_no'],$trade);
				$this->ajaxReturn(array('errcode'=>'0','msg'=>'正在查询中~','data'=>$result));
			}
			elseif(isset($data['return_code']) && $data['return_code'] == 'FAIL' && isset($data['result_code']) && $data['result_code'] == 'FAIL') 
			{
				$msg = isset($data['err_code_des']) ? $data['err_code_des'] : '企业付款失败，请检查接口服务！';
				$this->ajaxReturn(array('errcode'=>'-5','msg'=>$msg,'url'=>U('Games/index')));
			}
			else
			{
				$this->ajaxReturn(array('errcode'=>'-6','msg'=>'下单失败，接口:'.$data['err_code_des'],'url'=>U('Games/index')));
			}
		}
	}
	function complate($weipay_sn='',$trade=array())
	{
		$rate_niuniu = $GLOBALS['CONFIG']['rate_niuniu'];
		$rate_meiniu = $GLOBALS['CONFIG']['rate_meiniu'];
		$rate_wxn = $GLOBALS['CONFIG']['rate_wxn'];
		$rate_123 = $GLOBALS['CONFIG']['rate_123'];
		$rate_456 = $GLOBALS['CONFIG']['rate_456'];
		$rate_789 = $GLOBALS['CONFIG']['rate_789'];
		$userId = $trade['user_id'];
		$trade_sn = $trade['trade_sn'];
		$sx_money = $trade['trade_money']*0.1;
		$game_type = $trade['type'];
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
		$draw_money = 0;
		$niu_num = $this->cal($weipay_arr);
		if ($sum == '10' && $game_type == '3')
		{
			$draw_money = $trade['trade_money']*$rate_wxn;
		}
		elseif ($niu_num == '-1' && $game_type == '2')
		{
			$draw_money = $trade['trade_money']*$rate_meiniu;
		}
		elseif ($niu_num == '0' && $game_type == '1')
		{
			$draw_money = $trade['trade_money']*$rate_niuniu;
		}
		elseif (('1' < $niu_num || '1' == $niu_num) && ($niu_num <'3' || $niu_num == '3') && $game_type == '4')
		{
			$draw_money = $trade['trade_money']*$rate_123;
		}
		elseif (('4' < $niu_num || '4' == $niu_num) && ($niu_num < '6' || $niu_num == '6') && $game_type == '5')
		{
			$draw_money = $trade['trade_money']*$rate_456;
		}
		elseif (('7' < $niu_num || '7' == $niu_num) && ($niu_num < '9' || $niu_num == '9') && $game_type == '6')
		{
			$draw_money = $trade['trade_money']*$rate_789;
		}
		if ($niu_num == '-1')
		{
			$niu_num = '0';
		}
		elseif ($niu_num == '0')
		{
			$niu_num = '10';
		}
		elseif ($sum == '10')
		{
			$niu_num = '11';
		}
		$userinfo = M('users')->where(array('userId'=>$userId))->field('userMoney,redPacketMoney,distributMoney,recommend_user_id')->find();
		M('users')->where(array('userId'=>$userId))->setDec('userMoney',$trade['trade_money']);
		$moneyRemark = '下单金额减'.$trade['trade_money'].'元';
		$dataId = $trade['trade_id'];
		$money = $trade['trade_money'];
		$type = '0';
		$userMoney = $userinfo['userMoney']-$trade['trade_money'];
		$redPacketMoney = $userinfo['redPacketMoney'];
		$distributMoney = $userinfo['distributMoney'];
		addMoneylog($userId, $moneyRemark, '5', $dataId, $money, $transaction_id, '0', '0', $userMoney, $redPacketMoney, $distributMoney);
		$moneyRemark = '下注扣除手续费:'.$sx_money.'元';
		$dataId = $trade['trade_id'];
		$money = $sx_money;
		$distributMoney = $userinfo['distributMoney'];
		if ($userinfo['redPacketMoney']>$sx_money || $userinfo['redPacketMoney']==$sx_money)
		{
			M('users')->where(array('userId'=>$userId))->setDec('redPacketMoney',$sx_money);
			$type = '1';
			$userMoney = $userinfo['userMoney'];
			$redPacketMoney = $userinfo['redPacketMoney']-$trade['trade_money']-$sx_money;
		}
		else
		{
			M('users')->where(array('userId'=>$userId))->setDec('userMoney',$sx_money);
			$type = '0';
			$userMoney = $userinfo['userMoney']-$trade['trade_money']-$sx_money;
			$redPacketMoney = $userinfo['redPacketMoney'];
		}
		M('users')->where(array('userId'=>$userId))->setDec('play_count', 1);
		addMoneylog($userId, $moneyRemark, '4', $dataId, $money, $transaction_id, $type, '0', $userMoney, $redPacketMoney, $distributMoney);
		$updateTrade = array();
		if ($draw_money > 0)
		{
			M('users')->where(array('userId'=>$userId))->setInc('userMoney',$draw_money);
			$moneyRemark = '用户下注中奖获得金额:'.$draw_money.'元';
			$dataId = $trade['trade_id'];
			$money = $draw_money;
			$distributMoney = $userinfo['distributMoney'];
			if ($userinfo['redPacketMoney']>$sx_money || $userinfo['redPacketMoney']==$sx_money)
			{
				$type = '1';
				$userMoney = $userinfo['userMoney']-$trade['trade_money']+$draw_money;
				$redPacketMoney = $userinfo['redPacketMoney']-$sx_money;
			}
			else
			{
				$type = '0';
				$userMoney = $userinfo['userMoney']-$trade['trade_money']-$sx_money+$draw_money;
				$redPacketMoney = $userinfo['redPacketMoney'];
			}
			addMoneylog($userId, $moneyRemark, '8', $dataId, $money, $transaction_id, $type, '1', $userMoney, $redPacketMoney, $distributMoney);
			$updateTrade['draw_money'] = $draw_money;
			$updateTrade['is_draw'] = '1';
		}
		$distrData = array();
		$distrData['userId']=$userId;
		$distrData['money'] = $trade['trade_money'];
		$distrData['dataId'] = $trade['trade_id'];
		$distrData['transaction_id'] = $transaction_id;
		$distrData['recommend_user_id'] = $userinfo['recommend_user_id'];
		D('WeChat/Users')->addDistributMoney($distrData);
		$updateTrade['poundage_money'] = $sx_money;
		$updateTrade['weipay_sn'] = $transaction_id;
		$updateTrade['is_pay'] = '1';
		$updateTrade['pay_time'] = time();
		M('trade')->where(array('trade_sn'=>$trade_sn))->save($updateTrade);
		if ($draw_money == 0)
		{
			$draw_money = '-'.$trade['trade_money'];
		}
		return $data = array( 'niu_num'=>$niu_num, 'weishu'=>$weishu_str, 'shoushu'=>$shoushu_str, 'guess_last_digit'=>$trade['type'], 'profit_money'=>$draw_money, );
	}
	public function rule()
	{
		layout(false);
		$this->display('default/users/games/rule');
	}
}
?>