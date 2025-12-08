<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

$db['inventory_items']=array (
  'columns' =>
  array (
    'item_id' =>
     array (
      'type' => 'mediumint(8)',
      'required' => true,
      'pkey' => true,
      'extra' => 'auto_increment',
      'label' => '明细表主键ID',
    ),
    'inventory_id' =>
    array (
      'type' => 'varchar(32)',
      'required' => true,
      'label' => '盘点单ID',
    ),
    'bm_id' =>
    array (
      'type' => 'int(10)',
      'required' => true,
      'label' => '基础物料ID',
    ),


    'pos_accounts_num' =>
    array (
      'type' => 'mediumint(8)',
      'label' => 'POS账面数',//oms_store
    ),
    'accounts_num' =>
    array (
      'type' => 'mediumint(8)',
      'label' => '线上账面数',//oms_store
    ),
    'accounts_share_num' =>
    array (
      'type' => 'mediumint(8)',
      'label' => '共享账面数',
    ),
    'actual_num' =>
    array (
      'type' => 'mediumint(8)',
      'label' => '线上实际数',//wms_store
    ),
    'actual_share_num' =>
    array (
      'type' => 'mediumint(8)',
      'label' => '共享实际数',
    ),
    'short_over' =>
    array (
      'type' => 'mediumint(9)',
      'label' => '线上盘盈亏',
    ),
    'share_short_over' =>
    array (
      'type' => 'mediumint(9)',
      'label' => '共享盘盈亏',
    ),
    'material_bn' =>
    array (
      'type' => 'varchar(200)',
      'label' => '基础物料编码',
    ),
    'material_name' =>
    array (
      'type' => 'varchar(200)',
    
      'label' => '基础物料名称',
    ),
    'price' => 
    array (
      'type' => 'money',
      'default' => '0',
      'label' => '价格',
    ),
    'amount'=> array(
        'type'     => 'money',
        'default'  => '0',
        'label' => '小计',
    ),
  ),
  'index' =>
    array (
        'ind_inventory_id' =>
        array (
            'columns' =>
            array (
                0 => 'inventory_id',
            ),
        ),
        'ind_bm_id' =>
        array (
            'columns' =>
            array (
                0 => 'bm_id',
            ),
        ),
  ),
  'comment' => '门店盘点明细表',
  'engine' => 'innodb',
  'version' => '$Rev:  $',
);