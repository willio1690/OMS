<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class finance_shop_relation{

    /**
     * 店铺绑定
     */
    public function bind($shop_id){
        $shop = app::get('ome')->model('shop')->dump($shop_id);
        if ($shop_id && $shop['node_type'] == 'taobao') {
            kernel::single('erpapi_router_request')->set('shop', $shop_id)->finance_bill_account_get();
        }
        return true;
    }
}