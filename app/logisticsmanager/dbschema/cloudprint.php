<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

$db['cloudprint']=array (
  'columns' => 
  array (
    'id' => 
    array (
      'type' => 'int unsigned',
      'required' => true,
      'pkey' => true,
      'editable' => false,
      'extra' => 'auto_increment',
      'comment' => 'ID。自增主键',
    ),
    'code'=>array(
      'type' => 'varchar(25)',

      'editable' => false,
      'comment' => '云打印机编码',
      'label' => '云打印机编码',
      'width' => 120,
      'in_list'         => true,
      'default_in_list' => true,
      'order' => 10,
       'filtertype' => 'normal',
      'filterdefault' => true,

    ),
    'channel_type' => 
    array (
      'type' => 'varchar(25)',

      'editable' => false,
      'comment' => '渠道。可选值: yilianyun (易联云)',
      'label' => '渠道类型',
      'width' => 120,
      'in_list'         => true,
      'default_in_list' => true,
      'order' => 10,
    ),
    'channel_id'         => array(
        'type'     => 'int unsigned',
       
        'editable' => false,

        'comment'  => '渠道主键',
        'label'    => '渠道ID',

    ),
    'disabled' => 
    array (
    'type'     => 'bool',
    'default'  => 'false',
      'editable' => false,
      'comment' => '有效状态。可选值: true (是), false (否)',
      'label' => '禁用状态',
      'width' => 100,
      'in_list'         => true,
      'default_in_list' => true,
      'order' => 20,
    ),
    'machine_code' => 
    array (
      'type' => 'varchar(100)',
      'editable' => false,
      'comment' => '终端号',
      'label' => '终端号',
      'width' => 150,
      'in_list'         => true,
      'default_in_list' => true,
       'filtertype' => 'normal',
      'filterdefault' => true,
      'order' => 30,
    ),
   
    'store_id' => 
    array (
      'type' => 'table:store@o2o',
      'unsigned' => true,
      'editable' => false,
      'comment' => '门店',
      'label' => '门店',
      'width' => 100,
      'in_list' => true,
      'filtertype' => 'normal',
      'filterdefault' => true,
      'width' => 250,
      'order' => 10,
    ),
    'node_id' =>
    array (
      'type' => 'varchar(32)',
      'editable' => false,
      'label' => '节点',
     
    ),
    'is_bind' => 
    array (
      'type' => 'tinyint unsigned',
      'default' => 1,
      'editable' => false,
      'comment' => '绑定状态。可选值: 1 (是), 0 (否)',
      'label' => '绑定状态',
      'width' => 100,
     
      'order' => 70,
    ),
    'at_time'           => [
        'type'    => 'TIMESTAMP',
        'label'   => '创建时间',
        'default' => 'CURRENT_TIMESTAMP',
        'width'           => 130,
        'in_list' => true,
    ],
    'up_time'           => [
        'type'    => 'TIMESTAMP',
        'label'   => '更新时间',
        'default' => 'CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP',
        'width'           => 130,
        'in_list' => true,
    ],
  ),
  'index' =>
  array (
    'ind_cloudprint_store' => array (
      'columns' => array (
        0 => 'store_id',
      ),
    ),
    'ind_cloudprint_machine' => array (
      'columns' => array (
        0 => 'machine_code',
      ),
    ),
    'ind_cloudprint_channel' => array (
      'columns' => array (
        0 => 'channel_type',
      ),
    ),
  ),
  'comment' => '云打印终端表',
  'engine' => 'innodb',
  'version' => '$Rev:  $',
);
