<?php
 namespace Payment\Action;
/**
 * 
 * 码支付接口 ...
 * @author lenovo
 *
 */
class CodepayAction extends BaseAction{
   public function index(){
          exit('hello1');
   }
   
   /**
    *创建订单接口地址 
    **/
   public function create()
   {
	   	$data = array(
		    "id" => 13242,//你的码支付ID
		    "pay_id" => "admin", //唯一标识 可以是用户ID,订单ID,用户名
		    "type" => 1,//支付宝支付
		    "price" => 0.1//金额100元
	   	); //构造需要传递的参数
	   	ksort($data); //重新排序$data数组
	   	reset($data); //内部指针指向数组中的第一个元素
	   	$sign = '';
	   	$codepay_key = "tKtSBJ3oqJIBTIX5Am0eUzddqoJZ5rKO"; //这是您的密钥
	   	foreach ($data AS $key => $val) {
	   		if ($val == ''||$key == 'sign') continue;//跳过这些不参与签名
	   		if ($sign) $sign .= '&'; //第一个字符串签名不加& 其他加&连接起来参数
	   		$sign .= "$key=$val"; //拼接为url参数形式
	   	}
	   	$query = $sign . '&sign=' . md5($sign . $codepay_key); //创建订单所需的参数
	   	$url="http://codepay.fateqq.com:52888/creat_order/?{$query}"; //支付页面
	   	header("Location:{$url}"); //跳转到支付页面
   }
   
   /**
    * 付款通知
    * */
   public function notify()
   {
	   	ksort($_POST); //排序post参数
	   	reset($_POST); //内部指针指向数组中的第一个元素
	   	$codepay_key = "tKtSBJ3oqJIBTIX5Am0eUzddqoJZ5rKO"; //这是您的密钥
	   	$sign = '';//初始化
	   	foreach ($_POST AS $key => $val) { //遍历POST参数
	   		if ($val == '' || $key == 'sign') continue; //跳过这些不签名
	   		if ($sign) $sign .= '&'; //第一个字符串签名不加& 其他加&连接起来参数
	   		$sign .= "$key=$val"; //拼接为url参数形式
	   	}
	   	if (!$_POST['pay_no'] || md5($sign . $codepay_key) != $_POST['sign']) { //不合法的数据
	   		exit('fail');  //返回失败 继续补单
	   	} else { //合法的数据
	   		//业务处理
	   		$pay_id = $_POST['pay_id']; //需要充值的ID 或订单号 或用户名
	   		$money = (float)$_POST['money']; //实际付款金额
	   		$price = (float)$_POST['price']; //订单的原价
	   		$param = $_POST['param']; //自定义参数
	   		exit('success'); //返回成功 不要删除哦
	   	}
   }
}