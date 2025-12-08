<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

$db['delivery_items']=array (
  'columns' => 
  array (
    'item_id' => 
    array (
      'type' => 'int unsigned',
      'required' => true,
      'pkey' => true,
      'extra' => 'auto_increment',
      'editable' => false,
      'comment' => '自增主键ID'
    ),
    'delivery_id' => 
    array (
      'type' => 'table:delivery@ome',
      'required' => true,
      'default' => 0,
      'editable' => false,
      'comment' => '发货单ID,关联ome_delivery.delivery_id',
    ),
    'product_id' => 
    array (
      'type' => 'table:products@ome',
      'required' => true,
      'default' => 0,
      'editable' => false,
      'comment' => '基础物料ID,关联material_basic_material.bm_id',
    ),
    'shop_product_id' =>
    array (
      'type' => 'varchar(50)',
      'editable' => false,
      'comment' => '平台SKU ID',
    ),
    'bn' => 
    array (
      'type' => 'varchar(200)',
      'editable' => false,
      'is_title' => true,
      'comment' => '基础物料编码',
    ),
    'product_name' => 
    array (
      'type' => 'varchar(200)',
      'editable' => false,
      'comment' => '基础物料名称',
    ),
    'number' => 
    array (
      'type' => 'number',
      'required' => true,
      'default' => 0,
      'editable' => false,
      'comment' => '需发货数',
    ),
    'verify' => 
    array (
      'type' => 'bool',
      'required' => true,
      'default' => 'false',
      'editable' => false,
      'comment' => '校验状态,可选值:true(是),false(否)'
    ),
    'verify_num' => 
    array (
      'type' => 'number',
      'required' => true,
      'default' => 0,
      'editable' => false,
      'comment' => '校验数量',
    ),
    'purchase_price' => array (
        'type' => 'money',
        'default' => '0',
        'editable' => false,
        'label' => '仓储采购价格',
        'in_list' => true,
        'default_in_list' => false,
    ),
    'is_wms_gift' => array(
        'type' => 'bool',
        'default' => 'false',
        'label' => '是否WMS赠品',
        'editable' => false,
        'filtertype' => 'normal',
        'in_list' => true,
        'default_in_list' => false,
    ),
    'oid' => array (
        'type' => 'varchar(50)',
        'label' => 'WMS包裹号',
        'editable' => false,
        'in_list' => true,
        'default_in_list' => false,
        'order' => 99,
    ),
  ),
  'index' => array(
      'idx_product_bn' => array(
          'columns' => array('bn'),
      ),
  ),
  'comment' => '发货单商品明细,用于存储需要发货的基础物料',
  'engine' => 'innodb',
  'version' => '$Rev:  $',
);
