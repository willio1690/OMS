<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * 订单发货插件
 * 当订单状态为finish但发货单未发货时自动发货
 *
 * @category 
 * @package 
 * @author 
 * @version $Id: delivery.php
 */
class erpapi_shop_response_plugins_order_delivery extends erpapi_shop_response_plugins_order_abstract
{
    /**
     * 数据转换，判断是否需要自动发货
     *
     * @param erpapi_shop_response_abstract $platform 平台对象
     * @return array 返回是否需要自动发货的标记
     */
    public function convert(erpapi_shop_response_abstract $platform)
    {
        $autoDelivery = array();
        
        // 只在更新订单时判断
        if (!$platform->_tgOrder) {
            return $autoDelivery;
        }
        
        // 检查新订单状态是否为finish
        if (isset($platform->_ordersdf['status']) && $platform->_ordersdf['status'] == 'finish') {
            // 直接使用 $platform->_tgOrder 获取当前订单信息
            $orderInfo = $platform->_tgOrder;
            
            // 检查订单是否未发货或部分发货 (ship_status: 0-未发货, 1-已发货, 2-部分发货)
            if ($orderInfo['ship_status'] != '1') {
                // 标记需要自动发货
                $autoDelivery['need_auto_delivery'] = true;
                $autoDelivery['order_id'] = $orderInfo['order_id'];
                $autoDelivery['order_bn'] = $orderInfo['order_bn'];
            }
        }
        
        return $autoDelivery;
    }

    /**
     * 订单保存之后处理
     *
     * @param int $order_id 订单ID
     * @param array $params 参数
     * @return void
     */
    public function postCreate($order_id, $params)
    {
        // 创建订单时不处理自动发货
    }

    /**
     * 订单更新之后处理
     * 根据convert返回的标记判断是否需要自动发货
     *
     * @param int $order_id 订单ID
     * @param array $params convert返回的参数
     * @return void
     */
    public function postUpdate($order_id, $params)
    {
        // 检查是否需要自动发货
        if (!isset($params['need_auto_delivery']) || !$params['need_auto_delivery']) {
            return;
        }

        if (!$order_id) {
            return;
        }

        // 获取订单信息
        $orderObj = app::get('ome')->model('orders');
        $orderInfo = $orderObj->db_dump(['order_id' => $order_id], 'order_id,order_bn,status,ship_status');
        
        if (!$orderInfo) {
            return;
        }

        // 再次确认订单状态（双重保险）
        if ($orderInfo['status'] == 'finish' || $orderInfo['ship_status'] == '1') {
            return;
        }

        // 查找该订单的未发货发货单
        $deliveryObj = app::get('ome')->model('delivery');
        $deliveryOrderObj = app::get('ome')->model('delivery_order');
        
        // 获取该订单关联的发货单
        $deliveryOrders = $deliveryOrderObj->getList('delivery_id', ['order_id' => $order_id]);
        
        if (!$deliveryOrders) {
            return;
        }

        $delivery_ids = array_column($deliveryOrders, 'delivery_id');
        
        // 查找未发货的发货单 (只处理主发货单或独立发货单，不处理子发货单)
        $deliveryList = $deliveryObj->getList('delivery_id,delivery_bn,status,process', [
            'delivery_id' => $delivery_ids,
            'status' => ['ready', 'progress'], // 只处理待发货和处理中的发货单
            'process' => 'false', // 未处理的
            'parent_id' => 0 // 只处理主发货单或独立发货单
        ]);

        if (!$deliveryList) {
            return;
        }

        // 记录日志对象
        $operationLogObj = app::get('ome')->model('operation_log');

        // 逐个发货单进行自动发货
        foreach ($deliveryList as $delivery) {
            try {
                // 调用发货接口
                $deliveryReceive = kernel::single('ome_event_receive_delivery');
                
                $data = [
                    'status' => 'delivery',
                    'delivery_bn' => $delivery['delivery_bn'],
                ];

                $result = $deliveryReceive->update($data);

                // 记录日志
                if ($result && isset($result['rsp']) && $result['rsp'] == 'succ') {
                    $deliveryObj    = app::get('wap')->model('delivery');
                    $wapDly = $deliveryObj->db_dump(['outer_delivery_bn'=>$delivery['delivery_bn']], 'delivery_id');
                    if($wapDly) {
                        #wap发货单更新
                        $dlydata    = array();
                        $delivery_time    = time();
                        
                        $dlydata['status'] = 3;
                        $dlydata['process_status'] = 7;
                        $dlydata['last_modified'] = $delivery_time;
                        $dlydata['delivery_time'] = $delivery_time;
                        $deliveryObj->update($dlydata, ['delivery_id'=>$wapDly['delivery_id'], 'status|noequal'=>'3']);
                    }
                    $operationLogObj->write_log('delivery_process@ome', $delivery['delivery_id'], 
                        '订单状态为finish，自动发货成功，订单号：' . $orderInfo['order_bn']);
                } else {
                    $error_msg = isset($result['msg']) ? $result['msg'] : '未知错误';
                    $operationLogObj->write_log('delivery_modify@ome', $delivery['delivery_id'], 
                        '订单状态为finish，自动发货失败：' . $error_msg . '，订单号：' . $orderInfo['order_bn']);
                }
            } catch (Exception $e) {
                // 捕获异常，记录日志但不中断流程
                $operationLogObj->write_log('delivery_modify@ome', $delivery['delivery_id'], 
                    '订单状态为finish，自动发货异常：' . $e->getMessage() . '，订单号：' . $orderInfo['order_bn']);
            }
        }
    }
}

