<?php
namespace WeChat\Action;
/**
 * 会员控制器
 */
class UsersAction extends BaseAction {
	/**
	 * 会员中心入口
	 */
  public function index(){  
  	//layout(false);   
    $users = D('WeChat/Users');
    $usersId = (int)session('WST_USER.userId');  
    session('WST_SHAREUSERID', I("shareUserId"));//保存shareUserId
    
    $cur_url = "http://".$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
    session('back_url',$cur_url);
    if($usersId){
      $info = M('users')->where(array('userId'=>$usersId))->field('userMoney,redPacketMoney,isBuyer,loginName,is_first_login,userId,play_count')->find();
      $userStatus = M('users')->where('userId='.$usersId)->getField('userStatus',1);
$usertx = M('users')->where('userId=' . $usersId)->find();
	 //zzc 增加被禁用无法登录系统
	 if(!$usertx){
                session_destroy();
                redirect('/');
            }
            elseif($userStatus == 0){
		$this->display("default/users/stop"); 
	  }else{
		$this->assign('info', $info);
		$this->display("default/users/home");   
	  }	
	 //zzc end
	  //$this->assign('info', $info);
      //$this->display("default/users/home");    
    }else{//snsapi_userinfo   
    	$url=urlencode($_SERVER["SERVER_NAME"].$_SERVER["REQUEST_URI"]); 
    	$url='https://open.weixin.qq.com/connect/oauth2/authorize?appid='.$GLOBALS['CONFIG']['WeiXinAppId'].'&redirect_uri=http%3a%2f%2f'.$url.'&response_type=code&scope=snsapi_userinfo&state='.$GLOBALS['CONFIG']['WeiXinAppCode'].'#wechat_redirect';
		redirect($url);
    } 
  }  
  /* ====================个人信息页面================================================= */
  /**
   * 个人信息页面
   */
  public function toUserInfo(){
  	$this->isLogin();
  	
  	$this->display("default/users/user_info_new");
  }
  
  /**
   * 联系方式
   */
  public function contact(){
  	$this->isLogin();
  	$this->display("default/users/contact");
  }
  
   /**
   * 各项明细
   */
  public function dealDetails(){
  	$this->isLogin();
  	//资金流水
  	$moneys = M('log_moneys')->where(array('targetId'=>(int)session('WST_USER.userId')))->limit('10')->order('moneyId desc')->select();
  	//交易
  	$trades = M('trade')->where(array('user_id'=>(int)session('WST_USER.userId'),'is_pay'=>'1'))->limit('10')->order('trade_id desc')->select();
  	foreach ($trades as $k => $v){
  		if ($v['type']>0){
  		   $trades[$k]['selectd_num'] = implode(',', json_decode($v['selectd_num']));   
  		}
  	}
  	$this->assign('trades',$trades);
  	$this->assign('moneys',$moneys);
    $this->display("default/users/dealDetails");
  }
  
  /**
   *常见问题 
   **/
  public function problem(){
  	$this->isLogin();
    $this->display("default/users/problem");
  }
  
  /**
   *每日签到
   **/
  public function sign(){
  	$this->isLogin();
  	 if(IS_POST){
  	 	$today = getdate ();
  	 	$today_start_time = mktime ( 0, 0, 0, $today ['mon'], $today ['mday'], $today ['year'] );
  	 	$sign_in_money = $GLOBALS['CONFIG']['sign_in_money'];
  	 	
  	 	$userId = session('WST_USER.userId');
  	 	$condition = array(); 
  	 	$condition['targetId']=$userId;
  	 	$condition['createTime']=array('gt',$today_start_time);
  	 	$condition['dataSrc']='7';
  	 	$check = M('log_moneys')->where($condition)->find();
  	 	if ($check['moneyId'] < 1){
  	 		$moneyRemark = '每日签到获得'.$sign_in_money.'元红包奖励';
  	 		$redPacketMoney = $this->user['redPacketMoney']+$sign_in_money;
  	 		$userMoney = $this->user['userMoney'];
  	 		$distributMoney = $this->user['distributMoney'];
  	 	    addMoneyLog($userId, $moneyRemark, '7', '0', $sign_in_money, '0', '1', '1',$userMoney,$redPacketMoney,$distributMoney);
  	 	    M('Users')->where(array('userId'=>$userId))->setInc('redPacketMoney',$sign_in_money);
  	 	    $this->ajaxReturn(array('status'=>'1','msg'=>'签到成功!'));
  	 	}else{
  	 	    $this->ajaxReturn(array('status'=>'0','msg'=>'您已签到,请明天再来!'));
  	 	}
  	 }
     $this->display("default/users/sign");
  }
  
  /**
   *首次登录奖励
   **/
  public function first(){
  	$this->isLogin();
  	if(IS_POST){
  		$userId = $this->user['userId'];
  	    if ($this->user['is_first_login']==0){
  	    	$money = $GLOBALS['CONFIG']['first_login_money'];
  	        $result = M('Users')->where(array('userId'=>$userId))->save(array('is_first_login'=>1));
  	        if ($result > 0){
  	           $money = $GLOBALS['CONFIG']['first_login_money'];
  	           $moneyRemark = '首次登录获得'.$money.'元红包奖励';
  	           $dataSrc = '6';
  	           $type = '1';
  	           $moneyType = '1';
  	           $redPacketMoney = $this->user['redPacketMoney']+$money;
	  	 	   $userMoney = $this->user['userMoney'];
	  	 	   $distributMoney = $this->user['distributMoney'];
  	           addMoneyLog($userId, $moneyRemark, $dataSrc, 0, $money, 0, $type, $moneyType,$userMoney,$redPacketMoney,$distributMoney);
  	           
  	           M('Users')->where(array('userId'=>$this->user['userId']))->setInc('redPacketMoney',$money);
  	           $my_user = M('users')->where(array('userId'=>$this->user['userId']))->field('userMoney,redPacketMoney')->find();
  	           $cur_money = $my_user['userMoney']+$my_user['redPacketMoney'];
  	           $this->ajaxReturn(array('status'=>'1','msg'=>'领取成功','cur_money'=>$cur_money));
  	        }
  	    } 
  	    $this->ajaxReturn(array('status'=>'0','msg'=>'已领取过!!!'));
  	}
  }
 /**=====================================================================**/
  /**
   * 代理佣金提现界面
   **/
  public function course(){
  	$this->isLogin();
    $this->display("default/users/course");
  }
  
  /**  
   * 代理界面
   **/
  public function agent(){
  	 $this->isLogin();
  	 vendor("phpqrcode.qrlib");
     $this->user['userId'];
     $uid = $this->user['userId'];
     //$file = $this->setQrcode($uid);
     //$this->assign('qrcode',$file);
       
     //下线佣金列表
     $condition = array();
     $condition['userId']=$uid;
     $distribut_moneys = M('distribut_moneys')->where($condition)->select();
     $this->assign('distribut_moneys',$distribut_moneys);
     //佣金统计
     //$recommend_info = D('WeChat/DistributMoneys')->dataCount(5);
     $res = D('WeChat/Users')->getRecommedInfo();
     $this->assign('res',$res);
     
        $today = getdate ();
		//昨天结束时间
		/*$yesterday_start_time = mktime ( 0, 0, 0, $today ['mon'], $today ['mday'] - 1, $today ['year'] );
		//昨天结束时间
		$yesterday_end_time = mktime ( 23, 59, 59, $today ['mon'], $today ['mday'] - 1, $today ['year']);*/
		//今天开始时间
		$today_start_time = mktime ( 0, 0, 0, $today ['mon'], $today ['mday'], $today ['year'] );
		//今天结束时间
		$today_end_time = mktime ( 23, 59, 59, $today ['mon'], $today ['mday'], $today ['year'] );
		
		$begin = date('Y-m-d H:i:s',$today_start_time);
        $end = date('Y-m-d H:i:s',$today_end_time);
        
		$condition = array();
		$condition['userId'] = (int)I('id');
		$condition['createTime'] = array('between', "$begin,$end");
		$distrMoney = M('distribut_moneys')->where($condition)->sum('distributMoney');
		$this->assign('distrMoney',$distrMoney);
     
    
     $this->display("default/users/agent");
  }
   
	public function setQrcode($uid){
		//$this->isLogin();
		$phpqrcode = new \QRcode();
        $filename = "Public/qrcode/";
        if(!file_exists($filename)){
            mkdir($filename,true);
        }
        $matrixPointSize = 5.8;
        $data = U("Users/index",array("shareUserId"=>$uid),true,true);
        $file = $filename . "$uid.png";
        $phpqrcode::png($data, $file, '', $matrixPointSize, 2);
		$file = $this->hebin($file,$filename,$uid);
		return $file;
	} 
	 
	public function hebin($file,$filename,$uid){
		$backgroud = '/Public/qrcode/bg.png';//准备好的背景图片 
		$backgroud = "http://{$_SERVER['HTTP_HOST']}".$backgroud;
		$QR = $file;//已经生成的原始二维码图 
		$backgroud = imagecreatefromstring(file_get_contents($backgroud)); 
		$QR = imagecreatefromstring(file_get_contents($QR)); 
		$backgroud_width = imagesx($backgroud);//logo图片宽度 
	    $backgroud_height = imagesy($backgroud);//logo图片高度 
		$QR_width = imagesx($QR);//二维码图片宽度 
	    $QR_height = imagesy($QR);//二维码图片高度 
		$QR_x = $backgroud_width/3.2;
		$QR_y = $backgroud_height/1.52;      
		imagecopyresampled($backgroud, $QR, $QR_x, $QR_y, 0, 0, $QR_width,$QR_width, $QR_width, $QR_width); 
		$savefile = $filename."$uid.png";
	    imagepng($backgroud, $savefile); 
		return $savefile;
	}
  
  /**
   * 新手指南
   **/
  public function reward(){
  	$this->isLogin();
    $this->display("default/users/reward");
  }
  
  /**
   *掌上云充值异步通知
  **/
  public function rechargeByTool(){
	if (IS_POST){
		
		$appid = I('appid'); //掌上云商户appid
		$ordernum = I('ordernum');/*掌上支付系统订单号*/
		$usernum = I('usernum');/*用户传输过去的订单号*/
		$rjine = I('rjine');/*真实支付金额*/
        $remark = I('remark');/*备注*/
		$akey = I('akey');/*此参数暂时不处理*/
		$off = I('off');/*支付成功*/
		$zhen = I('zhen');
		$tyid = I('tyid'); /*充值方式 1,微信 2,支付宝*/
		$nkey = I('nkey');/*掌上支付通过计算返回的nkey*/
		$dis = '充值成功'; //充值状态描述
		
		$saveTest = array();
		
		if( $off != 2 )
		{
			$saveTest['pay_result'] = '支付失败';
			$rs = M('recharge')->add($saveTest);
			//支付失败
			return;
		}
		
		//截取订单号和userid
		$pos = stripos($remark, '.');
		$userID = strstr($remark, '.', true);
		$orderNum = substr($remark, $pos+1, strlen($remark)-$pos);
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
		
		$apidSrc = '920'; //掌上云平台appid
		$akeySrc = '8d5307e8787c845bf6cf0eccd864fe44dd011998af315248c0fbf7c29ce759ee'; //通信密钥
		
		//生成验证密码
		$keyCheck = md5( $apidSrc.$_POST['ordernum'].$_POST['usernum'].$_POST['rjine'].$_POST['remark'].$_POST['off'].$_POST['zhen'].$akeySrc .$_POST['tyid'] );
		
		if($keyCheck != $_POST['nkey'])
		{	$saveTest['pay_result'] = '验证失败';
			$rs = M('recharge')->add($saveTest);
			//验证失败
			$this->ajaxReturn(array('status'=>'-1' ,'msg'=>'密码验证失败!'));
		}
	
		
		if ($rjine <= 0){
		   
		   $saveTest['pay_result'] = '金额无效';
			$rs = M('recharge')->add($saveTest);
		   $this->ajaxReturn(array('status'=>'-2' ,'msg'=>'充值金额无效!'));
		}
		 	/*if ($appid !== 'wxdedci39dkcpdfg' || $key !== 'qscpkjkdmejhcilcdr5'){
		 	   $this->ajaxReturn(array('status'=>'-1' ,'msg'=>'appid或key错误!'));
		 	}*/
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
		$data1 ["recharge_sn"] = createRecharge (); //充值单号 
		$data1 ["recharge_money"] = $rjine; //充值金额
		$data1 ["receive_money"] = $rjine; //到账金额
		$data1 ["remark"] = '掌上云充值'; 
		$data1 ["add_time"] = time (); //充值时间
		$data1 ["pay_time"] = time ();
		$data1 ["recharge_status"] = 1; 
		$data1 ["pay_result"] = $dis;
		$data1 ["pay_orderNum"] = $orderNum;
		$rs = M('recharge')->add($data1);
		 	
		if(false !== $rs){
		   $userMoney = $userInfo['userMoney']+$rjine;
		    $redPacketMoney = $userInfo['redPacketMoney'];
		    $distributMoney = $userInfo['distributMoney'];
		    //资金流水表
			$cur_money = $userInfo['userMoney']+$rjine;
			$data = array();
			$data["targetType"] = 0;
			$data["targetId"] = $userInfo['userId'];
		    $data["moneyRemark"] = '成功充值:'.$rjine;
			$data["dataSrc"] = 2;//充值订单
			$data["dataId"] =  $rs;
			$data["money"] = $rjine;
			$data["transactionId"] = $data1 ["recharge_sn"];
			$data["type"] = 0;
			$data["moneyType"] = 1;
			$data["createTime"] = time();
			$data["userMoney"] = $cur_money;
			$data["redPacketMoney"] = $userInfo['redPacketMoney'];
			$data["distributMoney"] = $userInfo['distributMoney'];
			M('log_moneys')->add($data);
		    M('users')->where(array('userId'=>$userID))->setInc('userMoney',$rjine);
		}  
		$this->ajaxReturn(array('status'=>$off ,'msg'=>'success'));
	}else{
	 	exit('非法请求');
	}  
	  
  }
	
  /**
   *掌上云充值同步通知
  **/
  public function rechargeReturn(){
	  
	  
  }
     /**
	  * 账号充值
	  */
	 public function recharge(){
	 	if (IS_POST){
		 	$rd = array('status'=>-1);
		 	$appid = I('appid');
		 	$key = I('key');
		 	$user_id = I('user_id');
            $money = I('money');
		 	if (empty($money)){
		 	   $this->ajaxReturn(array('status'=>'-1' ,'msg'=>'金额必填')); 
		 	}
		 	/*if ($appid !== 'wxdedci39dkcpdfg' || $key !== 'qscpkjkdmejhcilcdr5'){
		 	   $this->ajaxReturn(array('status'=>'-1' ,'msg'=>'appid或key错误!'));
		 	}*/
		 	$userInfo = M('users')->where(array('userId'=>$user_id))->field('userId,loginName,userStatus,userType,userMoney,redPacketMoney,distributMoney')->find();
		 	
		 	if ($userInfo['userId'] <1){  
		 	    $this->ajaxReturn(array('status'=>'-2' ,'msg'=>'用户不存在'));
		 	}
		 	
		 	$condition = array();
		 	$condition['user_id']=$user_id;
		 	$condition['pay_time'] = time();
		 	$cnt = M('recharge')->where($condition)->select();

		 	if ($cnt>0){
		 	   $this->ajaxReturn(array('status'=>'-3' ,'msg'=>'您已充值成功'));
		 	}
		    
		 	$data1 = array ();
			$data1 ["user_id"] = $userInfo['userId']; //用户id
			$data1 ["recharge_sn"] = createRecharge (); //充值单号 
			$data1 ["recharge_money"] = I ('money'); //充值金额
			$data1 ["receive_money"] = I ('money'); //到账金额
			$data1 ["remark"] = '扫描二维码,个人转账'; 
			$data1 ["add_time"] = time (); //充值时间
			$data1 ["pay_time"] = time ();
			$data1 ["recharge_status"] = 1; 
		 	$rs = M('recharge')->add($data1);
		 	
		    if(false !== $rs){
		    	$userMoney = $userInfo['userMoney']+I ('money');
		    	$redPacketMoney = $userInfo['redPacketMoney'];
		    	$distributMoney = $userInfo['distributMoney'];
		    	   //资金流水表
				   $cur_money = $userInfo['userMoney']+I ('money');
				   $data = array();
				   $data["targetType"] = 0;
				   $data["targetId"] = $userInfo['userId'];
				   $data["moneyRemark"] = '成功充值:'.I('money');
				   $data["dataSrc"] = 2;//充值订单
				   $data["dataId"] =  $rs;
				   $data["money"] = I ('money');
				   $data["transactionId"] = $data1 ["recharge_sn"];
				   $data["type"] = 0;
				   $data["moneyType"] = 1;
				   $data["createTime"] = time();
				   $data["userMoney"] = $cur_money;
				   $data["redPacketMoney"] = $userInfo['redPacketMoney'];
				   $data["distributMoney"] = $userInfo['distributMoney'];
				   M('log_moneys')->add($data);
		    	   M('users')->where(array('userId'=>$user_id))->setInc('userMoney',I('money'));
			}  
			$this->ajaxReturn(array('status'=>'1' ,'msg'=>'充值成功'));
	 	}else{
	 	  exit('非法请求');
	 	}
	 }

  
  /**
   * 测试
   */
  public function test(){
  	$this->display("default/users/test");
  	
  }
}