<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

$db['dly_corp_channel']=array (
  'columns' => 
  array (
    'id' =>
    array (
      'type' => 'number',
      'required' => true,
      'pkey' => true,
      'editable' => false,
      'extra' => 'auto_increment',
    ),
    'corp_id' =>
    array (
      'type' => 'table:dly_corp@ome',
      'required' => true,
      'editable' => false,
    ),
    'shop_type' =>
    array (
      'type' => 'varchar(50)',
      'editable' => false,
      'label' => '店铺类型',
    ),
    'channel_id' =>
    array (
      'type' => 'table:channel@logisticsmanager',
      'editable' => false,
      'comment' => '来源主键',
      'label' => '面单来源',
      'width' => 130,
      'in_list' => true,
      'default_in_list' => true,
    ),
    'prt_tmpl_id' =>
    array (
      'type' => 'table:express_template@logisticsmanager',
      'editable' => false,
    ),
  ), 
  'engine' => 'innodb',
  'version' => '$Rev:  $',
);