<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

$db['return_product_360buy']=array (
  'columns' => 
  array (
    'shop_id' =>
    array (
      'type' => 'table:shop@ome',
      'label' => '来源店铺',
      'pkey' => true,
      'required' => true,
      'width' => 75,
      'editable' => false,
      ),
    'return_id' => 
    array(
      'type' => 'table:return_product@ome',
      'pkey' => true,
      'required' => true,
      'editable' => false,
      'comment' => '售后ID',
    ),
    'return_bn' =>
    array (
      'type' => 'varchar(32)',
      'required' => true,
      'label' => '退货记录流水号',
      'comment' => '退货记录流水号',
      'editable' => false,
     
    ),
    'refund_type'=>array(
        'type'=>array(
            'refund'=>'退款单',
            'return'=>'退货单',
            'change' =>'换货',
        ),

       'default'=>'return',
    ),
    'oid' =>
    array (
      'type' => 'varchar(32)',
      'default' => 0,
      'editable' => false,
      'label' => '子订单号',
    ),
    'exchange_num'=>array(
      'type'=>'number',
      'label' => '换货商品数量',
      'default'=>0,
    ),
    'exchange_price'=>array(
        'type'=>'money',
        'label'=>'换货金额',
    ),
    'exchange_sku'=>array(
      'type'=>'varchar(32)',
      'label' => '换货商品的sku',
      'default'=>'',
    ),
   'receive_state'=>array(
    'type'=>'varchar(32)',
    /*array(
        'BUYER_NOT_RECEIVED'=>'买家未收到货',
        'BUYER_RECEIVED'=>'买家已收到货',
        'BUYER_RETURNED_GOODS'=>'买家已退货',
    ),*/
    'label'=>'货物状态',
   ),
   'send_type'=>array(
    'type'=>'varchar(45)',
    'label'=>'物流方式',
   
   ),
   'refuse_memo'=>array(
        'type' => 'longtext',
        'label' => '拒绝退款原因留言',
    ),
   'return_address'=>array(
     'type' => 'longtext',
      'label' => '返件地址',
      
      'editable' => false,
   ),
   'pick_address'=>array(
      'type' => 'longtext',
      'label' => '取件地址',
      'editable' => false,
   ),
   'customer_info'=>array(
      'type' => 'longtext',
      'label' => '客户信息',
      'editable' => false,
   ),
   'online_memo'=>array(
      'type' => 'longtext',
      'label' => '协商内容',
      'editable' => false,
   ),
   'apply_detail'=>array(
        'type' => 'longtext',
        'label' => '申请单明细列表',
    ),
    'logi_no' => array (
       'type' => 'varchar(50)',
       'label' => '运单号',
    ),
    'refund_version'=>array (
      'type' => 'varchar(50)',
      'label'=>'退款版本号',
      'editable' => false,
    ),
    'buyer_nick'=>array(
        'type' => 'varchar(50)',
        'label'=>'买家昵称',
    ),
    'pickware_type' => array (
        'type' => array (
            '40' => '客户发货',
            '4' => '上门取件',
            '7' => '客户送货',
        ),
        'label' => '取件类型',
        'default' => '40',
    ),
   'contact_id' =>
    array (
      'type' => 'int',
      'label' => '收货地区',
      'width' => 170,
      'editable' => false,
    ),
    'approve_reason' => array (
      'type' => 'int',
      'label' => '审核理由',
      'default' => '1',
    ),
 ),
  'index' =>
  array (
    'ind_return_apply_bn_shop' =>
    array (
        'columns' =>
        array (
          0 => 'return_id',
          1 => 'shop_id',
        ),
        'prefix' => 'unique',
    ),
    
  ),
    'comment' => '售后申请京东附加信息表',
    'engine' => 'innodb',
  'version' => '$Rev:  $',
);