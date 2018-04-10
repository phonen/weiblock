<?php
 namespace Home\Action;
/**

 */
class IndexAction extends BaseAction{
   public function index(){
          exit('hello');
   }

  public function notify(){
  	/*
  	 '====================================================================
  	 '
  	 '		本页是自动发货文件
  	 '		软件异步通知页面文件 (当用户付款成功后，软件自动调用此文件进行更新数据库（自动发货）等操作)
  	 '
  	 '*====================================================================
  	 '接收返回数据
  	 */

  	$MerchaOrder	=	$_GET['MerchaOrder'];	//'订单号
  	$Money			=	$_GET['Money'];			//用户充值的金额 (商家按此金额为客户发货)
  	$Payment		=	$_GET['Payment'];		//实际支付金额
  	$State			=	$_GET['State'];			//订单状态( 0处理中 1支付成功 2处理失败)
  	$BillID			=	$_GET['BillID'];		//网关订单号(微信)
  	$PayType		=	$_GET['PayType'];		//支付类型(WX微信支付)
  	$text			=	$_GET['text'];			//自定义参数(按原样返回)
  	$Sign			=	$_GET['Sign'];			//签名

  	/*签名验证
  	 '-----------------------------------------------------------------
  	 '参与签名的数据及格式(MerchaID+MerchaOrder+Money+Payment+State+MerchaKey)
  	 */
  	//此处请修改为自己的商户ID
  	$MerchaID	=	"18";
  	//此处请修改为自己的商户Key
  	$MerchaKey	=	"fnaonqrmoq1lpz7a";
  	$Sign_data = $MerchaID.$MerchaOrder.$Money.$Payment.$State.$MerchaKey;
  	if ($Sign != md5($Sign_data)){
  		//Echo("<ok>");
  		Echo("Sign Erro");
  		Die();
  	}

  	If ($State == "1"){
  		/*----------付款成功，更新数据库中该笔订单----------
  		 '*******************************************************************
  		 '给用户增加余额
  		 '处理完后返回接收到标识为 ok
  		 '只要商户接到通知，不管订单状态如何
  		 '都只需要返回接收到标识为 ok
  		 */
  		$Payment = Number_format($Payment, 2, '.','');
     	//截取订单号和userid
		$pos = stripos($text, '.');
		$userID = strstr($text, '.', true);
		$orderNum = substr($text, $pos+1, strlen($text)-$pos);
		$Temp = array();
		$Temp['user_id']=$userID;
		$Temp['pay_orderNum'] = $orderNum;
		$nTemp = M('recharge')->where($Temp)->select();
		if($nTemp > 0)
		{
			$saveTest['pay_result'] = $userID.$ordernum;
			$rs = M('recharge')->add($saveTest);
			//该订单已处理
			return;
		}
		
  	   if ($Payment <= 0){
		   $saveTest['pay_result'] = '金额无效';
			$rs = M('recharge')->add($saveTest);
		   $this->ajaxReturn(array('status'=>'-2' ,'msg'=>'充值金额无效!'));
		}
		
		$userInfo = M('users')->where(array('userId'=>$userID))->field('userId,loginName,userStatus,userType,userMoney,redPacketMoney,distributMoney')->find();
		if ($userInfo['userId'] <1){  
			$saveTest['pay_result'] = '用户不存在'.$userID;
			$rs = M('recharge')->add($saveTest);
		    $this->ajaxReturn(array('status'=>'-3' ,'msg'=>'用户不存在!'));
		}
		 	
		$condition = array();
		$condition['user_id']=$userID;
		$condition['pay_time'] = time();
		$cnt = M('recharge')->where($condition)->select();
		if ($cnt>0){
			$saveTest['pay_result'] = '已充值';
			$rs = M('recharge')->add($saveTest);
		 	return;
		}
		    
		$data1 = array ();
		$data1 ["user_id"] = $userInfo['userId']; //用户id
		$data1 ["recharge_sn"] = $MerchaOrder; //充值单号 
		$data1 ["recharge_money"] = $Payment; //充值金额
		$data1 ["receive_money"] = $Payment; //到账金额
		$data1 ["remark"] = '扫码支付';  
		$data1 ["add_time"] = time (); //充值时间
		$data1 ["pay_time"] = time ();
		$data1 ["recharge_status"] = 1; 
		$data1 ["pay_result"] = '充值成功';
		$data1 ["pay_orderNum"] = $BillID;
		$rs = M('recharge')->add($data1);
		
  	   if(false !== $rs){
		   $userMoney = $userInfo['userMoney']+$Payment;
		    $redPacketMoney = $userInfo['redPacketMoney'];
		    $distributMoney = $userInfo['distributMoney'];
		    //资金流水表
			$cur_money = $userInfo['userMoney']+$Payment;
			$data = array();
			$data["targetType"] = 0;
			$data["targetId"] = $userInfo['userId'];
		    $data["moneyRemark"] = '成功充值:'.$Payment;
			$data["dataSrc"] = 2;//充值订单
			$data["dataId"] =  $rs;
			$data["money"] = $Payment;
			$data["transactionId"] = $data1 ["recharge_sn"];
			$data["type"] = 0;
			$data["moneyType"] = 1;
			$data["createTime"] = time();
			$data["userMoney"] = $cur_money;
			$data["redPacketMoney"] = $userInfo['redPacketMoney'];
			$data["distributMoney"] = $userInfo['distributMoney'];
			M('log_moneys')->add($data);
		    M('users')->where(array('userId'=>$userID))->setInc('userMoney',$Payment);
		}
		exit('123456');
  		echo("<ok>");
  		echo("客户 ".$_GET['UserID']." 成功支付 $Payment 元<br>");
  		die();
  	}else{
  		Echo("ok");
  		Echo("Order Erro");
  	}
  }
}