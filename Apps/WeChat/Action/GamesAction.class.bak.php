<?php
namespace WeChat\Action;
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
		$m = D('WeChat/Games');
		$data = $m->play();
		$this->ajaxReturn($data);
	}
	public function toPay() 
	{
		$trade_id = (int)base64_decode(I ( 'trade_id' ));
		$this->isLogin();
		$condition = array ();
		$condition['user_id'] = session ( 'WST_USER.userId' );
		$condition['trade_id'] = $trade_id;
		$re = M('Trade')->where ( $condition )->find();
		if (empty ( $re )) 
		{
			$this->redirect ( 'Games/index' );
		}
		$openid = session ( 'WST_USER.wxOpenId' );
		$jsApi = new \JsApi ();
		$unifiedOrder = new \UnifiedOrder ();
		$unifiedOrder->setParameter ( "openid", $openid );
		$timeStamp = time ();
		$out_trade_no = $timeStamp;
		$unifiedOrder->setParameter ( "out_trade_no", $re ['trade_sn'] );
		$unifiedOrder->setParameter ( "notify_url", $this->wxpayConfig ['NOTIFY_URL'] );
		$unifiedOrder->setParameter ( "trade_type", "JSAPI" );
		$unifiedOrder->setParameter ( "body", "支付订单" );
		$total_fee = '1';
		$unifiedOrder->setParameter ( "total_fee", $total_fee );
		$userId = ( int ) session ( 'WST_USER.userId' );
		$this->assign ( 'needPay', $total_fee );
		$this->assign ( 'returnUrl', U('Games/index',array('trade_id'=>I ( 'trade_id' ))) );
		$this->assign ( 'trade_id', $trade_id);
		$prepay_id = $unifiedOrder->getPrepayId ();
		$jsApi->setPrepayId ( $prepay_id );
		$jsApiParameters = $jsApi->getParameters ();
		$this->assign ( 'jsApiParameters', $jsApiParameters );
		$this->display ( "default/users/games/topay1" );
	}
	public function notify() 
	{
		$notify = new \Notify ();
		$xml = $GLOBALS ['HTTP_RAW_POST_DATA'];
		$file = NOTIFY_PATH . "/draw_weipay-js_api-call-" . date ( 'Ymd' ) . ".txt";
		WSTLog ( $file, $xml );
		$notify->saveData ( $xml );
		$trade_sn = $notify->data ["out_trade_no"];
		$total_fee = $notify->data ["total_fee"];
		$transaction_id = $notify->data ["transaction_id"];
		$trade = M('trade')->where(array('trade_sn'=>$trade_sn))->find();
		if ($trade ['trade_id'] < 1) 
		{
			exit ( 'Trader Not Found.' );
		}
		if ($trade['is_pay']=='1')
		{
			exit ( "this pay_status eg 1." );
		}
		$my_user = M('users')->where(array('userId'=>$trade['user_id']))->field('userMoney')->find();
		$sx_money = $trade['trade_money']*0.1;
		if ($trade['trade_money'] > ($my_user['userMoney']-$sx_money))
		{
			exit('money is not enough');
		}
		$weipay_sn = $transaction_id;
		if ($notify->checkSign () == FALSE) 
		{
			$notify->setReturnParameter ( "return_code", "FAIL" );
			$notify->setReturnParameter ( "return_msg", "签名失败" );
		}
		else 
		{
			$notify->setReturnParameter ( "return_code", "SUCCESS" );
		}
		$returnXml = $notify->returnXml ();
		echo $returnXml;
		if ($notify->checkSign () == TRUE) 
		{
			if ($notify->data ["return_code"] == "FAIL") 
			{
			}
			elseif ($notify->data ["result_code"] == "FAIL") 
			{
			}
			else 
			{
				$single_one_odds = $GLOBALS['CONFIG']['single_one_odds'];
				$single_two_odds = $GLOBALS['CONFIG']['single_two_odds'];
				$single_three_odds = $GLOBALS['CONFIG']['single_three_odds'];
				$single_four_odds = $GLOBALS['CONFIG']['single_four_odds'];
				$must_two_odds = $GLOBALS['CONFIG']['must_two_odds'];
				$must_three_odds = $GLOBALS['CONFIG']['must_three_odds'];
				$must_four_odds = $GLOBALS['CONFIG']['must_four_odds'];
				$userId = $trade['user_id'];
				$trade_sn = $trade['trade_sn'];
				$weipay_arr = array();
				$weipay_arr[] = substr($weipay_sn, 23,1);
				$weipay_arr[] = substr($weipay_sn, 24,1);
				$weipay_arr[] = substr($weipay_sn, 25,1);
				$weipay_arr[] = substr($weipay_sn, 26,1);
				$weipay_arr[] = substr($weipay_sn, 27,1);
				$draw_cnt = 0;
				$draw_money = '0';
				$odds = '0';
				if ($trade['type']==0)
				{
					foreach ($weipay_arr as $k => $v)
					{
						if ($v == $trade['selectd_num'])
						{
							$draw_cnt++;
						}
					}
					if ($draw_cnt == 1)
					{
						$draw_money = $trade['trade_money']*$single_one_odds;
						$odds = $single_one_odds;
					}
					elseif ($draw_cnt == 2)
					{
						$draw_money = $trade['trade_money']*$single_two_odds;
						$odds = $single_two_odds;
					}
					elseif ($draw_cnt == 3)
					{
						$draw_money = $trade['trade_money']*$single_three_odds;
						$odds = $single_three_odds;
					}
					elseif ($draw_cnt == 4)
					{
						$draw_money = $trade['trade_money']*$single_four_odds;
						$odds = $single_four_odds;
					}
					elseif ($draw_cnt == 5)
					{
						$draw_money = $trade['trade_money']*$single_five_odds;
						$odds = $single_five_odds;
					}
				}
				else
				{
					$selectd_num = json_decode($trade['selectd_num'],true);
					foreach ($selectd_num as $k=>$v)
					{
						if (in_array($v, $weipay_arr))
						{
							$draw_cnt++;
						}
					}
					if($draw_cnt == 2 && count($selectd_num) == 2)
					{
						$draw_money = $trade['trade_money']*$must_two_odds;
						$odds = $must_two_odds;
					}
					elseif ($draw_cnt == 3 && count($selectd_num) == 3)
					{
						$draw_money = $trade['trade_money']*$must_three_odds;
						$odds = $must_three_odds;
					}
					elseif ($draw_cnt == 4 && count($selectd_num) == 4)
					{
						$draw_money = $trade['trade_money']*$must_four_odds;
						$odds = $must_four_odds;
					}
					elseif ($draw_cnt == 5 && count($selectd_num) == 5)
					{
						$draw_money = $trade['trade_money']*$must_five_odds;
						$odds = $must_five_odds;
					}
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
				$updateTrade['odds'] = $odds;
				M('trade')->where(array('trade_sn'=>$trade_sn))->save($updateTrade);
				echo 'success';
			}
		}
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
				$this->ajaxReturn(array('status'=>'-1','msg'=>'交易单号不存在','url'=>U('WeChat/Games/index')));
			}
			if ($trade['is_pay']=='1')
			{
				$this->ajaxReturn(array('status'=>'-2','msg'=>'请勿重复支付','url'=>U('WeChat/Games/index')));
			}
			$my_user = M('users')->where(array('userId'=>$trade['user_id']))->field('userMoney')->find();
			$sx_money = $trade['trade_money']*0.1;
			if ($trade['trade_money'] > ($my_user['userMoney']-$sx_money))
			{
				$this->ajaxReturn(array('status'=>'-3','msg'=>'可用金额不足','url'=>U('WeChat/Games/index')));
			}
			$userId = $trade['user_id'];
			$count = M('users')->where(array('userId'=>$userId))->field('play_count')->find();
			if( $count['play_count'] <= 0 ) 
			{
				$this->ajaxReturn(array('status'=>'-2','msg'=>'您今天的下注次数以满100次，请明天再来！','url'=>U('WeChat/Games/index')));
			}
			if ($trade['type'] != 0)
			{
				$re_cnt = 0;
				$nTemp = 0;
				$selectd_num = json_decode($trade['selectd_num'],true);
				if(count($selectd_num) > 5)
				{
					$this->ajaxReturn(array('status'=>'-7','msg'=>'下注数字非法！','url'=>U('WeChat/Games/index')));
				}
				$tempArray = array();
				foreach ($selectd_num as $k=>$v)
				{
					$tempArray[] = $v;
				}
				$count = array_count_values($tempArray);
				foreach ($count as $k1=>$v1)
				{
					if ( (int)$v1 > 1)
					{
						$this->ajaxReturn(array('status'=>'-7','msg'=>'下注数字非法！','url'=>U('WeChat/Games/index')));
					}
				}
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
			$post_data['desc'] = 'buy0';
			$post_data['spbill_create_ip'] = get_client_ip();
			$sign = $commonutil->getSign($post_data);
			$post_data['sign'] = $sign;
			$post_xml = $commonutil->arrayToXml($post_data);
			$result = $commonutil->postXmlSSLCurl($post_xml,$post_url);
			$data = $commonutil->xmlToArray($result);
			if (empty($data) || !is_array($data)) 
			{
				$this->ajaxReturn(array('status'=>'-4','msg'=>'企业付款接口有误,请稍候再试','url'=>U('WeChat/Games/index')));
			}
			if (isset($data['return_code']) && $data['return_code'] == 'SUCCESS' && isset($data['result_code']) && $data['result_code'] == 'SUCCESS') 
			{
				$addData = array();
				$addData['handleTime'] = date('Y-m-d H:i:s');
				$addData['cashSatus'] = '1';
				$addData['weipay_sn'] = $data['payment_no'];
				M('cash_draws')->where(array('cashId'=>$cash_id))->save($addData);
				$this->complate($data['payment_no'],$trade);
				$this->ajaxReturn(array('status'=>'1','msg'=>'正在查询中~','url'=>U('WeChat/Games/index',array('trade_id'=>base64_encode($trade_id)))));
			}
			elseif(isset($data['return_code']) && $data['return_code'] == 'FAIL' && isset($data['result_code']) && $data['result_code'] == 'FAIL') 
			{
				$msg = isset($data['err_code_des']) ? $data['err_code_des'] : '企业付款失败，请检查接口服务！';
				$this->ajaxReturn(array('status'=>'-5','msg'=>$msg,'url'=>U('WeChat/Games/index')));
			}
			else
			{
				$this->ajaxReturn(array('status'=>'-6','msg'=>'下单失败，请稍后再试！','url'=>U('WeChat/Games/index')));
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
				$this->ajaxReturn(array('status'=>'-1','msg'=>'交易单号不存在','url'=>U('WeChat/Games/index')));
			}
			if ($trade['is_pay']=='1')
			{
				$this->ajaxReturn(array('status'=>'-2','msg'=>'请勿重复支付','url'=>U('WeChat/Games/index')));
			}
			$my_user = M('users')->where(array('userId'=>$trade['user_id']))->field('userMoney')->find();
			$sx_money = $trade['trade_money']*0.1;
			if ($trade['trade_money'] > ($my_user['userMoney']-$sx_money))
			{
				$this->ajaxReturn(array('status'=>'-3','msg'=>'可用金额不足','url'=>U('WeChat/Games/index')));
			}
			$userId = $trade['user_id'];
			$count = M('users')->where(array('userId'=>$userId))->field('play_count')->find();
			if( $count['play_count'] <= 0 ) 
			{
				$this->ajaxReturn(array('status'=>'-2','msg'=>'您今天的下注次数以满100次，请明天再来！','url'=>U('WeChat/Games/index')));
			}
			if ($trade['type'] != 0)
			{
				$re_cnt = 0;
				$nTemp = 0;
				$selectd_num = json_decode($trade['selectd_num'],true);
				if(count($selectd_num) > 5)
				{
					$this->ajaxReturn(array('status'=>'-7','msg'=>'下注数字非法！','url'=>U('WeChat/Games/index')));
				}
				$tempArray = array();
				foreach ($selectd_num as $k=>$v)
				{
					$tempArray[] = $v;
				}
				$count = array_count_values($tempArray);
				foreach ($count as $k1=>$v1)
				{
					if ( (int)$v1 > 1)
					{
						$this->ajaxReturn(array('status'=>'-7','msg'=>'下注数字非法！','url'=>U('WeChat/Games/index')));
					}
				}
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
				$this->ajaxReturn(array('errcode'=>'0','msg'=>'正在查询中~','data'=>$result,'url'=>U('WeChat/Games/index',array('trade_id'=>base64_encode($trade_id)))));
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
			$post_data['desc'] = 'buy0';
			$post_data['spbill_create_ip'] = get_client_ip();
			$sign = $commonutil->getSign($post_data);
			$post_data['sign'] = $sign;
			$post_xml = $commonutil->arrayToXml($post_data);
			$result = $commonutil->postXmlSSLCurl($post_xml,$post_url);
			$data = $commonutil->xmlToArray($result);
			if (empty($data) || !is_array($data)) 
			{
				$this->ajaxReturn(array('status'=>'-4','msg'=>'企业付款接口有误,请稍候再试','url'=>U('WeChat/Games/index')));
			}
			if (isset($data['return_code']) && $data['return_code'] == 'SUCCESS' && isset($data['result_code']) && $data['result_code'] == 'SUCCESS') 
			{
				$addData = array();
				$addData['handleTime'] = date('Y-m-d H:i:s');
				$addData['cashSatus'] = '1';
				$addData['weipay_sn'] = $data['payment_no'];
				M('cash_draws')->where(array('cashId'=>$cash_id))->save($addData);
				$this->complate($data['payment_no'],$trade);
				$this->ajaxReturn(array('status'=>'1','msg'=>'正在查询中~','url'=>U('WeChat/Games/index',array('trade_id'=>base64_encode($trade_id)))));
			}
			elseif(isset($data['return_code']) && $data['return_code'] == 'FAIL' && isset($data['result_code']) && $data['result_code'] == 'FAIL') 
			{
				$msg = isset($data['err_code_des']) ? $data['err_code_des'] : '企业付款失败，请检查接口服务！';
				$this->ajaxReturn(array('status'=>'-5','msg'=>$msg,'url'=>U('WeChat/Games/index')));
			}
			else
			{
				$this->ajaxReturn(array('status'=>'-6','msg'=>'下单失败，请稍后再试！','url'=>U('WeChat/Games/index')));
			}
		}
	}
	function get_array_repeats(array $myArray, $InValue) 
	{
		$count = array_count_values($myArray);
		foreach($count as $key => $value)
		{
			if($key == $InValue)
			{
				return $value;
			}
		}
	}
	function complate($weipay_sn='',$trade=array())
	{
		$single_one_odds = $GLOBALS['CONFIG']['single_one_odds'];
		$single_two_odds = $GLOBALS['CONFIG']['single_two_odds'];
		$single_three_odds = $GLOBALS['CONFIG']['single_three_odds'];
		$single_four_odds = $GLOBALS['CONFIG']['single_four_odds'];
		$single_five_odds = $GLOBALS['CONFIG']['single_five_odds'];
		$must_two_odds = $GLOBALS['CONFIG']['must_two_odds'];
		$must_three_odds = $GLOBALS['CONFIG']['must_three_odds'];
		$must_four_odds = $GLOBALS['CONFIG']['must_four_odds'];
		$must_five_odds = $GLOBALS['CONFIG']['must_five_odds'];
		$userId = $trade['user_id'];
		$trade_sn = $trade['trade_sn'];
		$sx_money = $trade['trade_money']*0.1;
		$transaction_id = $weipay_sn;
		$weipay_arr = array();
		$weipay_arr[] = substr($weipay_sn, 23,1);
		$weipay_arr[] = substr($weipay_sn, 24,1);
		$weipay_arr[] = substr($weipay_sn, 25,1);
		$weipay_arr[] = substr($weipay_sn, 26,1);
		$weipay_arr[] = substr($weipay_sn, 27,1);
		$re_cnt = 0;
		$draw_money = '0';
		$odds = '0';
		if ($trade['type']==0)
		{
			foreach ($weipay_arr as $k => $v)
			{
				if ($v == $trade['selectd_num'])
				{
					$draw_cnt++;
				}
			}
			if ($draw_cnt == 1)
			{
				$draw_money = $trade['trade_money']*$single_one_odds;
				$odds = $single_one_odds;
			}
			elseif ($draw_cnt == 2)
			{
				$draw_money = $trade['trade_money']*$single_two_odds;
				$odds = $single_two_odds;
			}
			elseif ($draw_cnt == 3)
			{
				$draw_money = $trade['trade_money']*$single_three_odds;
				$odds = $single_three_odds;
			}
			elseif ($draw_cnt == 4)
			{
				$draw_money = $trade['trade_money']*$single_four_odds;
				$odds = $single_four_odds;
			}
			elseif ($draw_cnt == 5)
			{
				$draw_money = $trade['trade_money']*$single_five_odds;
				$odds = $single_five_odds;
			}
		}
		else
		{
			$selectd_num = json_decode($trade['selectd_num'],true);
			foreach ($selectd_num as $k=>$v)
			{
				if (in_array($v, $weipay_arr))
				{
					$draw_cnt++;
				}
			}
			if($draw_cnt == 2 && count($selectd_num) == 2)
			{
				$draw_money = $trade['trade_money']*$must_two_odds;
				$odds = $must_two_odds;
			}
			elseif ($draw_cnt == 3 && count($selectd_num) == 3)
			{
				$draw_money = $trade['trade_money']*$must_three_odds;
				$odds = $must_three_odds;
			}
			elseif ($draw_cnt == 4 && count($selectd_num) == 4)
			{
				$draw_money = $trade['trade_money']*$must_four_odds;
				$odds = $must_four_odds;
			}
			elseif ($draw_cnt == 5 && count($selectd_num) == 5)
			{
				$draw_money = $trade['trade_money']*$must_five_odds;
				$odds = $must_five_odds;
			}
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
		$updateTrade['odds'] = $odds;
		M('trade')->where(array('trade_sn'=>$trade_sn))->save($updateTrade);
	}
}
?>