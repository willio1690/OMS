<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class ediws_finder_refundinfo
{
    
    
    public $detail_items = '明细列表';
    /**
     * detail_items
     * @param mixed $id ID
     * @return mixed 返回值
     */
    public function detail_items($id)
    {

        $render = app::get('ediws')->render();
       
        
        $itemsMdl = app::get('ediws')->model('refundinfo_items');

        $items = $itemsMdl->getlist('*',array('refundinfo_id'=>$id));

        $render->pagedata['items'] = $items;
        unset($items);
        return $render->fetch('refundinfo_items.html');
        
    }

   
    
}
