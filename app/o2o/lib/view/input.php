<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */


class o2o_view_input
{
    function input_region($params)
    {
        $regionsObj    = app::get('eccommon')->model('regions');
        $package       = 'mainland';
        
        if($params['required'] == 'true')
        {
            $req = ' vtype="area"';
        }else{
            $req = ' vtype='.$params['vtype'];
        }

        $objRegionsSelect    = kernel::single('o2o_regions_select');
        
        if(!$params['value'])
        {
            $html    = '<span package="'.$package.'" class="span _x_ipt"'.$req.'>';
            $html    .= '<input '. ( $params['id']?' id="'.$params['id'].'"  ':'' ) .' type="hidden" name="'.$params['name'].'" />';
            $html    .= $objRegionsSelect->get_area_select(null,$params);
            $html    .= '</span>';
            return $html;
        }
        else
        {
            list($package,$region_name,$region_id) = explode(':',$params['value']);
            $arr_region_name    = explode("/", $region_name);
            $depth              = count($arr_region_name);
            
            if(!is_numeric($region_id))
            {
                $html    = '<span package="'.$package.'" class="span _x_ipt"'.$req.'>';
                $html    .= '<input '. ( $params['id']?' id="'.$params['id'].'"  ':'' ) .' type="hidden" name="'.$params['name'].'" />';
                $html    .= $objRegionsSelect->get_area_select(null,$params);
                $html    .= '</span>';
                return $html;
            }
            else
            {
                #地区级数
                $regionsInfo   = $regionsObj->dump(array('region_id'=>$region_id), 'p_region_id, region_grade');
                $depth         = $regionsInfo['region_grade'];
                $p_region_id   = $regionsInfo['p_region_id'];
                
                #循环地区查询(最大程序上匹配地区)
                $region    = array();
                while ($depth > 0)
                {
                    $region_grade    = 'region_' . $depth;
                    $sql             = "SELECT b.region_id, b.p_region_id, b.region_grade FROM sdb_o2o_store_regions AS a 
                                        LEFT JOIN sdb_eccommon_regions AS b ON a.". $region_grade ."=b.region_id 
                                        WHERE a.". $region_grade ."=". $region_id;
                    $region          = $regionsObj->db->selectRow($sql);
                    if($region)
                    {
                        break;
                    }
                    
                    $depth--;
                    
                    #上一级地区
                    $regionsInfo   = $regionsObj->dump(array('region_id'=>$p_region_id), 'region_id, p_region_id');
                    $region_id     = $regionsInfo['region_id'];
                    $p_region_id   = $regionsInfo['p_region_id'];
                }
                unset($region_id, $depth, $p_region_id);
                
                #格式化
                $depth        = $region['region_grade'];
                $region_id    = $region['region_id'];
                unset($region, $regionsInfo);
                
                #
                $arr_regions = array();
                $ret = '';
                while($region_id && ($region = $regionsObj->dump($region_id,'region_id,local_name,p_region_id'))){
                    $params['depth'] = $depth--;
                    array_unshift($arr_regions,$region);
                    if($region_id = $region['p_region_id']){
                        $notice = "-";
                        $data = $objRegionsSelect->get_area_select($region['p_region_id'],$params,$region['region_id']);
                        if(!$data){
                            $notice = "";
                        }
                        $ret = '<span class="x-region-child">&nbsp;'.$notice.'&nbsp'.$objRegionsSelect->get_area_select($region['p_region_id'],$params,$region['region_id']).$ret.'</span>';
                    }else{
                        $ret = '<span package="'.$package.'" class="span _x_ipt"'.$req.'><input type="hidden" value="'.$params['value'].'" name="'.$params['name'].'" />'.$objRegionsSelect->get_area_select(null,$params,$region['region_id']).$ret.'</span>';
                    }
                }
                if(!$ret){
                    $ret = '<span package="'.$package.'" class="span _x_ipt"'.$req.'><input type="hidden" value="" name="'.$params['name'].'" />'.$objRegionsSelect->get_area_select(null,$params,$region['region_id']).'</span>';
                }
                
                return $ret;
            }
        }
    }
}
