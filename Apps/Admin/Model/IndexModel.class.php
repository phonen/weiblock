<?php
namespace Admin\Model;
/**
 * 首页服务类
 */
class IndexModel extends BaseModel {
	/**
	 * 获取商品配置分类信息
	 */
    public function loadConfigsForParent(){
		$sql = "select * from ".$this->tablePrefix."sys_configs where fieldType!='hidden' order by parentId asc,fieldSort asc";
		$rs = $this->query($sql);
		$configs = array();
		if(count($rs)>0){
			foreach ($rs as $key=>$v){
				if($v['fieldType']=='radio' || $v['fieldType']=='select'){
					$v['txt'] = explode('||',$v['valueRangeTxt']);
					$v['val'] = explode(',',$v['valueRange']);
				}
				$configs[$v['parentId']][] = $v;
			}
		}
		unset($rs);
		return $configs;
	}
	
	/**
	 * 保存商城配置信息
	 */
	public function saveConfigsForCode(){
		$rd = array('status'=>-1);
		$sql = "select * from ".$this->tablePrefix."sys_configs where fieldType!='hidden' order by parentId asc,fieldSort asc";
		$rs = $this->query($sql);
		if(!empty($rs)){
			$m = M('sys_configs');
			foreach ($rs as $key => $v){
				$result = $m-> where('fieldCode="'.$v['fieldCode'].'"')->setField('fieldValue',WSTAddslashes(I($v['fieldCode'])));
				if(false === $result){
				    $rd['status']= -1;
				}
			}
			$rd['status'] = 1;
			WSTDataFile("mall_config",'',null);
		}
		return $rd;
	}
	

	 

}