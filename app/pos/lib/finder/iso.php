<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class pos_finder_iso
{
    var $detail_basic = '商品明细';
    
    function detail_basic($iso_id)
    {
        $render                          = app::get('pos')->render();
        $isoItems                        = app::get('pos')->model('iso_items');
        $itemsList                       = $isoItems->getList('*', ['iso_id' => $iso_id]);
        $render->pagedata['order_items'] = $itemsList;
        return $render->fetch('admin/iso/detail_goods.html');
    }
}