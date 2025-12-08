<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * 美团闪购订单处理
 *
 * @category
 * @package
 * @author system
 * @version $Id: order.php
 */
class erpapi_shop_matrix_meituan4sg_request_order extends erpapi_shop_request_order
{
    /**
     * 订单确认接口
     * 调用store.trade.confirm
     */

    public function confirm($order)
    {
        if (empty($order) || empty($order['order_bn'])) {
            return $this->error('订单信息不完整');
        }
        
        // 组织接口参数
        $api_params = array(
            'order_id' => $order['order_bn']
        );
        
        $title = sprintf('美团闪购订单确认[%s]', $order['order_bn']);
        
        // 调用接口，使用父类的callback方法
        $callback = array(
            'class' => get_class($this),
            'method' => 'callback',
        );
        $response = $this->__caller->call(STORE_TRADE_CONFIRM, $api_params, $callback, $title, 10, $order['order_bn']);
        
        return $response;
    }
    
} 