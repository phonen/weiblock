<?php
namespace BaJie\Action;
/**
 * Ajax对接 by SS
 */
class ServerAction extends BaseAction {
	
	public function notice(){
		$trades = M('trade t')->join('xz_users u on t.user_id = u.userId', 'left')->where(array('is_pay' => '1', 'is_draw' => '1'))->field('t.draw_money,t.user_id,u.loginName')->order('trade_id desc')->limit(50)->select();
		
		$data = array('code'=>200,'msg'=>'ok','result'=>array('list'=>array()));
		foreach($trades as $i=>$trade){
			$data['result']['list'][] = array('title'=>'恭喜玩家 '.$trade['loginName'].' 获得 '.$trade['draw_money']);
		}
		$this->ajaxReturn($data);
		
		//echo '{"code":200,"msg":"ok","result":{"list":[{"id":"3","title":"","content":"","create_time":"2017-11-09 03:40:33","update_time":"2017-11-09 03:40:33","oper_user":"2"},{"id":"1","title":"\u9080\u8bf7\u65b0\u73a9\u5bb6\u9001\u73b0\u91d1\u6d3b\u52a8\u6b63\u5728\u8fdb\u884c\u4e2d\uff08\u9886\u7ea2\u5305\u540e\u751f\u6548\uff09","content":"\u9080\u8bf7\u65b0\u73a9\u5bb6\u9001\u73b0\u91d1\u6d3b\u52a8\u6b63\u5728\u8fdb\u884c\u4e2d\uff0c\u9700\u8981\u65b0\u73a9\u5bb6\u8fdb\u5165\u9996\u9875\u70b9\u51fb\u9886\u53d6\u7ea2\u5305\u624d\u4f1a\u751f\u6548\u54df","create_time":"2017-11-06 04:24:52","update_time":"2017-11-09 03:44:12","oper_user":"2"}]}}';
	}
	
	public function user(){
		$data = array();
		$data['code'] = 1;
		$usersId = (int)session('WST_USER.userId');
		
		$data['userid'] = $usersId;
		$data['msg'] = 'select succeed';
		$data['result'] = '';
		if ($usersId > 0) {
            $info = M('users')->where(array('userId' => $usersId))->field('loginName as userid,userMoney as balance,redPacketMoney as red_packet,userStatus as state')->find();
			$data['code'] == 200;
			$data['result'] = $info;
		}
		
		$this->ajaxReturn($data);
		//echo '{"code":200,"userid":"110825","result":{"userid":"110825","balance":"0","state":"1","red_packet":"500"},"msg":"select succeed"}';
	}
	
	public function balance(){
		$data = array();
		$data['code'] = 200;
		$usersId = (int)session('WST_USER.userId');
		
		$data['userid'] = $usersId;
		$data['msg'] = 'query succeed';
		$data['result'] = '';
		if ($usersId) {
            $info = M('users')->where(array('userId' => $usersId))->field('loginName as id,userMoney as balance,redPacketMoney as red_packet,play_count')->find();
			$data['result'] = $info;
			$data['result']['macthcount'] = 100-$info['play_count'];
			$data['result']['witharwcount'] = 0;
		}
		
		$this->ajaxReturn($data);
		//echo '{"code":200,"msg":"query succeed","result":{"balance":0,"id":"110825","red_packet":5,"macthcount":0,"witharwcount":0}}';
	}
	
	public function red(){
		echo '{"code":201,"msg":"red packtets already give","msgcode":"none"}';
	}
	
	public function startgame(){
		$this->isLogin();
        $m = D('WeChat/Games');
        $data = $m->play();
        $this->ajaxReturn($data);
	}
	
	//二维码
	public function qrcode(){
		$usersId = (int)session('WST_USER.userId');
		$this->setQrcode($usersId);
	}
	
	public function setQrcode($uid){
        $path = "Public/qrcodes/";
        if(!file_exists($path)){
            mkdir($path,true);
        }
        $file = $path . "$uid.jpg";
		if(!file_exists($file)){
			$matrixPointSize = 6.3;
			vendor("phpqrcode.qrlib");
			$phpqrcode = new \QRcode();
			$data = U("BaJie/Users/index",array("shareUserId"=>$uid),true,true);
			$QR = $phpqrcode::png($data, $file, '', $matrixPointSize, 2);
			$backgroud = $path . 'bg.png';//准备好的背景图片 
			$backgroud = imagecreatefromjpeg($backgroud);
			$QR = imagecreatefrompng($file);
			$backgroud_width = imagesx($backgroud);//logo图片宽度 
			$backgroud_height = imagesy($backgroud);//logo图片高度 
			$QR_width = imagesx($QR);//二维码图片宽度 
			$QR_height = imagesy($QR);//二维码图片高度 
			$QR_x = $backgroud_width/2.7;
			$QR_y = $backgroud_height/1.67;      
			imagecopyresampled($backgroud, $QR, $QR_x, $QR_y, 0, 0, $QR_width,$QR_width, $QR_width, $QR_width);
			imagejpeg($backgroud,$file,70);
		}
		header("Content-type:image/jpg");
		echo file_get_contents($file);
		//return $this->contentType("image/jpg");
	}
	
	//游戏记录
	public function querymatchhistory(){
		$cur = I('cur');
		$usersId = (int)session('WST_USER.userId');
		if($cur <= 6){
			$tradess = M('trade')->where(array('user_id' => $usersId, 'is_pay' => '1'))->limit((($cur-1)*20).',20')->order('trade_id desc')->select();
		}else{
			$tradess = array();
		}
		
		$data = array('code'=>200,'msg'=>'ok','result'=>array('page'=>array('totalPage'=>1,'totalRows'=>20,'curRows'=>0,'curPage'=>1,'list'=>array())));
		if(!empty($tradess)){
			foreach($tradess as $trades){
				$d = array();
				$d['type'] = $trades['type'];
				$d['total_fee'] = $trades['trade_money'];
				$d['poundage_fee'] = $trades['poundage_money'];
				$d['match_fee'] = $trades['draw_money'];
				$d['create_time'] = date('Y-m-d H:i:s',$trades['add_time']);
				$d['match_result'] = $trades['is_draw'];
				$d['order_id'] = $trades['weipay_sn'];
				if($d['type'] == 3){
					$trades['selectd_num'] = implode('',json_decode($trades['selectd_num']));
				}
				$d['user_in_val'] = $trades['selectd_num'];
				$d['odds'] = $trades['odds'];
				//$d[''] = $trades[''];
				$data['result']['page']['list'][] = $d;
			}
		}
		$this->ajaxReturn($data);
	}
	//{"code":200,"msg":"ok","result":{"page":{"totalPage":1,"totalRows":"2","curRows":20,"curPage":1,"list":[{"id":"9134","type":"3","total_fee":"1200","single_order_fee":"100","poundage_fee":"100","match_fee":"1000","create_time":"2017-12-14 22:27:39","match_result":"2","order_id":"1000018301201712142199876191","user_in_val":"13458","odds":"0","user_id":"110825","key_num":"0","level":"0"},{"id":"9133","type":"1","total_fee":"1200","single_order_fee":"100","poundage_fee":"100","match_fee":"1000","create_time":"2017-12-14 22:25:31","match_result":"2","order_id":"1000018301201712142199858182","user_in_val":"6","odds":"200","user_id":"110825","key_num":"0","level":"1"}]}}}
	
	function rank(){
		echo '';
	}
	
	function withdraw(){
		$sc = A("DouNiu/CashDraws");
		$sc->pay_run(I('fee'),0);
	}
	
	// 交易记录查询
	public function transfershistory(){
		$cur = I('cur');
		$usersId = (int)session('WST_USER.userId');
		$moneys = M('log_moneys')->where(array('targetId' => $usersId))->limit((($cur-1)*20).',20')->order('moneyId desc')->select();
		$data = array('code'=>200,'msg'=>'ok','result'=>array('page'=>array('totalPage'=>1,'totalRows'=>20,'curRows'=>0,'curPage'=>1,'list'=>array())));
		if(!empty($moneys)){
			foreach($moneys as $money){
				$d = array();
				$d['type'] = $money['type'];
				$d['inex'] = $money['moneyType']+1;
				$d['balance'] = $money['money'];
				$d['create_time'] = date('Y-m-d H:i:s',$money['createTime']);
				$d['mark'] = getMoneySrc($money['dataSrc']);
				$data['result']['page']['list'][] = $d;
			}
		}
		$this->ajaxReturn($data);
	}
	//{"code":200,"msg":"ok","result":{"page":{"totalPage":1,"totalRows":"8","curRows":20,"curPage":1,"list":[{"id":"106894","trade_no":"81972017121422273769257105","user_id":"110825","type":"2","inex":"1","balance":"1000","mark":"\u593a\u5b9d\u8d39\u7528-\u6263\u9664\u5e10\u6237\u4f59\u989d","create_time":"2017-12-14 22:27:37"},{"id":"106893","trade_no":"82362017121422273791519767","user_id":"110825","type":"6","inex":"1","balance":"100","mark":"\u593a\u5b9d\u8ba2\u5355\u53f7\u63d0\u73b0-\u6263\u9664\u5e10\u6237\u4f59\u989d","create_time":"2017-12-14 22:27:37"},{"id":"106892","trade_no":"88732017121422273791828468","user_id":"110825","type":"5","inex":"4","balance":"100","mark":"\u4e0b\u6ce8\u624b\u7eed\u8d39-\u6263\u9664\u5e10\u6237\u7ea2\u5305\u4f59\u989d","create_time":"2017-12-14 22:27:37"},{"id":"106884","trade_no":"88652017121422252947894418","user_id":"110825","type":"2","inex":"1","balance":"1000","mark":"\u593a\u5b9d\u8d39\u7528-\u6263\u9664\u5e10\u6237\u4f59\u989d","create_time":"2017-12-14 22:25:29"},{"id":"106883","trade_no":"85192017121422252850173702","user_id":"110825","type":"6","inex":"1","balance":"100","mark":"\u593a\u5b9d\u8ba2\u5355\u53f7\u63d0\u73b0-\u6263\u9664\u5e10\u6237\u4f59\u989d","create_time":"2017-12-14 22:25:29"},{"id":"106882","trade_no":"86522017121422252847617520","user_id":"110825","type":"5","inex":"4","balance":"100","mark":"\u4e0b\u6ce8\u624b\u7eed\u8d39-\u6263\u9664\u5e10\u6237\u7ea2\u5305\u4f59\u989d","create_time":"2017-12-14 22:25:28"},{"id":"106866","trade_no":"83252017121422140589342882","user_id":"110825","type":"1","inex":"2","balance":"2200","mark":"\u5145\u503c\u6210\u529f \u5fae\u4fe1\u5145\u503c","create_time":"2017-12-14 22:14:05"},{"id":"106206","trade_no":"89042017121319290972524529","user_id":"110825","type":"7","inex":"3","balance":"500","mark":"\u65b0\u4eba\u90015\u5143\u7ea2\u5305","create_time":"2017-12-13 19:29:09"}]}}}
	
	// 提现记录查询
	public function withdrawhistory(){
		$cur = I('cur');
		$usersId = (int)session('WST_USER.userId');
		$list = M('cash_draws')->where(array('targetId'=>$usersId,'targetType'=>array('in','0,1'),'cashSatus'=>'1'))->limit((($cur-1)*20).',20')->order('cashId desc')->select();
		$data = array('code'=>200,'msg'=>'ok','result'=>array('page'=>array('totalPage'=>1,'totalRows'=>20,'curRows'=>0,'curPage'=>1,'list'=>array())));
		if(!empty($list)){
			foreach($list as $item){
				$d = array();
				$d['type'] = $item['targetType'];
				$d['state'] = 2;
				$d['fee'] = $item['money'];
				$d['create_time'] = $item['createTime'];
				//$d['order_on'] = $item['draw_sn'];
				$data['result']['page']['list'][] = $d;
			}
		}
		$this->ajaxReturn($data);
	}
	//{"code":200,"msg":"ok","result":{"page":{"totalPage":1,"totalRows":"1","curRows":20,"curPage":1,"list":[{"id":"12724","type":"1","vonder_order_on":"402017121422.0031730.001149","order_on":"81692017121422123021555562","getway":"1","fee":"2200","state":"2","user_id":"110825","create_time":"2017-12-14 22:12:30","mark":"\u5145\u503c\u6210\u529f \u5fae\u4fe1\u5145\u503c","update_time":"2017-12-14 22:14:05","mercha_id":"40"}],"topup":"22.00","withdraw":"0"}}}
	
	// 推广记录查询
	public function agency(){
		$usersId = (int)session('WST_USER.userId');
		
		$info = M('users')->where(array('userId' => $usersId))->field('distributMoney')->find();
		
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
        
		$condition = array('userId'=>$usersId,'createTime'=>array('between', "$begin,$end"));
		$distrMoney = M('distribut_moneys')->where($condition)->sum('distributMoney');
		
		$data = array('code'=>200,'msg'=>'ok','result'=>array('totalfee'=>$info['distributMoney'],'todayfee'=>sprintf('%.2f',$distrMoney),'recount'=>array()));
		
		$res = D('WeChat/Users')->getRecommedInfo();
		foreach($res as $i=>$item){
			$data['result']['recount']['level'.($i+1)] = $item;
		}
		
		$this->ajaxReturn($data);
	}
	//{"code":200,"msg":"ok","result":{"totalfee":null,"list":[],"todayfee":null,"recount":{"level1":{"count":"0"},"level2":{"count":"0"},"level3":{"count":"0"},"level4":{"count":"0"}},"userid":"110825"}}
}