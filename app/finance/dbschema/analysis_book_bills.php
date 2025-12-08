<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

$db['analysis_book_bills']=array (
    'columns' => array(
        'book_bill_id' => array(
            'type'     => 'int unsigned',
            'required' => true,
            'pkey'     => true,
            'extra'    => 'auto_increment',
            'editable' => false,
        ),
        'bid' => array(
            'type' => 'varchar(32)',
            'label' => '费用流水编号',
            'in_list' => true,
            'default_in_list' => true,
            'searchtype' => 'nequal',
        ),
        'account_id' => array(
            'type' => 'varchar(32)',
            'label' => '虚拟账户科目编号',
        ),
        'journal_type' => array(
            'type' => 'mediumint',
            'label' => '流水类型',
            'in_list' => true,
            'default_in_list' => true,
        ),
        'amount' => array(
            'type' => 'money',
            'label' => '操作金额', 
            'in_list' => true,
            'default_in_list' => true,
        ),
        'book_time' => array(
            'type' => 'time',
            'label' => '记账时间',
            'in_list' => true,
            'default_in_list' => true,
        ),
        'description' => array(
            'type' => 'longtext',
            'label' => '备注',
        ),
        'gmt_create' => array(
            'type' => 'time',
            'label' => '创建时间',
            'in_list' => true,
            'default_in_list' => true,
        ),
        'taobao_alipay_id' => array(
            'type' => 'varchar(32)',
            'label' => '流水的淘宝支付宝id',
        ),
        'other_alipay_id' => array(
            'type' => 'varchar(32)',
            'label' => '流水的商家支付宝id',
        ),
        'shop_id' => array(
            'type' => 'table:shop@ome',
            'label' => '店铺名称',
            'default_in_list' => true,
            'in_list' => true,
            'filtertype' => true,
            'filterdefault' => true,
        ),
        'shop_type' => array(
          'type' => 'varchar(50)',
          'label' => '店铺类型',
          'width' => 75,
          'editable' => false,
          'in_list' => true,
          'filtertype' => 'normal',
          'filterdefault' => true,
        ),
        'fee_item_id' => array(
            'type' => 'table:bill_fee_item@finance',
            'label' => '科目名称',
            'default_in_list' => true,
            'in_list' => true,
            'filtertype' => true,
            'filterdefault' => true,
        ),
    ),
    'index' => array(
        'idx_bid_shopid' => array(
            'columns' => array('bid','shop_id'),
            'prefix' => 'unique',
        ),
        'idx_book_time' => array(
            'columns' => array('book_time'),
        ),
        'idx_gmt_create' => array(
            'columns' => array('gmt_create'),
        ),
        
    ),
    'comment' => '运营费用表',
    'engine' => 'innodb',
    'version' => '$Rev:  $',
);