<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class vop_finder_sales 
{
    var $detail_items = '明细信息';
    
    function detail_items($sale_id){

        $render   = app::get('vop')->render();
        
        $saleMdl = app::get('vop')->model('sales');
        $columns = $saleMdl->_columns();
        
        $sales = $saleMdl->db_dump($sale_id);
        $sales['in_ar'] = $columns['in_ar']['type'][$sales['in_ar']];
        
        $render->pagedata['data'] = [
            'header' => $columns,
            'body' => $sales,
        ];
        

        $itemMdl = app::get('vop')->model('sales_items');
        $items = app::get('vop')->model('sales_items')->getList('*', ['sale_id'  => $sale_id]);
        $render->pagedata['lines'] = [
            'header' => $itemMdl->_columns(),
            'body' => $items,
        ];
        
        return $render->fetch('finder/detail.html', 'desktop');
    }
}