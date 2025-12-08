<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

$db['reship_package_items'] = array (
  'columns' => 
  array (
    'item_id' => array (
        'type' => 'int unsigned',
        'required' => true,
        'pkey' => true,
        'editable' => false,
        'extra' => 'auto_increment',
    ),
    'package_id' =>  array (
        'type' => 'int unsigned',
        'required' => true,
        'editable' => false,
        'label' => '退货包裹ID',
    ),
    'product_id' => array (
      'type' => 'table:products@ome',
      'required' => true,
      'default' => 0,
      'editable' => false,
      'order' => 30,
    ),
    'bn' => array (
      'type' => 'varchar(50)',
      'editable' => false,
      'label' => '基础物料号',
      'searchtype' => 'has',
      'filtertype' => 'normal',
      'filterdefault' => true,
      'in_list' => true,
      'default_in_list' => true,
      'order' => 31,
    ),
    'outer_sku' => array (
      'type' => 'varchar(50)',
      'label' => '外部sku',
      'editable' => false,
      'filtertype' => 'normal',
      'filterdefault' => true,
      'in_list' => true,
      'default_in_list' => true,
      'order' => 32,
    ),
    'product_name' => array (
      'type' => 'varchar(80)',
      'editable' => false,
      'label' => '基础物料名称',
      'in_list' => true,
      'default_in_list' => true,
      'order' => 35,
    ),
    'return_nums' => array (
      'type' => 'int',
      'label' => '退货数量',
      'editable' => false,
      'in_list' => true,
      'default_in_list' => true,
      'order' => 60,
    ),
    'is_wms_gift' => array(
        'type' => 'bool',
        'default' => 'false',
        'label' => '是否WMS赠品',
        'editable' => false,
        'filtertype' => 'normal',
        'in_list' => true,
        'default_in_list' => false,
        'order' => 70,
    ),
    'wms_order_code' => array (
        'type' => 'varchar(50)',
        'label' => '京东售后申请单号',
        'editable' => false,
        'in_list' => true,
        'default_in_list' => true,
        'order' => 90,
    ),
  ),
  'index' => 
  array (
    
    'in_re_product' => array (
        'columns' => array (
            0 => 'package_id',
            1 => 'product_id',
        ),
    ),
  ),
  'comment' => '退货包裹明细表',
  'engine' => 'innodb',
  'version' => '$Rev: 91002 $',
);