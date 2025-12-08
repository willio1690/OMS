<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * 基础物料与条码关联数据结构
 *
 * @author kamisama.xia@gmail.com 
 * @version 0.1
 */

$db['codebase']=array (
  'columns' =>
  array (
    'bm_id' =>
    array (
      'type' => 'int unsigned',
      'required' => true,
      'width' => 110,
      'hidden' => true,
      'editable' => false,
    ),
    'type' =>
    array (
      'type' => 'tinyint(1)',
      'label' => '条码类型',
      'required' => true,
      'default' => 1,
      'hidden' => true,
      'editable' => false,
      'in_list' => true,
      'default_in_list' => false,
    ),
    'code' =>
    array (
      'type' => 'varchar(200)',
      'editable' => false,
      'label' => '条码',
      'required' => true,
      'hidden' => true,
      'in_list' => true,
      'default_in_list' => false,
    ),
    'at_time'       => array(
        'type'            => 'TIMESTAMP',
        'label'           => '创建时间',
        'default_in_list' => false,
        'in_list'         => false,
        'default'         => 'CURRENT_TIMESTAMP',
    ),
    'up_time'       => array(
        'type'            => 'TIMESTAMP',
        'label'           => '更新时间',
        'default_in_list' => false,
        'in_list'         => false,
        'default'         => 'CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP',
    ),
  ),
'index' =>
  array (
    'ind_code' =>
    array (
        'columns' =>
        array (
          0 => 'code',
        ),
    ),
    'ind_bm_id' =>
    array (
        'columns' =>
        array (
          0 => 'bm_id',
        ),
    ),
    'idx_at_time'           => array(
        'columns' => array(
            0 => 'at_time'
        )
    ),
    'idx_up_time'           => array(
        'columns' => array(
            0 => 'up_time'
        )
    ),
  ),
  'comment' => '基础物料与条码关联表',
  'engine' => 'innodb',
  'version' => '$Rev:  $',
);
