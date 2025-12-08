<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

$db['sales_items'] = array(
    'columns' => array(
        'item_id'           => array(
            'type'     => 'int unsigned',
            'required' => true,
            'pkey'     => true,
            'extra'    => 'auto_increment',
        ),
        'sale_id'           => array(
            'type'     => 'bigint unsigned',
            'required' => true,
            'comment'  => '销售单编号id',
        ),
        'product_id'        => array(
            'type'     => 'table:products@ome',
            'required' => true,
            'default'  => 0,
            'editable' => false,
            'comment'  => '货品ID',
        ),
        'bn'                => array(
            'type'            => 'varchar(200)',
            'required'        => true,
            'filtertype'      => 'normal',
            'filterdefault'   => true,
            'default_in_list' => true,
            'in_list'         => true,
            'label'           => '货号',
            'comment'         => '货号',
        ),
        'name'              => array(
            'type'    => 'varchar(255)',
            'default' => '',
            'comment' => '商品名称',
            'label'   => '商品名称',
        ),
        'pmt_price'         => array(
            'type'     => 'money',
            'default'  => '0',
            'editable' => false,
            'label'    => '商品优惠价',
            'comment'  => '商品优惠价',
        ),
        'orginal_price'     => array(
            'type'    => 'money',
            'default' => 0,
            'label'   => '商品原始价格',
            'comment' => '商品原始价格',
        ),
        'price'             => array(
            'type'    => 'money',
            'default' => 0,
            'label'   => '销售价格',
            'comment' => '销售价格',
        ),
        'spec_name'         => array(
            'type'    => 'varchar(255)',
            'default' => '',
            'label'   => '商品规格',
            'comment' => '商品规格',
        ),
        'nums'              => array(
            'type'     => 'mediumint',
            'required' => true,
            'comment'  => '销售数量',
        ),
        'sales_amount'      => array(
            'type'            => 'money',
            'default'         => '0',
            'filterdefault'   => true,
            'default_in_list' => true,
            'in_list'         => true,
            'label'           => '销售额',
            'comment'         => '正销售:商品在订单实际成交金额；（原始金额-优惠金额-其他费用）;负销售:退款金额',
            'order'           => '14',
        ),
        'sale_price'        => array(
            'type'    => 'money',
            'default' => '0',
            'label'   => '订单明细货品销售价',
        ),
        'apportion_pmt'     => array(
            'type'    => 'money',
            'default' => '0',
            'label'   => '平摊优惠金额',
        ),
        'refund_money'        => array(
            'type'     => 'money',
            'default'  => '0',
            'label'    => '退款费用',
        ),
        'cost'              => array(
            'type'    => 'money',
            'default' => 0,
            'comment' => '成本价格',
        ),
        'cost_amount'       => array(
            'type'            => 'money',
            'default'         => 0,
            'label'           => '成本金额',
            'filterdefault'   => true,
            'default_in_list' => true,
            'comment'         => '数量*成本单价',
        ),
        'gross_sales'       => array(
            'type'    => 'money',
            'default' => 0,
            'label'   => '销售毛利',
            'comment' => '商品的销售毛利',
            'in_list' => true,
        ),
        'gross_sales_rate'  => array(
            'type'    => 'decimal(10,2)',
            'default' => 0,
            'label'   => '毛利率',
            'comment' => '商品的毛利率',
            'in_list' => true,
        ),
        'cost_tax'          => array(
            'type'    => 'money',
            'comment' => '税率',
        ),
        'branch_id'         => array(
            'type'    => 'table:branch@ome',
            'comment' => '仓库名称',
        ),
        'iostock_id'        => array(
            'type'    => 'table:iostock@ome',
            'comment' => '出入库单号',
        ),

        'sales_material_bn' => array(
            'type'  => 'varchar(200)',
            'label' => '销售物料编码',

        ),
        'obj_type'          => array(
            'type'     => 'varchar(50)',
            'default'  => 'product',
            'required' => true,
            'editable' => false,
        ),
        's_type'            => array(
            'type'    => 'varchar(50)',
            'label'   => '销售类型',
            'default' => 'zx',
        ),
        'addon' =>
            array(
                'type'     => 'longtext',
                'editable' => false,
                'label'    => '扩展字段',
                'comment'  => '扩展字段',
            ),
        'oid'     =>
            array(
                'type'     => 'varchar(50)',
                'default'  => 0,
                'editable' => false,
                'label'    => '子订单号',
            ),
        'order_item_id'    => array(
            'type'     => 'int unsigned',
            'default'  => 0,
            'editable' => false,
            'label' => '订单明细item_id',
        ),
        'obj_id'     => array(
            'type'     => 'int unsigned',
            'default'  => 0,
            'editable' => false,
            'label' => '销售单objects表ID',
        ),
        'platform_amount' => array(
            'type'    => 'money',
            'default' => '0',
            'label'   => '平台承担金额（不包含支付优惠）',
        ),
        'settlement_amount' => array(
            'type'    => 'money',
            'default' => '0',
            'label'   => '结算金额',//客户实付 + 平台支付总额
        ),
        'actually_amount' => array(
            'type'    => 'money',
            'default' => '0',
            'label'   => '客户实付',// 已支付金额 减去平台支付优惠，加平台支付总额
        ),
        'platform_pay_amount' => array(
            'type'    => 'money',
            'default' => '0',
            'label'   => '支付优惠金额',
        ),
        'sell_code' => array(
            'type'    => 'varchar(32)',
            'comment' => '销售编码',
        ),
    ),
    'index'   => array(
        'ind_bn'          => array(
            'columns' => array('bn'),
        ),
        'ind_material_bn' => array(
            'columns' => array('sales_material_bn'),
        ),
        'ind_obj_id' => array(
            'columns' => array('obj_id'),
        ),
        'ind_order_item_id' => array(
            'columns' => array('order_item_id'),
        ),
        'idx_saleid_nums_costamount' => array(
            'columns' => array(
                'sale_id',
                'nums',
                'cost_amount',
            ),
        ),
    ),
    'comment' => '销售明细表',
    'engine'  => 'innodb',
    'version' => '$Rev:  $',
    'charset' => 'utf8mb4',
);
