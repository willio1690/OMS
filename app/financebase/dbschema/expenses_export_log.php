<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

$db['expenses_export_log']=array (
  'columns' => 
  array (
    'id' => 
    array (
      'type' => 'int unsigned',
      'required' => true,
      'pkey' => true,
      'extra' => 'auto_increment',
      'editable' => false,
    ),
    'export_type' => array(
      'type'            => array(
        'main' => '汇总',
        'items' => '明细',
      ),
      'label'           => '类型',
      'editable'        => false,
      'width'           => 120,
      'in_list'         => true,
      'default_in_list' => true,
      'filtertype'      => 'normal',
      'filterdefault'   => true,
    ),
    'filter' => array(
      'type'            => 'longtext',
      'label'           => '导出条件',
      'editable'        => false,
      'width'           => 120,
      'in_list'         => false,
      'default_in_list' => false,
    ),
    'export_time' => array(
      'type'            => 'time',
      'label'           => '导出时间',
      'editable'        => false,
      'width'           => 120,
      'in_list'         => true,
      'default_in_list' => true,
      'filtertype'      => 'normal',
      'filterdefault'   => true,
    ),
    'op_id' => array(
      'type'            => 'table:account@pam',
      'label'           => '操作人',
      'editable'        => false,
      'width'           => 60,
      'in_list'         => true,
      'default_in_list' => true,
    ),
  ),
  'index' => array(
    'ind_export_time' => array('columns' => array(0 => 'export_time')),
   ),
  'comment' => '导出日志表',
  'engine' => 'innodb',
  'version' => '$Rev:  $',
);
