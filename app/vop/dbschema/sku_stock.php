<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * 唯品会购物车冻结
 *
 * @author wangbiao@shopex.cn
 * @version 2025.05.27
 */
$db['sku_stock'] = array(
    'columns' => array(
        'id' => array(
            'type' => 'int unsigned',
            'required' => true,
            'pkey' => true,
            'extra' => 'auto_increment',
            'editable' => false,
            'label' => 'ID',
            'comment' => '自增主键ID',
            'order' => 1,
        ),
        'barcode' => array(
            'type' => 'varchar(200)',
            'required' => true,
            'label' => '条形码',
            'is_title' => true,
            'searchtype' => 'nequal',
            'filtertype' => 'normal',
            'filterdefault' => true,
            'editable' => false,
            'in_list' => true,
            'default_in_list' => true,
            'width' => 130,
            'order' => 10,
        ),
        'bm_id' => array(
            'type' => 'table:basic_material@material',
            'label' => '基础物料ID',
            'in_list' => false,
            'default_in_list' => false,
            'order' => 20,
        ),
        'material_bn' => array(
            'type' => 'varchar(200)',
            'required' => true,
            'label' => '基础物料编码',
            'is_title' => true,
            'searchtype' => 'nequal',
            'filtertype' => 'normal',
            'filterdefault' => true,
            'editable' => false,
            'in_list' => true,
            'default_in_list' => true,
            'width' => 130,
            'order' => 22,
        ),
        'shop_bn' => array(
            'type' => 'varchar(30)',
            'required' => true,
            'label' => '店铺编码',
            'in_list' => true,
            'default_in_list' => true,
            'order' => 30,
        ),
        'shop_id' => array(
            'type' => 'table:shop@ome',
            'editable' => false,
            'label' => '店铺名称',
            'filtertype' => 'normal',
            'filterdefault' => true,
            'in_list' => true,
            'default_in_list' => true,
            'order' => 32,
        ),
        'leaving_stock' => array(
            'type' => 'number',
            'editable' => false,
            'label' => '剩余库存',
            'default' => 1,
            'in_list' => true,
            'default_in_list' => true,
            'order' => 50,
        ),
        'current_hold' => array(
            'type' => 'number',
            'editable' => false,
            'label' => '库存占用',
            'comment' => '目前为购物车+未支付订单占用的库存值',
            'default' => 1,
            'in_list' => true,
            'default_in_list' => true,
            'order' => 52,
        ),
        'unpaid_hold' => array(
            'type' => 'number',
            'editable' => false,
            'label' => '未支付占用数',
            'default' => 1,
            'in_list' => true,
            'default_in_list' => true,
            'order' => 53,
        ),
        'circuit_break_value' => array(
            'type' => 'number',
            'editable' => false,
            'label' => '熔断值',
            'default' => 1,
            'in_list' => true,
            'default_in_list' => true,
            'order' => 55,
        ),
        'area_warehouse_id' => array(
            'type' => 'number',
            'editable' => false,
            'label' => '分区仓库代码ID',
            'default' => 1,
            'in_list' => true,
            'default_in_list' => true,
            'order' => 58,
        ),
        'warehouse_flag' => array(
            'type' => 'number',
            'editable' => false,
            'label' => '仓库编码标识',
            'comment' => '(0或者null)全国逻辑仓或7大仓 1：省仓编码',
            'default' => 1,
            'in_list' => true,
            'default_in_list' => true,
            'order' => 60,
        ),
        'at_time' => array(
            'type' => 'TIMESTAMP',
            'label' => '创建时间',
            'default' => 'CURRENT_TIMESTAMP',
            'in_list' => true,
            'default_in_list' => true,
            'filtertype' => 'time',
            'filterdefault' => true,
            'width' => 135,
            'order' => 98,
        ),
        'up_time' => array(
            'type' => 'TIMESTAMP',
            'label' => '更新时间',
            'default' => 'CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP',
            'in_list' => true,
            'default_in_list' => true,
            'filtertype' => 'time',
            'filterdefault' => true,
            'width' => 135,
            'order' => 99,
        ),
    ),
    'index' => array(
        'ind_shop_material_bn' => array(
            'columns' => array(
                0 => 'shop_id',
                1 => 'material_bn',
            ),
            'prefix' => 'unique',
        ),
        'ind_shop_barcode' => array(
            'columns' => array(
                0 => 'shop_id',
                1 => 'barcode',
            ),
            'prefix' => 'unique',
        ),
        'ind_barcode' => array(
            'columns' => array(
                0 => 'barcode',
            ),
        ),
        'ind_material_bn' => array(
            'columns' => array(
                0 => 'material_bn',
            ),
        ),
        'ind_at_time' => array(
            'columns' => array(
                0 => 'at_time',
            ),
        ),
        'ind_up_time' => array(
            'columns' => array(
                0 => 'up_time',
            ),
        ),
    ),
    'comment' => '唯品会购物车冻结表',
    'engine' => 'innodb',
    'version' => '$Rev:  $',
);