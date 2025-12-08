<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * 销售物料店铺冻结数据结构
 *
 * @author kamisama.xia@gmail.com 
 * @version 0.1
 */

$db['sales_material_shop_freeze']=array (
  'columns' =>
  array (
    'sm_id' =>
    array (
      'type' => 'int unsigned',
      'required' => true,
      'width' => 260,
      'in_list' => true,
      'default_in_list' => true,
    ),
    'shop_id' => array(
        'type' => 'varchar(32)',
        'required' => true,
        'in_list' => true,
        'default_in_list' => true,
        'width' => 260,
    ),
    'shop_freeze' => array(
        'type' => 'number',
        'label' => '店铺销售物料库存冻结',
        'default' => 0,
        'in_list' => true,
        'default_in_list' => true,
    ),
  ),
  'comment' => '销售物料店铺冻结表',
  'engine' => 'innodb',
  'version' => '$Rev:  $',
);
