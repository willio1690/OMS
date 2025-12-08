<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

$db['difference_items_freeze'] = array(
    'columns' => array(
        'id'     => array(
            'type'      => 'mediumint(8)',
            'label'     => 'ID',
            'comment'   => 'ID',
            'required'  => true,
            'pkey'      => true,
            'extra'     => 'auto_increment',
        ),
        'diff_id'     => array(
            'type'              => 'table:difference@console',
            'label'             => '盘点差异单ID',
            'in_list'           => false,
            'default_in_list'   => false,
            'order'             => 10,
        ),
        'di_id'     => array(
            'type'              => 'table:difference_items@console',
            'label'             => '盘点差异单明细ID',
            'in_list'           => false,
            'default_in_list'   => false,
            'order'             => 10,
        ),
        'branch_id' => array(
            'type'              => 'table:branch@ome',
            'label'             => '盘点库存',
            'in_list'           => true,
            'default_in_list'   => true,
            'order'             => 70,
        ),
        'bm_id'    => array(
            'type'              => 'int unsigned',
            'label'             => '基础物料ID',
            'in_list'           => false,
            'default_in_list'   => false,
            'order'             => 20,
        ),
        'material_bn'    => array(
            'type'              => 'varchar(200)',
            'label'             => '基础物料编码',
            'in_list'           => true,
            'default_in_list'   => true,
            'order'             => 30,
        ),
        'freeze_num' => array(
            'type'              => 'mediumint',
            'label'             => '冻结',
            'in_list'           => true,
            'default_in_list'   => true,
            'order'             => 80,
        ),
        'out_status'   => array(
            'type'              => [
                '0'=>'','1'=>'生成失败'
            ],
            'label'             => '出库单',
            'default'           => '0',
            'in_list'           => false,
            'default_in_list'   => false,
            'order'             => 90,
        ),
    ),
    'comment' => '盘点差异单明细冻结',
    'engine'  => 'innodb',
    'version' => '$Rev:  $',
);
