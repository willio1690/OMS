<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

$db['return_refund_apply']=array (
  'columns' => 
  array(
    'refund_apply_id' => 
    array (
      'type' => 'table:refund_apply@ome',
      'required' => true,
      'pkey' => true,
      'editable' => false,
    ),
    'return_id' => 
    array(
      'type' => 'table:return_product@ome',
      'required' => true,
      'pkey' => true,
      'editable' => false,
    ),
    
  ),
    'comment' => '售后服务和退款申请的对应关系表',
    'engine' => 'innodb',
  'version' => '$Rev:  $',
  'charset' => 'utf8mb4',
);