<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

$db['iostock_notify']=array (
  'columns' => 
  array (
    'cin_id' => 
    array (
      'type' => 'int unsigned',
      'required' => true,
      'pkey' => true,
      'editable' => false,
      'extra' => 'auto_increment',
    ),
    'iso_bn' =>
    array (
        'type' => 'varchar(32)',
        'required' => true,
        'label' => '出入库单号',
        'is_title' => true,
        'default_in_list'=>true,
        'searchtype' => 'has',
        'in_list'=>true,
        'width' => 125,
        'filtertype' => 'normal',
        'filterdefault' => true,
    ),
    'trfoutstore' =>
    array (
      'type' => 'varchar(32)',
      'editable' => false,
      'label' => '转出仓编码',
      'in_list' => true,
      'default_in_list' => true,
      'width' => 80,
    ),
    'trfoutno' =>
    array (
      'type' => 'varchar(32)',
      'default' => 0,
      'required' => true,
      'editable' => false,
      'label' => '转出单据号',
      'in_list' => true,
      'default_in_list' => true,
      'width' => 200,
    ),
    'trfoutdate' =>
    array (
      'type' => 'time',
      'default' => '0',
      'editable' => false,
      'label' => '转出时间',
      'in_list' => true,
      'default_in_list' => true,
      'width' => 150,
    ),
    'trfoutreplflag' =>
    array (
      'type' => 'varchar(32)',
      'editable' => false,
      'label' => '转出标记',
      'in_list' => true,
      'default_in_list' => true,
      'width' => 80,
    ),
    'trfoutremark' =>
    array (
      'type' => 'text',
      'editable' => false,
      'label' => '转出备注',
      'in_list' => true,
      'default_in_list' => true,
      'width' => 160,
    ),
    'trfinstore' =>
    array (
      'type' => 'varchar(32)',
      'editable' => false,
      'label' => '转入仓编号',
      'in_list' => true,
      'default_in_list' => true,
      'width' => 80,
    ),
    'trfoutdtlcount' =>
    array (
      'type' => 'number',
      'editable' => false,
      'label' => '出仓单的总行数',
      'in_list' => true,
      'default_in_list' => true,
      'width' => 120,
    ),
    'storecode' =>
    array (
      'type' => 'varchar(6)',
      'editable' => false,
      'label' => '店铺编码',
      'in_list' => true,
      'default_in_list' => true,
    ),
    'country' =>
    array (
      'type' => 'varchar(30)',
      'editable' => false,
      'label' => '国家编码',
      'in_list' => true,
      'default_in_list' => true,
    ),
    'brand' =>
    array (
      'type' => 'varchar(30)',
      'editable' => false,
      'label' => '品牌编码',
      'in_list' => true,
      'default_in_list' => true,
    ),
    'status' =>
    array (
      'type' => 'tinyint(1)',
      'required' => true,
      'default' => '0',
      'editable' => false,
      'label' => '状态',
      'in_list' => true,
      'default_in_list' => true,
    ),
    'createtime' =>
    array (
      'type' => 'time',
      'label' => '创建时间',
      'width' => 130,
      'editable' => false,
      'in_list' => true,
      'default_in_list' => true,
    ),
    'last_modified' =>
    array (
      'label' => '修改时间',
      'type' => 'last_modify',
      'width' => 130,
      'editable' => false,
      'in_list' => true,
      'default_in_list' => true,
    ),
  ),
  'index' =>
  array (
    'ind_trfoutno' =>
    array (
        'columns' =>
        array (
          0 => 'trfoutno',
        ),
    ),
    'ind_status' =>
    array (
        'columns' =>
        array (
          0 => 'status',
        ),
    ),
    'ind_createtime' =>
    array (
        'columns' =>
        array (
          0 => 'createtime',
        ),
    ),
    'ind_iso_bn' =>
    array (
        'columns' =>
        array (
            0 => 'iso_bn',
        ),
    ),
  ),
  'comment' => '转仓单通知表',
  'engine' => 'innodb',
  'version' => '$Rev: 44513 $',
);
