<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

$db['delivery_items_detail'] = array(
    'columns' => array(
        'item_detail_id'   => array(
            'type'     => 'int unsigned',
            'required' => true,
            'pkey'     => true,
            'extra'    => 'auto_increment',
            'editable' => false,
            'comment' => '自增主键ID'
        ),
        'delivery_id'      => array(
            'type'     => 'table:delivery@ome',
            'required' => true,
            'editable' => false,
            'comment' => '发货单ID,关联ome_delivery.delivery_id',
        ),
        'delivery_item_id' => array(
            'type'     => 'int unsigned',
            'required' => true,
            'editable' => false,
            'comment'  => '发货单明细ID,关联ome_delivery_items.item_id',
        ),
        'order_id'         => array(
            'type'     => 'table:orders@ome',
            'required' => true,
            'editable' => false,
            'comment'  => '订单ID,关联ome_orders.order_id',
        ),
        'order_item_id'    => array(
            'type'     => 'int unsigned',
            'required' => true,
            'editable' => false,
            'comment'  => '订单明细ID,关联ome_order_items.item_id',
        ),
        'order_obj_id'     => array(
            'type'     => 'int unsigned',
            'required' => true,
            'editable' => false,
            'comment' => '订单子单ID,关联ome_order_objects.obj_id',
        ),
        'item_type'        => array(
            'type' => 'varchar(50)',
            'default'  => 'product',
            'required' => true,
            'editable' => false,
            'comment' => '订单行明细类型,可选值:product(商品),pkg(捆绑商品),gift(赠品),giftpackage(礼包),lkb(福袋),pko(多选一)',
        ),
        'product_id'       => array(
            'type'     => 'table:products@ome',
            'required' => true,
            'editable' => false,
            'comment' => '基础物料ID,关联material_basic_material.bm_id',
        ),
        'bn'               => array(
            'type'     => 'varchar(200)',
            'editable' => false,
            'is_title' => true,
            'comment' => '基础物料编码',
        ),
        'number'           => array(
            'type'     => 'number',
            'required' => true,
            'editable' => false,
            'comment' => '需发货数',
        ),
        'price'            => array(
            'type'     => 'money',
            'default'  => '0',
            'required' => true,
            'editable' => false,
            'comment' => '订单行明细零售价',
        ),
        'amount'           => array(
            'type'     => 'money',
            'default'  => '0',
            'required' => true,
            'editable' => false,
            'comment' => '订单行明细零售小计,公式: price * number',
        ),
        'is_wms_gift'      => array(
            'type'            => 'bool',
            'default'         => 'false',
            'label'           => '是否WMS赠品',
            'editable'        => false,
            'filtertype'      => 'normal',
            'in_list'         => true,
            'default_in_list' => false,
        ),
        'oid'              => array(
            'type'     => 'varchar(50)',
            'default'  => 0,
            'editable' => false,
            'label'    => '子订单号',
        ),
        's_type'            => array(
            'type'    => 'varchar(50)',
            'label'   => '销售类型',
            'default' => 'zx',
        ),
        'divide_order_fee' => array (
            'type' => 'money',
            'editable' => false,
            'default'  => '0',
            'label' => '实付金额',
        ),
        'divide_user_fee' => array (
            'type' => 'money',
            'editable' => false,
            'default'  => '0',
            'label' => '用户实付',
        ),
        'retail_price' => array (
            'type' => 'money',
            'editable' => false,
            'default'  => '0',
            'label' => '零售价',
        ),
        'origin_amount' => array(
            'type' => 'money',
            'editable' => false,
            'default' => '0.00',
            'label' => '货品单件价格',
            'width' => 110,
        ),
        'total_price' => array(
            'type' => 'money',
            'editable' => false,
            'default' => '0.00',
            'label' => '行小计,公式: price * number',
            'width' => 110,
        ),
        'total_promotion_amount' => array(
            'type' => 'money',
            'editable' => false,
            'default' => '0.00',
            'label' => 'SKU商品优惠小计',
            'width' => 110,
        ),
    ),
    'index'   => array(
        'ind_delivery_item_id' => array(
            'columns' => array(
                0 => 'delivery_item_id',
            ),
        ),
        'ind_order_item_id'    => array(
            'columns' => array(
                0 => 'order_item_id',
            ),
        ),
        'ind_order_obj_id'     => array(
            'columns' => array(
                0 => 'order_obj_id',
            ),
        ),
        'ind_oid'              => array(
            'columns' => array(
                0 => 'oid',
            ),
        ),

    ),
    'comment' => '发货单明细详情,用于存储以订单行明细纬度数量.如:合单发货会以订单行明细平铺',
    'engine'  => 'innodb',
    'version' => '$Rev:  $',
);
