<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */


class o2o_regions_select
{
    /**
     * 通过p_region_id，区域层级来得到地区的信息
     * @params object app object
     * @params string p_region_id
     * @params array 参数数组 - depth
     * @params string 当前激活的regions id
     */
    public function get_area_select($p_region_id, $params, $selected_id=null)
    {
        $regionsObj        = app::get('eccommon')->model('regions');
        
        $params['depth']   = ($params['depth'] ? $params['depth'] : 1);
        
        $html = '<select onchange="selectO2oArea(this,this.value,'.($params['depth']+1).')">';
        $html.='<option value="_NULL_">请选择...</option>';
        
        $where    = '1';
        if($p_region_id)
        {
            $where    .= " AND b.p_region_id=" . $p_region_id;
        }
        $region_grade    = 'region_' . $params['depth'];
        
        $sql    = "SELECT b.region_id, b.local_name, b.haschild FROM sdb_o2o_store_regions AS a
                   LEFT JOIN sdb_eccommon_regions AS b ON a.". $region_grade ."=b.region_id
                   WHERE ". $where ." GROUP BY a.". $region_grade ." ORDER BY a.". $region_grade ." ASC";
        $rows   = $regionsObj->db->select($sql);
        if ($rows)
        {
            foreach ($rows as $item)
            {
                if ($item['region_grade']<=app::get('eccommon')->getConf('system.area_depth'))
                {
                    $selected = $selected_id == $item['region_id']?'selected="selected"':'';

                    // 查找当前地区是否有子集
                    if ($item['haschild'] == 1)
                    {
                        $html.= '<option has_c="true" value="'.$item['region_id'].'" '.$selected.'>'.$item['local_name'].'</option>';
                    }
                    else
                    {
                        $html.= '<option value="'.$item['region_id'].'" '.$selected.'>'.$item['local_name'].'</option>';
                    }
                }
                else
                {
                    $no = true;
                }
            }

            $html.='</select>';
            if($no) $html="";

            return $html;
        }
        else
        {
            return false;
        }
    }
}
