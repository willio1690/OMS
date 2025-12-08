<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

$db['order_service'] = array(
    'columns' => array(
        'id' => array(
            'type'     => 'int unsigned',
            'required' => true,
            'pkey'     => true,
            'extra'    => 'auto_increment',
            'editable' => false,
            'label'    => 'ID',
            'width'    => 110,
            'hidden'   => true,
            'editable' => false,
        ),
        'order_id' => array(
            'type'     => 'int unsigned',
            'required' => true,
            'editable' => false,
            'label'    => '订单号',
            'width'    => 110,
            'in_list'  => true,
            'default_in_list' => true,
        ),
        'item_oid' => array(
            'type'     => 'varchar(50)',
            'default'  => '',
            'editable' => false,
            'label'    => '服务所属的交易订单号',
            'width'    => 150,
            'in_list'  => true,
        ),
        'tmser_spu_code' => array(
            'type'     => 'varchar(50)',
            'default'  => '',
            'editable' => false,
            'label'    => '支持家装类物流的类型',
            'width'    => 150,
            'in_list'  => true,
        ),
        'sale_price' => array(
            'type'     => 'money',
            'default'  => '0.000',
            'editable' => false,
            'label'    => '销售价',
            'width'    => 75,
            'in_list'  => true,
        ),
        'num' => array(
            'type'     => 'longtext',
            'editable' => false,
            'label'    => '购买数量',
            'width'    => 100,
            'in_list'  => true,
        ),
        'total_fee' => array(
            'type'     => 'money',
            'default'  => '0.000',
            'editable' => false,
            'label'    => '服务子订单总费用',
            'width'    => 120,
            'in_list'  => true,
        ),
        'type' => array(
            'type'     => 'varchar(15)',
            'default'  => 'service',
            'editable' => false,
            'label'    => '服务',
            'width'    => 100,
            'in_list'  => true,
        ),
        'type_alias' => array(
            'type'     => 'varchar(50)',
            'default'  => '',
            'editable' => false,
            'label'    => '服务别名',
            'width'    => 120,
            'in_list'  => true,
        ),
        'title' => array(
            'type'     => 'varchar(50)',
            'default'  => '',
            'editable' => false,
            'label'    => '商品名称',
            'width'    => 200,
            'in_list'  => true,
        ),
        'service_id' => array(
            'type'     => 'varchar(50)',
            'default'  => '',
            'editable' => false,
            'label'    => '服务数字id',
            'width'    => 120,
            'in_list'  => true,
        ),
        'refund_id' => array(
            'type'     => 'varchar(50)',
            'default'  => '',
            'editable' => false,
            'label'    => '最近退款的id',
            'width'    => 120,
            'in_list'  => true,
        ),
        'archive_time' => array(
            'type'     => 'int unsigned',
            'required' => true,
            'editable' => false,
            'label'    => '归档时间',
            'width'    => 130,
            'in_list'  => true,
            'default_in_list' => true,
            'comment'  => '数据归档时间戳',
        ),
    ),
    'index' => array(
        'ind_order_id' => array(
            'columns' => array(
                0 => 'order_id',
            ),
        ),
        'ind_archive_time' => array(
            'columns' => array(
                0 => 'archive_time',
            ),
        ),
        'ind_id' => array(
            'columns' => array(
                0 => 'id',
            ),
        ),
    ),
    'comment' => '订单服务归档表',
    'engine'  => 'innodb',
    'version' => '$Rev: 1.0 $',
); 