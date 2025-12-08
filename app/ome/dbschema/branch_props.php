<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

$db['branch_props']=array (
  'columns' =>
  array (
    'id'          => array(
        'type'     => 'int unsigned',
        'required' => true,
        'pkey'     => true,
        'extra'    => 'auto_increment',
        'editable' => false,

    ),
    'branch_id' =>
    array (
      'type' => 'varchar(32)',
     
    ),
    
    'props_col'=>array(
      'type' => 'varchar(50)',
      'editable' => false,
      'label' => '键名',
     
    ),
    'props_value'=>array(
      'type' => 'varchar(200)',
      'editable' => false,
      'label' => '值',
     
    ),
    'operator' => array(
      'type' => 'varchar(50)',
      'editable' => false,
      'label' => '操作人',
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
    'idx_branch_id' => array('columns' => array('branch_id')),
    'idx_props_col' => array('columns' => array('props_col')),
  ),
  'comment' => '仓库自定义表',
  'engine' => 'innodb',
  'version' => '$Rev:  $',
); 