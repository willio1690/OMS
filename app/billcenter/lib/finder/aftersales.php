<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class billcenter_finder_aftersales
{
    var $detail_items = '明细信息';
    
    function detail_items($aftersale_id){
        
        $render   = app::get('billcenter')->render();
        
        $saleMdl = app::get('billcenter')->model('aftersales');
        $columns = $saleMdl->_columns();
        
        $sales = $saleMdl->db_dump($aftersale_id);
        $sales['in_ar'] = $columns['in_ar']['type'][$sales['in_ar']];

        foreach ($columns as $key => $column) {
            if ($column['type'] == 'time') {
                $sales[$key] = $sales[$key] ? date("Y-m-d H:i:s", $sales[$key]) : '';
            }
        }
        
        $render->pagedata['data'] = [
            'header' => $columns,
            'body' => $sales,
        ];
        
        
        $itemMdl = app::get('billcenter')->model('aftersales_items');
        $items = app::get('billcenter')->model('aftersales_items')->getList('*', ['aftersale_id'  => $aftersale_id]);
        $render->pagedata['lines'] = [
            'header' => $itemMdl->_columns(),
            'body' => $items,
        ];
        
        return $render->fetch('finder/detail.html', 'desktop');
    }
}