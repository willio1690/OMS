<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

$db['supplier_relation']=array (
  'columns' =>
  array (
    'wms_id' =>
    array (
      'type' => 'int unsigned',
      'required' => true,
      'pkey' => true,
      'editable' => false,
    ),
    'supplier_id' =>
    array (
      'type' => 'number',
      'required' => true,
      'pkey' => true,
      'editable' => false,
      'comment' => '供应商ID',
    ),
    'wms_supplier_bn' =>
    array (
      'type' => 'varchar(32)',
      'editable' => false,
      'comment' => 'WMS供应商编号',
    ),
  ),
  'engine' => 'innodb',
  'version' => '$Rev: 41996 $',
);