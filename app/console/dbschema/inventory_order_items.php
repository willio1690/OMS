<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * 唯品会销售单明细列表
 *
 * @author wangbiao@shopex.cn
 * @version 2025.03.27
 */
$db['inventory_order_items'] = array(
    'columns' => array(
        'item_id' => array(
            'type' => 'int unsigned',
            'required' => true,
            'pkey' => true,
            'extra' => 'auto_increment',
            'editable' => false,
            'label' => 'ID',
            'comment' => '自增主键ID'
        ),
        'id' => array (
            'type' => 'table:inventory_orders@console',
            'label' => 'ID',
            'required' => true,
        ),
        'barcode' => array(
            'type' => 'varchar(50)',
            'label' => '条形码',
            'searchtype' => 'nequal',
            'filtertype' => 'normal',
            'in_list'  => true,
            'default_in_list' => true,
            'editable' => false,
            'order' => 30,
        ),
        'product_id' => array (
            'type' => 'int unsigned',
            'default' => 0,
            'label' => 'OMS货品ID',
            'width' => 110,
            'editable' => false,
        ),
        'product_bn' => array (
            'type' => 'varchar(50)',
            'label' => 'OMS货号',
            'in_list' => false,
            'default_in_list' => false,
        ),
        'status' => array(
            'type' => array(
                'none' => '未处理',
                'running' => '处理中',
                'succ' => '已完成',
                'cancel' => '已取消',
                'needless' => '无需处理',
                'fail' => '处理失败',
            ),
            'default' => 'none',
            'required' => true,
            'label' => '处理状态',
            'width' => 110,
            'hidden' => true,
            'editable' => false,
            'in_list' => true,
            'default_in_list' => true,
            'order' => 12,
        ),
        'is_fail' => array (
            'type' => 'bool',
            'required' => true,
            'default' => 'false',
            'editable' => false,
            'label' => '失败商品',
        ),
        'amount' => array(
            'type' => 'number',
            'label' => '数量',
            'in_list'  => true,
            'default_in_list' => true,
            'editable' => false,
            'order' => 32,
        ),
        'sales_no' => array(
            'type' => 'varchar(30)',
            'label' => '运营专场编号',
            'in_list'  => true,
            'default_in_list' => true,
            'editable' => false,
            'order' => 25,
        ),
        'pick_no' => array(
            'type' => 'varchar(30)',
            'label' => '拣货单',
            'in_list'  => true,
            'default_in_list' => true,
            'editable' => false,
            'order' => 25,
        ),
        'brand_id' => array(
            'type' => 'varchar(30)',
            'label' => '品牌ID',
            'in_list'  => true,
            'default_in_list' => true,
            'editable' => false,
            'order' => 25,
        ),
        'warehouse' => array(
            'type' => 'varchar(30)',
            'label' => '仓库编码',
            'in_list'  => true,
            'default_in_list' => true,
            'editable' => false,
            'order' => 25,
        ),
        'warehouse_flag' => array(
            'type' => 'varchar(30)',
            'label' => '仓库编码标识',
            'in_list'  => true,
            'default_in_list' => true,
            'editable' => false,
            'order' => 25,
        ),
        'cooperation_no' => array(
            'type' => 'varchar(30)',
            'label' => '常态合作编码',
            'in_list'  => true,
            'default_in_list' => true,
            'editable' => false,
            'order' => 25,
        ),
        'cooperation_mode' => array(
            'type' => 'varchar(30)',
            'label' => '合作模式',
            'in_list'  => true,
            'default_in_list' => true,
            'editable' => false,
            'order' => 25,
        ),
        'sales_source_indicator' => array(
            'type' => 'varchar(30)',
            'label' => '销售来源',
            'in_list'  => true,
            'default_in_list' => true,
            'editable' => false,
            'order' => 25,
        ),
        'dispose_msg' => array(
            'type' => 'varchar(200)',
            'label' => '处理失败原因',
            'editable' => false,
            'in_list' => true,
            'default_in_list' => false,
        ),
        'create_time' => array(
            'type' => 'time',
            'default' => 0,
            'label' => '订单时间',
            'filtertype' => 'time',
            'filterdefault' => true,
            'in_list' => true,
            'default_in_list' => true,
            'order' => 95,
        ),
        'at_time' => array(
            'type' => 'TIMESTAMP',
            'label' => '创建时间',
            'default' => 'CURRENT_TIMESTAMP',
            'filtertype' => 'time',
            'filterdefault' => true,
            'in_list' => true,
            'default_in_list' => true,
            'width' => 135,
            'order' => 98,
        ),
        'up_time' => array(
            'type' => 'TIMESTAMP',
            'label' => '更新时间',
            'default' => 'CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP',
            'in_list' => true,
            'default_in_list' => true,
            'width' => 135,
            'order' => 99,
        ),
    ),
    'index' => array(
        'ind_product_status' => array(
            'columns' => array(
                0 => 'product_id',
                1 => 'status',
            ),
        ),
        'ind_barcode' => array(
            'columns' => array(
                0 => 'barcode',
            ),
        ),
        'ind_status' => array(
            'columns' => array(
                0 => 'status',
            ),
        ),
        'ind_create_time' => array(
            'columns' => array(
                0 => 'create_time',
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
    'comment' => '唯品会成交的销售单明细表',
    'engine' => 'innodb',
    'version' => '$Rev:  $',
);