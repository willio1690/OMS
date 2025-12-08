<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/* 
 * 20160504
 * wangjianjun
 */
class omeauto_regions_li{

    /**
     * 获取_area_li
     * @param mixed $params 参数
     * @return mixed 返回结果
     */
    public function get_area_li($params){
	    $regionsObj = app::get('eccommon')->model('regions');
        
	    if($params["p_region_id"]){
	        $filter_arr = array("p_region_id"=>$params["p_region_id"]);
	    }else{
	        //新增或无p_region_id，则只需显示一级区域的checkbox列表即可
	        $filter_arr = array("region_grade"=>1);
	    }
	    
	    $html = '';
	    
	    $rows = $regionsObj->getList("*",$filter_arr);
	    if ($rows){
	        foreach ($rows as $item){
	            if ($item['region_grade'] <= app::get('eccommon')->getConf('system.area_depth')){
	                $html.= '<li style="cursor:pointer" value="'.$item['region_id'].'" onclick="doSelfRegion(this);">'.$item['local_name'].'</li>';
	            }else{
	                $no = true;
	            }
	        }
	    
	        if($no) $html="";
	    
	        return $html;
	    }else{
	        return "<li>无下级地区</li>";
	    }
	    
	}
}
