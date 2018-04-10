<?php
namespace Common\Model;
/**
 * 首页
 */
class IndexModel extends BaseModel {
	/**
	 * 获取城市列表
	 */
	public function getCitys(){
		$key = WSTAddslashes(I('key'));
		$key2 = WSTAddslashes(I('key2'));
		$sql = "select areaId,areaName from __PREFIX__areas where  areaType=1 ";
		if($key !='') $sql .= " and areaName like '%".$key."%'";
		$sql .= " and areaFlag=1 and isShow=1 order by areaSort asc ,areaKey asc,areaId asc";
		$city = $this->query($sql);
		if($key==''&& $key2=='') return $city;
		$city = $city[0];
		if($key2=='') return $city;
		$sql = "select areaId,areaName from __PREFIX__areas where areaType=2 and parentId=".$city['areaId'];
		if($key2 !='')$sql.=" and areaName like '%".$key2."%' ";
		$sql .= " and areaFlag=1 and isShow=1 order by areaSort asc ,areaKey asc,areaId asc";
		$city2 = $this->query($sql);
		$city['areaId2'] = $city2[0]['areaId'];
		$city['areaName2'] = $city2[0]['areaName'];
		return $city;
	}
	/**
	 * 获取所有城市-根据字母分类
	 */
	public function getCityGroupByKey(){
		$rs = array();
		$m = D('WeChat/Areas');
		$rslist = $m->cache('WST_CACHE_CITY_000',31536000)->where('isShow=1 AND areaFlag = 1 AND areaType=1')->field('areaId,areaName,areaKey')->order('areaKey, areaSort')->select();
		foreach ($rslist as $key =>$row){
			$rs[$row["areaKey"]][] = $row;
		}
		return $rs;
	}
}