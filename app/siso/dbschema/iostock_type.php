<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

# ALTER TABLE `sdb_siso_iostock_type` DROP PRIMARY KEY; 
# ALTER TABLE `sdb_siso_iostock_type` ADD COLUMN `id` mediumint(8) unsigned not null auto_increment PRIMARY KEY FIRST
$db['iostock_type']=array (
  'columns' =>
  array (
    'id' =>
    array (
      'type' => 'number',
      'extra' => 'auto_increment',
      'required' => true,
      'pkey' => true,
    ),
    'type_id' =>
    array (
      'type' => 'number',
      'label' => '类型ID',
    ),
    'type_name' =>
    array (
      'type' => 'varchar(32)',
      'label' => '类型名称',
      'is_title' => true,
      'in_list' => true,
      'default_in_list' => true,
    ),
    'bill_type' =>
    array (
      'type' => 'varchar(255)',
      'label' => '业务类型编码',
      'in_list' => true,
      'default_in_list' => true,
    ),
    'bill_type_name' =>
    array (
      'type' => 'varchar(255)',
      'label' => '业务类型名称',
      'in_list' => true,
      'default_in_list' => true,
    ),
    'at_time'       => array(
        'type'            => 'TIMESTAMP',
        'label'           => '创建时间',
        'default_in_list' => true,
        'in_list'         => true,
        'filtertype'      => 'time',
        'filterdefault'   => true,
        'default'         => 'CURRENT_TIMESTAMP',
        'order' => 100,
    ),
    'up_time'       => array(
        'type'            => 'TIMESTAMP',
        'label'           => '更新时间',
        'default_in_list' => true,
        'in_list'         => true,
        'filtertype'      => 'time',
        'filterdefault'   => true,
        'default'         => 'CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP',
        'order' => 110,
    ),
  ),
  'index' =>
  array (
    'idx_type_bill_type' => ['columns'=>['type_id','bill_type'], 'prefix' => 'UNIQUE']
  ),
  'comment' => '出入库类型',
  'engine' => 'innodb',
  'version' => '$Rev:  $',
);