<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

$db['syncproduct']=array (
  'columns' => 
  array (
    'id' =>
    array (
        'type' => 'int unsigned',
        'required' => true,
        'pkey' => true,
        'extra' => 'auto_increment',
    ),
    'material_bn' => 
    array (
      'type' => 'varchar(50)',
      'required' => true,
      'label' => '基础物料编码',
    
      'in_list' => true,
      'default_in_list' => true,
      'searchtype'      => 'has',
      'filterdefault'   => true,
      'filtertype'      => 'textarea',
      'order' => 2,
    ),
    'bm_id' => 
    array (
      'type' => 'int unsigned',
      'required' => true,
      'label' => '货品ID',
      'width' => 110,
      'editable' => false,
      'order' => 11,
    ),
   
    'retail_price' =>
    array (
        'type' => 'money',
        'default' => '0.000',
        'label' => '物料售价',
        'width' => 75,
        'in_list' => true,
        'default_in_list' => true,
        'order'   => 8,
    ),
    'at_time'       => array(
      'type'    => 'TIMESTAMP',
      'label'   => '创建时间',
      'default' => 'CURRENT_TIMESTAMP',
      'width'   => 120,
      'in_list' => true,
      'order'   => 11,
    ),
    'up_time'       => array(
      'type'    => 'TIMESTAMP',
      'label'   => '更新时间',
      'default' => 'CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP',
      'width'   => 120,
      'in_list' => true,
      'order'   => 11,
    ),
   
   
  ),
  'index' =>
      array (
        'ind_bm_id'   => array('columns' => array('bm_id'), 'prefix' => 'UNIQUE'),
        'ind_material_bn'=>array('columns' =>array (0 => 'material_bn'),),
        
      ),
  'comment' => 'pos sku关联表',
  'engine' => 'innodb',
  'version' => '$Rev: 40654 $',
);