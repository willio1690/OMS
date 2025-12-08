<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

$db['difference_items'] = array(
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
            'type'              => 'int unsigned',
            'label'             => '盘点差异单ID',
            'comment'           => '盘点差异单ID',
            'required'          => true,
            'in_list'           => false,
            'default_in_list'   => false,
            'order'             => 10,
        ),
        'bm_id'    => array(
            'type'              => 'int unsigned',
            'label'             => '基础物料ID',
            'comment'           => '基础物料ID',
            'in_list'           => false,
            'default_in_list'   => false,
            'order'             => 20,
        ),
        'material_bn'    => array(
            'type'              => 'varchar(200)',
            'label'             => '基础物料编码',
            'comment'           => '基础物料编码',
            'in_list'           => true,
            'default_in_list'   => true,
            'order'             => 30,
        ),
        'wms_stores' => array(
            'type'              => 'mediumint',
            'label'             => 'WMS库存',
            'in_list'           => true,
            'default_in_list'   => true,
            'order'             => 70,
        ),
        'oms_stores' => array(
            'type'              => 'mediumint',
            'label'             => '系统库存',
            'comment'           => '系统库存',
            'in_list'           => true,
            'default_in_list'   => true,
            'order'             => 80,
        ),
        'diff_stores' => array(
            'type'              => 'mediumint',
            'label'             => 'wms实物差异',
            'in_list'           => true,
            'default_in_list'   => true,
            'order'             => 90,
        ),
        'oms_diff_stores' => array(
            'type'              => 'mediumint',
            'label'             => 'oms实物差异',
            'in_list'           => true,
            'default_in_list'   => true,
            'order'             => 90,
        ),
        'number' => array(
            'type'              => 'mediumint',
            'label'             => '数量',
            'in_list'           => true,
            'default_in_list'   => true,
            'order'             => 90,
        ),
        'pos_accounts_num' =>array (
            'type' => 'mediumint(8)',
            'label' => 'POS账面数',//pos_store
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
        'ind_diff_id' => array(
            'columns' => array(
                0 => 'diff_id',
            ),
        ),
        'ind_bm_id' => array(
            'columns' => array(
                0 => 'bm_id',
            ),
        ),
        'ind_material_bn' => array(
            'columns' => array(
                0 => 'material_bn',
            ),
        ),
    ),
    'comment' => '盘点差异单明细',
    'engine'  => 'innodb',
    'version' => '$Rev:  $',
);
