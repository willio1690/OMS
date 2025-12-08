<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class vop_finder_vreturn_diff
{
    var $detail_items = '明细信息';
    
    function detail_items($diff_id){

        $render   = app::get('vop')->render();
        
        $vreturnDiffMdl = app::get('vop')->model('vreturn_diff');
        $columns = $vreturnDiffMdl->_columns();
        
        $vreturnDiff = $vreturnDiffMdl->db_dump($diff_id);

        $shop = app::get('ome')->model('shop')->db_dump(['shop_id' => $vreturnDiff['shop_id']], 'name');
        $vreturnDiff['shop_id'] = $shop['name'];
        
        $render->pagedata['data'] = [
            'header' => $columns,
            'body' => $vreturnDiff,
        ];
        

        $itemMdl = app::get('vop')->model('vreturn_diff_items');
        $items = $itemMdl->getList('*', ['diff_id'  => $diff_id]);
        foreach ($items as $key => $item) {
            $items[$key]['sku_img'] = $item['sku_img'] ? explode(',', $item['sku_img']) : [];
        }

        $itemsColumns = $itemMdl->_columns();
        $itemsColumns['sku_img']['filtertype'] = 'image';
        // echo "<pre>";print_r($itemsColumns);
        $render->pagedata['lines'] = [
            'header' => $itemsColumns,
            'body' => $items,
        ];
        
        return $render->fetch('finder/detail.html', 'desktop');
    }
}