<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * 订单处理
 *
 * @author wangbiao@shopex.cn
 * @version $Id: Z
 */
class erpapi_shop_matrix_dewu_request_order extends erpapi_shop_request_order
{
    // 查询买家地址接口,【品牌直发】（仅支持履约模式为商家指定物流的订单)
    /**
     * 获取BuyerAddress
     * @param mixed $order_bn order_bn
     * @return mixed 返回结果
     */

    public function getBuyerAddress($order_bn = '')
    {
        $orderMdl  = app::get('ome')->model('orders');
        $orderInfo = $orderMdl->db_dump(['order_bn' => $order_bn]);
        if (!$orderInfo) {
            $error_msg = $order_bn . '订单信息不存在';
            return ['rsp' => 'fail', 'msg' => $error_msg, 'data' => '', 'msg_code' => ''];
        }
        $orderExtend                                                 = app::get('ome')->model('order_extend')->db_dump(['order_id' => $orderInfo['order_id']]);
        $orderExtend['extend_field'] && $orderExtend['extend_field'] = json_decode($orderExtend['extend_field'], 1);
        if (strtolower($orderInfo['shop_type']) != 'dewu' || !kernel::single('ome_order_bool_type')->isDWBrand($orderInfo['order_bool_type']) || $orderExtend['extend_field']['performance_type'] != '2') {
            $error_msg = $order_bn . '仅支持履约模式为商家指定物流的订单';
            return ['rsp' => 'fail', 'msg' => $error_msg, 'data' => '', 'msg_code' => ''];
        }

        $title      = '查询买家地址接口';
        $timeout    = 30;
        $primary_bn = $order_bn;

        //请求参数
        $param = [
            'order_no' => $order_bn,
        ];

        //request
        $result = $this->__caller->call(STORE_ORDER_BRAND_DELIVER_QUERY_BUYER_ADDRESS, $param, [], $title, $timeout, $primary_bn);

        if ($result['data']) {
            $result['data'] = json_decode($result['data'], true);
        }

        return $result;
    }

    // 接单(针对履约模式为多仓的品牌直发订单),履约模式为多仓的品牌直发订单需要使用该接口。买家下单后，商家需要先接单，然后获取运单号，再发货
    /**
     * acceptOrder
     * @param mixed $order_bn order_bn
     * @param mixed $address_id ID
     * @return mixed 返回值
     */
    public function acceptOrder($order_bn = '', $address_id)
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

        $title      = '接单';
        $timeout    = 30;
        $primary_bn = $order_bn;

        //请求参数
        $param = [
            'order_no'   => $order_bn, // 订单号,注意：订单号和订单号list两者必须且只允许传入一个参数
            'address_id' => $address_id, // 发货地址ID
            'type'       => '1', // 1:接单并获取运单号（默认），0:接单但不获取运单号
            // 'order_no_list' => [], // 最多支持8个订单，如果多个订单，此时为合并发货，注意商家是否有合并发货权限
        ];

        //request
        $result = $this->__caller->call(STORE_ORDER_BRAND_DELIVER_ACCEPT_ORDER, $param, [], $title, $timeout, $primary_bn);

        if ($result['data']) {
            $result['data'] = json_decode($result['data'], true);
        }

        // 接单成功以后，修改接单状态
        if ($result['rsp'] == 'succ' && $result['data']) {
            app::get('ome')->model('order_extend')->update(['platform_logi_no' => $result['data']['logistics_no']], ['order_id' => $orderInfo['order_id']]);
        }
        return $result;
    }

}
