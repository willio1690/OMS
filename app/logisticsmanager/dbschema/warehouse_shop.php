<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

$db['warehouse_shop']=array (
  'columns' => 
  array (
    'warehouse_id' => 
    array (
      'type' => 'number',
      'required' => true,
      'pkey' => true,
      
    ),
    'shop_id' => 
    array (
      'type' => 'varchar(32)',
      'required' => true,
      'pkey' => true,
   
    ),
    'branch_id' =>
    array (
      'type' => 'table:branch@ome',
      'editable' => false,
      'label' => '仓库',
      'width' => 110,
      'filtertype' => 'normal',
      'filterdefault' => true,
      'in_list' => true,
      'order' => 2,
    ),
    'outwarehouse_id'=>array(
        'type' => 'varchar(25)',
        'editable' => false,
        'label' => '外部区域仓id',
        'width' => 130,
        'in_list' => true,
        'default_in_list' => true,
        'order' => 1,
    ),
    'sync_warehouse'=>array(
      'type'=>array(
        '0'   => '未同步',
        '1'   => '已同步',
        '2'   => '失败',
       
      ),
      'required' => true,
      'default' => '0',
      'label' => '同步状态',
      'in_list' => true,
      'default_in_list' => true,
    ),
    'sync_status'=>array(
      'type'=>array(
        '0'   => '未同步',
        '1'   => '已同步',
        '2'   => '失败',
       
      ),
      'required' => true,
      'default' => '0',
      'label' => '同步状态',
      'in_list' => true,
      'default_in_list' => true,
    ),
  ),
  
  'comment' => '区域店铺关系表',
  'engine' => 'innodb',
  'version' => '$Rev:  $',
);