<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

$db['branch_product']=array (
  'columns' =>
  array (
    'id' =>
     array ( 
      'type' => 'number',
      'required' => true,
      'pkey' => true,
      'extra' => 'auto_increment',
      'editable' => false,
    ),
    'branch_id' =>
    array (
      'type' => 'table:branch@ome',
      'required' => true,
      'editable' => false,
    ),
    'bm_id' =>
    array (
      'type' => 'table:basic_material@material',
      'required' => true,
      'editable' => false,
    ),
    'is_ctrl_store' =>
    array (
      'type' => 'tinyint(1)',
      'default' => 2,
      'label' => '库存',
      'width' => 80,
      'required' => false,
      'editable' => false,
      'in_list' => true,
      'default_in_list' => true,
      'order' => 70,
    ),
    'status' => 
    array(
      'type' => 'tinyint(1)',
      'label' => '销售状态',
      'width' => 80,
      'default' => 1,
      'in_list' => true,
      'default_in_list' => true,
      'order' => 80,
    ),
    'is_bind' =>
    array (
      'type' => 'tinyint(1)',
      'default' => 1,
      'label' => '绑定状态',
      'required' => false,
      'editable' => false,
      'order' => 90,
    ),
  ),
  'index' => array(
        'ind_branch_id_bm_id' =>
        array (
            'columns' =>
            array (
                0 => 'branch_id',
                1 => 'bm_id',
            ),
            'prefix' => 'unique',
        ),
  ),
  'comment' => '门店供货关系表',
  'engine' => 'innodb',
  'version' => '$Rev:  $',
);