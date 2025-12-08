<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

$db['iso'] = array(
    'columns' => array(
        'iso_id'            => array(
            'type'     => 'number',
            'required' => true,
            'pkey'     => true,
            'extra'    => 'auto_increment',
            'editable' => false,
        ),
        'iso_bn'            => array(
            'type'            => 'varchar(32)',
            'in_list'         => true,
            'default_in_list' => true,
            'label'           => '单据编号',
            'order'           => '10',
        ),
        'stock_unit'        => array(
            'type'            => 'varchar(32)',
            'in_list'         => true,
            'default_in_list' => true,
            'label'           => '出入库单位编码',
            'order'           => '20',
        ),
        'created'           => array(
            'type'            => 'time',
            'label'           => '单据时间',
            'in_list'         => true,
            'default_in_list' => true,
            'order'           => '30',
        ),
        'salesman'          => array(
            'type'            => 'varchar(32)',
            'label'           => '业务员',
            'in_list'         => true,
            'default_in_list' => true,
            'order'           => '40',
        ),
        'branch_id'         => array(
            'type'            => 'table:branch@ome',
            'label'           => '仓库编码',
            'filtertype'      => 'normal',
            'filterdefault'   => true,
            'in_list'         => true,
            'default_in_list' => true,
        ),
        'remark'            => array(
            'type'            => 'text',
            'label'           => '备注',
            'in_list'         => true,
            'default_in_list' => true,
            'order'           => '50',
        ),
        'iso_type'          => array(
            'type'            => array(
                '0' => '出库',
                '1' => '入库',
            ),
            'default'         => '0',
            'label'           => '单据类型',
            'width'           => 75,
            'in_list'         => true,
            'default_in_list' => true,
        ),
        'create_iso_status' => array(
            'type'            => array(
                '0' => '未创建',
                '1' => '创建成功',
                '2' => '创建失败',
            ),
            'default'         => '0',
            'label'           => 'iso单据创建状态',
            'width'           => 75,
            'in_list'         => false,
            'default_in_list' => false,
        ),
        'create_iso_msg'    => array(
            'type'            => 'varchar(100)',
            'label'           => 'iso单据创建失败原因',
            'in_list'         => false,
            'default_in_list' => false,
            'order'           => '50',
        ),
        'at_time'           => array(
            'type'    => 'TIMESTAMP',
            'label'   => '创建时间',
            'default' => 'CURRENT_TIMESTAMP',
            'width'   => 120,
            'in_list' => false,
            'order'   => 90,
        ),
        'up_time'           => array(
            'type'    => 'TIMESTAMP',
            'label'   => '更新时间',
            'default' => 'CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP',
            'width'   => 120,
            'in_list' => false,
            'order'   => 100,
        ),
    ),
    'index'   => array(
        'ind_iso_bn' => array('columns' => array('iso_bn'), 'prefix' => 'unique'),
    ),
    'comment' => 'POS其他出入库单据',
    'engine'  => 'innodb',
    'version' => '$Rev:  $',
);
