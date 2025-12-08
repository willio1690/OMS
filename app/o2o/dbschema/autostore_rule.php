<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

$db['autostore_rule']=array (
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
      'comment' => '仓库ID',
    ),
    'rule_name'=>
    array(
        'type'=>'varchar(200)',
        'label' => '规则名称',
        'in_list'         => true,
        'default_in_list' => true,
    ),
    'rule_type' =>
    array (
        'label' => '规则类型',
        'type' => array (
            'area' => '按区域覆盖',
            'lbs' => '按定位服务',
        ),
        'default' => 'area',
        'width' => 100,
        'editable' => false,
        'in_list'         => true,
        'default_in_list' => true,
    ),
    'disabled' =>
    array (
      'type' => 'bool',
      'required' => true,
      'default' => 'false',
      'editable' => false,
      'label' => '是否启用',
    ),
  ),
  'comment' => '门店优选规则',
  'engine' => 'innodb',
  'version' => '$Rev:  $',
);