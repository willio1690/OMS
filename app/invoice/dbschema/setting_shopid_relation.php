<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

$db['setting_shopid_relation']=array (
  'columns' => 
  array (
    'rel_id' => array (
        'type' => 'number',
        'required' => true,
        'pkey' => true,
        'extra' => 'auto_increment',
        'editable' => false,
        'label' => "关系ID",
    ),
    
    'sid' => array (
        'type' => 'table:order_setting@invoice',
        'required' => true,
        'editable' => false,
        'label' => "开票配置ID",
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
    'comment' => '开票配置和店铺关系表',
    'engine' => 'innodb',
    'version' => '$Rev:  $',
);