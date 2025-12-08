<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

$db['adjust_items'] = array(
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
        'adjust_id'        => array(
            'type'            => 'table:adjust@console',
            'label'           => '调整单编码',
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
       
        'origin_number'      => array(
            'type'            => 'int',
            'label'           => '调整前数量',
            'default_in_list' => true,
            'in_list'         => true,
            'order' => 20,
        ),
        'number'      => array(
            'type'            => 'int',
            'label'           => '调整数量',
            'default_in_list' => true,
            'in_list'         => true,
            'order' => 20,
        ),
        'final_number'      => array(
            'type'            => 'int',
            'label'           => '调整后数量',
            'default_in_list' => true,
            'in_list'         => true,
            'order' => 20,
        ),
        'sn'      => array(
            'type'            => 'text',
            'label'           => 'sn',
            'default_in_list' => true,
            'in_list'         => true,
            'order' => 20,
        ),
        'adjust_status'      => array(
            'type'            => [
                '0' => '未调整',
                '1' => '调整完成'
            ],
            'label'           => '调整状态',
            'default' => '0',
            'default_in_list' => true,
            'in_list'         => true,
            'order' => 20,
        ),
        'batch'      => array(
            'type'            => 'text',
            'label'           => 'batch',
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
    'comment' => '库存调整表明细',
);
