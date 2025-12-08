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
      'required' => true,
      'editable' => false,
      'comment' => '渠道所属店铺',
      'label' => '主店铺',
    ),
    'channel_type' =>
    array (
      'type' => 'varchar(32)',
      'required' => true,
      'default' => 'wlb',
      'comment' => '渠道类型',
      'label' => '渠道类型',
    ),
    'logistics_code' =>
    array (
      'type' => 'varchar(32)',
      'required' => true,
      'default' => '',
      'comment' => '物流公司编码',
      'label' => '物流公司',
    ),
    'exp_type'=>array (
      'type' => 'varchar(32)',
     
      'default' => '',
      'comment' => '产品类型',
      'label' => '产品类型',
      'in_list' => true,
      'default_in_list' => true,
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
    'bind_status' =>
    array (
      'type' => 'bool',
      'required' => true,
      'default' => 'false',
      'editable' => false,
      'label' => '绑定状态',
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
    'service_code' =>
    array (
      'type' => 'text',
      'editable' => false,
      'comment' => '服务信息',
    ),
    'node_id' =>
    array (
      'type'     => 'varchar(20)',
      'editable' => false,
    ),
    'addon' => 
    array (
      'type' => 'serialize',
      'editable' => false,
    ),
    'outer_id' => array(
      'type' => 'varchar(50)',
      'label' => '外部渠道ID',
      'in_list' => true,
      'default_in_list' => true,
    ),
    'ver' => array(
      'type' => 'tinyint',
      'label' => '版本',
      'default' => '1',
      'in_list' => true,
      'default_in_list' => true,
    ),
  ),
  'comment' => '面单来源表',
  'engine' => 'innodb',
  'version' => '$Rev:  $',
);