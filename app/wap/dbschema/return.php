<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

$db['return']=array (
  'columns' =>
  array (
    'return_id' =>
    array (
      'type' => 'int unsigned',
      'required' => true,
      'pkey' => true,
      'editable' => false,
      'extra' => 'auto_increment',
      'label' => '退换货ID',
    ),
    'return_bn' =>
    array(
        'type' => 'varchar(32)',
        'required' => true,
        'label' => '退换货单号',
        'editable' => false,
    ),
    'original_reship_bn' =>
    array(
        'type' => 'varchar(32)',
        'required' => true,
        'label' => '原始退换货单号',
        'editable' => false,
    ),
    'order_id' =>
    array(
        'type' => 'table:orders@ome',
        'required' => true,
        'label' => '订单号',
        'editable' => false,
    ),
    'shop_id' =>
    array (
        'type' => 'table:shop@ome',
        'required' => true,
        'label' => '来源店铺',
        'editable' => false,
    ),
    'aftersale_id' =>
    array (
        'type' => 'int(10)',
        'editable' => false,
        'label' => '售后申请单ID',
    ),
    'branch_id' =>
    array (
        'type' => 'table:branch@ome',
        'required' => true,
        'editable' => false,
        'label'=>'门店仓ID',
    ),
    'changebranch_id' =>
    array (
        'type' => 'number',
        'editable' => false,
        'label'=>'换货门店仓ID',
    ),
    'return_type' =>
    array (
        'type' =>
        array (
            'return' => '退货',
            'change' => '换货',
        ),
        'default' => 'return',
        'required' => true,
        'editable' => false,
        'label' => '退换货状态',
    ),
    'status' =>
    array(
        'type' => 'tinyint(1)', //1待处理 2拒绝 3已处理
        'label' => '状态',
        'default' => 1,
        'required' => true,
        'editable' => false,
    ),
    'ship_name' =>
    array (
        'type' => 'varchar(50)',
        'label' => '收货人',
        'editable' => false,
    ),
    'ship_addr' =>
    array (
        'type' => 'varchar(100)',
        'label' => '收货地址',
        'editable' => false,
    ),
    'ship_area' =>
    array (
        'type' => 'region',
        'label' => '收货地区',
        'editable' => false,
    ),
    'ship_zip' =>
    array (
        'type' => 'varchar(20)',
        'label' => '收货邮编',
        'editable' => false,
    ),
    'ship_tel' =>
    array (
        'type' => 'varchar(30)',
        'label' => '收货人电话',
        'editable' => false,
    ),
    'ship_mobile' =>
    array (
        'type' => 'varchar(50)',
        'label' => '收货人手机',
        'editable' => false,
    ),
    'ship_email' =>
    array (
        'type' => 'varchar(150)',
        'label' => '收货人Email',
        'editable' => false,
    ),
    'createtime' =>
    array (
        'type' => 'time',
        'label' => '单据创建时间',
        'editable' => false,
    ),
    'last_modified' =>
    array (
        'type' => 'time',
        'editable' => false,
        'label' => '最后更新时间',
    ),
    'tmoney' =>
    array (
        'type' => 'money',
        'editable' => false,
        'label' => '退款的金额',
    ),
    'bmoney' =>
    array (
        'type' => 'money',
        'editable' => false,
        'label' => '折旧(其他费用)',
    ),
    'diff_money' => array(
        'type' => 'money',
        'label' => '买家已补款金额',
    ),
    'cost_freight' => array(
        'type' => 'money',
        'label' => '买家承担运费',
    ),
    'bcmoney' => array(
        'type' => 'money',
        'label' => '商家补偿买家金额',
    ),
    'change_money' => array(
        'type' => 'money',
        'label' => '换出商品金额',
    ),
    'total_amount' =>
    array (
          'type' => 'money',
          'editable' => false,
          'label' => '合计总金额', //正数为需退金额，负数为需补金额
    ),
  ),
  'index' =>
  array (
    'ind_ship_mobile' =>
    array (
        'columns' =>
        array (
          0 => 'ship_mobile',
        ),
    ),
    'ind_aftersale_id' =>
    array (
        'columns' =>
        array (
          0 => 'aftersale_id',
        ),
    ),
    'ind_return_type' =>
    array (
        'columns' =>
        array (
          0 => 'return_type',
        ),
    ),
  ),
  'comment' => '门店H5移动端-退换货处理单据主表',
  'engine' => 'innodb',
  'version' => '$Rev: 41996 $',
);