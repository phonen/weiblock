<?php
namespace BaJie\Action;
/**
 * 游戏控制器
 */
class GamesAction extends BaseAction {    
  /**
   * 游戏界面
   **/
  public function index(){
  	layout(false);
  	$this->isLogin();

  	$userId = (int)session('WST_USER.userId');

  	 $info = M('users')->where(array('userId'=>$userId))->field('userMoney,redPacketMoney,distributMoney,isBuyer,loginName,is_first_login,userId,play_count,userPhone')->find();

     $userStatus = M('users')->where('userId='.$userId)->getField('userStatus',1);
  	
    $trades = M('trade')->where(array('game_type'=>'2','user_id'=>$userId))->order('trade_id desc')->select();
    foreach ($trades as $k =>$v){
        $trades[$k]['last_num'] =substr($v['weipay_sn'], 27,1);
        $trades[$k]['hand'] =$v['trade_money']/10;
    }
    
    $this->assign('info', $info);
  	$this->assign('trades',$trades);
    $this->display("default/users/games/index");
  }


    /**
     * 录入用户手机号码
     * */

    public function editphone()
    {
        $data['userPhone']=I('userPhone');
        $condition['loginName']=I('loginName');
        $userPhone=M('users')->M('users')->where('loginName='.$condition['loginName'])->getField('userPhone');
        $rs=M('users')->where($condition)->data($data)->save();
//
        $this->index();


    }




  /**
   * 玩游戏生成交易订单
   **/
  public function buyNumber(){
  	$this->isLogin();   
  	$this->isLogin();
  	$m = D('Common/Games');
  	$data = $m->buyNumber();
    $this->ajaxReturn($data);
  }
  
  /**
   * 中奖弹幕
   **/
  public function getBarrage(){
    $this->isLogin(); 
    /*$data = array(
          array('letter_id'=>'101','win_money'=>'20'),
          array('letter_id'=>'100','win_money'=>'220'),
          array('letter_id'=>'520','win_money'=>'260'),
          array('letter_id'=>'340','win_money'=>'20'),
          array('letter_id'=>'900','win_money'=>'20'),
    );*/
    $trades = M('trade')->where(array('is_draw'=>'1','game_type'=>'2'))->select();
    foreach ($trades as $k =>$v){
         $data[$k]['letter_id'] = M('users')->where(array('userId'=>$v['user_id']))->getField('loginName');
         $data[$k]['win_money'] = $v['draw_money'];
    }
    $this->ajaxReturn($data);
  }
}