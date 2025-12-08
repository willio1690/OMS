<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

$db['analysis_productnsale']=array (
  'columns' =>
  array (
    'nsale_id' => 
    array (
      'type' => 'number',
      'required' => true,
      'pkey' => true,
      'extra' => 'auto_increment',
      'editable' => false,
      'order' => 1,
    ),
    'material_bn' =>
    array (
      'type' => 'varchar(200)',
      'label' => '物料编码',
      'width' => 120,
      'in_list' => true,
      'default_in_list' => true,
      'editable' => false,
      'required'        => true,
      'filtertype' => 'normal',
      'filterdefault' => true,
      'order' => 2,
    ),
    'material_name' =>
    array (
      'type' => 'varchar(200)',
      //'required' => true,
      'label' => '物料名称',
      'width' => 260,
      'in_list' => true,
      'default_in_list' => true,
      'editable' => false,
      'filtertype' => 'normal',
      'filterdefault' => true,
      'order' => 3,
    ),
    'nsale_days' =>
    array (
      'type' => 'number',
      //'required' => true,
      'label' => '不动销天数',
      'width' => 120,
      'in_list' => true,
      'default_in_list' => true,
      'editable' => false,
      'filtertype' => 'normal',
      'filterdefault' => true,
      'order' => 4,
    ),
    'material_type' =>
    array (
      'type' => 'tinyint(1)',
      //'required' => true,
      'label' => '物料类型',
      'width' => 100,
      'default' => 1,
      'in_list' => true,
      'default_in_list' => true,
      'editable' => false,
      'filtertype' => 'normal',
      'filterdefault' => true,
      'order' => 5,
    ),
    'barcode' =>
    array (
      'type' => 'varchar(255)',
      // 'required' => true,
      'label' => '条形码',
      'width' => 120,
      'in_list' => true,
      'default_in_list' => true,
      'editable' => false,
      'filtertype' => 'normal',
      'filterdefault' => true,
      'order' => 6,
    ),
    'now_num' =>
    array (
      'type' => 'mediumint(8)',
      //'required' => true,
      'label' => '结存数量',
      'default' => 0,
      'width' => 120,
      'in_list' => true,
      'default_in_list' => true,
      'editable' => false,
      'filtertype' => 'normal',
      'filterdefault' => true,
      'order' => 7,
    ),
    'inventory_cost' =>
    array (
      'type' => 'decimal(20,3)',
      //'required' => true,
      'label' => '库存成本',
      'default' => 0.000,
      'width' => 120,
      'in_list' => true,
      'default_in_list' => true,
      'editable' => false,
      'filtertype' => 'normal',
      'filterdefault' => true,
      'order' => 8,
    ),
    'now_inventory_cost' =>
    array (
      'type' => 'decimal(20,3)',
      //'required' => true,
      'label' => '结存库存成本',
      'default' => 0.000,
      'width' => 120,
      'in_list' => true,
//       'default_in_list' => true,
      'editable' => false,
      'filtertype' => 'normal',
      'filterdefault' => true,
      'order' => 9,
    ),
    'unit_cost' =>
    array (
      'type' => 'decimal(20,3)',
      //'required' => true,
      'label' => '单位成本',
      'default' => 0.000,
      'width' => 120,
      'in_list' => true,
//       'default_in_list' => true,
      'editable' => false,
      'filtertype' => 'normal',
      'filterdefault' => true,
      'order' => 10,
    ),
    'now_unit_cost' =>
    array (
      'type' => 'decimal(20,3)',
      //'required' => true,
      'label' => '结存单位成本',
      'default' => 0.000,
      'width' => 120,
      'in_list' => true,
//       'default_in_list' => true,
      'editable' => false,
      'filtertype' => 'normal',
      'filterdefault' => true,
      'order' => 11,
    ),
    'balance_nums' =>
    array (
      'type' => 'number',
      //'required' => true,
      'label' => '不动销库存',
      'width' => 120,
      'in_list' => true,
//       'default_in_list' => true,
      'editable' => false,
      'filtertype' => 'normal',
      'filterdefault' => true,
      'order' => 12,
    ),
    'create_time' =>
    array (
      'type' => 'time',
      //'required' => true,
      'label' => '出入库时间',
      'width' => 120,
      'in_list' => true,
//       'default_in_list' => true,
      'editable' => false,
      'filtertype' => 'normal',
      'filterdefault' => true,
      'order' => 20,
    ),
  ),
  'index' =>
  array (
    'ind_material_bn' =>
    array (
        'columns' =>
        array (
          0 => 'material_bn',
        ),
        'prefix' => 'unique',
    ),
    'ind_material_name' =>
    array (
        'columns' =>
        array (
          0 => 'material_name',
        ),
    ),
    'ind_material_type' =>
    array (
        'columns' =>
        array (
          0 => 'material_type',
        ),
    ),
    'ind_barcode' =>
    array (
        'columns' =>
        array (
          0 => 'barcode',
        ),
    ),
    'ind_create_time' =>
    array (
        'columns' =>
        array (
          0 => 'create_time',
        ),
    ),
  ),
  'comment' => '不动销商品报表',
  'engine' => 'innodb',
  'version' => '$Rev:  $',
);