<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */


$db['shop_skustockset'] = array(
    'columns' => array(
        'id' => array(
            'type' => 'int',
            'required' => true,
            'pkey' => true,
            'extra' => 'auto_increment',
            'label' => 'ID',
            'comment' => ''
        ),
        'skus_id' => array(
            'type' => 'table:shop_skus@inventorydepth',
            'label' => '店铺商品ID',
            //'in_list' => true,
            //'default_in_list' => true,
            //'order' => 5,
        ),
        'shop_product_bn' => array(
            'type'            => 'varchar(200)',
            'required'        => false,
            'label'           => 'OMS商品编码',
            'default'         => '',
            'in_list' => true,
            'default_in_list' => true,
            'filterdefault'   => true,
            'filtertype'      => 'normal',
            'order'           => 10
        ),
        'branch_id' => array(
            'type' => 'varchar(255)',
            'label' => '系统仓库ID',
            'in_list' => false,
            'default_in_list' => false,
        ),
        'branch_bn' => array(
            'type' => 'varchar(255)',
            'label' => '系统仓库ID',
            'in_list' => true,
            'default_in_list' => true,
            'filterdefault'   => true,
            'filtertype'      => 'normal',
            'order'           => '20'
        ),
        'stock' => array(
            'type' => 'number',
            'label' => '导入时库存',
            'in_list' => true,
            'default_in_list' => true,
        ),
        'freeze' => array(
            'type' => 'number',
            'label' => '导入时冻结',
            'in_list' => true,
            'default_in_list' => true,
        ),
        'stock_only' => array(
            'type' => 'number',
            'label' => '平台独立库存',
            'in_list' => true,
            'default_in_list' => true,
        ),
        'last_modify' => array(
            'type' => 'last_modify',
            'label' => '修改时间',
            'in_list' => true,
            'default_in_list' => true,
        ),
    ),
    'index' => array(
        'idx_branch_bn_skus_id' => array(
            'columns' => array('branch_bn','skus_id'),
            'prefix' => 'unique',
        ),
        'idx_shop_product_bn' => array(
            'columns' => ['shop_product_bn']
        ),
        'idx_last_modify' => array(
            'columns' => ['last_modify']
        )
    ),
    'comment' => '店铺货品库存设置',
    'engine' => 'innodb',
    'version' => '$Rev: $'
);
