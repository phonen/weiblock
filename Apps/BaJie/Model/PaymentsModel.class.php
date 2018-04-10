<?php
 namespace BaJie\Model;
/**
 * 支付类
 */
class PaymentsModel extends BaseModel {
	/**
     * 获取商城支付方式
     */
    public function getPayType(){
         $m = M('payments');
         $payments = $m->where('enabled=1 and find_in_set (3,payFor)')->order('payOrder asc')->field('id,payCode,payName,isOnline,payOrder')->select();//不显示支付宝支付
         $paylist = array();
         foreach ($payments as $key => $payment) {
             $payConfig = json_decode($payment["payConfig"]);
             foreach ($payConfig as $key2 => $value) {
                $payment[$key2] = $value;
             }
             $paylist[$key] = $payment;
             if($payment["isOnline"]){
                $paylist[$key]["onlines"] = 1;
             }else{
                $paylist[$key]["onlines"] = 0;
             }
         }
         return $paylist;
    }
    
    /**
     * 获取支付信息
     * @return unknown
     */
    public function getPayment($payCode=""){
    	$m = M('payments');
    	$payCode = $payCode?$payCode:I("payCode");
    	$payment = $m->where("enabled=1 AND payCode='$payCode' AND isOnline=1")->find();
    	$payConfig = json_decode($payment["payConfig"]) ;
    	foreach ($payConfig as $key => $value) {
    		$payment[$key] = $value;
    	}
    	return $payment;
    }
    
    
    /**
     * 完成支付订单
     */
    public function complatePay ($obj){
    	
    }
};
?>