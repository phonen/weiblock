<?php
namespace DouNiu\Model;
/**
 * 会员服务类
 */
class UsersModel extends BaseModel {
	/**
	 * 用户自动注册
	 */
	public function register($userinfo){
		$rv = array('status'=>-1);
		$shareUserId = (int)session('WST_SHAREUSERID');
		$loginName = $this->randomLoginName($loginName);
		$rs = $this->checkLoginKey($loginName);
		if($rs['status']==1){
			//记录登录信息
			$m = M('users');
			$data = array();
			$data['wxOpenId'] = session('WST_WX_OPENID'); 
			$data['userName'] = $userinfo['nickname'];
			$data['loginName'] = $loginName;
			$data['userSex'] = $userinfo['sex'];
			$data['userPhoto'] = WSTDataFile($userinfo['headimgurl'],'./Upload/users/'.date("Y-m/"));
			$data["loginSecret"] = rand(1000,9999);
			$data['userType'] = 0;
			$data['createTime'] = date('Y-m-d H:i:s');
			$data['userFlag'] = 1;
			$data['lastIP'] = get_client_ip();
			$data['recommend_user_id'] = $shareUserId;
			$userId = $m->add($data);
			if(false !== $rs){
				if($shareUserId>0){
						$sql = "select isBuyer from __PREFIX__users where userId = $shareUserId";
						$sharer = $this->queryRow($sql);
							$m = M('distribut_users');
							$data = array();
							$data["userId"] = $shareUserId;
						    $data["buyerId"] = $userId;
							$data["createTime"] = date("Y-m-d H:i:s");
							$datas['ip'] = get_client_ip();
							$m->add($data);
							session('WST_SHAREUSERID',null);
				}
				
				$data = array();
				$data["userId"] = $rs['userId'];
				$data["loginTime"] = date('Y-m-d H:i:s');
				$data["loginIp"] = get_client_ip();
				$data["loginSrc"] = 3;
				$data["loginRemark"] = I('loginRemark');
				M('log_user_logins')->add($data);
				$m = M('users');
				$sql = "select * from __PREFIX__users where loginName='".$loginName."' and userFlag=1 ";
				$urs = $this->query($sql);
				$urs = $urs[0];
				$urs["status"] = 1;
				session('WST_USER',$urs);
			}
		}
		return $rv;
	}
	/**
	 * 获取指定对象的资料
	 */
	public function getUserInfo($userId,$field =''){
		$rs = M('users')->where("userId=".$userId)->field("userId,loginName,userName,".$field)->find();
		return $rs;
	}
	/**
	 * 自动登录
	 */
	public function WSTAutomaticLogon(){
		$rv = array('status' => -1);
		$openId=session('WST_WX_OPENID');
		$m = M('users');
		$sql="SELECT * FROM __PREFIX__users WHERE (wxOpenId='".$openId."') AND userFlag=1 order by lastTime desc";
		$urs = $m->query($sql);
		$urs = $urs[0];
		if(!empty($urs)){
			$this->shareUser($urs['userId']);
			//记录登录信息
			$data =array();
			$data['lastTime'] = date('Y-m-d H:i:s');
			$data['lastIP'] = get_client_ip();
			$m->where("userId=".$urs['userId'])->data($data)->save();
			//登录记录
			$data =array();
			$data['loginSrc'] = 3;
			$data["userId"] = $urs['userId'];
			$data["loginTime"] = date('Y-m-d H:i:s');
			$data["loginIp"] = get_client_ip();
			$data["loginRemark"] = I('loginRemark');
			$data["loginIp"] = get_client_ip();
			M('log_user_logins')->add($data);
			$rv=$urs;
			$rv["status"] = 1;
			session('WST_USER',$rv);
		}
		return $rv;
	}
	/**
	 * 已登录/分享处理
	 */
	public function shareUser($userId){
		$shareUserId = (int)base64_decode(session('WST_SHAREUSERID'));
		$duser = D('distribut_users')->getInfo($userId);
		if(count($duser)==0 && $shareUserId>0 && $userId!=$shareUserId){
			$isDistribut = $GLOBALS['CONFIG']["isDistribut"];
			$distributDeal = $GLOBALS['CONFIG']["distributDeal"];
			if($isDistribut==1){
				$sql = "select isBuyer from __PREFIX__users where userId = $shareUserId";
				$sharer = $this->queryRow($sql);
				if($distributDeal==1 || ($sharer["isBuyer"]==1 && $distributDeal==2)){
					$m = M('distribut_users');
					$data = array();
					$data["userId"] = $shareUserId;
					$data["buyerId"] = $userId;
					$data["createTime"] = date("Y-m-d H:i:s");
					$datas['ip'] = get_client_ip();
					$m->add($data);
					session('WST_SHAREUSERID', null);
				}
			}
		}
	}

	/**
	 * 查询登录名是否存在
	 */
	public function checkLoginKey($loginName,$id = 0){
		$loginName = ($loginName!='')?$loginName:I('loginName');
		$rd = array('status'=>-1);
		if($loginName=='')return $rd;
		$sql = " (loginName ='%s' or userPhone ='%s' or userEmail='%s') ";
		$m = M('users');
		if($id>0){
			$sql.=" and userId!=".$id;
		}
		$rs = $m->where($sql,array($loginName,$loginName,$loginName))->count();
		if($rs==0)$rd['status'] = 1;
		return $rd;
	}

	/**
	 * 随机生成一个账号
	 */
	public function randomLoginName($loginName){
		return 'BJ'.rand('1000000', '9999999');
	}
	
	/**
	 * 用户下注,上线获得佣金
	 **/
	public function addDistributMoney($obj){
	   $userId =  $obj['userId'];//当前用户信息
	   $money = $obj['money'];
	   $dataId = $obj['dataId'];
	   $transactionId = $obj['transaction_id'];
	   $recommend_user_id = $obj['recommend_user_id'];
	  
	   
	   $firt_commission = $GLOBALS['CONFIG']['first_rate']*$money/100;//第一级佣金
	   $second_commission = $GLOBALS['CONFIG']['second_rate']*$money/100;//第二级佣金
	   $three_commission = $GLOBALS['CONFIG']['three_rate']*$money/100;//第三级佣金
	   $four_commission = $GLOBALS['CONFIG']['four_rate']*$money/100;//第四级佣金
	   $five_commission = $GLOBALS['CONFIG']['five_rate']*$money/100;//第五级佣金
	   
	   $first = $this->getUserInfo($recommend_user_id,'userId,recommend_user_id,userMoney,redPacketMoney,distributMoney');//第一级上线
	   if ($first['userId']>0){
	        M('Users')->where(array('userId'=>$first['userId']))->setInc('distributMoney',$firt_commission);
	        //写记录
	        $moneyRemark = '获得一级下线下注佣金';
	        $distributMoney = $first['distributMoney']+$firt_commission;
	        addMoneyLog($first['userId'], $moneyRemark, '1', $dataId, $firt_commission, $transactionId, 2, 1,$first['userMoney'],$first['redPacketMoney'],$distributMoney);
	        addDistruMoney($first['userId'], $userId, $moneyRemark, $dataId, $money, $firt_commission, '1',$transactionId);
	   }
	   //第二级上线  
	   $second = $this->getUserInfo($first['recommend_user_id'],'userId,recommend_user_id,userMoney,redPacketMoney,distributMoney');
	   if ($second['userId']>0){
	        M('Users')->where(array('userId'=>$second['userId']))->setInc('distributMoney',$second_commission);
	        //写记录
	        $moneyRemark = '获得二级下线下注佣金';
	        $distributMoney = $second['distributMoney']+$second_commission;
	        addMoneyLog($second['userId'], $moneyRemark, '1', $dataId, $second_commission, $transactionId, 2, 1,$second['userMoney'],$second['redPacketMoney'],$distributMoney);
	        addDistruMoney($second['userId'], $userId, $moneyRemark, $dataId, $money, $second_commission, '2',$transactionId); 
	   }
	   //第三级上线
	   $three = $this->getUserInfo($second['recommend_user_id'],'userId,recommend_user_id,userMoney,redPacketMoney,distributMoney');
	   if ($three['userId']>0){
	        M('Users')->where(array('userId'=>$three['userId']))->setInc('distributMoney',$three_commission);
	        //写记录 
	        $moneyRemark = '获得三级下线下注佣金';
	        $distributMoney = $three['distributMoney']+$three_commission;
	        addMoneyLog($three['userId'], $moneyRemark, '1', $dataId, $three_commission, $transactionId, 2, 1,$three['userMoney'],$three['redPacketMoney'],$distributMoney);
	        addDistruMoney($three['userId'], $userId, $moneyRemark, $dataId, $money, $three_commission, '3',$transactionId); 
	   }
	   
	   //第四级上线
	   $four = $this->getUserInfo($three['recommend_user_id'],'userId,recommend_user_id,userMoney,redPacketMoney,distributMoney');
	   if ($four['userId']>0){
	        M('Users')->where(array('userId'=>$four['userId']))->setInc('distributMoney',$four_commission);
	        //写记录 
	        $moneyRemark = '获得四级下线下注佣金';
	        $distributMoney = $four['distributMoney']+$four_commission;
	        addMoneyLog($four['userId'], $moneyRemark, '1', $dataId, $four_commission, $transactionId, 2, 1,$four['userMoney'],$four['redPacketMoney'],$distributMoney);
	        addDistruMoney($four['userId'], $userId, $moneyRemark, $dataId, $money, $four_commission, '4',$transactionId); 
	   }
	    
	   //第五级上线
	   $five = $this->getUserInfo($four['recommend_user_id'],'userId,recommend_user_id,userMoney,redPacketMoney,distributMoney');
	   if ($five['userId']>0){
	        M('Users')->where(array('userId'=>$five['userId']))->setInc('distributMoney',$five_commission);
	        //写记录 
	        $moneyRemark = '获得五级下线下注佣金';
	        addMoneyLog($five['userId'], $moneyRemark, '1', $dataId, $five_commission, $transactionId, 2, 1,$five['userMoney'],$five['redPacketMoney'],$distributMoney);
	        addDistruMoney($five['userId'], $userId, $moneyRemark, $dataId, $money, $five_commission, '5',$transactionId); 
	   }
	}
	
	/**
	 * 获取会员列表 前台
	 * */
	public function queryUsersList($condition = array()){
	   $result = M('users')->where($condition)->field('userId,recommend_user_id')->select();
	   return $result;
	}  
	
	/**
	 * 获得下线用户的信息
	 **/
	public function getRecommedInfo(){
		$userId = (int)session('WST_USER.userId');//当前用户信息
		$condition = array();
		$condition['recommend_user_id'] = $userId;
		$first = $this->queryUsersList($condition);//第一级下线
		
		//implode  
		$second_sub_user = array();
		foreach ($first as $k => $user){
		    $second = $this->queryUsersList(array('recommend_user_id'=>$user['userId']));//二级
		    foreach ($second as $k2=>$user2){
		    	$second_sub_user[] = $user2;
		    }
		}
		
		$three_sub_user = array();
	    foreach ($second_sub_user as $k3 => $user3){
		    $three = $this->queryUsersList(array('recommend_user_id'=>$user3['userId']));//三级
		    foreach ($three as $k4=>$user4){
		    	$three_sub_user[] = $user4;  
		    }
		}
		
	     
	   $four_sub_user = array();
	   foreach ($three_sub_user as $k5 => $user5){
		    $four = $this->queryUsersList(array('recommend_user_id'=>$user5['userId']));//四级
		    foreach ($four as $k6=>$user6){   
		    	$four_sub_user[] = $user6;
		    }
		}
		
		
	   $five_sub_user = array();
	   foreach ($four_sub_user as $k7 => $user7){
		    $five = $this->queryUsersList(array('recommend_user_id'=>$user7['userId']));//四级
		    foreach ($five as $k8=>$user8){
		    	$five_sub_user[] = $user8;
		    }
		}
		
		//$res = array();
/*		$res['cnt']=count($first);  
		$res['money']= D('WeChat/DistributMoneys')->dataCount(1);
		
		$res['secode_cnt']=count($second_sub_user);  
		$res['secode_money']= D('WeChat/DistributMoneys')->dataCount(2);
		
		$res['three_cnt']=count($three_sub_user);  
		$res['three_money']= D('WeChat/DistributMoneys')->dataCount(3);
		
		$res['four_cnt']=count($four_sub_user);  
		$res['four_money']= D('WeChat/DistributMoneys')->dataCount(4);
		
		$res['five_cnt']=count($five_sub_user);  
		$res['five_money']= D('WeChat/DistributMoneys')->dataCount(5);*/
		
		$arr = array(
		   array(
		      'cnt'=>count($first),
		      'money' => D('WeChat/DistributMoneys')->dataCount(1)
		   ),
		   array(
		      'cnt'=>count($second_sub_user),
		      'money' => D('WeChat/DistributMoneys')->dataCount(2)
		   ),
		   array(
		       'cnt'=>count($three_sub_user), 
		      'money' => D('WeChat/DistributMoneys')->dataCount(3)
		   ),
		   array(
		       'cnt'=>count($four_sub_user),  
		      'money' => D('WeChat/DistributMoneys')->dataCount(4)
		   ),
		   array(
		       'cnt'=>count($five_sub_user),
		      'money' => D('WeChat/DistributMoneys')->dataCount(5)
		   )
		);

	    return $arr;

	}

	
}