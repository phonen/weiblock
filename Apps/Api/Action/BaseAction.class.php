<?php
namespace Api\Action;
/**
 * 基础控制器    
 */
use Think\Controller;  
class BaseAction extends Controller { 
	public function __construct(){
		parent::__construct();
		//exit('The WebSite Is Close');
		//初始化系统信息   
		//初始化系统信息   
		$m = D('Common/System');
		$GLOBALS['CONFIG'] = $m->loadConfigs();    
	}
	
}