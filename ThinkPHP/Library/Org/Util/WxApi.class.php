<?php
/**
 * 微信红包Api
 *
 */

namespace Org\Util;

class WxApi{
	/**
	 * 初始化
	 * */
	public function __construct(){
	    vendor ( 'WxPayHelper.WxPayConfig' );
		vendor ( 'WxPayHelper.WxPayHelper' );
		vendor ( 'WxPayHelper.SDKRuntimeException' );
	}
	
	/**
	 *企业付款到微信零钱
	 *@param $mch_appid      商户账号appid
	 *@param 
	 **/
	public function pay(){  
		return '00122222';
	}
	/**
	 * 微信红包发放
	 * @param string(32) 	$re_openid	  	用户openId
	 * @param int		$total_amount  付款金额（分）
	 * @param string(32) 	$act_name		活动名称
	*/
	public function sendRedPack($send_name,$re_openid,$total_amount,$act_name){
		if(empty($send_name))return false;
                if(empty($re_openid))return false;
		if(empty($total_amount))return false;
                if(empty($act_name))return false;
                
		include_once SEED_WWW_ROOT.'/api/weipay_v3_3_7/WxPayPubHelper/WxPayPubHelper.php';
                $nick_name=$send_name;
                $wishing=$act_name;
                $remark=$act_name;
                $total_amount=$total_amount*100;
		
		$commonutil = new Common_util_pub();
		
		$post_url = "https://api.mch.weixin.qq.com/mmpaymkttransfers/sendredpack";
		
		$mch_id = WxPayConf_pub::$MCHID;
		$wxappid = WxPayConf_pub::$APPID;
		$post_data = array();
		$post_data['nonce_str'] = $commonutil->createNoncestr(32);
		$post_data['mch_billno'] = $mch_id.date('Ymd').Seed_Common::genRandomString(10);
		$post_data['mch_id'] = $mch_id;
		$post_data['wxappid'] = $wxappid;
		$post_data['nick_name'] = $nick_name;
		$post_data['send_name'] = $send_name;
		$post_data['re_openid'] = $re_openid;
		$post_data['total_amount'] = $total_amount;
		$post_data['min_value'] = $post_data['total_amount'];
		$post_data['max_value'] = $post_data['total_amount'];
		$post_data['total_num'] = 1;
		$post_data['wishing'] = $wishing;
		$post_data['client_ip'] = '125.88.171.45';
		$post_data['act_name'] = $act_name;
		$post_data['remark'] = $remark;
				
		$sign = $commonutil->getSign($post_data);
		$post_data['sign'] = $sign;
		$post_xml = $commonutil->arrayToXml($post_data);
		$rs = $commonutil->postXmlSSLCurl($post_xml,$post_url);
		//Seed_Log::record($post_xml, 'PAY.TXT');
		$data = $commonutil->xmlToArray($rs);
                $result = array();
                if (empty($data) || !is_array($data)) {
                    $result['errnum'] = 1;
                    $result['errmsg'] = '微信红包发放接口有误，请稍后再试1！';
                    return json_encode($result);
                }
                if (isset($data['return_code']) && $data['return_code'] == 'SUCCESS' && isset($data['result_code']) && $data['result_code'] == 'SUCCESS' && $data['total_amount']>0) {
                    //插入数据到数据库中
                    $userM = new Seed_Model_User('system');
		            $user = $userM->fetchRow(array('wc_username'=>$re_openid)); 
                    $total_amount = $data['total_amount']/100;	
                    $repackData = array();
                    $repackData['redpack_from_type'] = '0';
                    $repackData['redpack_amount'] = $total_amount;
                    $repackData['redpack_time'] = time();
                    $repackData['redpack_userid'] = $user['user_id'];
                    $repackData['redpack_username'] = $user['nick_name'];
                    $repackData['redpack_from_userid'] = '0';
                    $repackData['redpack_from_username'] = '系统';
                    $repackData['redpack_billno'] = $data['mch_billno'];
                    $redpackM = new Wechat_Model_Redpack('wechat');
                    $redpackM->insertRow($repackData);
                    
                    $result['errnum'] = 0;
                    $result['errmsg'] = '发放成功';
                    $redArr = array();
                    $redArr['result_code'] = $data['result_code'];
                    $redArr['mch_billno'] = $data['mch_billno'];
                    $redArr['mch_id'] = $data['mch_id'];
                    $redArr['wxappid'] = $data['wxappid'];
                    $redArr['re_openid'] = $data['re_openid'];
                    $redArr['total_amount'] = $data['total_amount']/100;
                    $redArr['send_listid'] = $data['send_listid'];
                    $redArr['send_time'] = strtotime($data['send_time']);
                    $redArr['return_msg'] = $data['return_msg'];
                    $result['result'] = $redArr;
                    return json_encode($result);
                } elseif(isset($data['return_code']) && $data['return_code'] == 'FAIL' && isset($data['result_code']) && $data['result_code'] == 'FAIL') {
                    $result['errnum'] = 1;
                    $result['errmsg'] = isset($data['err_code_des']) ? $data['err_code_des'] : '红包发放失败，请检查接口服务！';
                    $redArr = array();
                    $redArr['result_code'] = $data['result_code'];
                    $redArr['err_code'] = $data['err_code'];
                    $redArr['err_code_des'] = $data['err_code_des'];
                    $redArr['mch_billno'] = $data['mch_billno'];
                    $redArr['mch_id'] = $data['mch_id'];
                    $redArr['wxappid'] = $data['wxappid'];
                    $redArr['re_openid'] = $data['re_openid'];
                    $redArr['total_amount'] = $data['total_amount']/100;
                    $redArr['return_msg'] = $data['return_msg'];
                    $result['result'] = $redArr;
                    return json_encode($result);
                }else{
                    $result['errnum'] = 1;
                    $result['errmsg'] = '微信红包发放失败，请稍后再试2！';
                    return json_encode($result);
                }
	}
	
	/**
	 * 
	 * Enter description here ...
	 * @param string(28) $mch_billno
	 * @param string(32) $mch_id
	 * @param string(32) $appid
	 * @param string(32) $bill_type
	 */
	public function gethbinfo($mch_billno,$mch_id,$appid,$bill_type = 'MCHT'){
		if(empty($mch_billno))
			exit('$mch_billno不能为空！');
		if(empty($mch_id))
			exit('$mch_id不能为空!');
		if(empty($appid))
			exit('$appid不能为空！');
		$redpackinfo = new Wechat_Redpack_RedpackInfo();
		$redpackinfo->setParameter('nonce_str', $redpackinfo->great_rand());
		$redpackinfo->setParameter('mch_billno', $mch_billno);
		$redpackinfo->setParameter('mch_id', $mch_id);
		$redpackinfo->setParameter('appid', $appid);
		$redpackinfo->setParameter('bill_type', $bill_type);
		$postXml = $redpackinfo->create_hongbao_xml($this->partnerkey);
		$url = $this->gethongbaoUrl();
		$responseXml = $redpackinfo->curl_post_ssl($url, $postXml);
		$responseObj = simplexml_load_string($responseXml,'SimpleXMLElement',LIBXML_NOCDATA);
		$data = json_decode(json_encode($responseObj), true);
                $result = array();
                if (empty($data) || !is_array($data)) {
                    $result['errnum'] = 1;
                    $result['errmsg'] = '微信红包查询接口有误，请稍后再试！';
                    return json_encode($result);
                }
                if (isset($data['return_code']) && $data['return_code'] == 'SUCCESS' && isset($data['result_code']) && $data['result_code'] == 'SUCCESS') {
                    $result['errnum'] = 0;
                    $result['errmsg'] = '查询成功';
                    $redArr = array();
                    $redArr['mch_billno'] = $data['mch_billno'];
                    $redArr['mch_id'] = $data['mch_id'];
                    $redArr['total_amount'] = $data['amount']/100;
                    $redArr['send_time'] = strtotime($data['send_time']);
                    $redArr['refund_time'] = strtotime($data['Refund_time']);
                    $redArr['reason'] = $data['reason'];
                    $redArr['rcv_time'] = strtotime($data['Rcv_time']);
                    $redArr['status'] = $data['status'];
                    $result['result'] = $redArr;
                    return json_encode($result);
                } elseif(isset($data['return_code']) && $data['return_code'] == 'FAIL' && isset($data['result_code']) && $data['result_code'] == 'FAIL') {
                    $result['errnum'] = 1;
                    $result['errmsg'] = isset($data['err_code_des']) ? $data['err_code_des'] : '红包查询失败，请检查接口服务！';
                    return json_encode($result);
                }else{
                    $result['errnum'] = 1;
                    $result['errmsg'] = '微信红包查询失败，请稍后再试！';
                    return json_encode($result);
                }
	}
	
}