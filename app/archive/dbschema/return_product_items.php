<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

$db['return_product_items']=array (
  'columns' => 
  array (
    'item_id' => 
    array (
      'type' => 'int unsigned',
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
      'comment'=>'售后ID',      
    ),
    'product_id' => 
    array (
      'type' => 'int unsigned',
      'required' => true,
      'editable' => false,
      'label'=>'货品ID',
      'comment'=>'货品ID',       
    ),
    'bn' => 
    array (
      'type' => 'varchar(50)',
      'editable' => false,
      'label'=>'货品bn',
      'comment'=>'货品bn',      
    ),
    'name' => 
    array (
      'type' => 'varchar(200)',
      'editable' => false,
      'label'=>'货品名称',
      'comment'=>'货品名称',      
    ),
    'branch_id' => 
    array (
      'type' => 'number',
      'editable' => false,
      'label'=>'仓库ID',
      'comment'=>'仓库ID',         
    ),
    'num' => 
    array (
      'type' => 'number',
      'editable' => false,
      'label'=>'数量',
      'comment'=>'数量',      
    ),
    'price' => 
    array (
      'type' => 'money',
      'default' => '0',
   
      'editable' => false,
    ),
   
    
  ), 
  'engine' => 'innodb',
  'version' => '$Rev:  $',
  'comment'=>'售后申请单据明细',
);