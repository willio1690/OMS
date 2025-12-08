<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

$db['order_objects']=array (
  'columns' =>
  array (
     'obj_id' =>
    array (
      'type' => 'int unsigned',
      'required' => true,
      'pkey' => true,
      'extra' => 'auto_increment',
      'editable' => false,
    ),
    'order_id' =>
    array (
      'type' => 'table:orders@archive',
      'required' => true,
      'default' => 0,
      'editable' => false,
      'comment' => '订单ID',
    ),
    'obj_type' =>
    array (
      'type' => 'varchar(50)',
      'default' => '',
      'required' => true,
      'editable' => false,
      'comment' => '商品对象类型',
    ),
    'obj_alias' => array(
        'type' => 'varchar(255)',
        'editable' => false,
    ),
    'shop_goods_id' => array(
        'type' => 'varchar(50)',
        'editable' => false,
    ),
    'goods_id' =>
    array (
      'type' => 'int unsigned',
      'required' => true,
      'default' => 0,
      'editable' => false,
      'comment' => '商品ID',
    ),
    'bn' =>
    array (
      'type' => 'varchar(200)',
      'editable' => false,
      'is_title' => true,
      'comment' => '货号',
    ),
    'name' =>
    array (
      'type' => 'varchar(200)',
      'editable' => false,
      'comment' => '名称',
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
      'comment' => '合计',
    ),
    'quantity' =>
    array (
      'type' => 'number',
      'default' => 1,
      'required' => true,
      'editable' => false,
      'comment' => '数量',
    ),
    'pmt_price' =>
    array (
      'type' => 'money',
      'default' => '0',
      'editable' => false,
      'comment' => '优惠金额',
    ),
    'sale_price' =>
    array (
      'type' => 'money',
      'default' => '0',
      'editable' => false,
      'comment' => '销售金额',
    ),
    'oid' => 
    array (
      'type' => 'varchar(50)',
      'default' => 0,
      'editable' => false,
      'label' => '子订单号',
    ),
    'divide_order_fee' =>
    array (
        'type' => 'money',
        'editable' => false,
        'label' => '分摊之后的实付金额',
    ),
    'part_mjz_discount' =>
    array (
        'type' => 'money',
        'editable' => false,
        'label' => '优惠分摊',
    ),
    'delete' => array(
        'type' => 'bool',
        'default' => 'false',
        'editable' => false,
    ),
    'ship_status' => array(
        'type' => array(
            0 => '未发货',
            1 => '已发货',
            2 => '部分发货',
            3 => '部分退货',
            4 => '已退货',
        ),
        'default' => '0',
        'label'   => '子订单状态',
    ),
    'presale_status' => array(
        'type' => array(
            0 => '非预售',
            1 => '预售',
        ),
        'default' => '0',
        'label'   => '预售状态',
    ),
  ),
  'index' =>
  array (
    'idx_c_order_id' =>
    array (
        'columns' =>
        array (
          0 => 'order_id',
        ),
    ),
  ),
  'comment' => '归档订单商品对象表',
  'engine' => 'innodb',
  'version' => '$Rev: 40912 $',
);