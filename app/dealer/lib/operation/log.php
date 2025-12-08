<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class dealer_operation_log
{

    //操作日志
    /**
     * 获取_operations
     * @return mixed 返回结果
     */
    public function get_operations()
    {
        $operations = array(
            'dealer_series_add'  => ['name' => '产品线新增', 'type' => 'series@dealer'],
            'dealer_series_edit' => ['name' => '产品线编辑', 'type' => 'series@dealer'],
            'order_create'       => ['name' => '平台订单创建', 'type' => 'platform_orders@dealer'],
            'order_edit'         => ['name' => '平台订单编辑', 'type' => 'platform_orders@dealer'],
            'order_modify'       => ['name' => '平台订单修改', 'type' => 'platform_orders@dealer'],
            'order_back'         => ['name' => '打回发货单', 'type' => 'platform_orders@dealer'],
            'order_confirm'      => ['name' => '平台订单确认', 'type' => 'platform_orders@dealer'],
            'order_dispose'      => ['name' => '平台订单处理', 'type' => 'platform_orders@dealer'],
            'aftersale'          => ['name' => '售后处理', 'type' => 'platform_aftersale@dealer'],
            'dealer_bbu_add'     => ['name' => '销售团队添加', 'type' => 'bbu@dealer'],
            'dealer_bbu_edit'    => ['name' => '销售团队编辑', 'type' => 'bbu@dealer'],
            'dealer_betc_add'    => ['name' => '贸易公司添加', 'type' => 'betc@dealer'],
            'dealer_betc_edit'   => ['name' => '贸易公司编辑', 'type' => 'betc@dealer'],
            'dealer_bs_add'      => ['name' => '经销商添加', 'type' => 'bs@dealer'],
            'dealer_bs_edit'     => ['name' => '经销商编辑', 'type' => 'bs@dealer'],
            'set_shop_yjdfType'  => ['name' => '设置发货方式', 'type' => 'series_endorse_products@dealer'],
            'dealer_sm_add'      => ['name' => '销售物料添加', 'type' => 'sales_material@dealer'],
            'dealer_sm_edit'     => ['name' => '销售物料编辑', 'type' => 'sales_material@dealer'],
            'dealer_series_on'   => ['name' => '产品线开启', 'type' => 'series@dealer'],
            'dealer_series_off'  => ['name' => '产品线关停', 'type' => 'series@dealer'],
            'dealer_goods_price_add'  => ['name' => '经销商品价格新增', 'type' => 'goods_price@dealer'],
            'dealer_goods_price_edit' => ['name' => '经销商品价格编辑', 'type' => 'goods_price@dealer'],
            'dealer_goods_price_batch_update_start_time' => ['name' => '批量编辑生效时间', 'type' => 'goods_price@dealer'],
            'dealer_goods_price_batch_update_end_time' => ['name' => '批量编辑过期时间', 'type' => 'goods_price@dealer'],
        );

        return array('dealer' => $operations);
    }

}
