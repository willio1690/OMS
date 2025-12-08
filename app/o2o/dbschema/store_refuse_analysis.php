<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

$db['store_refuse_analysis'] = array(
    'columns' => array(
        'sra_id'      => array(
            'type'     => 'int unsigned',
            'required' => true,
            'width'    => 110,
            'hidden'   => true,
            'editable' => false,
            'pkey'     => true,
            'extra'    => 'auto_increment',
        ),
        'delivery_bn' => array(
            'type'     => 'varchar(32)',
            'required' => true,
            'label'    => '发货单单号',
            'editable' => false,
        ),
        'delivery_id' => array(
            'type'     => 'table:delivery@ome',
            'required' => true,
            'label'    => '发货单ID',
            'editable' => false,
        ),
        'store_bn'    => array(
            'type'     => 'varchar(20)',
            'required' => true,
            'label'    => '门店编码',
            'editable' => false,
        ),
        'store_name'  => array(
            'type'            => 'varchar(255)',
            'required'        => true,
            'label'           => '门店名称',
            'editable'        => false,
            'in_list'         => true,
            'default_in_list' => true,
        ),
        'reason_id'   => array(
            'type'     => 'varchar(255)',
            'required' => true,
            'label'    => '拒绝原因',
            'editable' => false,
        ),
        'memo'        => array(
            'type'     => 'text',
            'label'    => '备注',
            'editable' => false,
        ),
        'createtime'  => array(
            'type'     => 'time',
            'required' => true,
            'label'    => '创建时间',
            'width'    => 130,
            'editable' => false,
        ),
    ),
    'index'   => array(
        'ind_store_bn'   => array(
            'columns' => array(
                0 => 'store_bn',
            ),
        ),
        'ind_createtime' => array(
            'columns' => array(
                0 => 'createtime',
            ),
        ),
    ),
    'comment' => '门店拒单原因表',
    'engine'  => 'innodb',
);
