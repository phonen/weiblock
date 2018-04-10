<?php
namespace WeChat\Action;
/**
 * 基础控制器    
 */
use Think\Controller;
class BaseAction extends Controller { 
	public function __construct(){
		parent::__construct();
		//exit('The WebSite Is Close');
		//初始化系统信息   
		$m = D('WeChat/System');
		$GLOBALS['CONFIG'] = $m->loadConfigs();  
		$this->assign('CONF',$GLOBALS['CONFIG']);
		$this->assign('WST_V', C('WST_VERSION'));
		$this->assign("WST_USER", session('WST_USER'));
		//用户信息
  	    $user = M('Users')->where(array('userId'=>session('WST_USER.userId')))->field('userId,userName,loginName,wxOpenId,recommend_user_id,redPacketMoney,userMoney,distributMoney,is_first_login,play_count')->find();
  	    $this->assign('user', $user);
		//WSTIsWeixin();//检测是否在微信浏览器上使用
		if($_REQUEST['state']==$GLOBALS['CONFIG']['WeiXinAppCode']){
	        WSTBindWeixin();
		}
	}
	/**
	 * 定位所在城市
	 */
	public function getDefaultCity(){
		$areas= D('WeChat/Areas');
		return $areas->getDefaultCity();
	}
    /**
     * 是否已登录
     */
    public function isLogin(){
      $USER = session('WST_USER');
      if (empty($USER)){
        if(IS_AJAX){
          die("{status:-999}");
        }else{
          D('users')->shareUser((int)session('WST_USER.userId'));
          $this->redirect("Users/index");
        }
      }
    }
	/**
	 * 产生验证码图片
	 *
	 */
	public function getVerify(){
		// 导入Image类库
		$Verify = new \Think\Verify();
		$Verify->entry();
	}
	/**
	 * 核对单独的验证码
	 * $re = false 的时候不是ajax返回
	 * @param  boolean $re [description]
	 * @return [type]      [description]
	 */
	public function checkCodeVerify($re = true){
		$code = I('code');
		$verify = new \Think\Verify(array('reset'=>false));
		$rs =  $verify->check($code);
		if ($re == false) return $rs;
		else $this->ajaxReturn(array('status'=>(int)$rs));
	}
	/**
	 * 上传图片
	 */
	public function uploadImage(){
		$this->isLogin();
		$rs = array('status'=>-1);
		$base64Image = I('imgData');//图片的base64值
		$saveRootPath = I('saveRootPath','Upload/');//保存的根路径
		$imgPath = I('saveModulePath','uploads').'/'.I('saveSubPath', date('Y-m'));//保存目录，默认uploads
		$exts = array('jpg','png','gif','jpeg'); //允许的后缀
		$nameRule = time().rand(1111,9999);//命名
	
		WSTCreateDir(WSTRootPath()."/".$saveRootPath.$imgPath."/");
	
		if (preg_match('/^(data:\s*image\/(\w+);base64,)/', $base64Image, $result)){
			$ext = $result[2];//图片后缀
			if( in_array($ext, $exts) ){
				$newFile = $saveRootPath.$imgPath.'/'.$nameRule.'.'.$ext;//保存的名称
				if (file_put_contents($newFile, base64_decode(str_replace($result[1], '', $base64Image)))){
					if(file_exists($newFile)){
						//生成缩略图
						$images = new \Think\Image();
						$images->open($newFile);
						$newsavename = str_replace('.','_thumb.', $nameRule.'.'.$ext);//缩略图名称
						$vv = $images->thumb(I('width',200), I('height',200))->save($saveRootPath.$imgPath.'/'.$newsavename);
						if(C('WST_M_IMG_SUFFIX')!=''){
							$msuffix = C('WST_M_IMG_SUFFIX');
							$mnewsavename = str_replace('.',$msuffix.'.', $nameRule.'.'.$ext);
							$mnewsavename_thmb = str_replace('.',"_thumb".$msuffix.'.', $nameRule.'.'.$ext);
							$images->open($newFile);
							$images->thumb(I('width',600), I('height',600))->save($saveRootPath.$imgPath.'/'.$mnewsavename);
							$images->thumb(I('width',200), I('height',200))->save($saveRootPath.$imgPath.'/'.$mnewsavename_thmb);
							$rs['filepath'] = $saveRootPath.$imgPath.'/'.$mnewsavename;
							$rs['filethumbpath'] = $saveRootPath.$imgPath.'/'.$mnewsavename_thmb;
						}else{
							$rs['filepath'] = $newFile;
							$rs['filethumbpath'] = $saveRootPath.$imgPath.'/'.$newsavename;
						}
						$rs['status'] = 1;
						$rs['msg'] = '图片保存成功';
					}else{
						$rs['status'] = -2;
						$rs['msg'] = '图片保存失败';
					}
				}else{
					$rs['status'] = -2;
					$rs['msg'] = '图片保存失败';
				}
			}else{
				$rs['status'] = -3;
				$rs['msg'] = '图片后缀应为jpg、jpeg、png或gif';
			}
		}else{
			$rs['status'] = -4;
			$rs['msg'] = '图片数据格式错误';
		}
		$this->ajaxReturn($rs);
	}
	/**
	 * 空操作处理
	 */
	public function _empty(){
		$this->redirect("Index/index");
	}
}