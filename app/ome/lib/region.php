<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class ome_region{

    function get_region_node($region_ids){
        if($region_ids){
            $oRegion = kernel::single('eccommon_regions');
            $unset_array = array();
            $t_region_ids = $region_ids;
            foreach($t_region_ids as $k=>$v){
                if(strpos($v,"|")){
                    $tmp = explode("|",$v);
                    $region_ids[$k] = $tmp[0];
                    $v = $tmp[0];
                }
                if(!in_array($v,$unset_array)){
                    $region_info = $oRegion->getOneById($v);
                    $region_path = explode(",",$region_info['region_path']);
                    array_shift($region_path);
                    array_pop($region_path);
                    array_pop($region_path);
                    $unset_array = array_merge($unset_array,$region_path);
                }
            }

            $ret = array_diff($region_ids,$unset_array);
            return $ret;
        }else{
            return array();
        }
    }
    /*
     * 暂时废弃不用
     */
    function get_region_list($region_ids){
        $oRegion = kernel::single('eccommon_regions');
        $ret_region_ids = array();
        $ret_region_names = array();

        if($region_ids){
            foreach($region_ids as $region_id){
                if(!in_array($region_id,$ret_region_ids)){
                    $region = $oRegion->getOneById($region_id,"local_name,region_path");
                    $ret_region_ids[] = $region_id;
                    $region_name = $region['local_name'];

                    $region_path = explode(",",$region['region_path']);
                    array_shift($region_path);
                    array_pop($region_path);
                    array_pop($region_path);

                    if($region_path){
                        rsort($region_path);
                        foreach($region_path as $v){
                            $region = $oRegion->getOneById($v,"local_name,region_path");
                            if(!in_array($v,$ret_region_ids)){
                                $ret_region_ids[] = $v;
                            }
                            $region_name = $region['local_name']."/".$region_name;
                        }
                    }
                    $ret_region_names[] = $region_name;
                }
            }
            sort($ret_region_ids);
            $ret = array(
                'region_ids' => $ret_region_ids,
                'region_names' => $ret_region_names,
            );
            return $ret;
        }else{
            return array();
        }
    }
    
    /**
     * 兼容匹配平台特殊省份及自治区
     *
     * @param $province
     * @return mixed|string
     */
    public function matchingReceiverProvince($province)
    {
        $mapping = array(
            '新疆' => '新疆维吾尔自治区',
            '宁夏' => '宁夏回族自治区',
            '广西' => '广西壮族自治区',
        );
        
        //特殊自治区处理
        if ($mapping[$province]) {
            return $mapping[$province];
        }
        
        $zhixiashi = array('北京','上海','天津','重庆');
        $zizhiqu = array('内蒙古','宁夏回族','新疆维吾尔','西藏','广西壮族');
        
        //format
        if (in_array($province, $zhixiashi)) {
            $province = $province . '市';
        }elseif(in_array($province, $zizhiqu)) {
            $province = $province . '自治区';
        }elseif(!preg_match('/(.*?)省/', $province)){
            $province = $province . '省';
        }
        
        return $province;
    }
}