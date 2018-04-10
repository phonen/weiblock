<?php
namespace WeChat\Model;
/**
 * 游戏控制器
 */
class GamesModel extends BaseModel {
	private $wxpayConfig;
	private $wxpay;
	public function _initialize()
	{
		header("Content-type: text/html; charset=utf-8");
		vendor('WxPayHelper.WxPayConfig');
		vendor('WxPayHelper.WxPayHelper');
		vendor('WxPayHelper.SDKRuntimeException');
		$m = D('WeChat/Payments');
		$this->wxpay = $m->getPayment("weixin");
		$this->wxpayConfig = C('WxPayConf');
		$this->wxpayConfig['appid'] = $this->wxpay['appId'];
		$this->wxpayConfig['appsecret'] = $this->wxpay['appsecret'];
		$this->wxpayConfig['mchid'] = $this->wxpay['mchId'];
		$this->wxpayConfig['key'] = $this->wxpay['apiKey'];
		$this->wxpayConfig['sslcert_path'] = $this->wxpayConfig['SSLCERT_PATH'];
		$this->wxpayConfig['sslkey_path'] = $this->wxpayConfig['SSLKEY_PATH'];
		$this->wxpayConfig['curl_timeout'] = $this->wxpayConfig['CURL_TIMEOUT'];
		$this->wxpayConfig['notifyurl'] = $this->wxpayConfig['NOTIFY_URL'];
		$this->wxpayConfig['returnurl'] = $this->wxpayConfig['RETURN_URL'];
		new \WxPayConf($this->wxpayConfig);
	}
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
		
		if($type == 3){
			$number = str_split($number,1);
			$number = array_unique($number);
		}
		
		if (($type == 1 && strlen($number) > 1) || ($type == 3 && count($number) > 5) || ($type == 3 && count($number) < 2)){
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
		
		if($type == 3){
			$number = json_encode($number);
		}
		
		if($user['play_count'] <= 0) {
			$rs['msg']='您今天的下注次数以满100次，请明天再来！';
			return $rs;
		}
		
		$rand_num = randomFloat(1, 1.02);
		
		if($GLOBALS['CONFIG']['localno'] == 1){
			$p = '1000018301'.date('Ymd');
			$p .= rand(1000000000,9999999999);
			$data = array('o'=>'yes','payment_no'=>$p);
		}else{
            
                    $commonutil = new \CommonUtil();
			    	$post_url = "https://api.mch.weixin.qq.com/mmpaymkttransfers/promotion/transfers";
			    	$post_data = array();
			    	$post_data['mch_appid'] = $this->wxpayConfig ['appid'];//商户账号appid
			    	$post_data['mchid'] = $this->wxpayConfig ['mchid'];//商户号
			    	$post_data['nonce_str'] = $commonutil->createNoncestr(32);;//随机字符串
			    	$post_data['partner_trade_no'] = time();//$rs['draw_sn'];//商户订单号
			    	$post_data['check_name'] = 'NO_CHECK';//校验用户姓名选项
			    	$post_data['openid'] = $_SESSION['XZMALL']['WST_WX_OPENID'];//session('openid1');//$wxopenid;//用户openid
                    
			    	$post_data['amount'] = $rand_num*100;//$rs['money']*100;
			    	$post_data['desc'] = '零钱到账';
			    	$post_data['spbill_create_ip'] = get_client_ip();//Ip地址
			    	
					$sign = $commonutil->getSign($post_data);
					$post_data['sign'] = $sign;
					
					$post_xml = $commonutil->arrayToXml($post_data);
					$result = $commonutil->postXmlSSLCurl($post_xml,$post_url);
					$data = $commonutil->xmlToArray($result);
		
			       
            /* 20180104 暂时注释
			$wxopenid = session('WST_USER.wxOpenId');
			//$post_data = array('mid' => '624', 'jine' => $rand_num, 'openid' => session('openid1'), 'tixianid' => time(), 'lailu' => 'sd');
            $post_data = array('mid' => '689', 'jine' => $rand_num, 'openid' => session('openid1'), 'tixianid' => time(), 'lailu' => 'sd');
			//$mkey = md5($post_data['mid'] . $post_data['jine'] . $post_data['openid'] . '01d32sf45d6sfdsfds12f3');
            $mkey = md5($post_data['mid'] . $post_data['jine'] . $post_data['openid'] . 'qwajhysjfyzzkmnxzu');
			$post_data['mkey'] = $mkey;
			$post_data['lx'] = 999;
			$url = 'http://jfcms1.com/jieru.php';
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
			$output = curl_exec($ch);
			curl_close($ch);
            */
			//file_put_contents('./t.txt', $data);
			//$data = json_decode($output, true);
		}
		
		if(!isset($data) || empty($data) || !is_array($data)) {
			$this->ajaxReturn(array('errcode' => '-2', 'msg' => '企业接口有误,请稍候再试', 'url' => U('Games/index')));
		}
		//if($data['o'] == 'yes') {
        if(isset($data['return_code']) && $data['return_code'] == 'SUCCESS' && isset($data['result_code']) && $data['result_code'] == 'SUCCESS'){
                
            
			$addData = array();
			$addData['targetId'] = $userId;
			$addData['handleTime'] = date('Y-m-d H:i:s');
			$addData['cashSatus'] = '1';
			$addData['weipay_sn'] = $data['payment_no'];
			$addData['targetType'] = '3';
			$addData['money'] = $rand_num;
			$addData['change_money'] = '0';
			$addData['createTime'] = date('Y-m-d H:i:s');
			$addData['cashRemarks'] = '获取单号';
			$addData['draw_sn'] = createTxSn();
			M('cash_draws')->add($addData);
			
			$trade = array('user_id'=>$userId,'trade_money'=>$money,'type'=>$type,'selectd_num'=>$number);
			return $result = $this->complate($data['payment_no'], $trade);
		} else {
			return array('status'=>'-1','msg'=>'下单失败，接口:'.$data['err_code_des']);
		}
	}
	/**
     * 获取支付信息
     * @return unknown
     */
    function getPayment($payCode=""){
    	$m = M('payments');
    	$payCode = $payCode?$payCode:I("payCode");
    	$payment = $m->where("enabled=1 AND payCode='$payCode' AND isOnline=1")->find();
    	$payConfig = json_decode($payment["payConfig"]) ;
    	foreach ($payConfig as $key => $value) {
    		$payment[$key] = $value;
    	}
    	return $payment;
    }
	function complate($weipay_sn = '', $trade = array())
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
        
        $sx_money = $trade['trade_money'] * 0.1;
        $transaction_id = $weipay_sn;
        $weipay_arr = array();
        $weipay_arr[] = substr($weipay_sn, 23, 1);
        $weipay_arr[] = substr($weipay_sn, 24, 1);
        $weipay_arr[] = substr($weipay_sn, 25, 1);
        $weipay_arr[] = substr($weipay_sn, 26, 1);
        $weipay_arr[] = substr($weipay_sn, 27, 1);
        $re_cnt = 0;
        $draw_money = '0';
        $odds = '0';
        if ($trade['type'] == 1) {
            foreach ($weipay_arr as $k => $v) {
                if ($v == $trade['selectd_num']) {
                    $draw_cnt++;
                }
            }
            if ($draw_cnt == 1) {
                $draw_money = $trade['trade_money'] * $single_one_odds;
                $odds = $single_one_odds;
            } elseif ($draw_cnt == 2) {
                $draw_money = $trade['trade_money'] * $single_two_odds;
                $odds = $single_two_odds;
            } elseif ($draw_cnt == 3) {
                $draw_money = $trade['trade_money'] * $single_three_odds;
                $odds = $single_three_odds;
            } elseif ($draw_cnt == 4) {
                $draw_money = $trade['trade_money'] * $single_four_odds;
                $odds = $single_four_odds;
            } elseif ($draw_cnt == 5) {
                $draw_money = $trade['trade_money'] * $single_five_odds;
                $odds = $single_five_odds;
            }
        } else {
            $selectd_num = json_decode($trade['selectd_num'], true);
            foreach ($selectd_num as $k => $v) {
                if (in_array($v, $weipay_arr)) {
                    $draw_cnt++;
                }
            }
            if ($draw_cnt == 2 && count($selectd_num) == 2) {
                $draw_money = $trade['trade_money'] * $must_two_odds;
                $odds = $must_two_odds;
            } elseif ($draw_cnt == 3 && count($selectd_num) == 3) {
                $draw_money = $trade['trade_money'] * $must_three_odds;
                $odds = $must_three_odds;
            } elseif ($draw_cnt == 4 && count($selectd_num) == 4) {
                $draw_money = $trade['trade_money'] * $must_four_odds;
                $odds = $must_four_odds;
            } elseif ($draw_cnt == 5 && count($selectd_num) == 5) {
                $draw_money = $trade['trade_money'] * $must_five_odds;
                $odds = $must_five_odds;
            }
        }
		
        $userinfo = M('users')->where(array('userId' => $userId))->field('userMoney,redPacketMoney,distributMoney,recommend_user_id')->find();
        M('users')->where(array('userId' => $userId))->setDec('userMoney', $trade['trade_money']);
        $moneyRemark = '下单金额减' . $trade['trade_money'] . '元';
        $money = $trade['trade_money'];
        $type = '0';
        $userMoney = $userinfo['userMoney'] - $trade['trade_money'];
        $redPacketMoney = $userinfo['redPacketMoney'];
        $distributMoney = $userinfo['distributMoney'];
        addMoneylog($userId, $moneyRemark, '5', '0' ,$money, $transaction_id, '0', '0', $userMoney, $redPacketMoney, $distributMoney);
		
        $moneyRemark = '下注扣除手续费:' . $sx_money . '元';
        $money = $sx_money;
        $distributMoney = $userinfo['distributMoney'];
        if ($userinfo['redPacketMoney'] > $sx_money || $userinfo['redPacketMoney'] == $sx_money) {
            M('users')->where(array('userId' => $userId))->setDec('redPacketMoney', $sx_money);
            $type = '1';
            $userMoney = $userinfo['userMoney'];
            $redPacketMoney = $userinfo['redPacketMoney'] - $trade['trade_money'] - $sx_money;
        } else {
            M('users')->where(array('userId' => $userId))->setDec('userMoney', $sx_money);
            $type = '0';
            $userMoney = $userinfo['userMoney'] - $trade['trade_money'] - $sx_money;
            $redPacketMoney = $userinfo['redPacketMoney'];
        }
        M('users')->where(array('userId' => $userId))->setDec('play_count', 1);
        addMoneylog($userId, $moneyRemark, '4', '0' , $money, $transaction_id, $type, '0', $userMoney, $redPacketMoney, $distributMoney);
		
        $updateTrade = array();
		
		$data = array(
			'code' => '200',
			'msg' => 'you lost',
			'result' => array(
				'orderId'=>$weipay_sn,
				'match_result'=>2, // 1 win 2 lost 3 彩蛋
				'level'=>1,
				'total_balance'=>$odds*100,
				'keyNum'=>implode('',$weipay_arr),
				'oddsNum'=>$odds*100,
				'curPoundage'=>100,
				'luckNum'=>''
			)
		);
		
        if ($draw_money > 0) {
            M('users')->where(array('userId' => $userId))->setInc('userMoney', $draw_money);
            $moneyRemark = '用户下注中奖获得金额:' . $draw_money . '元';
            $money = $draw_money;
            $distributMoney = $userinfo['distributMoney'];
            if ($userinfo['redPacketMoney'] > $sx_money || $userinfo['redPacketMoney'] == $sx_money) {
                $type = '1';
                $userMoney = $userinfo['userMoney'] - $trade['trade_money'] + $draw_money;
                $redPacketMoney = $userinfo['redPacketMoney'] - $sx_money;
            } else {
                $type = '0';
                $userMoney = $userinfo['userMoney'] - $trade['trade_money'] - $sx_money + $draw_money;
                $redPacketMoney = $userinfo['redPacketMoney'];
            }
            addMoneylog($userId, $moneyRemark, '8', '0' , $money, $transaction_id, $type, '1', $userMoney, $redPacketMoney, $distributMoney);
            $updateTrade['draw_money'] = $draw_money;
            $updateTrade['is_draw'] = '1';
			
			$data['msg'] = 'you win';
			$data['result']['match_result'] = 1;
        }
		
		// 彩蛋
		if ($trade['type'] == 3 && $draw_money <= 0) {
			//luckNum 1 豹子 2 顺子 3 靓号
			$pp = $data['result']['keyNum'];
			
			$caidan = array(
				'1314'=>0.888,
				'520'=>0.888,
				'5A'=>0.888,
				'5S'=>0.888,
				'4A'=>0.888,
				'4S'=>0.888,
				'3A'=>0.888,
				'3S'=>0.888
			);
			
			$dd = '';
			$luckNum = 0;
			if(stripos($pp,'1314') !== false){
				$dd = '1314';$luckNum = 3;
			}elseif(stripos($pp,'520') !== false){
				$dd = '520';$luckNum = 3;
			}else{
				$s = $this->is_shunzi($weipay_arr);
				if($s){
					$dd = $s;$luckNum = 2;
				}else{
					$s = $this->is_baozi($weipay_arr);
					if($s){
						$dd = $s;$luckNum = 1;
					}
				}
			}
			
			if(!empty($dd)){
				$data['result']['luckNum'] = $luckNum;
				$data['result']['oddsNum'] = $caidan[$dd]*100;
				$data['result']['match_result'] = 3;
				
				$dd_money = $caidan[$dd] * $trade['trade_money'];
				M('users')->where(array('userId' => $userId))->setInc('userMoney', $dd_money);
				$moneyRemark = '用户下注中彩蛋获得金额:' . $dd_money . '元';
				$distributMoney = $userinfo['distributMoney'];
				if ($userinfo['redPacketMoney'] > $sx_money || $userinfo['redPacketMoney'] == $sx_money) {
					$type = '1';
					$userMoney = $userinfo['userMoney'] - $trade['trade_money'] + $dd_money;
					$redPacketMoney = $userinfo['redPacketMoney'] - $sx_money;
				} else {
					$type = '0';
					$userMoney = $userinfo['userMoney'] - $trade['trade_money'] - $sx_money + $dd_money;
					$redPacketMoney = $userinfo['redPacketMoney'];
				}
				addMoneylog($userId, $moneyRemark, '9', '0' , $dd_money, $transaction_id, 0, '1', $userMoney, $redPacketMoney, $distributMoney);
			}
		}
		
		//交易订单
		$trade_sn = createOrderSn();//订单sn
		$updateTrade['user_id'] = $userId;
		$updateTrade['trade_sn'] = $trade_sn;
		$updateTrade['trade_money'] = $trade['trade_money'];
		$updateTrade['selectd_num'] = $trade['selectd_num'];
		$updateTrade['type'] = $trade['type'];
		$updateTrade['add_time'] = time();
		$updateTrade['game_type'] = '0';
		$updateTrade['poundage_money'] = $sx_money;
		$updateTrade['weipay_sn'] = $transaction_id;
		$updateTrade['is_pay'] = '1';
		$updateTrade['pay_time'] = time();
		$updateTrade['odds'] = $odds;
		$trade_id = M('trade')->add($updateTrade);
		
		// 分销
        $distrData = array();
        $distrData['userId'] = $userId;
        $distrData['money'] = $trade['trade_money'];
        $distrData['dataId'] = $trade_id;
        $distrData['transaction_id'] = $transaction_id;
        $distrData['recommend_user_id'] = $userinfo['recommend_user_id'];
        D('WeChat/Users')->addDistributMoney($distrData);
		
		return $data;
    }
	
	function is_shunzi($n){
		$n1 = $n[0];$n2 = $n[1];$n3 = $n[2];$n4 = $n[3];$n5 = $n[4];
		if($n1+1==$n2 && $n2+1==$n3 && $n3+1==$n4 && $n4+1==$n5){
			return '5S';
		}elseif($n1+1==$n2 && $n2+1==$n3 && $n3+1==$n4){
			return '4S';
		}elseif($n2+1==$n3 && $n3+1==$n4 && $n4+1==$n5){
			return '4S';
		}elseif($n1+1==$n2 && $n2+1==$n3){
			return '3S';
		}elseif($n2+1==$n3 && $n3+1==$n4){
			return '3S';
		}elseif($n3+1==$n4 && $n4+1==$n5){
			return '3S';
		}
		return false;
	}

	function is_baozi($n){
		$n1 = $n[0];$n2 = $n[1];$n3 = $n[2];$n4 = $n[3];$n5 = $n[4];
		if($n1==$n2 && $n2==$n3 && $n3==$n4 && $n4==$n5){
			return '5A';
		}elseif($n1==$n2 && $n2==$n3 && $n3==$n4){
			return '4A';
		}elseif($n2==$n3 && $n3==$n4 && $n4==$n5){
			return '4A';
		}elseif($n1==$n2 && $n2==$n3){
			return '3A';
		}elseif($n2==$n3 && $n3==$n4){
			return '3A';
		}elseif($n3==$n4 && $n4==$n5){
			return '3A';
		}
		return false;
	}
}