<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

$db['dly_corp_area']=array (
  'columns' =>
  array (
    'corp_id' =>
    array (
      'type' => 'table:dly_corp@ome',
      'required' => true,
      'pkey' => true,
      'editable' => false,
    ),
    'region_id' =>
    array (
      'type' => 'table:regions@eccommon',
      'required' => true,
      'pkey' => true,
      'editable' => false,
    ),
  ),
  'comment' => '物流公司能对应的地区id 关系表',
  'engine' => 'innodb',
  'version' => '$Rev:  $',
);