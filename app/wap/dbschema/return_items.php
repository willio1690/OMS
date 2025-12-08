<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

$db['return_items']=array (
  'columns' =>
  array (
    'item_id' =>
    array (
        'type' => 'number',
        'required' => true,
        'pkey' => true,
        'extra' => 'auto_increment',
        'editable' => false,
        'label' => '明细ID',
    ),
    'return_id' =>
    array (
        'type' => 'table:return@wap',
        'editable' => false,
        'required' => true,
        'label' => '退换货单ID',
    ),
    'product_id' =>
    array (
        'type' => 'int unsigned',
        'editable' => false,
        'comment' => '货品ID',
    ),
    'bn' =>
    array (
        'type' => 'varchar(30)',
        'editable' => false,
        'required' => true,
        'comment' => '货号',
    ),
    'name' =>
    array (
        'type' => 'varchar(200)',
        'editable' => false,
        'comment' => '货品名称',
    ),
    'return_type' =>
    array (
        'type' =>
        array (
          'return' => '退货',
          'change' => '换货',
        ),
        'default' => 'return',
        'required' => true,
        'editable' => false,
        'label' => '退换货类型',
    ),
    'num' =>
    array (
        'type' => 'number',
        'editable' => false,
        'required' => true,
        'default' => 1,
        'label' => '数量',
    ),
    'price' =>
    array (
        'type' => 'money',
        'required' => true,
        'editable' => false,
        'label' => '价格',
    ),
  ),
  'index' =>
  array (
    /*
    'ind_return_bn_shop' =>
    array (
        'columns' =>
        array (
          0 => 'return_bn',
          1 => 'shop_id',
        ),
        'prefix' => 'unique',
    ),
    'ind_return_bn' =>
    array (
        'columns' =>
        array (
          0 => 'return_bn',
        ),
    ),
    */
  ),
  'comment' => '门店H5移动端-退换货单据明细表',
  'engine' => 'innodb',
  'version' => '$Rev: 41996 $',
);