<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

$db['return_product_bbc']=array (
  'columns' => 
  array (
    'shop_id' =>
    array (
      'type' => 'table:shop@ome',
      'label' => '来源店铺',
     
  
      'width' => 75,
      'editable' => false,
      ),
    
    'return_bn' =>
    array (
      'type' => 'varchar(32)',
   
      'label' => '退货记录流水号',
      'comment' => '退货记录流水号',
      'editable' => false,
     
    ),
   'return_type' =>
    array (
      'type' =>
      array (
        'return' => '退货',
        'change' => '换货',
		'refund' => '退款',
      ),
      'default' => 'return',
      'required' => true,
      'comment' => '退换货状态',
      'editable' => false,
      'label' => '退换货状态',
      'width' =>65,
   
    ),
    
 ),
  'index' =>
  array (
    'ind_return_apply_bn_shop' =>
    array (
        'columns' =>
        array (
          0 => 'return_bn',
          1 => 'shop_id',
        ),
        'prefix' => 'unique',
    ),
    
  ),
  'comment' => '售后申请BBC附加表',
  'engine' => 'innodb',
  'version' => '$Rev:  $',
);