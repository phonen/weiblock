<?php
 namespace Admin\Action;
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
	 * 跳去提现页面
	 */
	public function toHandle(){
		$this->isLogin();
		$this->checkPrivelege('txgl_04');
		$rs = D('Admin/CashDraws')->get();
		$this->assign('object',$rs);
		$this->view->display('/moneys/handle');
	}
	
	/**
	 * 申请提现
	 */
	public function handle(){
		$this->isLogin();
		$this->checkPrivelege('txgl_04');
		$id = (int)I('id');
		$rd = array('status'=>-1,'msg'=>'操作失败!');
		//判断提现记录状态
		$sql="select * from __PREFIX__cash_draws where cashId=".$id;
		$rs = M('cash_draws')->where(array('cashId'=>$id))->find();

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
		
		//if ($rs['targetType']>0){
		//	if ($rs['money']>($my_user['distributMoney']*1.1)){
		//		$rd['msg'] = '用户的佣金账户不足,暂时不能提现';
		//	    $this->ajaxReturn($rd);
		//	}
		//}else{
		//    if ($rs['money']>($my_user['userMoney']*1.1)){
		//		$rd['msg'] = '用户的可用资金账户不足,暂时不能提现';
		//	    $this->ajaxReturn($rd);
		//	}
		//}
		
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
			            $rd['msg']='企业付款接口有误,请稍候再试!';
			            $this->ajaxReturn($rd);
			       }
			         
			       if (isset($data['return_code']) && $data['return_code'] == 'SUCCESS' && isset($data['result_code']) && $data['result_code'] == 'SUCCESS') {
					    //扣除用户冻结金额
						if($rs['targetType']==1){
						   M('users')->where(array('userId'=>$rs['targetId']))->setDec('lockMoney',$rs['money']*1.01);
						   
						   $addData = array();
				       	   $addData['handleTime'] = date('Y-m-d H:i:s');
				       	   $addData['cashSatus'] = '1';
				       	   $addData['weipay_sn'] = $data['payment_no'];
				           M('cash_draws')->where(array('cashId'=>$rs['cashId']))->save($addData);
				           
				       	   $moneyRemark='用户佣金资金提现'.$rs['money'].'元,扣除手续费:'.$rs['money']*0.1;
				       	   $userMoney = $my_user['userMoney'];
				       	   $redPacketMoney = $my_user['redPacketMoney'];
				       	   $distributMoney = $my_user['distributMoney']; 
				       	   addMoneylog($rs['targetId'], $moneyRemark, '3', $rs['cashId'], $rs['money'], $data['payment_no'], '2', '0', $userMoney, $redPacketMoney, $distributMoney);
						}
					    if($rs['targetType']==0){
							M('users')->where(array('userId'=>$rs['targetId']))->setDec('lockMoney',$rs['money']*1.01);
							
							$addData = array();
				       	    $addData['handleTime'] = date('Y-m-d H:i:s');
				       	    $addData['cashSatus'] = '1';
				       	    $addData['weipay_sn'] = $data['payment_no'];
				            M('cash_draws')->where(array('cashId'=>$rs['cashId']))->save($addData);
							
							$moneyRemark='用户可用资金提现'.$rs['money'].'元,扣除手续费:'.$rs['money']*0.1;
				       	    $userMoney = $my_user['userMoney'];
				       	    $redPacketMoney = $my_user['redPacketMoney'];   
				       	    $distributMoney = $my_user['distributMoney'];
				       	    addMoneylog($rs['targetId'], $moneyRemark, '3', $rs['cashId'], $rs['money'], $data['payment_no'], '0', '0', $userMoney, $redPacketMoney, $distributMoney);
						}
			       	    $this->ajaxReturn(array('status'=>'1','msg'=>'提现成功~'));
			       }elseif(isset($data['return_code']) && $data['return_code'] == 'FAIL' && isset($data['result_code']) && $data['result_code'] == 'FAIL') {
			       	   $rd['msg']=isset($data['err_code_des']) ? $data['err_code_des'] : '企业付款失败，请检查接口服务！';
			       	   $this->ajaxReturn($rd);
			       }else{
			           $rd['msg'] = '提现失败，请稍后再试！'; 
			           $this->ajaxReturn($rd);
			       }
				$rd['status']= 1;
			    $rd['msg'] = '操作成功!';
                $this->ajaxReturn($rd);
		}
		$this->ajaxReturn($rd);
	}
	
	/**
	 * 获取提现记录列表
	 */
	public function index(){
		$this->isLogin();
		$this->checkPrivelege('txgl_00');
		$page = D('Admin/CashDraws')->queryByPage();
		$pager = new \Think\Page($page['total'],$page['pageSize'],I());
		$page['pager'] = $pager->show();
		$this->assign('Page',$page);
		$this->assign('targetType',(int)I('targetType',-1));
		$this->assign('startDate',I('startDate'));
		$this->assign('endDate',I('endDate'));
		
		$total = M('cash_draws')->where(array('cashSatus'=>'1'))->sum('money');
		$this->assign('total',$total);
		
		$this->view->display('/moneys/list_draws');
	}

};
?>