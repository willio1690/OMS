<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

$db['material_package_items'] = array(
    'columns' => array(
        'id'        => array(
            'type'     => 'int unsigned',
            'required' => true,
            'pkey'     => true,
            'extra'    => 'auto_increment',
            'label'    => 'ID',
            'width'    => 110,
            'hidden'   => true,
            'editable' => false,
        ),
        'mp_id'        => array(
            'type'            => 'table:material_package@console',
            'label'           => '组装单号',
            'default_in_list' => true,
            'in_list'         => true,
            'order' => 10,
            'width'           => 200,
        ),
        'bm_id'      => array(
            'type'            => 'table:basic_material@material',
            'label'           => '基础物料ID',
            'default_in_list' => true,
            'in_list'         => true,
            'order' => 20,
        ),
        'bm_bn'      => array(
            'type'            => 'varchar(255)',
            'label'           => '基础物料编码',
            'default_in_list' => true,
            'in_list'         => true,
            'order' => 20,
        ),
        'bm_name'      => array(
            'type'            => 'varchar(255)',
            'label'           => '基础物料名称',
            'default_in_list' => true,
            'in_list'         => true,
            'order' => 20,
        ),
        'number'      => array(
            'type'            => 'int',
            'label'           => '数量',
            'default_in_list' => true,
            'in_list'         => true,
            'order' => 20,
        ),
        'in_number'      => array(
            'type'            => 'int',
            'label'           => '入库数量',
            'default'         => 0,
            'default_in_list' => true,
            'in_list'         => true,
            'order' => 20,
        ),
        'out_number'      => array(
            'type'            => 'int',
            'label'           => '出库数量',
            'default'         => 0,
            'default_in_list' => true,
            'in_list'         => true,
            'order' => 20,
        ),
    ),
    'index'   => array(
        'idx_bm_bn'   => array('columns' => array('bm_bn'),),
    ),
    'engine'  => 'innodb',
    'version' => '$Rev:  $',
    'comment' => '组装单表明细',
);
