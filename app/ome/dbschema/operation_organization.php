<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */


$db['operation_organization']=array (
  'columns' => 
  array (
    'org_id' => 
    array (
      'type' => 'number',
      'required' => true,
      'pkey' => true,
      'extra' => 'auto_increment',
      'label' => '运营组ID',
      'width' => 80,
      'comment' => '运营组织ID',
      'editable' => false,
    ),
    'name' => 
    array (
      'type' => 'varchar(200)',
      'label' => app::get('ome')->_('运营组织名称'),
      'width' => 120,
      'editable' => false,
      'comment' => '运营组织名称',
      'in_list' => true,
      'default_in_list' => true,
    ),
    'code' =>
    array (
        'type' => 'varchar(50)',
        'width' => 100,
        'label' => app::get('ome')->_('组织编码'),
        'comment' => '组织编码',
        'editable' => false,
        'in_list' => true,
        'default_in_list' => true,
    ),
    'status' => 
    array (
      'type' => 'tinyint(1)',
      'default' => '1',
      'required' => true,
      'label' => app::get('ome')->_('状态'),
      'width' => 80,
      'comment' => '状态',
      'editable' => false,
      'in_list' => true,
      'default_in_list' => true,
    ),
    'disabled' =>
    array (
      'type' => 'bool',
      'required' => true,
      'default' => 'false',
      'editable' => false,
    ),
  ),
  'comment' => app::get('ome')->_('运营组织主表'),
  'index' => 
  array (   
    'ind_status' => 
    array (
      'columns' => 
      array (
        0 => 'status',
      ),
    ),
  ),
  'engine' => 'innodb',
  'version' => '$Rev: 40912 $',
);
