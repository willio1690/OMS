<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

$db['supplier_goods']=array (

  'columns' => 
  array (
    'gid' =>
    array (
      'type' => 'int unsigned',
      'required' => true,
      'pkey' => true,
      'extra' => 'auto_increment',
      'editable' => false,
    ),
    'supplier_id' => 
    array (
      'type' => 'int unsigned',
      'required' => true,
      'editable' => false,
      'in_list' => false,
      'default_in_list' => false,
    ),
    'bm_id' => 
    array (
      'type' => 'int unsigned',
      'required' => true,
      'editable' => false,
      'in_list' => false,
      'default_in_list' => false,
    ),
  ),
  'index' =>
    array (
        'ind_sid_bmid' =>
        array (
            'columns' =>
            array (
                    0 => 'supplier_id',
                    1 => 'bm_id',
            ),
            'prefix' => 'unique',
        ),
  ),
  'comment' => '供应商商品',
  'engine' => 'innodb',
  'version' => '$Rev: 44513 $',
);
