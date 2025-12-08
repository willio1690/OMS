<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

$db['autostore_rule_area_items']=array (
  'columns' =>
  array (
    'rule_id' =>
    array (
      'type' => 'int unsigned',
      'required' => true,
      'editable' => false,
    ),
    'area_id' =>
    array (
      'type' => 'int unsigned',
      'required' => true,
      'editable' => false,
    ),
    'p_area_id' => 
    array (
      'type' => 'number',
      'label' => '分类ID',
      'editable' => false,
      'default' => 0,
    ),
  ),
  'comment' => '门店优选规则区域覆盖表',
  'engine' => 'innodb',
  'version' => '$Rev:  $',
);