<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

$db['syncproduct']=array (
  'columns' => 
  array (
    'id' =>
    array (
        'type' => 'int unsigned',
        'required' => true,
        'pkey' => true,
        'extra' => 'auto_increment',
    ),
    'material_bn' => 
    array (
      'type' => 'varchar(50)',
      'required' => true,
      'label' => '基础物料编码',
    
      'in_list' => true,
      'default_in_list' => true,
      'searchtype'      => 'has',
      'filterdefault'   => true,
      'filtertype'      => 'textarea',
      'order' => 2,
    ),
    'bm_id' => 
    array (
      'type' => 'int unsigned',
      'required' => true,
      'label' => '货品ID',
      'width' => 110,
      'editable' => false,
      'order' => 11,
    ),
   
    'type' =>
    array (
      'type' => array (
        1 => '成品',
        2 => '半成品',
        3 => '普通',
        4 => '礼盒',
        5 => '虚拟',
      ),
      'label' => '物料属性',
      'width' => 100,
      'editable' => false,
      'default'         => 1,
      'editable' => false,
      'in_list' => true,
      'default_in_list' => true,
      'required'        => true,
    ),
    'retail_price' =>
    array (
        'type' => 'money',
        'default' => '0.000',
        'label' => '物料售价',
        'width' => 75,
        'in_list' => true,
        'default_in_list' => true,
        'order'   => 8,
    ),
    'at_time'       => array(
      'type'    => 'TIMESTAMP',
      'label'   => '创建时间',
      'default' => 'CURRENT_TIMESTAMP',
      'width'   => 120,
      'in_list' => true,
      'order'   => 11,
    ),
    'up_time'       => array(
      'type'    => 'TIMESTAMP',
      'label'   => '更新时间',
      'default' => 'CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP',
      'width'   => 120,
      'in_list' => true,
      'order'   => 11,
    ),
    'price_status'        => [
        'type'            => [
            '0' => '正常',
            '1' => '异常',

        ],
        'editable'        => false,
        'width'           => 100,
        'in_list'         => true,
        'default'         => '0',
        'comment'         => '价格获取状态',
        'label'           => '价格获取状态',
        'in_list'         => true,
        'default_in_list' => true,
        'filtertype'      => 'normal',
        'filterdefault'   => true,
    ],
    'sync_status' =>
    array (
     'type' =>
      array (
        0 => '待同步',
        1 => '同步成功',
        2 => '同步失败',
        3 => '等待同步',
      ),
      'default' => '0',
      'required' => true,
      'label' => '同步状态',
      'in_list' => true,
      'default_in_list' => true,
      'searchtype' => 'has',
      'editable' => false,
      'filtertype' => 'normal',
      'filterdefault' => true,
      'order' => 90,
    ),
    'sync_msg' => array (
        'type' => 'varchar(80)',
        'label' => '推送失败原因',
        'editable' => false,
        'in_list' => true,
        'default_in_list' => true,
        'order' => 99,
      ),
  ),
  'index' =>
      array (
        'ind_bm_id'   => array('columns' => array('bm_id'), 'prefix' => 'UNIQUE'),
        
        'ind_sync_status' =>
        array (
            'columns' =>
            array (
              0 => 'sync_status',
            ),
        ),
      ),
  'comment' => 'pos sku关联表',
  'engine' => 'innodb',
  'version' => '$Rev: 40654 $',
);