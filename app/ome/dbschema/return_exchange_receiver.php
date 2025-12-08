<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

$db['return_exchange_receiver']=array (
  'columns' => 
  array (
   'return_id'=>array(
      'type' => 'table:return_product@ome',
      'required' => true,
      'pkey' => true,
      'editable' => false,
   ),
   'buyer_nick' =>
    array (
      'type' => 'varchar(255)',
      'editable' => false,
      'label' => '买家昵称',
      'in_list' => true,
      'default_in_list' => true,
    ),
   'buyer_name' =>
    array (
      'type' => 'varchar(255)',
      'editable' => false,
      'label' => '买家名称',
      'in_list' => true,
      'default_in_list' => true,
    ),
   'buyer_phone' =>
    array (
      'type' => 'varchar(255)',
      'editable' => false,
      'label' => '买家电话',
      'in_list' => true,
      'default_in_list' => true,
    ),
   'buyer_province' =>
    array (
      'type' => 'varchar(255)',
      'editable' => false,
      'label' => '买家省',
      'in_list' => true,
      'default_in_list' => true,
    ),
   'buyer_city' =>
    array (
      'type' => 'varchar(255)',
      'editable' => false,
      'label' => '买家市',
      'in_list' => true,
      'default_in_list' => true,
    ),
   'buyer_district' =>
    array (
      'type' => 'varchar(255)',
      'editable' => false,
      'label' => '买家区',
      'in_list' => true,
      'default_in_list' => true,
    ),
   'buyer_town' =>
    array (
      'type' => 'varchar(255)',
      'editable' => false,
      'label' => '买家镇',
      'in_list' => true,
      'default_in_list' => true,
    ),
   'buyer_address' =>
    array (
      'type' => 'varchar(255)',
      'editable' => false,
      'label' => '买家地址',
      'in_list' => true,
      'default_in_list' => true,
    ),
   'encrypt_source_data' =>
    array (
      'type' => 'text',
      'editable' => false,
      'label' => '加密数据',
      'in_list' => true,
      'default_in_list' => true,
    ),
  ),
  'index' => array (
        /*'in_reship_id' => array (
            'columns' => array (
                    0 => 'reship_id',
            ),
        ),*/
  ),
  'engine' => 'innodb',
  'version' => '$Rev:  $',
  'comment'=>'换货地址表',
);