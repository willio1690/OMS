<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * 重试推送wms失败的发货单取消 
 *
 *
 * @author
 * @version 0.1
 */

class ome_autotask_timer_retrydeliverycancel extends ome_autotask_timer_common
{
    private $ttl = 60*6; // kv过期时间

    public function process($params, &$error_msg = '')
    {
        set_time_limit(0);
        ignore_user_abort(1);

        base_kvstore::instance('ome/retrydeliverycancel')->fetch('process_status', $process_status);
        if (!$process_status) {
            $process_status = 'finish';
        }
        if ($process_status == 'running') {
            $error_msg = 'is running';
            return true; // 时间未到
        }

        // $db = kernel::database();

        // ==================================================================
        // ==================================================================

        base_kvstore::instance('ome/retrydeliverycancel')->store('process_status', 'running', $this->ttl);

        $orderMdl           = app::get('ome')->model('orders');
        $oderObjectMdl      = app::get('ome')->model('order_objects');
        $deliveryMdl        = app::get('ome')->model('delivery');
        $deliveryItemDtlMdl = app::get('ome')->model('delivery_items_detail');
        $refundApplyMdl     = app::get('ome')->model('refund_apply');
        $dlyOrderMdl        = app::get('ome')->model('delivery_order');
        $apiFailMdl         = app::get('erpapi')->model('api_fail');


        // 每次取最早30条，order by last_modified asc
        $retryError = [ 
            '系统繁忙',
            '请稍后再试',
            '不存在',
            'timed out',
            '请求超时',
        ];
        // 发货单取消推送wms失败
        $filter = array(
            'sync_status'           => '4', // 取消失败
            'status'                => ['ready','progress'],
            'parent_id'             => '0',
            'filter_sql'            => ' sync_msg REGEXP "'.implode('|',$retryError).'"',
            // 'delivery_bn'           => '25052610029788',
        );
        $deliveryList = $deliveryMdl->getList('delivery_id, delivery_bn', $filter, 0 ,30, 'last_modified ASC');
        
        if (!$deliveryList) {
            $error_msg = 'no info to work';
            return $this->_finish();
        }

        $deliveryList = array_column($deliveryList, null, 'delivery_id');
        $deliveryIds  = array_column($deliveryList, 'delivery_id');
        $deliveryBns  = array_column($deliveryList, 'delivery_bn');

        // 检测失败重试里，是否有发货单推送失败的，有则过滤掉不处理
        $addDeliveryFailList = $apiFailMdl->getList('obj_bn', ['obj_type'=>'delivery', 'obj_bn|in'=>$deliveryBns]);
        if ($addDeliveryFailList) {
            $addDeliveryFailList = array_column($addDeliveryFailList, 'obj_bn');
        }

        foreach ($deliveryList as $delivery_id => $dv) {
            // 先更新最后更新时间，保证数据流动
            $deliveryMdl->update(['last_modified'=>time()], ['delivery_id'=>$delivery_id]);

            if (in_array($dv['delivery_bn'], $addDeliveryFailList)) {
                unset($deliveryList[$delivery_id]);
            }
        }
        if (!$deliveryList) {
            $error_msg = 'no normal info to work';
            return $this->_finish();
        }


        // 根据delivery_id获取sdb_ome_delivery_items_detail里的oid,order_id,order_obj_id
        $deliveryItemDtlList = $deliveryItemDtlMdl->getList('delivery_id,oid,order_id,order_obj_id', ['delivery_id' => $deliveryIds]);
        if (!$deliveryItemDtlList) {
            $error_msg = 'delivery_items_detail is null';
            return $this->_finish();
        }
        foreach ($deliveryItemDtlList as $dv) {
            $deliveryList[$dv['delivery_id']]['detail'][$dv['order_id']][] = $dv;
        }

        // 根据delivery_id获取对应的order_id
        $deliveryOrderList = $dlyOrderMdl->getList('*', ['delivery_id|in' => $deliveryIds]);
        $orderIds = array_column($deliveryOrderList, 'order_id');
        if (!$orderIds) {
            $error_msg = 'delivery_order is null';
            return $this->_finish();
        }

        // 获取order信息
        $orderList = $orderMdl->getList('order_id, pay_status, status, process_status', ['order_id|in' => $orderIds]);
        $orderList = array_column($orderList, null, 'order_id');
        if (!$orderList) {
            $error_msg = 'orders is null';
            return $this->_finish();
        }
        foreach ($deliveryOrderList as $_doinfo) {
            $deliveryList[$_doinfo['delivery_id']]['orders'][$_doinfo['order_id']] = $orderList[$_doinfo['order_id']];
        }

        // 根据order_id获取order_object信息
        $orderObjectList = $oderObjectMdl->getList('order_id,oid,pay_status', ['order_id|in'=>$orderIds]);
        $orderObjectList = array_column($orderObjectList, null, 'order_id');
        if (!$orderObjectList) {
            $error_msg = 'order_objects is null';
            return $this->_finish();
        }

        // 根据order_id获取退款状态是‘已退款’的退款单申请单
        $refundApplyList = [];
        $_refundApplyList = $refundApplyMdl->getList('*', [
            'order_id'      => $orderIds, 
            'status|in'     => ['4'],
        ]);
        foreach ($_refundApplyList as $_rainfo) {
            $_rainfo['product_data'] = @unserialize($_rainfo['product_data']);
            //价保退款标识
            if ($_rainfo['bool_type'] & ome_refund_bool_type::__PROTECTED_CODE) {
                $_rainfo['isPriceProtect'] = true;
            }
            $refundApplyList[$_rainfo['order_id']][] = $_rainfo;
        }

        foreach ($deliveryList as $delivery_id => $delivery) {

            $memo = '系统自动重试发货单取消 ';

            // 订单是全额退款的，并且订单状态是active的，直接cancel
            foreach ($delivery['detail'] as $order_id => $deliveryDetails) {
                if (!isset($delivery['orders'][$order_id]) || !$delivery['orders'][$order_id]) {
                    unset($delivery['detail'][$order_id]);
                    continue;
                }
                if (!isset($refundApplyList[$order_id]) || !$refundApplyList[$order_id]) {
                    unset($delivery['detail'][$order_id]);
                    continue;
                }

                if ($delivery['orders'][$order_id]['pay_status'] == '5') {
                    if ($delivery['orders'][$order_id]['status'] == 'active' && in_array($delivery['orders'][$order_id]['process_status'], ['splitting', 'splited'])) {
                        $res = $orderMdl->cancel($order_id, $memo, 'false', 'async', false);
                    }
                    unset($delivery['detail'][$order_id]);
                }
            }

            // 暂时只处理订单全额退款的单据
            // 暂时只处理订单全额退款的单据
            // $deliveryMdl->update(['last_modified'=>time()], ['delivery_id'=>$delivery_id]);
            continue;
            // 暂时只处理订单全额退款的单据
            // 暂时只处理订单全额退款的单据

            if (!$delivery['detail']) {
                continue;
            }

            // 取消发货单
            $result = $deliveryMdl->rebackDelivery($delivery_id, $memo);
            if (!$result) {
                continue;
            }

            // delivery对应的order，如果pay_status=5，直接调用ordercancel
            // deliver下的oid对应的退款申请单
            // 再用退款申请单查order_obj，pay_status是不是5，不是5，调用_autoEditorder，如果是5，continue。

            foreach ($delivery['detail'] as $order_id => $deliveryDetails) {

                // 退款申请单详情
                $refundApplys = $refundApplyList[$order_id];
                if (!$refundApplys) {
                    // 如果没有对应的退款申请单，只取消发货单
                    continue;
                }

                // 订单详情
                $orderDetails = [];
                if ($orderObjectList[$order_id]) {
                    $orderDetails = array_column($orderObjectList[$order_id], null, 'oid');
                }

                $deliveryOids = array_column($deliveryDetails, 'oid');
                $deliveryDetails = array_column($deliveryDetails, null, 'oid');

                foreach ($refundApplys as $_k => $refundApply) {
                    $refundApplyOids = array_column($refundApply['product_data'], 'oid');
                    if (!$refundApplyOids) {
                        continue;
                    }
                    // 判断当前发货单详情是否在退款申请单的详情里,如果一张订单有多张发货单，退款申请一次全退，暂不处理
                    if (count(array_intersect($deliveryOids, $refundApplyOids)) != count($refundApplyOids)) {
                        continue;
                    }

                    // 退款申请单的oid对应的order_object的oid如果有pay_status=5，暂不处理这个退款申请单
                    foreach ($refundApplyOids as $_refundOid) {
                        if (isset($orderDetails[$_refundOid]) && $orderDetails[$_refundOid]['pay_status']== '5') {
                            continue 2;
                        }
                    }

                    // 组参数把order_objcts明细做删除处理
                    $sdf = [
                        'order' => [
                            'order_id' => $refundApply['order_id'],
                        ],
                        'reason'            =>  $memo,
                        'refund_item_list'  =>  $refundApply['product_data'],
                        // 'oid'               =>  $refundApply['oid'],
                        'refund_fee'        =>  $refundApply['money'],
                        'shop_type'         =>  $refundApply['shop_type'],
                        'isPriceProtect'    =>  $refundApply['isPriceProtect'],
                        'tmall_mcard_pz_sp' =>  '0',
                    ];
                    $error_msg = '';
                    $is_abnormal = false;
                    $isResultEdit = kernel::single('erpapi_shop_response_process_aftersalev2')->_autoEditorder($sdf, $error_msg, $is_abnormal);

                }
            }

        }

        return $this->_finish();
    }


    private function _finish($status = true, $process_status = 'finish')
    {
        base_kvstore::instance('ome/retrydeliverycancel')->store('process_status', $process_status, $this->ttl);
        return $status;
    }

}
