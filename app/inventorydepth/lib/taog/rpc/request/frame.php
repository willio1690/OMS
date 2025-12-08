<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * 更新商品上下架，RPC实现类
 *
 * @author chenping<chenping@shopex.cn>
 */

class inventorydepth_taog_rpc_request_frame extends ome_rpc_request {

    /**
     * @description
     * @access public
     * @param void
     * @return void
     */
    public function __construct() 
    {
    }

    /**
     * 更新商品上下架
     *
     * @param Array $approve_status 上下架参数
     * @param String $shop_id 店铺ID
     * @param Array $addon 附加参数
     * @return Array
     **/
    public function approve_status_list_update($approve_status,$shop_id)
    {
        if(!$approve_status || !$shop_id) return false;
        kernel::single('erpapi_router_request')->set('shop', $shop_id)->product_approveStatusListUpdate($approve_status);
    }

    /**
     * 单个更新上下架
     *
     * @return void
     * @author
     **/
    public function approve_status_update($approve,$shop_id)
    {
        if(!$approve || !$shop_id) return false;
        return kernel::single('erpapi_router_request')->set('shop', $shop_id)->product_approveStatusUpdate($approve);
    }
}
