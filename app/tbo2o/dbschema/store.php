<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

$db['store']=array (
  'columns' =>
  array (
    'store_id' =>
    array (
      'type' => 'int unsigned',
      'required' => true,
      'width' => 110,
      'hidden' => true,
      'editable' => false,
      'pkey' => true,
      'extra' => 'auto_increment',
    ),
    'store_name' =>
    array (
      'type' => 'varchar(255)',
      'label' => '门店名称',
      'editable' => false,
      'in_list' => true,
      'default_in_list' => true,
    ),
    'store_bn' =>
    array (
      'type' => 'varchar(20)',
      'label' => '门店编码',
      'editable' => false,
      'in_list' => true,
      'default_in_list' => true,
    ),
    'cat_id' =>
    array (
      'type' => 'int(10)',
      'label' => '门店类目',
      'editable' => false,
      'in_list' => true,
      'default_in_list' => true,
    ),
    'outer_store_id' =>
    array (
      'type' => 'int(10)',
      'label' => '淘宝门店ID',
      'editable' => false,
      'in_list' => true,
    ),
    'local_store_id' =>
    array (
      'type' => 'int(10)',
      'label' => '本地门店ID',
      'editable' => false,
    ),
    'store_type' =>
    array (
      'type' =>
      array (
        'normal' => '普通门店',
        'mall' => '商城',
        'mall_shop' => '店中店',
        'light_shop' => '淘小铺',
        'hospital' => '阿里健康(医院)',
        'department' => '阿里健康(医院科室)',
        'warehous' => '仓库',
      ),
      'required' => true,
      'default' => 'normal',
      'editable' => false,
      'label' => '门店类型',
      'in_list' => true,
      'default_in_list' => true,
    ),
    'open_hours' =>
    array (
      'type' => 'varchar(20)',
      'label' => '营业时间',
      'editable' => false,
    ),
    'status' =>
    array (
      'type' =>
      array (
        'hold' => '暂停营业',
        'close' => '关店',
        'normal' => '正常',
      ),
      'required' => true,
      'default' => 'normal',
      'editable' => false,
      'label' => '门店状态',
      'in_list' => true,
      'default_in_list' => true,
    ),
    'contacter' =>
    array (
      'type' => 'varchar(50)',
      'label' => '联系人',
      'width' => 60,
      'searchtype' => 'head',
      'editable' => false,
      'filtertype' => 'normal',
      'filterdefault' => true,
      'in_list' => true,
      'default_in_list' => true,
    ),
    'area' =>
    array (
      'type' => 'region',
      'label' => '地区',
      'width' => 170,
      'editable' => false,
      'filtertype' => 'yes',
      'filterdefault' => true,
      'in_list' => true,
      'default_in_list' => true,
    ),
    'address' =>
    array (
      'type' => 'varchar(100)',
      'label' => '详细地址',
      'width' => 180,
      'editable' => false,
      'filtertype' => 'normal',
      'in_list' => true,
      'default_in_list' => true,
    ),
    'tel' =>
    array (
      'type' => 'varchar(30)',
      'label' => '固定电话',
      'width' => 75,
      'editable' => false,
      'in_list' => true,
      'default_in_list' => true,
    ),
    'mobile' =>
    array (
      'label' => '手机',
      'hidden' => true,
      'type' => 'varchar(50)',
      'editable' => false,
      'width' => 85,
      'in_list' => true,
      'default_in_list' => true,
    ),
    'fax' => 
    array (
      'type' => 'varchar(20)',
      'label' => '传真',
      'width' => 110,
      'editable' => false,
      'in_list' => true,
      'default_in_list' => true,
    ),
    'zip' =>
    array (
      'label' => '邮编',
      'type' => 'varchar(20)',
      'editable' => false,
      'in_list' => true,
      'default_in_list' => true,
    ),
   'sync' =>
    array (
      'type' => 'tinyint(1)',
      'default' => '1',
      'label' => '同步状态',
      'in_list' => true,
      'default_in_list' => true,
    ),
  ),
  'comment' => '淘宝门店信息表',
  'index' =>
  array (
    'ind_outer_store_id' =>
    array (
      'columns' =>
      array (
        0 => 'outer_store_id',
      ),
      'prefix' => 'unique',
    ),
    'ind_local_store_id' =>
    array (
      'columns' =>
      array (
        0 => 'local_store_id',
      ),
      'prefix' => 'unique',
    ),
  ),
  'engine' => 'innodb',
);