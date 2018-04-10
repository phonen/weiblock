<?php
 namespace WeChat\Action;
 use \Org\Util\WxApi as WxApi;
 
 use \Org\Util\Merchpay as api;  
/**
 * 申请提现控制器
 */
class CashDrawsAction extends BaseAction{
   /**
	 * 初始化
	 */
	private $wxpayConfig;
	private $wxpay;
	public function _initialize() {
		header ( "Content-type: text/html; charset=utf-8" );
		vendor ( 'WxPayHelper.WxPayConfig' );
		vendor ( 'WxPayHelper.WxPayHelper' );
		vendor ( 'WxPayHelper.SDKRuntimeException' );
		
		$m = D ( 'WeChat/Payments' );
		$this->wxpay = $m->getPayment ( "weixin" ); 
   
		$this->wxpayConfig = C ( 'WxPayConf3' ); //读取config文件
		$this->wxpayConfig ['appid'] = $this->wxpay ['appId']; // 微信公众号身份的唯一标识
		$this->wxpayConfig ['appsecret'] = $this->wxpay ['appsecret']; // JSAPI接口中获取openid
		$this->wxpayConfig ['mchid'] = $this->wxpay ['mchId']; // 受理商ID
		$this->wxpayConfig ['key'] = $this->wxpay ['apiKey']; // 商户支付密钥Key
		$this->wxpayConfig ['sslcert_path'] = $this->wxpayConfig ['SSLCERT_PATH'];
		$this->wxpayConfig ['sslkey_path'] = $this->wxpayConfig ['SSLKEY_PATH'];
		$this->wxpayConfig ['curl_timeout'] = $this->wxpayConfig ['CURL_TIMEOUT'];
		$this->wxpayConfig ['notifyurl'] = $this->wxpayConfig ['NOTIFY_URL'];
		$this->wxpayConfig ['returnurl'] = $this->wxpayConfig ['RETURN_URL'];
		// 初始化WxPayConf
		new \WxPayConf ( $this->wxpayConfig );
	}
	
	  /**
	   * 提现界面
	   **/
	  public function withdrawals(){
	  	$this->isLogin();
	    $this->display("default/users/withdrawals");
	  }
	  
	   /**
	   * 提现操作
	   **/
	  public function cash(){
	  	$back_url = session('back_url');
	  	if (empty($back_url)){
	  	    $back_url = U('Users/index');
	  	}
	  	$this->assign('back_url',$back_url);
	    $this->display("default/users/cash");
	  }
	  
      /**
	   * 代理提现界面
	   **/
	  public function agentCash(){
	  	$this->isLogin();
	    $this->display("default/users/agent_cash");
	  }
	  

	/**
	 * 用户申请提现
	 */
    public function drawsCashByUser(){
		$this->isLogin();
		$rs = D('WeChat/CashDraws')->insertByUser();
		$this->ajaxReturn($rs);
	}
	
	/**
     * 企业支付
     * @param string $openid 	用户openID
     * @param string $trade_no 	单号
     * @param string $money 	金额
     * @param string $desc 		描述
     * @return string 	XML 结构的字符串
     */
    function pay($mon=0,$type){
    	$this->isLogin();
    	if (IS_POST){
	    	$rd = array('status'=>-1);
	    	$openid = session ( 'WST_USER.wxOpenId' );
	    	$userId = (int)session('WST_USER.userId');   
	    	$my_user = M('users')->where(array('userId'=>$userId))->field('userMoney,distributMoney')->find();
	        $money = $mon > 0 ? $mon : I('drawMoney');
	        $targetType = is_numeric($type) ? $type : I('targetType');
	       
			//zzc 帐号禁用禁止提现
			$userStatus = M('users')->where('userId='.$userId)->getField('userStatus',1);
			if($userStatus == 0){
				$this->ajaxReturn(array('status'=>'1','msg'=>'帐号异常！'));
				exit;
			}
			//zzc end;
		    $draw_rate = $GLOBALS['CONFIG']['draw_rate']/100;//提现手续费
    	    if ($GLOBALS['CONFIG']['is_open'] == 0){
			    $rd['msg']='牛牛埋雷9号18点开放,敬请期待';
			    $this->ajaxReturn($rd);
			}
			if($money<1){
				$rd['msg'] = '提现金额最少大于1元!';
				$this->ajaxReturn($rd);
			}
    	   if ($targetType==0){
				if ($money > ($my_user['userMoney']*(1-$draw_rate))){
			    	$rd['msg'] = '提现金额大于可用余额,请重新填写~';
			    	$this->ajaxReturn($rd);
			    }
			    $remark = '可用资金提现,需扣除'.$GLOBALS['CONFIG']['draw_rate'].'%的手续费'.$money*$draw_rate;
			}else{
			    if ($money >= ($my_user['distributMoney']*(1-$draw_rate))){
			    	$rd['msg'] = '提现金额大于佣金余额,请重新填写~';
			    	 $this->ajaxReturn($rd);
			    }
			    $remark = '佣金提现,需扣除'.$GLOBALS['CONFIG']['draw_rate'].'%的手续费'.$money*$draw_rate;
			}
			
    	   if($GLOBALS['CONFIG']['cashStartMoney']>$money){
				$rd['msg'] = '对不起，最低提现金额'.$GLOBALS['CONFIG']['cashStartMoney'].'元';
				$this->ajaxReturn($rd);
			}
			
			$trade_cnt = M('trade')->where(array('user_id'=>$userId,'is_pay'=>'1'))->count();
			if ($trade_cnt < 2 && $targetType==0){
				$rd['msg'] = '首次提现必须实盘夺宝两次';
				$this->ajaxReturn($rd);
			}
			
			$addData = array();
	       	$addData['targetType']=$targetType;
	       	$addData['targetId']=$userId;
	       	$addData['money']=$money;
	       	$addData['change_money']=$money*0.01;
	       	$addData['cashSatus']='0';
	       	$addData['createTime']=date('Y-m-d H:i:s');
	       	$addData['cashRemarks']=$remark;
	       	$addData['draw_sn']=createTxSn();
	        $insert_id = M('cash_draws')->add($addData);
	        
	        if ($insert_id>0){
	        	if($GLOBALS['CONFIG']['is_check'] == 1)
				{
				   if ($targetType==0){
						M('users')->where(array('userId'=>$userId))->setInc('lockMoney',$money*1.01);
						M('users')->where(array('userId'=>$userId))->setDec('userMoney',$money*1.01);
					}else{
						M('users')->where(array('userId'=>$userId))->setInc('lockMoney',$money*1.01);
						M('users')->where(array('userId'=>$userId))->setDec('distributMoney',$money*1.01);
					}
					//提现需要审核
					$rd = array('status'=>'1','msg'=>'提交成功,请等待管理员审核~~');
				}else{
					//提现无需审核，处理提现
					//$this->ajaxReturn(array('status'=>'1','msg'=>'提交成功，现金正在飞奔到您的微信零钱'));
					$this->handle($addData['draw_sn']);
				}	
				
	        }else{
	        	$rd = array('status'=>'-1','msg'=>'提交失败,请重试~~');
	        }
			$this->ajaxReturn($rd);
			exit;
    	}else{
    		exit('非法请求');
    	}
	}
	
	
	/**
	 * 处理提现
	 */
	public function handle($draw_sn){
		$this->isLogin();
		$rd = array('status'=>-1,'msg'=>'操作失败!');
		$draw_rate = $GLOBALS['CONFIG']['draw_rate']/100;//提现手续费
		
		//判断提现记录状态
		//$sql="select * from __PREFIX__cash_draws where draw_sn=".$draw_sn;
		$rs = M('cash_draws')->where(array('draw_sn'=>$draw_sn))->find();
		
		$my_user = M('users')->where(array('userId'=>$rs['targetId']))->field('userId,wxOpenId,userMoney,distributMoney,redPacketMoney')->find();
		$wxopenid = $my_user['wxOpenId'];

		if(empty($rs)){
			$rd['msg'] = '无效的提现记录';
			$this->ajaxReturn($rd);
		}
		if($rs['cashSatus']==1){
			$rd['status'] = 1;
			$rd['msg'] = '提现记录已处理';
			$this->ajaxReturn($rd);
		}
		
	   if($rs['targetType']==1){
			if ($rs['money']*(1+$draw_rate) > $my_user['distributMoney']){
			    $rd['status'] = 1;
				$rd['msg'] = '佣金账户不足';
				$this->ajaxReturn($rd);
			}
		}else{
		    if ($rs['money']*(1+$draw_rate) > $my_user['userMoney']){
			    $rd['status'] = 1;
				$rd['msg'] = '可用资金不足';
				$this->ajaxReturn($rd);
			}
		}
		
		if($rs['cashSatus']==0){ 
					$commonutil = new \CommonUtil();
			    	$post_url = "https://api.mch.weixin.qq.com/mmpaymkttransfers/promotion/transfers";
			    	$post_data = array();
			    	$post_data['mch_appid'] = $this->wxpayConfig ['appid'];//商户账号appid
			    	$post_data['mchid'] = $this->wxpayConfig ['mchid'];//商户号
			    	$post_data['nonce_str'] = $commonutil->createNoncestr(32);;//随机字符串
			    	$post_data['partner_trade_no'] = $rs['draw_sn'];//商户订单号
			    	$post_data['check_name'] = 'NO_CHECK';//校验用户姓名选项
			    	$post_data['openid'] = $wxopenid;//用户openid
			    	$post_data['amount'] = $rs['money']*100;
			    	$post_data['desc'] = '用户提现';
			    	$post_data['spbill_create_ip'] = get_client_ip();//Ip地址
			    	
					$sign = $commonutil->getSign($post_data);
					$post_data['sign'] = $sign;
					
					$post_xml = $commonutil->arrayToXml($post_data);
					$result = $commonutil->postXmlSSLCurl($post_xml,$post_url);
					$data = $commonutil->xmlToArray($result);
		
			        if (empty($data) || !is_array($data)) {
			        	M('cash_draws')->delete($rs['cashId']);
			            $rd['msg']='企业付款接口有误,请稍候再试!';
			            $this->ajaxReturn($rd);
			       }
			         
			       if (isset($data['return_code']) && $data['return_code'] == 'SUCCESS' && isset($data['result_code']) && $data['result_code'] == 'SUCCESS') {
					    //扣除用户冻结金额
						if($rs['targetType']==1){
						   M('users')->where(array('userId'=>$rs['targetId']))->setDec('distributMoney',$rs['money']*(1+$draw_rate));
						   
						   $addData = array();
				       	   $addData['handleTime'] = date('Y-m-d H:i:s');
				       	   $addData['cashSatus'] = '1';
				       	   $addData['weipay_sn'] = $data['payment_no'];
				           M('cash_draws')->where(array('cashId'=>$rs['cashId']))->save($addData);
				           
				       	    $moneyRemark='用户佣金资金提现'.$rs['money'].'元,扣除手续费:'.$rs['money']*$draw_rate;
				       	   $userMoney = $my_user['userMoney'];
				       	   $redPacketMoney = $my_user['redPacketMoney'];
				       	   $distributMoney = $my_user['distributMoney']-($rs['money']*(1+$draw_rate)); 
				       	   addMoneylog($rs['targetId'], $moneyRemark, '3', $rs['cashId'], $rs['money'], $data['payment_no'], '2', '0', $userMoney, $redPacketMoney, $distributMoney);
						}
					    if($rs['targetType']==0){
							M('users')->where(array('userId'=>$rs['targetId']))->setDec('userMoney',$rs['money']*(1+$draw_rate));
							
							$addData = array();
				       	    $addData['handleTime'] = date('Y-m-d H:i:s');
				       	    $addData['cashSatus'] = '1';
				       	    $addData['weipay_sn'] = $data['payment_no'];
				            M('cash_draws')->where(array('cashId'=>$rs['cashId']))->save($addData);
							
							$moneyRemark='用户可用资金提现'.$rs['money'].'元,扣除手续费:'.$rs['money']*$draw_rate;
				       	    $userMoney = $my_user['userMoney']-($rs['money']*(1+$draw_rate));
				       	    $redPacketMoney = $my_user['redPacketMoney'];   
				       	    $distributMoney = $my_user['distributMoney'];
				       	    addMoneylog($rs['targetId'], $moneyRemark, '3', $rs['cashId'], $rs['money'], $data['payment_no'], '0', '0', $userMoney, $redPacketMoney, $distributMoney);
						}
			       	    $this->ajaxReturn(array('status'=>'1','msg'=>'提现成功!'));
			       }elseif(isset($data['return_code']) && $data['return_code'] == 'FAIL' && isset($data['result_code']) && $data['result_code'] == 'FAIL') {
			       	   M('cash_draws')->delete($rs['cashId']);
			       	   $rd['msg']=isset($data['err_code_des']) ? $data['err_code_des'] : '企业付款失败，请检查接口服务！';
			       	   $this->ajaxReturn($rd);
			       }else{
			       	   M('cash_draws')->delete($rs['cashId']);
			           $rd['msg'] = '提现失败，请稍后再试！'; 
			           $this->ajaxReturn($rd);
			       }
				$rd['status']= 1;
			    $rd['msg'] = '提现成功!';
                $this->ajaxReturn($rd);
		}
		$this->ajaxReturn($rd);
	}
	
}