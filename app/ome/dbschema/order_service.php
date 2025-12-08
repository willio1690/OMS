<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

$db['order_service']=array (
  'columns' => 
  array (
    'id' => 
    array (
      'type' => 'number',
      'required' => true,
      'pkey' => true,
      'extra' => 'auto_increment',
      'editable' => false,
    ),
    'order_id' =>
    array (
      'type' => 'number',
      'required' => true,
      'editable' => false,
        'label' => '订单号',
    ),

    'item_oid' =>
      array (
          'type' => 'varchar(50)',
          'default' => 0,
          'editable' => false,
          'label' => '服务所属的交易订单号',
      ),

      'tmser_spu_code' =>
        array (
            'type' => 'varchar(50)',
            'default' => 0,
            'editable' => false,
            'label' => '支持家装类物流的类型',
        ),
      'sale_price' =>
          array (
              'type' => 'money',
              'editable' => false,
              'label'=>'销售价',
          ),
    'num' =>
        array (
          'type' => 'longtext',
          'edtiable' => false,
            'label'=>'购买数量',
        ),
    'total_fee' =>
        array (
            'type' => 'money',
          'editable' => false,
            'label'=>'服务子订单总费用',
        ),
      'type' =>
          array (
              'type' => 'varchar(15)',
              'editable' => false,
              'default' => 'service',
              'label'=>'服务',
          ),
    'type_alias' =>
      array (
          'type' => 'varchar(50)',
          'default' => 0,
          'editable' => false,
          'label'=>'服务别名',
      ),
    'title' =>
      array (
          'type' => 'varchar(50)',
          'default' => 0,
          'editable' => false,
            'label'=>'商品名称',
      ),
    'service_id' =>
      array (
          'type' => 'varchar(50)',
          'default' => 0,
          'editable' => false,
          'label' => '服务数字id',
      ),
      'refund_id' =>
          array (
              'type' => 'varchar(50)',
              'default' => 0,
              'editable' => false,
              'label' => '最近退款的id',
          ),
  ),
'index' =>
    array (
        'ind_order_id' =>
            array (
                'columns' =>
                    array (
                        0 => 'order_id',
                    ),
            ),

    ),
  'comment' => '服务订单表',
  'engine' => 'innodb',
  'version' => '$Rev:  $',
);