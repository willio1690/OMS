<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

$db['product_store']=array (
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
    'store' =>
    array (
      'type' => 'number',
      'default' => 0,
      'label' => '线上在售库存',
      'editable' => false,
      'in_list' => true,
      'default_in_list' => true,
      'order' => 30,
    ),
    'store_freeze' =>
    array (
      'type' => 'number',
      'editable' => false,
      'label' => '线上在售冻结',
      'default' => 0,
      'in_list' => false,
      'default_in_list' => false,
    ),
    'share_store' =>
    array (
      'type' => 'number',
      'editable' => false,
      'label' => '共享库存',
      'default' => 0,
      'in_list' => true,
      'default_in_list' => true,
      'order' => 40,
    ),
    'share_freeze' =>
    array (
      'type' => 'number',
      'editable' => false,
      'label' => '共享库存冻结',
      'default' => 0,
       'in_list' => true,
      'default_in_list' => true,
    ),
    'arrive_store'  => array(
        'type' => 'number',
        'editable' => false,
        'default' => 0,
        'label' => '在途库存',
        'in_list' => true,
        'default_in_list' => true,
    ),
    'last_modified' =>
    array (
      'type' => 'last_modify',
      'editable' => false,
      'label' => '最后更新时间',
      'in_list' => true,
      'default_in_list' => true,
      'order' => 50,
    ),
    'store_bn' => array(
        'type' => 'varchar(20)',
        'label' => '门店编码',
        'editable' => false,
        'in_list' => true,
        'default_in_list' => true,
    ),
    'store_mode' => array (
        'label' => '门店类型',
        'type' => array (
            'normal' => '正价店铺',
            'discount' => '奥莱店铺',
        ),
        'default' => 'normal',
        'width' => 120,
        'editable' => false,
        'in_list' => true,
        'default_in_list' => true,
        'order' => 50,
    ),
    'min_store' => array (
        'type' => 'number',
        'editable' => false,
        'label' => 'MIN最小库存',
        'default' => 0,
        'in_list' => true,
        'default_in_list' => true,
    ),
    'max_store' => array (
        'type' => 'number',
        'editable' => false,
        'label' => 'MAX最大库存',
        'default' => 0,
        'in_list' => true,
        'default_in_list' => true,
    ),
  ),
  'index' =>
  array (
    'ind_branch_id_bm_id' =>
     array (
        'columns' =>
        array (
            0 => 'branch_id',
            1 => 'bm_id',
        ),
        'prefix' => 'unique',
    ),
    'ind_store_bn' => array (
        'columns' => array (
            0 => 'store_bn',
        ),
    ),
    'ind_store_bm_id' => array (
        'columns' => array (
            0 => 'store_bn',
            1 => 'bm_id',
        ),
    ),
  ),
  'comment' => '门店货品库存表',
  'engine' => 'innodb',
  'version' => '$Rev:  $',
);