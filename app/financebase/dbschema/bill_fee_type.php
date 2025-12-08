<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

$db['bill_fee_type']=array (
  'columns' => 
  array (
    'fee_type_id' => 
    array (
      'type' => 'int unsigned',
      'required' => true,
      'pkey' => true,
      'extra' => 'auto_increment',
      'editable' => false,
    ),
    'fee_type' =>
    array (
      'type' => 'varchar(32)',
      //'required' => true,
      'editable' => false,
      'label' => '费用项',
      'default_in_list' => false,
      'in_list' => false,
    ),
    'rule_id' => array(
          'type' => 'table:bill_category_rules@financebase',
          'label' => '具体类别',
          'comment' => '具体类别',
          'width' => 100,
          'editable' => false,
          'in_list' => true,
          'default_in_list' => false,
          'order'=>60,
    ),
    'platform_type' => array(
      'type' => 'varchar(20)',
      'label' => '费用平台',
      'default_in_list' => true,
      'in_list' => true,
    ),
    'bill_type' => array(
      'type' => 'tinyint',
      'default' => 0,
      'required' => true,
      'label' => '资金流向',
      'comment' => '资金流向 0流入 ，1流出',
      'in_list' => true,
      'default_in_list' => true,
      ),
    'shop_id' => array(
          'type' => 'table:shop@ome',
          'label' => '店铺名称',
          'default_in_list' => true,
          'in_list' => true,
          'filtertype' => 'normal',
          'filterdefault' => true,
          'order' => 110,
      ),
    'createtime' => array(
      'type' => 'time',
      'label' => '创建时间',
      'in_list' => true,
    ),
    'whitelist' => array (
        'type' => 'text',
        'label' => '白名单',
    ),
  ),
  'index' => array(
   
    'ind_fee_type' => array('columns' => array(0 => 'fee_type')),
   ),
  'comment' => '费用表',
  'engine' => 'innodb',
  'version' => '$Rev:  $',
);
