<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */


class wap_event_trigger_delivery{

    /**
     *
     * 移动端门店自提单确认可自提回传
     * @param string $wms_id 仓库类型ID
     * @param array $data 请求参数
     * @param string $sync 是否同步请求，true为同步，false异步，默认异步
     */
    public function confirm($channel_id, &$data, $sync = false){
        $data['status'] = 'confirm';
        return kernel::single('erpapi_router_response')->set_channel_id($channel_id)->set_api_name('store.delivery.status_update')->dispatch($data);
    }

    /**
     *
     * 移动端门店自提单拒绝并打回
     * @param string $wms_id 仓库类型ID
     * @param array $data 请求参数
     * @param string $sync 是否同步请求，true为同步，false异步，默认异步
     */
    public function reback($channel_id, &$data, $sync = false){
        $data['status'] = 'cancel';
        return kernel::single('erpapi_router_response')->set_channel_id($channel_id)->set_api_name('store.delivery.status_update')->dispatch($data);
    }

    /**
     *
     * 移动端门店自提单发货完成回传
     * @param string $wms_id 仓库类型ID
     * @param array $data 请求参数
     * @param string $sync 是否同步请求，true为同步，false异步，默认异步
     */
    public function consign($channel_id, &$data, $sync = false){
        $data['status'] = 'delivery';
        return kernel::single('erpapi_router_response')->set_channel_id($channel_id)->set_api_name('store.delivery.status_update')->dispatch($data);
    }

    /**
     *
     * 移动端门店订单签收收货
     * 
     * @param string $wms_id 仓库类型ID
     * @param array $data 请求参数
     * @param string $sync 是否同步请求，true为同步，false异步，默认异步
     */
    public function sign($channel_id, &$data, $sync = false){
        $data['status'] = 'sign';
        return kernel::single('erpapi_router_response')->set_channel_id($channel_id)->set_api_name('store.delivery.status_update')->dispatch($data);
    }

    /**
     *
     * WMS发货单内容更新发起通知OMS的方法
     * @param string $channel_id 仓库类型ID
     * @param array $data 请求参数
     * @param string $sync 是否同步请求，true为同步，false异步，默认异步
     */
    public function doUpdate($channel_id, &$data, $sync = false){
        $data['status'] = 'update';
        return kernel::single('erpapi_router_response')->set_channel_id($channel_id)->set_api_name('wms.delivery.status_update')->dispatch($data);
    }

    /**
     *
     * WMS发货单打印完成发起通知OMS的方法
     * @param string $wms_id 仓库类型ID
     * @param array $data 请求参数
     * @param string $sync 是否同步请求，true为同步，false异步，默认异步
     */
    public function doPrint($channel_id, &$data, $sync = false){
        $data['status'] = 'print';
        return kernel::single('erpapi_router_response')->set_channel_id($channel_id)->set_api_name('wms.delivery.status_update')->dispatch($data);
    }
}

?>
