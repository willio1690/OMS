<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

$db['analysis_bills']=array (
    'columns' => array(
        'bill_id' => array(
            'type'     => 'int unsigned',
            'required' => true,
            'pkey'     => true,
            'extra'    => 'auto_increment',
            'editable' => false,
        ),
        'bid' => array(
            'type' => 'varchar(32)',
            'label' => '账单编号',
        ),
        'account_id' => array(
            'type' => 'varchar(32)',
            'label' => '科目编号',
        ),
        'tid' => array(
            'type' => 'varchar(32)',
            'label' => '交易订单编号',
            'default_in_list' => true,
            'in_list' => true,
            'order' => 10,
            'searchtype' => 'nequal',
        ),
        'oid' => array(
            'type' => 'varchar(32)',
            'label' => '交易子订单编号',
            'default_in_list' => true,
            'in_list' => true,
            'order' => 20,
            'searchtype' => 'nequal',
        ),
        'total_amount' => array(
            'type' => 'money',
            'label' => '订单交易金额',
            'default_in_list' => true,
            'in_list' => true,
            'order' => 50,
        ),
        'amount' => array(
            'type' => 'money',
            'label' => '费用账单金额',
            'default_in_list' => true,
            'in_list' => true,
            'order' => 60,
        ),
        'book_time' => array(
            'type' => 'time',
            'label' => '费用记账时间',
            'default_in_list' => true,
            'in_list' => true,
            'order' => 90,
        ),
        'biz_time' => array(
            'type' => 'time',
            'label' => '订单交易完成的时间',
            'in_list' => true,
            'order' => 40,
        ),
        'pay_time' => array(
            'type' => 'time',
            'label' => '费用支付时间',
            'in_list' => true,
            'order' => 70
        ),
        'alipay_mail' => array(
            'type' => 'varchar(255)',
            'label' => '支付宝账户名称',
            //'default_in_list' => true,
            //'in_list' => true,
        ),
        'obj_alipay_mail' => array(
            'type' => 'varchar(255)',
            'label' => '目标支付宝账户名称',
            'default_in_list' => true,
            'in_list' => true,
            'order' => 80,
        ),
        'obj_alipay_id' => array(
            'type' => 'varchar(255)',
            'label' => '目标支付宝账户编号',
            //'in_list' => true,
        ),
        'alipay_outno' => array(
            'type' => 'varchar(100)',
            'label' => '支付宝商户订单号',
            //'default_in_list' => true,
            //'in_list' => true,
        ),
        'alipay_notice' => array(
            'type' => 'longtext',
            'label' => '支付宝备注',
        ),
        'status' => array(
            'type' => array(
                '0' => '未支付',
                '1' => '支付成功',
                '2' => '支付失败',
            ),
            'label' => '状态',
            'default' => '0',
            'default_in_list' => true,
            'in_list' => true,
            'order' => 100,
        ),
        'gmt_create' => array(
            'type' => 'time',
            'label' => '创建时间',
            //'in_list' => true,
        ),
        'gmt_modified' => array(
            'type' => 'time',
            'label' => '修改时间',
            //'in_list' => true,
        ),
        'num_iid' => array(
            'type' => 'varchar(50)',
            'label' => '所属商品编号',
        ),
        'alipay_id' => array(
            'type' => 'varchar(50)',
            'label' => '支付宝账户编号',
        ),
        'alipay_no' => array(
            'type' => 'varchar(50)',
            'label' => '支付宝交易号',
        ),
        'shop_id' => array(
            'type' => 'table:shop@ome',
            'label' => '店铺名称',
            'default_in_list' => true,
            'in_list' => true,
            'filtertype' => true,
            'filterdefault' => true,
            'order' => 110,
        ),
        'shop_type' => array(
          'type' => 'varchar(50)',
          'label' => '店铺类型',
          'width' => 75,
          'editable' => false,
          //'in_list' => true,
          //'filtertype' => 'normal',
          //'filterdefault' => true,
        ),
        'fee_item_id' => array(
            'type' => 'table:bill_fee_item@finance',
            'label' => '科目名称',
            'default_in_list' => true,
            'in_list' => true,
            'filtertype' => true,
            'filterdefault' => true,
            'order' => 1,
        ),
        'finance_type' => array(
            'type' => array(
                '1' => '收入',
                '2' => '支付',
            ),
            'label' => '流水类型',
            'default' => '2',
        ),
    ),
    'index' => array(
        'idx_bid_shopid' => array(
            'columns' => array('bid','shop_id'),
            'prefix' => 'unique',
        ),
        'idx_gmt_create' => array(
            'columns' => array('gmt_create'),
        ),
        'idx_book_time' => array(
            'columns' => array('book_time'),
        ),
        'idx_tid' => array(
            'columns' => array('tid'),
        ),
        'idx_oid' => array(
            'columns' => array('oid'),
        ),
        'idx_biz_time' => array(
            'columns' => array('biz_time'),
        ),
        
    ),
    'comment' => '交易费用表',
    'engine' => 'innodb',
    'version' => '$Rev:  $',
);