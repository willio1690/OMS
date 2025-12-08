<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

$db['branch_product']=array (
  'columns' =>
  array (
  'id'  => array(
          'type'     => 'int',
          'required' => true,
          'pkey'     => true,
          'editable' => false,
          'extra'    => 'auto_increment',
      ),
  
    'branch_id' =>
    array (
      'type' => 'table:branch@ome',
      'required' => true,
     
      'editable' => false,
    ),
    'product_id' =>
    array (
      'type' => 'table:products@ome',
      'required' => true,
   
      'editable' => false,
    ),
    'store' =>
    array (
      'type'    => 'int NOT NULL',
      'default' => 0,
      'label' => '库存',
      'editable' => false,
      'in_list' => true,
      'default_in_list' => true,
      'width' => 110,
      'order' => 13,
    ),
    'store_freeze' =>
    array (
      'type' => 'int NOT NULL',
      'editable' => false,
      'label' => '冻结库存',
      'default' => 0,
      'in_list' => false,
      'default_in_list' => false,
      'order' => 15,
    ),
    'last_modified' =>
    array (
      'type' => 'last_modify',
      'editable' => false,
    ),
    'arrive_store' =>
    array (
      'type' => 'number',
      'editable' => false,
      'default' => 0,
      'label' => '在途库存',
      'in_list' => true,
      'default_in_list' => true,
      'width' => 100,
      'order' => 18,
    ),
    'store_id'      => array(
        'type'     => 'int',
  
        'width'    => 110,
        'default' => 0,
     ),
    'store_bn' => array(
        'type' => 'varchar(20)',
        'label' => '门店编码',

        'in_list' => true,
        'default_in_list' => true,
    ),
    'safe_store' =>
    array (
      'type' => 'number',
      'editable' => false,
      'default' => 0,
    ),
    'is_locked' =>
    array (
      'type' => 'intbool',
      'label' => '锁定安全库存',
      'editable' => false,
      'default' => '0',
      'order' => 19,
    ),
    'unit_cost' =>
    array (
      'type' => 'decimal(20,3)',
      'default' => '0.000',
      'comment' => '单位平均成本',
      'label'=>'单位平均成本',
      'required' =>true,
    ),
    'inventory_cost' =>
    array (
      'type' => 'decimal(20,3)',
      'default' => '0.000',
      'comment' => '库存成本',
      'label'=>'库存成本',
    ),
    'up_time'           => [
        'type'    => 'TIMESTAMP',
        'label'   => '更新时间',
        'default' => 'CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP',
        'width'   => 120,
        'in_list' => true,
    ],
  ),
  'index' =>
  array (
    
    'ind_branch_product' =>
    array (
        'columns' =>
        array (
          0 => 'branch_id',
          1 => 'product_id',
        ),
        'prefix' => 'unique',
    ),
    'ind_up_time' => ['columns' => ['up_time']],
  ),
  'comment' => '分店(仓库)和商品关联表',
  'engine' => 'innodb',
  'version' => '$Rev:  $',
);