<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * 订单挽单
 * Class erpapi_shop_response_process_deliverypriority
 */
class erpapi_shop_response_process_deliverypriority extends erpapi_shop_response_abstract
{
    /**
     * comeback
     * @param mixed $order order
     * @return mixed 返回值
     */

    public function comeback($order)
    {
        $business_type        = ['10001' => '优先发货', '10002' => '催发货'];
        $fulfillment_biz_type = $order['fulfillmentBizType'];
        
        //订单挽回打标
        $orderMdl = app::get('ome')->model('orders');
        switch ($fulfillment_biz_type) {
            case '10001':
                $order_bool_type = $order['order_bool_type'] | ome_order_bool_type::__COME_BACK;
                $title           = $business_type[$fulfillment_biz_type];
                break;
            case '10002':
                $order_bool_type = $order['order_bool_type'] | ome_order_bool_type::__URGENT_DELIVERY;
                $title           = $business_type[$fulfillment_biz_type];
                break;
            default:
                $title = '类型错误';
                break;
        }
        $result = $orderMdl->update(array('order_bool_type' => $order_bool_type), array('order_id' => $order['order_id']));
        if ($result === false) {
            return array('rsp' => 'fail', 'msg' => sprintf('订单%s标识,失败!', $title));
        }
        
        $data = [
            'tid' => $order['order_bn'],
        ];
        
        //日志
        $memo = sprintf('订单%s', $title) . '，时间：' . date('Y-m-d H:i:s', time());
        app::get('ome')->model('operation_log')->write_log('order_modify@ome', $order['order_id'], $memo);
        
        return array('rsp' => 'succ', 'msg' => sprintf('订单%s标识成功!', $title), 'data' => $data);
    }
}