<?php
namespace BaJie\Action;
/**
 * ============================================================================

 * ============================================================================
 * 会员充值控制器
 */
class RechargeAction extends BaseAction {
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
  
		$this->wxpayConfig = C ( 'WxPayConf2' ); //读取config文件
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

	public function index(){   
	    $this->display ( "default/users/recharge/recharge3" ); 
	}

	/**
	 * 充值
	 */
	public function create() {
		$m = D ( 'WeChat/Recharge' );
		$rs = $m->create ();
		$this->ajaxReturn ( $rs );
	}
	
	/**
	 * 微信支付
	 */
	public function toPay() {
		$this->isLogin();   
		$m = D ( 'WeChat/Recharge' );
		$condition = array ();
		$condition['user_id'] = session ( 'WST_USER.userId' );
		$condition['recharge_id'] = I ( 'recharge_id' );
		$re = M('Recharge')->where ( $condition )->find();

		if (empty ( $re )) {
			$this->redirect ( 'Users/index' );
		}
		$pm = D ( 'WeChat/Payments' );
		$openid = session ( 'WST_USER.wxOpenId' );
		//使用jsapi接口
		$jsApi = new \JsApi ();
		//使用统一支付接口
		$unifiedOrder = new \UnifiedOrder ();
		$unifiedOrder->setParameter ( "openid", $openid );
		//自定义订单号，此处仅作举例
		$timeStamp = time ();
		$out_trade_no = $timeStamp;
		$unifiedOrder->setParameter ( "out_trade_no", $re ['recharge_sn'] ); 
		$unifiedOrder->setParameter ( "notify_url", $this->wxpayConfig ['NOTIFY_URL'] ); 
		$unifiedOrder->setParameter ( "trade_type", "JSAPI" );
		$unifiedOrder->setParameter ( "body", "支付充值订单" );
		if ($GLOBALS['CONFIG']['limit_pay']>0){
		   $unifiedOrder->setParameter ( "limit_pay", "no_credit" ); 
		}
		$total_fee = $re['recharge_money'] * 100;
		$unifiedOrder->setParameter ( "total_fee", $total_fee ); //总金额     
		$userId = ( int ) session ( 'WST_USER.userId' );
		$this->assign ( 'needPay', $total_fee );
		$this->assign ( 'returnUrl', $this->wxpayConfig ['returnurl'] );
		$prepay_id = $unifiedOrder->getPrepayId ();
		//=========步骤3：使用jsapi调起支付============
		$jsApi->setPrepayId ( $prepay_id );
		
		$jsApiParameters = $jsApi->getParameters ();
		$this->assign ( 'jsApiParameters', $jsApiParameters );
		$this->assign ( 'account', $re ['recharge_money'] );
		$this->assign ( 'recharge_sn', $re ['recharge_sn'] );
		$this->display ( "default/users/recharge/topay" );
	}
	
	/**
	 * 异步处理
	 */
	public function notify() {
		// 使用通用通知接口
		$notify = new \Notify ();
		// 存储微信的回调
		$xml = $GLOBALS ['HTTP_RAW_POST_DATA'];
		
		$file = NOTIFY_PATH . "/weipay-js_api-call-" . date ( 'Ymd' ) . ".txt";
		WSTLog ( $file, $xml );
		//将微信的请求xml转换成关联数组，以方便数据处理
		$notify->saveData ( $xml );
			
		$recharge_sn = $notify->data ["out_trade_no"]; //订单号
		$total_fee = $notify->data ["total_fee"];
		$transaction_id = $notify->data ["transaction_id"]; //微信交易号
		
		$m = D ( 'Wechat/Recharge' );
		$check = $m->where ( array ('recharge_sn' => $recharge_sn ) )->find ();
		if ($check ['recharge_id'] < 1) {
			exit ( 'Recharge Not Found.' );
		}
		if ($total_fee != strval ( $check ['recharge_money'] * 100 )) {
			exit ( "recharge_money Is Wrong." );
		}
		if ($check['recharge_status']=='1'){
		    exit ( "this recharge_statrs eg 1." );
		}
		$check['weipay_sn'] = $transaction_id;
		if ($notify->checkSign () == FALSE) {
			$notify->setReturnParameter ( "return_code", "FAIL" ); // 返回状态码
			$notify->setReturnParameter ( "return_msg", "签名失败" ); // 返回信息
		} else {
			$notify->setReturnParameter ( "return_code", "SUCCESS" ); // 设置返回码
		}
		$returnXml = $notify->returnXml ();
		echo $returnXml;
		
		if ($notify->checkSign () == TRUE) {
			if ($notify->data ["return_code"] == "FAIL") {
				// 此处应该更新一下订单状态，商户自行增删操作
			} elseif ($notify->data ["result_code"] == "FAIL") {
				// 此处应该更新一下订单状态，商户自行增删操作
			} else {
				$m = D('WeChat/Recharge');
				$m->complete($check);
				echo 'success';
			}
		}
	}
  
	public function test(){
	   exit('123');
	}
	
  //支付
  public function youxinPay(){
  	$this->isLogin();
  	layout(false);
  	if (IS_POST){
  		if($GLOBALS['CONFIG']['open_yybpay'] == 0){
  		   $this->ajaxReturn(array('status'=>'-1','msg'=>'当前支付通道已关闭，请选择其他支付方式'));
  		}
  		
  		$pid = $GLOBALS['CONFIG']['yyb_appid'];
  		
        $money = I('fee');
  	    $data = array ();
		$data ["user_id"] = ( int ) session ( 'WST_USER.userId' ); //用户id
		$data ["recharge_sn"] = createRecharge (); //充值单号 
		$data ["recharge_money"] = $money; //充值金额
		$data ["add_time"] = time (); //充值时间
		$rv = array ('status' => - 1 );
		$rs = M ( 'recharge' )->add ( $data );
		if (false !== $rs) {
			$postdata = array();
			$postdata['pid'] = $pid;
			$postdata['money'] = $money;
			$postdata['lb'] = '3';
			$postdata['data'] = $data["recharge_sn"];
			$postdata['m'] = '1';
			$postdata['url'] = 'http://'.$_SERVER['HTTP_HOST'].U('Recharge/index');
			$postdata['bk'] = '1';
			$url = 'http://pay2.youyunnet.com/pay?pid='.$postdata['pid'].+'&money='.$postdata['money'].'&lb=3&bk=1&data='.$postdata['data'];
		    $rv = array ('status' => 1, 'msg' => '充值订单生成成功,正在跳转到支付页面', 'post_url' =>'http://pay1.youyunnet.com/pay','postdata'=>$postdata,'url'=>$url);
		} else {
			$rv = array ('status' => - 1, 'msg' => '充值订单生成失败,请重试');
		}
       
       $this->ajaxReturn($rv);
  	}else{
  	   exit('error');
  	}
  }
  
    //支付回调
  public function youxinNotify(){
  	layout(false);
  	if (IS_POST){
  		$ddh = I('ddh'); //支付宝订单号
  		$key = I('key'); //KEY验证
  		$name = I('name'); //备注信息  接收网关data 参数  支付订单号
  		$lb = I('lb'); //分类 =1 支付宝 =2财付通 =3 微信
  		$money = I('money');//金额
  		$paytime = I('paytime');//充值时间
  		$key2 = $GLOBALS['CONFIG']['yybpay_appsecretkey'];//APPKEY 和云端和软件上面保持一致
  		if($key == $key2){
  			$check = M('recharge')->where(array('recharge_sn'=>$name))->find();
  			$user = M('users')->where(array('userId'=>$check['user_id']))->field('userId,loginName,userStatus,userType,userMoney,redPacketMoney,distributMoney')->find();
  			if ($check['recharge_id']<1){//无效的订单号不用再请求
  			   M('recharge')->where(array('recharge_sn'=>$name))->save(array('pay_result'=>'无效订单'));
  			   exit('ok');
  			}elseif ($check['recharge_status'] == 1){//已支付不用再请求
  			   M('recharge')->where(array('recharge_sn'=>$name))->save(array('pay_result'=>'订单已支付'));
  			   exit('ok');
  			}elseif ($check['recharge_money'] !== $money){
  			   M('recharge')->where(array('recharge_sn'=>$name))->save(array('pay_result'=>'你好坏,请找管理员解释'));
  			   exit('ok');
  			}elseif ($money < 0){
  			   M('recharge')->where(array('recharge_sn'=>$name))->save(array('pay_result'=>'金额无效'));
  			   exit('ok');
  			}elseif ($user['userId'] !== $check['user_id']){
  			   M('recharge')->where(array('recharge_sn'=>$name))->save(array('pay_result'=>'用户无效'));
  			   exit('ok');
  			}
	  		$condition = array();
			$condition['user_id']=$check['user_id'];
			$condition['pay_time'] = time();
			$cnt = M('recharge')->where($condition)->select();
			
			if ($cnt>0){
				$saveTest['pay_result'] = '已充值';
				$rs = M('recharge')->add($saveTest);
			 	exit('ok');
			}
  			
  			/**增加资金开始*/
  			$userInfo = M('Users')->where(array('userId'=>$check['user_id']))->field('redPacketMoney,distributMoney,userMoney')->find();
			//资金流水表
		    $lm = M('log_moneys');
		    $data = array();
		    $data["targetType"] = 0;
		    $data["targetId"] = $check['user_id'];
		    $data["moneyRemark"] = '通过转账支付成功充值:'.$check["recharge_money"];
		    $data["dataSrc"] = 2;//充值订单
		    $data["dataId"] =  $check['recharge_id'];
		    $data["money"] = $check["recharge_money"];
		    $data["transactionId"] = $check['recharge_sn'];
		    $data["type"] = 0;
		    $data["moneyType"] = 1;
		    $data["createTime"] = strtotime($paytime);
		    $data["userMoney"] = $userInfo['userMoney']+$check['recharge_money'];
		    $data["redPacketMoney"] = $userInfo['redPacketMoney'];
		    $data["distributMoney"] = $userInfo['distributMoney'];
		    $rd = $lm->add($data);
		    M( 'Users' )->where ( array('userId'=>$check['user_id']) )->setInc ( 'userMoney', $check["recharge_money"]);
  			/**增加资金开始*/
  			
  			//验证KEY是否正确
  			if($lb==1) $leibie='支付宝';//可根据网站自定义数据
  			if($lb==2) $leibie='财付通QQ钱包';//可根据网站自定义数据
  			if($lb==3) $leibie='微信支付';//可根据网站自定义数据
  			$updateData = array();
  			$updateData['pay_time'] = $_POST['paytime'];
  			$updateData['recharge_status'] = '1';
  			$updateData['receive_money'] = $money;
  			$updateData['weipay_sn'] = $ddh;
  			$updateData['remark'] = '优云宝支付成功';
  			M('recharge')->where(array('recharge_sn'=>$name))->save($updateData);
  			/*
  			 此处执行你的程序逻辑 回执成功后
  			 1、可以做成 判断支付宝订单号是否存在来完成充值
  			 2、还可以做成 判断网站订单号(name)来完成充值
  			 3、请做好订单号充值判断
  			 */
  			//执行完毕回执输出ok 字符
  			echo "ok";
  		}else{
  			//密匙错误
  			echo 'key error';
  		}
  	}
  }
  /**
   * 二维码收款
   * */
  public function pay(){
  	$amount = I('Amount');
  	$order_id = I('order_id');
  	//$amount = '10'; 
  	
  	$MerchaOrder	= createRecharge ();		//商家订单号
  	$Money			= $amount;		//订单金额,不可由客户自己填写
  	$UserID			= ( int ) session ( 'WST_USER.userId' );		//一定要是客户的ID
  	$Text			= $order_id;	//商家扩展内容(可为空,如包含中文请先URL编码),按原样返回
  	     
  	if (empty($amount)){
  	    $this->redirect(U('Recharge/index'));
  	}

  	//此处请修改为自己的商户ID
  	$MerchaID	=	"18";

  	//此处请修改为自己的商户Key
  	$MerchaKey	=	"fnaonqrmoq1lpz7a";

  	//后台通知地址,订单完成后服务器将充值状态通知到这地址
  	$CallUrl = 'http://'.$_SERVER['SERVER_NAME'].'/index.php/Api/Index/notify';

  	$Sig = "MerchaID=$MerchaID&MerchaOrder=$MerchaOrder&Money=$Money&UserID=$UserID&CallUrl=$CallUrl";
  	$Sig = strtolower($Sig);
  	$Sign = md5($Sig.$MerchaKey);
  	$Url = "http://www.hmoo.net/pay/?$Sig&sign=$Sign&text=$Text";
  	header("Location: $Url");
  }
  
  /**
   * 码支付
   * */
  public function codePay($money = 0){
  	$codepay_id = $GLOBALS['CONFIG']['codepay_id'];//码支付ID
  	$codepay_key = $GLOBALS['CONFIG']['codepay_key'];//通信密钥
  	$token = $GLOBALS['CONFIG']['codepay_token'];
  	
  	$amount = $money > 0 ? $money : I('Amount') ;
  	$userId = ( int ) session ( 'WST_USER.userId' );
  	
  	$call_url = 'http://'.$_SERVER['SERVER_NAME'].'/index.php/Api/Index/codepayNotify';
  	$return_url = 'http://'.$_SERVER['SERVER_NAME'].'/index.php/BaJie/Recharge/index';

  	$data = array(
	    "id" => $codepay_id,//你的码支付ID
	    "pay_id" => $userId, //唯一标识 可以是用户ID,用户名,session_id(),订单ID,ip 付款后返回
	    "type" => 3,//1支付宝支付 3微信支付 2QQ钱包
	    "price" => $amount,//金额100元
	    "param" => createRecharge (),//自定义参数(订单号)
	    "notify_url" => $call_url,//通知地址
	    "return_url"=> $return_url,//跳转地址
  	); //构造需要传递的参数
  
  	ksort($data); //重新排序$data数组
  	reset($data); //内部指针指向数组中的第一个元素

  	$sign = ''; //初始化需要签名的字符为空
  	$urls = ''; //初始化URL参数为空

  	foreach ($data AS $key => $val) { //遍历需要传递的参数
    if ($val == ''||$key == 'sign') continue; //跳过这些不参数签名
    if ($sign != '') { //后面追加&拼接URL
    	$sign .= "&";
    	$urls .= "&";
    }
    $sign .= "$key=$val"; //拼接为url参数形式
    $urls .= "$key=" . urlencode($val); //拼接为url参数形式并URL编码参数值

  	}
  	$query = $urls . '&sign=' . md5($sign .$codepay_key); //创建订单所需的参数
  	$url = "http://api2.fateqq.com:52888/creat_order/?{$query}"; //支付页面

  	header("Location:{$url}"); //跳转到支付页面
  }
  
  public function zspay($money = 0){
	$userId = ( int ) session ( 'WST_USER.userId' );
	$currentdate = date('Y') + date('m')  + date('d') + date('H')  + date('i') + date('s');
	$nTemp = $money + (rand(1,9)/100);
	$url = 'http://zspay.cn/pay.php?appid=920&tyid=2&zh='.$userId.".".$currentdate.'&jine='.$nTemp;
	header("Location:{$url}"); //跳转到支付页面
  }
  public function bnvpay($money = 0){
	  
	$recharge_sn=createRecharge ();
	  
    $url = "http://api.bnvpay.cn/waporder/order_add";
	$mch='626913879951';
	$key='07d1e9b135b6737bc39b11a7e16aef72';
	
	
	$data = array ();
	$data ["user_id"] = ( int ) session ( 'WST_USER.userId' ); //用户id
	$data ["recharge_sn"] = $recharge_sn; //充值单号 
	$data ["recharge_money"] = $money; //充值金额
	$data ["add_time"] = time (); //充值时间
	$rv = array ('status' => - 1 );
	$rs = M ( 'recharge' )->add ( $data );
	if (false !== $rs) {
		$data = array();
		$data["mch"]=$mch;
		$data["key"]=$key;
		$data["order_id"]=$recharge_sn;
		$data["money"]=$money*100;
		
		$call_url = 'http://'.$_SERVER['SERVER_NAME'].'/index.php/Api/Index/hnvpayNotify';
		$return_url = 'http://'.$_SERVER['SERVER_NAME'].'/index.php/BaJie/Recharge/index';
		
		$data["return_url"]=$return_url;
		$data["notify_url"]=$call_url;
		$data["pay_type"]='qqwap';//支付方式。要换成什么方式就在这里改
		$data["extra"]='';
		$data["time"]=time();
		$key=md5($data["order_id"].$data["money"].$data["pay_type"].$data["time"].$data["mch"].md5($data["key"]));
		$data["sign"]=$key;
			
		$test=$this->arrysgin($data);
		$url = $url."?".$test;
		//echo $url;exit;
		header("Location: $url");
		die;
	} else {
		$rv = array ('status' => - 1, 'msg' => '充值订单生成失败,请重试');
	}

	$this->ajaxReturn($rv);
  }
    private function arrysgin($array){
        $topicid = ' ';
        foreach ($array as $key=>$val){
            $topicid.=$key."=".$val."&";
        }
        $topicid = rtrim($topicid, '&');
        return $topicid;
    }
  
  
  
  //转发处理
  public function payto(){
		$money = I('money');
		$type = I('type');
		if($money <= 0) return false;
		if($type == 3){
			$this->codePay($money);
		}elseif($type == 4){
			$this->bnvpay($money);
		}else{
			$this->zspay($money);
		}
  }
  
}