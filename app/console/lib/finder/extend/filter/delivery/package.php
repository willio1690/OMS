<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class console_finder_extend_filter_delivery_package
{
    /**
     * 获取_extend_colums
     * @return mixed 返回结果
     */
    public function get_extend_colums()
    {
        $deliveryLib = kernel::single('console_delivery');
        
        //京东包裹状态
        $status = $deliveryLib->getPackageStatus();
        
        //dbschame
        $db['delivery_package'] = array(
            'columns' => array(
                'status' => array (
                    'type' => $status,
                    'label' => '包裹状态',
                    'editable' => false,
                    'in_list' => true,
                    'default_in_list' => true,
                    'filtertype' => 'normal',
                    'filterdefault' => true,
                    'order' => 12,
                ),
                'order_bn'  => array(
                    'type' => 'varchar(50)',
                    'label' => '订单号',
                    'editable' => false,
                    'filtertype' => 'textarea',
                    'filterdefault' => true,
                ),
                /****
                'pay_status' => array(
                    'type' => array(
                        0 => '未支付',
                        1 => '已支付',
                        2 => '处理中',
                        3 => '部分付款',
                        4 => '部分退款',
                        5 => '全额退款',
                        6 => '退款申请中',
                        7 => '退款中',
                        8 => '支付中',
                    ),
                    'default'         => '0',
                    'label'           => '订单付款状态',
                    'width'           => 75,
                    'editable'        => false,
                    'filtertype'      => 'yes',
                    'filterdefault'   => true,
                    'in_list'         => true,
                    'default_in_list' => true,
                ),
                'ship_status' => array(
                    'type' => array(
                        0 => '未发货',
                        1 => '已发货',
                        2 => '部分发货',
                        3 => '部分退货',
                        4 => '已退货',
                    ),
                    'default'         => '0',
                    'label'           => '订单发货状态',
                    'width'           => 130,
                    'editable'        => false,
                    'filtertype'      => 'yes',
                    'filterdefault'   => true,
                    'in_list'         => true,
                    'default_in_list' => true,
                ),
                ***/
            ),
        );

        return $db;
    }
}
