<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class ediws_finder_accountorders
{
    
    
    public $detail_items = '详情列表';
    /**
     * detail_items
     * @param mixed $ord_id ID
     * @return mixed 返回值
     */
    public function detail_items($ord_id)
    {

        $render = app::get('ediws')->render();
       
        
        $itemsMdl = app::get('ediws')->model('account_orders');

        $items = $itemsMdl->dump(array('ord_id'=>$ord_id),'*');

        
        $render->pagedata['items'] = $items;
        return $render->fetch('accountorders.html', 'ediws');
        
    }

   
    
}
