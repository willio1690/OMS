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
      'type' => 'table:return_product@ome',
      'required' => true,
      'editable' => false,
      'label'=>'售后ID',
      'comment'=>'售后ID',      
    ),
    'product_id' => 
    array (
      'type' => 'table:products@ome',
      'required' => true,
      'editable' => false,
      'label'=>'货品ID',
      'comment'=>'货品ID',       
    ),
    'bn' => 
    array (
      'type' => 'varchar(200)',
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
    'amount' => 
    array (
      'type' => 'money',
      'default' => '0',
      'label' => '小计',
      'editable' => false,
    ),
    'disabled' => 
    array (
      'type' => 'bool',
      'default' => 'false',
      'required' => true,
    ),
    'order_item_id' =>
    array (
      'type' => 'int unsigned',
      'editable' => false,
      'comment' => '原订单id',
    ),
    'oid'               => array(
      'type'     => 'varchar(50)',
      'default'  => 0,
      'editable' => false,
      'label'    => '子订单号',
  ),
    'up_time' => array(
        'type'       => 'TIMESTAMP',
        'label'      => '修改时间',
        'filtertype' => 'time',
        'order'           => 100,
    ),
    'obj_type' =>
    array (
      'type' => 'varchar(50)',
      'default' => 'product',
      'label'=>'货品类型',
      'editable' => false,
    ),
    'shop_goods_bn' =>
    array (
      'type' => 'varchar(50)',
   
      'default' => 0,
      'label'=>'店铺货号',
      'editable' => false,
    ),
    'quantity' =>
    array (
      'type' => 'number',
      'default' => 0,
      'label'=>'申请数量',
      'editable' => false,
    ),
    'sku_uuid' => array(
        'type' => 'varchar(255)',
        'editable' => false,
        'label' => '商品行唯一标识',
    ),
    'is_del' => array(
      'type' => 'bool',
      'default' => 'false',
      'editable' => false,
      'comment' => '删除状态,可选值:true(是),false(否)'
    ),
  ),
  'index'=>array(
      'ind_order_item_id'=>array('columns'=>array('order_item_id'))
  ),
  'engine' => 'innodb',
  'version' => '$Rev:  $',
  'comment'=>'售后申请单据明细',
  'charset' => 'utf8mb4',
);
