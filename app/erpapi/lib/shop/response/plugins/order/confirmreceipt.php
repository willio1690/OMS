<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 *
 * @author sunjing@shopex.cn
 * @version
 */
class erpapi_shop_response_plugins_order_confirmreceipt extends erpapi_shop_response_plugins_order_abstract
{
    /**
     * convert
     * @param erpapi_shop_response_abstract $platform platform
     * @return mixed 返回值
     */

    public function convert(erpapi_shop_response_abstract $platform)
    {
        $extend = array();

        if ($platform->_ordersdf['end_time']) {
            $extend['end_time'] = $platform->_ordersdf['end_time'];
        }

        return $extend;
    }

    /**
     *
     * @param Array
     * @return void
     * @author
     **/
    public function postUpdate($order_id, $extendinfo)
    {
        $orderObj = app::get('ome')->model('orders');

        if ($extendinfo['end_time']) {
            $orderObj->update(array('end_time' => $extendinfo['end_time']), array('order_id' => $order_id));

            // 自动开票
            $einvoice = app::get('invoice')->model('order')->getList('*', array('order_id' => $order_id, 'is_status' => '0'), 0, 1, 'id DESC');
            if ($einvoice) {
                $billing = array(
                    "id"       => $einvoice[0]['id'],
                    "order_id" => $order_id,
                );
                kernel::single('invoice_process')->billing($billing, 'sign');
            }

            // 订单签收后触发服务
            foreach(kernel::servicelist('ome.service.order.sign.after') as $service) {
                if(method_exists($service,'after_sign')) {
                    $payload = [];
                    if (isset($extendinfo['end_time'])) {
                        $payload['end_time'] = $extendinfo['end_time'];
                    }
                    $service->after_sign($order_id, $payload);
                }
            }
        }
    }
}
