<?php
 namespace Admin\Action;
/**
 * 充值控制器
 */ 
class RechargeAction extends BaseAction{
    /**
	 * 获取充值记录列表
	 */
	public function index(){
		$this->isLogin();
		//$this->checkPrivelege('czgl_00');
		$m = D('Admin/Recharge');
		$page = $m->queryByPage();
		$pager = new \Think\Page($page['total'],$page['pageSize'],I());
		$page['pager'] = $pager->show();
		$this->assign('time_type',(int)I('time_type',-1));
		$this->assign('startDate',I('startDate'));
		$this->assign('endDate',I('endDate')); 
		$this->assign('Page',$page);
		$this->assign('userType',(int)I('userType',-1));
		$this->assign('userName',I('userName'));
		
		$total = M('recharge')->where(array('recharge_status'=>'1'))->sum('recharge_money');
		$this->assign('total_recharge',$total);
		
		$total_money = M('users')->where(array('userFlag'=>'1'))->sum('userMoney');
		$this->assign('total_money',$total_money);
		
		$this->view->display('/recharge/index');
	}
	 /* 导出充值管理表 */
	public function recharge_dc(){
		$this->isLogin();
		//$this->checkPrivelege('txgl_00');
		$m = D('Admin/Recharge');
		$rs= $m->queryByPage();

	          if(!empty($rs['root'])){
		  foreach ($rs['root'] as $key =>$v){
		  	if($v['recharge_status']==0){
			  $rs['root'][$key]['recharge_status'] = "未支付";
		  	}elseif($v['recharge_status']==1){
			  $rs['root'][$key]['recharge_status'] = "第一次已到账";
		  	}elseif($v['recharge_status']==2){
			  $rs['root'][$key]['recharge_status'] = "第二次已到账";
		  	}			  	
		  	if($v['recharge_from']==0){
			  $rs['root'][$key]['recharge_from'] = "后台";
		  	}elseif($v['recharge_from']==1){
			  $rs['root'][$key]['recharge_from'] = "微信";
		  	}elseif($v['recharge_from']==2){
			  $rs['root'][$key]['recharge_from'] = "电脑";
		  	}		  	
		  	if($v['user_source']==1){
			  $rs['root'][$key]['user_source'] = "云联商会";
		  	}elseif($v['user_source']==2){
			  $rs['root'][$key]['user_source'] = "大唐天下";
		  	}		
		  	$rs['root'][$key]['userType'] = ($v['userType']==1)?'店铺':'会员';  	
		  	$rs['root'][$key]['add_time'] = ($v['add_time'])?date('Y-m-d H:i:s',$v['add_time']):'';
		  	$rs['root'][$key]['pay_time'] = ($v['pay_time'])?date('Y-m-d H:i:s',$v['pay_time']):'';
		  	$rs['root'][$key]['secend_receive_time'] = ($v['secend_receive_time'])?date('Y-m-d H:i:s',$v['secend_receive_time']):'';
		  	$rs['root'][$key]['second_should_receive_time'] = ($v['second_should_receive_time'])?date('Y-m-d H:i:s',$v['second_should_receive_time']):'';
		  }
		}
		$xlsData=$rs['root'];
		$xlsName = "湖南云堂商超(充值管理表)";
		$xlsCell =array(array('recharge_sn','充值单号','20'),array('loginName','帐号','20'),array('userName','用户名','20'),array('userPhone','云堂手机号码','20'),array('jifenPhone','返积分手机号码','20'),array('user_source','账号来源','20'),array('user_link_account','ID号','20'),array('userType','充值类型','20'),array('recharge_money','充值金额','20'),array('receive_money','已到账金额','20'),array('unreceive_money','未到账金额','20'),array('add_time','下单时间','20'),array('pay_time','充值时间','20'),array('secend_receive_time','第二次到账时间','20'),array('second_should_receive_time','第二次应到账时间','20'),array('recharge_status','充值状态','20'),array('recharge_from','充值来源','20'));
		exportExcel($xlsName,$xlsCell,$xlsData);
	}
	/**
	 * 跳去充值页面
	 */
	public function toRecharge(){
		$this->isLogin();
		$this->checkPrivelege('czgl_01');
		
		$m = D('Admin/Users');
		$object = $m->getAccountById();
		$this->assign('object',$object);
		
		$this->display("/recharge/recharge");
	}
	
	/**
	 * 充值操作
	 */
	public function handle(){
		$this->isLogin();
		$this->checkPrivelege('czgl_01');
		$rs = D('Admin/Recharge')->handle();
		$this->ajaxReturn($rs);
	}
	
	/**
	 *修改页面 
	 */
	public function toEdit(){
		$this->isLogin();
		$this->checkPrivelege('czgl_01');
		
		$recharge_id = I('recharge_id');
		$recharge = M('Recharge')->where(array('recharge_id'=>$recharge_id))->find();
		
		$this->assign('object',$recharge);
		$this->display("/recharge/edit");
	}
	
    /**
	 *修改
	 */
	public function doEdit(){
		$this->isLogin();
		$this->checkPrivelege('czgl_01');
		
		$recharge_id = I('id');
		$update_time = strtotime(I('update_time'));
		$recharge = M('recharge')->where(array('recharge_id'=>$recharge_id))->find();
		
		$rs = array('status'=>'-1');
		if ($recharge['second_should_receive_time'] < time()){
                $rs['msg'] = '第二次应到账时间不能小于当前时间';		
                $this->ajaxReturn($rs);	
		}
		
	    if ($update_time < time()){
           $rs['msg'] = '修改的应到账时间不能小于今天!!!!';		
           $this->ajaxReturn($rs);	
		}

		$updateData = array();
		$update_time = $update_time + 60*60*12;
		$updateData['second_should_receive_time'] = $update_time;
		
		$result = M('recharge')->where(array('recharge_id'=>$recharge_id))->save($updateData);
		if (false !== $result){
			    $rs['status'] = '1';
			    $rs['msg'] = '第二次应到账时间不能大于要修改时间';	
                $this->ajaxReturn($rs);			
		}
	}
	
};
?>