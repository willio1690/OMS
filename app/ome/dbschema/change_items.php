<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

$db['change_items']=array (
  'columns' => 
  array (

    'item_id' => 
    array (
      'type' => 'number',
      'required' => true,
      'pkey' => true,
      'extra' => 'auto_increment',
      'editable' => false,
      'label'=>'明细ID',
      'comment'=>'明细ID',
    ),
    'return_id' => 
    array (
      'type' => 'int unsigned',
      'required' => true,
      'editable' => false,
      'label'=>'售后ID',
 
    ),
    'product_id' => 
    array (
      'type' => 'int unsigned',
      'required' => true,
      'editable' => false,
      'label'=>'货品ID',
      
    ),
    'shop_id' =>
    array (
     'type' => 'varchar(32)',

     'label'=>'店铺',
    ),
    'sales_material_bn' => 
    array (
      'type' => 'varchar(50)',
      'editable' => false,
      'label'=>'销售物料货号',
   
    ),
    'bn' => 
    array (
      'type' => 'varchar(50)',
      'editable' => false,
      'label'=>'货品bn',
    ),
    'name' => 
    array (
      'type' => 'varchar(200)',
      'editable' => false,
      'label'=>'货品名称',
     
    ),
   
    'num' => 
    array (
      'type' => 'number',
      'editable' => false,
      'label'=>'数量',
    ),
      
  ), 
  'engine' => 'innodb',
  'version' => '$Rev:  $',
  'comment'=>'换货明细',
);