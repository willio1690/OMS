<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

$db['delivery_items_detail']=array (
  'columns' => 
  array (
    'item_detail_id' => 
    array (
    'type' => 'int unsigned',
      'required' => true,
      'pkey' => true,
      'editable' => false,
     'extra' => 'auto_increment',
    ),
    'delivery_id' => 
    array (
      'type' => 'int unsigned',
      'required' => true,
      'editable' => false,
    ),
    'delivery_item_id' => 
    array (
      'type' => 'int unsigned',
      'required' => true,
      'editable' => false,
    ),
    'order_id' => 
    array (
      'type' => 'int unsigned',
      'required' => true,
      'editable' => false,
      'comment' => '订单号ID',
    ),
    'order_item_id' => 
    array (
      'type' => 'int unsigned',
      'required' => true,
      'editable' => false,
      'comment' => '订单商品明细ID',
    ),
    'order_obj_id' => 
    array (
      'type' => 'int unsigned',
      'required' => true,
      'editable' => false,
      'comment' => '订单商品对象ID',
    ),
    'item_type' => 
    array (
      'type' => 
      array (
        'product' => '商品',
        'gift' => '赠品',
        'pkg' => '捆绑商品',
        'adjunct' => '配件',
        'lkb' => '福袋',
        'pko' => '多选一',
      ),
      'default' => 'product',
      'required' => true,
      'editable' => false,
    ),
    'product_id' => 
    array (
      'type' => 'table:products@ome',
      'required' => true,
      'editable' => false,
      'comment' => '货号ID',
    ),
    'bn' => 
    array (
      'type' => 'varchar(30)',
      'editable' => false,
      'is_title' => true,
      'comment' => '货号',
    ),
    'number' => 
    array (
      'type' => 'number',
      'required' => true,
      'editable' => false,
      'comment' => '数量',
    ),
    'price' => 
    array (
      'type' => 'money',
      'default' => '0',
      'required' => true,
      'editable' => false,
      'comment' => '单价',
    ),
    'amount' =>
    array (
      'type' => 'money',
      'default' => '0',
      'required' => true,
      'editable' => false,
      'comment' => '总金额',
    ),
  ),
  'index' =>
  array (
    'ind_delivery_item_id' =>
    array (
        'columns' =>
        array (
          0 => 'delivery_item_id',
        ),
    ),
    'ind_order_item_id' =>
    array (
        'columns' =>
        array (
          0 => 'order_item_id',
        ),
    ),
    'ind_order_obj_id' =>
    array (
        'columns' =>
        array (
          0 => 'order_obj_id',
        ),
    ),
   ),
  'comment' => '归档发货单明细关联订单表',
  'engine' => 'innodb',
  'version' => '$Rev:  $',
);