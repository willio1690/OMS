<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/*
 * 20160504
 * wangjianjun
 */
class omeauto_view_list{
    
    function list_region($params){
        $objRegionsLi = kernel::single('omeauto_regions_li');
        
        $html = '<ul id="con_m_list">';
        $html .= $objRegionsLi->get_area_li($params);
        $html .= '</ul>';
        
        return $html;
    }
    
}
