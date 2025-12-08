<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

$db['delivery_bill_items'] = array(
    'columns' => array(
        'bill_item_id' => array(
            'type'     => 'int unsigned',
            'required' => true,
            'pkey'     => true,
            'editable' => false,
            'extra'    => 'auto_increment',
            'order'    => 1,
        ),
        'bill_id'      => array(
            'type'     => 'int',
            'required' => true,
            'editable' => false,
            'label'    => '包裹ID',
            'order'    => 10,
        ),
        'product_id'   => array(
            'type'     => 'table:products@ome',
            'required' => true,
            'default'  => 0,
            'editable' => false,
        ),
        'bn'           => array(
            'type'            => 'varchar(50)',
            'editable'        => false,
            'label'           => '基础物料号',
            'filtertype'      => 'normal',
            'filterdefault'   => true,
            'in_list'         => true,
            'default_in_list' => true,
            'order'           => 20,
        ),
        'product_name' => array(
            'type'            => 'varchar(200)',
            'label'           => '基础物料名称',
            'editable'        => false,
            'in_list'         => true,
            'default_in_list' => true,
            'order'           => 25,
        ),
        'number'       => array(
            'type'            => 'int',
            'label'           => '数量',
            'editable'        => false,
            'in_list'         => true,
            'default_in_list' => true,
            'order'           => 30,
        ),
        'verify_num'   => array(
            'type'     => 'number',
            'required' => true,
            'default'  => 0,
            'editable' => false,
        ),
    ),
    'index'   => array(
        'idx_bill_id' => array(
            'columns' => array('bill_id'),
        ),
    ),
    'comment' => '发货包裹明细表',
    'engine'  => 'innodb',
    'version' => '$Rev: 41996 $',
);
