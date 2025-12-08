<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * @author ykm 2022/6/28 17:28:44
 * @describe 订单接收回调
 */
class erpapi_shop_response_plugins_order_received extends erpapi_shop_response_plugins_order_abstract
{
    /**
     * convert
     * @param erpapi_shop_response_abstract $platform platform
     * @return mixed 返回值
     */

    public function convert(erpapi_shop_response_abstract $platform)
    {
        $ordersdf                    = $platform->_ordersdf;
        $sdf = [];
        if($ordersdf['order_source'] == 'maochao') {
            $sdf['received'] = true;
        }
        
        return $sdf;
    }

    /**
     * 订单完成后处理
     **/
    public function postCreate($order_id, $sdf)
    {
        if($sdf['received']) {
            kernel::single('ome_event_trigger_shop_order')->received($order_id);
        }
    }

    /**
     * 更新后操作
     *
     * @return void
     * @author
     **/
    public function postUpdate($order_id, $sdf)
    {
       
    }
}
