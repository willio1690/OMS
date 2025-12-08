<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

$db['setting_shop']=array (
  'columns' => 
  array (
    'rid' => array (
        'type' => 'number',
        'required' => true,
        'pkey' => true,
        'extra' => 'auto_increment',
        'editable' => false,
        'label' => "关系ID",
    ),
    'sid' => array (
        'type' => 'table:setting@purchase',
        'required' => true,
        'editable' => false,
        'label' => "JIT配置ID",
    ),
    'shop_id' => array (
        'type' => 'table:shop@ome',
        'required' => true,
        'editable' => false,
        'label' => "应用到的店铺ID",
    ),
  ),
  'index' =>
    array (
            
  ),
  'comment' => 'JIT配置关联店铺表',
  'engine' => 'innodb',
  'version' => '$Rev:  $',
);