<?php
 namespace BaJie\Action;;
/**
 * 支付控制器
 */
class PaymentsAction extends BaseAction{
	
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
		
		$this->wxpayConfig = C ( 'WxPayConf' );
		$this->wxpayConfig ['appid'] = $this->wxpay ['appId']; // 微信公众号身份的唯一标识
		$this->wxpayConfig ['appsecret'] = $this->wxpay ['appsecret']; // JSAPI接口中获取openid
		$this->wxpayConfig ['mchid'] = $this->wxpay ['mchId']; // 受理商ID
		$this->wxpayConfig ['key'] = $this->wxpay ['apiKey']; // 商户支付密钥Key

		$this->wxpayConfig ['sslcert_path'] = $this->wxpayConfig ['SSLCERT_PATH']; 
		$this->wxpayConfig ['sslkey_path'] = $this->wxpayConfig ['SSLKEY_PATH']; 
		$this->wxpayConfig ['curl_timeout'] = $this->wxpayConfig ['CURL_TIMEOUT'];
		$this->wxpayConfig ['notifyurl'] = $this->wxpayConfig ['NOTIFY_URL'];
		$this->wxpayConfig ['returnurl'] =  $this->wxpayConfig ['RETURN_URL'];
		
		// 初始化WxPayConf
		new \WxPayConf ( $this->wxpayConfig );
	}
    
	public function toPay(){
		$pm = D ( 'WeChat/Payments' );
		$key = WSTAddslashes(I("key"));
        $openid = session('WST_USER.wxOpenId');
        $extras =  explode ( "@",$key);
        $obj["oId"] = $extras[0];
        $obj["out_trade_no"] = $extras[1];
        $orders = D('WeChat/Orders');
        $payordersList = $orders->payOrderList($obj);
        $totalAmount=$payordersList["totalAmount"];
        $this->assign('payordersList',$payordersList);
        //使用jsapi接口
        $jsApi = new \JsApi();
        //使用统一支付接口
        $unifiedOrder = new \UnifiedOrder();
        $unifiedOrder->setParameter("openid",$openid);//商品描述

        //自定义订单号，此处仅作举例
        $timeStamp = time();
        $out_trade_no = $timeStamp;
        $unifiedOrder->setParameter("out_trade_no",$out_trade_no);//商户订单号
        $unifiedOrder->setParameter("notify_url",$this->wxpayConfig ['notifyurl']);//通知地址
        $unifiedOrder->setParameter("trade_type","JSAPI");//交易类型

        $unifiedOrder->setParameter("body","支付订单");//商品描述
       	$needPay = WSTBCMoney($totalAmount,0,2);
       	$unifiedOrder->setParameter("total_fee", $needPay * 100);//总金额
       	$userId = (int)session('WST_USER.userId');
       	$attach = $userId."@".$key;
        $this->assign('needPay',$needPay);
        $this->assign('returnUrl',$this->wxpayConfig ['returnurl'] );
       
        $unifiedOrder->setParameter("attach",$attach);//附加数据

        $prepay_id = $unifiedOrder->getPrepayId();
        //=========步骤3：使用jsapi调起支付============
        $jsApi->setPrepayId($prepay_id);
        
        $jsApiParameters = $jsApi->getParameters();
        $this->assign('jsApiParameters',$jsApiParameters);
        $this->display('default/wx_pay');
    }
	
	public function notify() {
		// 使用通用通知接口
		$notify = new \Notify();
		// 存储微信的回调
		$xml = $GLOBALS ['HTTP_RAW_POST_DATA'];
		$notify->saveData ( $xml );
		if ($notify->checkSign () == FALSE) {
			$notify->setReturnParameter ( "return_code", "FAIL" ); // 返回状态码
			$notify->setReturnParameter ( "return_msg", "签名失败" ); // 返回信息
		} else {
			$notify->setReturnParameter ( "return_code", "SUCCESS" ); // 设置返回码
		}
		$returnXml = $notify->returnXml ();
		if ($notify->checkSign () == TRUE) {
			if ($notify->data ["return_code"] == "FAIL") {
				// 此处应该更新一下订单状态，商户自行增删操作
			} elseif ($notify->data ["result_code"] == "FAIL") {
				// 此处应该更新一下订单状态，商户自行增删操作
			} else {
				$pm = D ( 'WeChat/Payments' );
				$order = $notify->getData ();
				$this->process($order);
				echo 'success';
			}
		}
	}
	
	//订单处理
    private function process($order) {
    	$pm = D ( 'WeChat/Payments' );
    	$obj = array();
    	$obj["trade_no"] = $order['transaction_id'];
    	
    	$obj["total_fee"] = $order["total_fee"]/100;
    	$extras =  explode ( "@", $order ["attach"] );
    	$obj["userId"] = $extras[0];
    	$obj["out_trade_no"] = $extras[1];
    	$obj["order_type"] = $extras[2];
    	$obj["payFrom"] = 1;
    	//支付成功业务逻辑
    	$payments = $pm->complatePay($obj);
        return true;
    }
    /**
     * 获取商城支付方式
     */
    public function getPayType(){
    	$m = D('WeChat/Payments');
    	$rs = $m->getPayType();
    	$this->ajaxReturn($rs);
    }
};
?>