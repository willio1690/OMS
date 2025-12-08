<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

$db['inventory_apply_items']=array (
  'columns' =>
  array (
    'item_id' =>
    array (
      'type' => 'int unsigned',
      'required' => true,
      'pkey' => true,
      'extra' => 'auto_increment',
    ),
    'inventory_apply_id' =>
    array (
      'type' => 'table:inventory_apply@console',
      'required' => true,
    ),
    'bm_id' =>
    array (
      'type' => 'table:products@ome',
      'required' => true,
    ),
    'material_bn' =>
    array (
      'type' => 'varchar(50)',
      'label' => '基础物流编码',
      'in_list' => true,
      'default_in_list' => true,
    ),
    'wms_stores' =>
    array (
      'type' => 'mediumint',
      'default' => 0,
      'label' => 'wms库存',
      'in_list' => true,
      'default_in_list' => true,
    ),
    'oms_stores' =>
    array (
      'type' => 'mediumint',
      'default' => 0,
      'label' => 'oms库存',
      'in_list' => true,
      'default_in_list' => true,
    ),
    'diff_stores' =>
    array (
      'type' => 'mediumint',
      'default' => 0,
      'label' => '差异数量',
      'in_list' => true,
      'default_in_list' => true,
    ),
    'm_type' =>
    array (
      'type' => [
        'zp' => '良品',
        'cc' => '残品',
      ],
      'default' => 'zp',
      'label' => '良/残品',
      'in_list' => true,
      'default_in_list' => true,
    ),
    'is_confirm' =>
    array (
      'type' => [
        '0' => '未确认',
        '1' => '已确认',
      ],
      'default' => '0',
      'label' => '状态',
      'in_list' => true,
      'default_in_list' => true,
    ),
    'memo' =>
    array (
      'type' => 'text',
      'label' => '备注',
      'in_list' => true,
    ),
  'batch'      => array(
    'type'            => 'text',
    'label'           => 'batch',
    'default_in_list' => true,
    'in_list'         => true,
    'order' => 20,
  ),
  ),
  'comment' => '盘点申请明细表',
  'engine' => 'innodb',
  'version' => '$Rev: 44513 $',
);