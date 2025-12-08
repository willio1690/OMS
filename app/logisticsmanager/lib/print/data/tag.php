<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * @author ykm 2015-12-28
 * @describe 处理自定义大头笔
 */
class logisticsmanager_print_data_tag {

    /**
     * 获取PrintTag
     * @param mixed $oriData ID
     * @param mixed $tagIds ID
     * @return mixed 返回结果
     */

    public function getPrintTag(&$oriData, $tagIds) {
        $zhixiashi = array('北京','上海','天津','重庆');
        $areaGAT = array('香港','澳门','台湾');
        $printTagObj = app::get('ome')->model('print_tag');
        $rows = $printTagObj->getList('tag_id,config', array('tag_id'=>$tagIds));
        foreach($rows as $row){
            foreach($oriData as &$data){
                $area2Str = substr($data['ship_district'],-3);
                $key = 'print_tag_'.$row['tag_id'];
                $tagConfig= unserialize($row['config']);
                if ($data['ship_province'] && in_array($data['ship_province'],$zhixiashi)) {
                    if($tagConfig['zhixiashi'] == '1'){
                        $data[$key] = $data['ship_district'];
                    }else{
                        $data[$key] = $data['ship_city'].$data['ship_district'];
                    }
                } elseif($data['ship_province'] && in_array($data['ship_province'],$areaGAT)) {
                    if($tagConfig['areaGAT'] == '1'){
                        $data[$key] = $data['ship_district'];
                    }else{
                        $data[$key] = $data['ship_city'].$data['ship_district'];
                    }
                } else {
                    $data[$key] = '';
                    if($tagConfig['province'] == '1'){
                        $data[$key] .= $data['ship_province'];
                    }

                    if ($area2Str=='区') {
                        if($tagConfig['district'] == '1'){
                            $data[$key] .= $data['ship_city'];
                        }else{
                            $data[$key] .= $data['ship_city'].$data['ship_district'];
                        }
                    } elseif ($area2Str=='市') {
                        if($tagConfig['city'] == '1'){
                            $data[$key] .= $data['ship_city'].$data['ship_district'];
                        }else{
                            $data[$key] .= $data['ship_district'] ? $data['ship_district'] : $data['ship_city'];
                        }
                    } else {
                        if($tagConfig['county'] == '1'){
                            $data[$key] .= $data['ship_district'] ? $data['ship_district'] : $data['ship_city'];
                        }else{
                            $data[$key] .= $data['ship_city'].$data['ship_district'];
                        }
                    }
                }
            }
        }
    }
}