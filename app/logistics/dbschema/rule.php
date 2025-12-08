<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

$db['rule']=array (
  'columns' =>
  array (
    'rule_id' =>
    array (
      'type' => 'int unsigned',
      'required' => true,
      'pkey' => true,
      'editable' => false,
      'extra' => 'auto_increment',
    ),
     'branch_id' =>
    array (
      'type' => 'table:branch@ome',
      'required' => true,
      'default' => 0,
      'editable' => false,
      'in_list'         => true,
      'default_in_list' => true,
      'label' => '仓库',
    ),
    'rule_name'=>
    array(
    'type'=>'varchar(200)',
    'label' => '规则名称',
    'in_list'         => true,
    'default_in_list' => true,
    ),

    'first_city'=>array(
        'type' => 'varchar(200)',
        'label' => '一级地区',
        'in_list' => true,
        'default_in_list' => true,
    ),
    
    'first_city_id'=>array (
      'type' => 'varchar(200)',
      'editable' => false,
      'comment' => '一级地区ID',

    ),

     'last_modified' =>
    array (
      'label' => '最后更新时间',
      'type' => 'last_modify',
      'width' => 130,
      'editable' => false,
      'in_list' => true,
    ),
    'disabled' =>
    array (
      'type' => 'bool',
      'required' => true,
      'default' => 'false',
      'editable' => false,
      'label' => '是否启用',
    ),
    'weight' =>
    array (
        'type' => 'number',
        'editable' => false,
        'in_list' => true,
        'default' => 0,
        'label' => '权重',
    ),
),

  'comment' => '规则',
  'engine' => 'innodb',
  'version' => '$Rev:  $',
);