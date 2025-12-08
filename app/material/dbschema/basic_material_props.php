<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * 基础物料配置信息数据结构
 *
 * @author kamisama.xia@gmail.com 
 * @version 0.1
 */

$db['basic_material_props']=array (
  'columns' =>
  array (
    'id' =>
    array (
      'type' => 'int unsigned',
      'required' => true,
      'pkey' => true,
      'extra' => 'auto_increment',
      'label' => 'ID',
      'width' => 110,
      'hidden' => true,
      'editable' => false,
      'comment' => '自增主键ID'
    ),

    'bm_id' =>
    array(
        'type' => 'int unsigned',
        'required' => true,
        'width' => 110,
        'hidden' => true,
        'editable' => false,

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
  'comment' => '基础物料配置表',
  'engine' => 'innodb',
  'version' => '$Rev:  $',
);
