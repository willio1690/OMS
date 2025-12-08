<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

$db['return_product_youzan']=array (
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
   'contact_id' =>
    array (
      'type' => 'int',
      'label' => '收货地区',
      'width' => 170,
      'editable' => false,
      
    ),
   
   'operation_contraint'=>array(
        'type'=>'varchar(45)',
        'label'=>'操作场景',
   ),
   'current_phase_timeout'=>array(
        'type'=>'time',
        'label'=>'当前状态超时时间',
   ),
    'refund_version'=>array (
      'type' => 'varchar(50)',
      'label'=>'退款版本号',
      'editable' => false,
    ),
    'alipay_no'=>array(
        'type'=>'varchar(45)',
        'label'=>'支付宝交易号',
    ),
    'tag_list'=>array(
        'type'=>'longtext',
        'label'=>'退款标签',
    ),
    'cs_status'=>array(
        'label'=>'淘宝小二是否介入',
        'type' => 'varchar(50)',
      'default' => 'no',
    ),
     
    'buyer_nick'=>array(
        'type' => 'varchar(50)',
        'label'=>'买家昵称',
    ),
    'seller_nick'=>array(
         'type' => 'varchar(50)',
        'label'=>'卖家昵称',
    ),
    'trade_status'=>array(
        'type'=>'varchar(64)',

    ),
    'refund_version'=>array (
      'type' => 'varchar(50)',
      'label'=>'退款版本号',
      'editable' => false,
    ),
     'refund_phase'=>array(
        'type'=>array(
            'onsale'=>'售中',
            'aftersale'=>'售后',
        
        ),
        'default' => 'onsale',
    ),
   
    'refuse_memo'=>array(
        'type' => 'longtext',
        'label' => '拒绝退款原因留言',
    ),
    'oid' => 
    array (
      'type' => 'varchar(50)',
      'default' => 0,
      'editable' => false,
      'label' => '子订单号',
    ),
    'refund_type'=>array(
        'type'=>array(
            'refund'=>'退款单',
            'return'=>'退货单',
            'change' =>'换货',
        ),
        
       'default'=>'return',
    ),
    'bill_type'=>array(
        'type'=>array(
            'refund_bill'=>'退款单',
            'return_bill'=>'退货单',
        ),
        'default'=>'return_bill',
    ),
    'online_memo'=>array(
        'type' => 'longtext',
        'label' => '线上留言凭证',
    ),
      'refund_fee'=>array(
          'type'=>'money',
          'label'=>'需退金额',
      ),
    'jsrefund_flag' =>
    array (
      'type' => 'bool',
      'default' => 'false',
      'required' => true,
      'label' => '是否极速退款',

      'editable' => false,

    ),
    'buyer_logistic_no'=>array(
      'type'=>'varchar(25)',
      'label' => '买家发货物流单号',
      'default'=>'',

    ),
    'buyer_address'=>array(
      'type'=>'varchar(150)',
      'label' => '买家换货地址',
      'default'=>'',

    ),
    'buyer_logistic_name'=>array(
      'type'=>'varchar(32)',
      'label' => '买家发货物流公司名称',
      'default'=>'',

    ),
    'buyer_phone'=>array(
      'type'=>'varchar(32)',
      'label' => '买家联系方式',
      'default'=>'',
    ),
    'seller_address'=>array(
      'type'=>'varchar(150)',
      'label' => '卖家换货地址',

    ),
    'seller_logistic_no'=>array(
      'type'=>'varchar(25)',
      'label' => '卖家发货快递单号',
      'default'=>'',
    ),
    'seller_logistic_name'=>array(
      'type'=>'varchar(20)',
      'label' => '卖家发货物流公司名称',
      'default'=>'',
    ),
    'exchange_sku'=>array(
      'type'=>'varchar(32)',
      'label' => '换货商品的sku',
      'default'=>'',
    ),
    'refusereason'=>array(
      'type' => 'longtext',
      'label' => '拒绝换货原因列表',

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
    'seller_mobile'=>array(
        'type'=>'varchar(35)',
         'label'=>'卖家手机号',
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
    'ind_oid' =>
    array (
        'columns' =>
        array (
          0 => 'oid',
        ),
    ),
  ),
    'comment' => '售后申请有赞附加信息表',
    'engine' => 'innodb',
  'version' => '$Rev:  $',
);