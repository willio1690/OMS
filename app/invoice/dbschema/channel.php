<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

$db['channel']=array (
  'columns' => 
  array (
    'channel_id' =>
    array (
      'type' => 'int unsigned',
      'required' => true,
      'editable' => false,
      'pkey' => true,
      'comment' => '渠道主键',
      'label' => '渠道ID',
      'extra' => 'auto_increment',
    ),
    'name' =>
    array (
      'type' => 'varchar(255)',
      'required' => true,
      'editable' => false,
      'comment' => '渠道名称',
      'label' => '来源名称',
      'width' => '180',
      'in_list' => true,
      'default_in_list' => true,
      'is_title' => true,
      'order' => 10,
    ),
    'shop_id' =>
    array (
      'type' => 'varchar(100)',
      // 'required' => true,
      'editable' => false,
      'comment' => '渠道所属店铺',
      'label' => '主店铺',
    ),
    'channel_type' =>
    array (
      'type' => 'varchar(32)',
      'required' => true,
      'default' => 'taobao',
      'comment' => '渠道类型',
      'default_in_list' => true,
      'label' => '渠道类型',
    ),
    'create_time' =>
    array (
      'type' => 'time',
      'editable' => false,
      'comment' => '渠道创建时间',
      'label' => '创建时间',
      'width' => '130',
      'in_list' => true,
      'default_in_list' => true,
      'order' => 50,
    ),
    'channel_id' =>
    array (
      'type' => 'int unsigned',
      'required' => true,
      'editable' => false,
      'pkey' => true,
      'comment' => '渠道主键',
      'label' => '渠道ID',
      'extra' => 'auto_increment',
    ),
    'status' =>
    array (
      'type' => 'bool',
      'required' => true,
      'default' => 'true',
      'editable' => false,
      'comment' => '启用状态',
      'label' => '启用状态',
      'width' => '80',
      'in_list' => true,
      'default_in_list' => true,
      'order' => 60,
    ), 
      #百望的电子发票需要客户配置设备类型、开票点编码
     'extend_data'=>array(
         'type' => 'longtext',
         'editable' => false,    
     ),
    'node_id' => array(
        'type' => 'varchar(32)',
        'label' => '节点',
        'editable' => false,
        'in_list' => true,
        'default_in_list' => true,
        'width' => '120',
    ),
    'node_type' => array(
        'type' => 'varchar(32)',
        'editable' => false,
        'label' => '节点类型',
        'in_list' => true,
        'default_in_list' => true,
    ),
    'golden_tax_version' => array (
        'type'            => array (
            0 => '金税三期',
            1 => '金税四期',
        ),
        'default'         => '0',
        'label'           => '金税系统版本',
        'in_list'         => true,
        'default_in_list' => true,
        'order'           => 3,
        'width'           => 100,
    ),
  ),
  'comment' => '电子发票渠道来源表',
  'engine' => 'innodb',
  'version' => '$Rev:  $',
);