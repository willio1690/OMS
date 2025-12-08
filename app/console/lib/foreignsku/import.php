<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class console_foreignsku_import
{
    function run(&$cursor_id,$params)
    {
        $wfsObj = app::get('console')->model('foreign_sku');
        foreach($params['sdfdata'] as $k=>$value)
        {
            $inner_sku = trim($value['inner_sku']);
            $inner_sku = str_replace(array("'", '"'), '', $inner_sku);
            
            $wms_id = $value['wms_id'];
            
            //替换空格及特殊字符
            if($value['outer_sku'])
            {
                $value['outer_sku'] = trim($value['outer_sku']);
                $value['outer_sku'] = str_replace(array("'", '"'), '', $value['outer_sku']);
            }
            
            if($value['oms_sku'])
            {
                $value['oms_sku'] = trim($value['oms_sku']);
                $value['oms_sku'] = str_replace(array("'", '"'), '', $value['oms_sku']);
            }
            
            $foreign_detail = $wfsObj->getList('inner_sku',array('inner_sku'=>$inner_sku,'wms_id'=>$wms_id));
            if (empty($foreign_detail[0]['inner_sku']))
            {
                $wfsObj->insert($value);
            }
        }
        
        return false;
    }
}
?>