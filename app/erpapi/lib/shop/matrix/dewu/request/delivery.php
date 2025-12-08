<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * 发货单处理
 *
 * @author wangbiao@shopex.cn
 * @version $Id: Z
 */
class erpapi_shop_matrix_dewu_request_delivery extends erpapi_shop_request_delivery
{
    // 订单发货仓库修改(仅针对已接单的多仓模式的品牌直发待发货订单，支持选择无运单号情况下的仅改仓，也支持作废运单号同时改仓)
    /**
     * changeDeliveryWarehouse
     * @param mixed $order_bn order_bn
     * @param mixed $address_id ID
     * @param mixed $is_cancel_logistic_no is_cancel_logistic_no
     * @return mixed 返回值
     */

    public function changeDeliveryWarehouse($order_bn = '', $address_id, $is_cancel_logistic_no = 0)
    {
        $orderMdl  = app::get('ome')->model('orders');
        $orderInfo = $orderMdl->db_dump(['order_bn' => $order_bn]);
        if (!$orderInfo) {
            $error_msg = $order_bn . '订单信息不存在';
            return ['rsp' => 'fail', 'msg' => $error_msg, 'data' => '', 'msg_code' => ''];
        }
        $orderExtend                                                 = app::get('ome')->model('order_extend')->db_dump(['order_id' => $orderInfo['order_id']]);
        $orderExtend['extend_field'] && $orderExtend['extend_field'] = json_decode($orderExtend['extend_field'], 1);
        if (strtolower($orderInfo['shop_type']) != 'dewu' || !kernel::single('ome_order_bool_type')->isDWBrand($orderInfo['order_bool_type']) || $orderExtend['extend_field']['performance_type'] != '3') {
            $error_msg = $order_bn . '仅支持履约模式为多仓的品牌直发订单';
            return ['rsp' => 'fail', 'msg' => $error_msg, 'data' => '', 'msg_code' => ''];
        }

        if (!$address_id) {
            $error_msg = '发货地址ID不能为空';
            return ['rsp' => 'fail', 'msg' => $error_msg, 'data' => '', 'msg_code' => ''];
        }

        $title      = '订单发货仓库修改';
        $timeout    = 30;
        $primary_bn = $order_bn;

        //请求参数
        $param = [
            'order_no'              => $order_bn, // 订单号
            'is_cancel_logistic_no' => 1, // 是否作废运单号（0：否（默认），1：是）
            'address_id'            => $address_id, // 发货地址ID
        ];

        //request
        $result = $this->__caller->call(STORE_ORDER_BRAND_DELIVER_CHANGE_DELIVERY_WAREHOUSE, $param, [], $title, $timeout, $primary_bn);

        if ($result['data']) {
            $result['data'] = json_decode($result['data'], true);
        }

        return $result;
    }

    /**
     * confirm
     * @param mixed $sdf sdf
     * @param mixed $queue queue
     * @return mixed 返回值
     */
    public function confirm($sdf, $queue = false)
    {

        $orderInfo = $sdf['orderinfo'];
        $order_bn  = $orderInfo['order_bn'];

        if (strtolower($orderInfo['shop_type']) != 'dewu' || !kernel::single('ome_order_bool_type')->isDWBrand($orderInfo['order_bool_type'])) {

            // 如果不是品牌直发，走默认回写
            $result = parent::confirm($sdf, $queue);
            return $result;
        }

        // 发货记录
        $opInfo = kernel::single('ome_func')->getDesktopUser();
        $log_id = uniqid($_SERVER['HOSTNAME']);
        $log    = array(
            'shopId'           => $this->__channelObj->channel['shop_id'],
            'ownerId'          => $opInfo['op_id'],
            'orderBn'          => $sdf['orderinfo']['order_bn'],
            'deliveryCode'     => $sdf['logi_no'],
            'deliveryCropCode' => $sdf['logi_type'],
            'deliveryCropName' => $sdf['logi_name'],
            'receiveTime'      => time(),
            'status'           => 'send',
            'updateTime'       => '0',
            'oid_list'         => $sdf['oid_list'] ? implode(',', $sdf['oid_list']) : '',
            'message'          => '',
            'log_id'           => $log_id,
        );

        $shipmentLogModel = app::get('ome')->model('shipment_log');
        $shipmentLogModel->insert($log);

        // 更新订单状态
        $orderModel = app::get('ome')->model('orders');
        $orderModel->update(array('sync' => 'run'), array('order_id' => $sdf['orderinfo']['order_id'], 'sync|noequal' => 'succ'));

        $title      = sprintf('发货状态回写[%s]-%s', $sdf['delivery_bn'], $sdf['orderinfo']['order_bn']);
        $timeout    = 30;
        $primary_bn = $order_bn;

        //请求参数
        $param = [
            'order_no'     => $order_bn, // 订单号
            'express_type' => $sdf['logi_type'], // 物流承运商(RRS:日日顺,AD:安得物流,AX:安迅物流,SN:苏宁物流,HX:海信物流,EMS:中国邮政,SF:顺丰,JD:京东,DB:德邦，ZT:中通)，注意：如果获取运单号接口入参type为2，也就是只获取运单号不发货，这里必填
            'express_no'   => $sdf['logi_no'], // 快递单号,注意：如果获取运单号接口入参type为2，也就是只获取运单号不发货，这里必填
        ];

        //request
        $result = $this->__caller->call(STORE_ORDER_BRAND_DELIVER_DELIVERY, $param, [], $title, $timeout, $primary_bn);

        if ($result['rsp'] == 'fail') {
            $shipmentLogModel->update(array('status' => 'fail', 'message' => $result['err_msg'], 'updateTime' => time()), array('log_id' => $log_id));
        }

        if ($result['data']) {
            $result['data'] = json_decode($result['data'], true);
        }

        $callback_params = [
            'shipment_log_id' => $log_id,
            'order_id'        => $sdf['orderinfo']['order_id'],
            'logi_no'         => $sdf['logi_no'],
            'obj_bn'          => $sdf['orderinfo']['order_bn'],
        ];
        $this->confirm_callback($result, $callback_params);

        return $result;
    }

}
