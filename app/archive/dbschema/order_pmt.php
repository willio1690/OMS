<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

$db['order_pmt'] = array(
    'columns' => array(
        'id' => array(
            'type' => 'int unsigned',
            'required' => true,
            'pkey' => true,
            'extra' => 'auto_increment',
            'editable' => false,
        ),
        'order_id' => array(
            'type' => 'int unsigned',
            'required' => true,
            'editable' => false,
            'label' => '订单ID',
        ),
        'pmt_amount' => array(
            'type' => 'money',
            'editable' => false,
            'label' => '促销金额',
        ),
        'pmt_memo' => array(
            'type' => 'longtext',
            'editable' => false,
            'label' => '促销备注',
        ),
        'pmt_describe' => array(
            'type' => 'longtext',
            'editable' => false,
            'label' => '促销描述',
        ),
        'coupon_id' => array(
            'type' => 'varchar(32)',
            'label' => '优惠券ID',
            'in_list' => false,
            'default_in_list' => false,
        ),
        'up_time' => array(
            'type' => 'TIMESTAMP',
            'label' => '更新时间',
            'default' => 'CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP',
            'width' => 130,
            'in_list' => true,
        ),
        'archive_time' => array(
            'type' => 'int unsigned',
            'label' => '归档时间',
            'width' => 130,
            'editable' => false,
            'in_list' => true,
            'filtertype' => 'time',
            'filterdefault' => true,
        ),
    ),
    'index' => array(
        'ind_up_time' => array(
            'columns' => array(
                0 => 'up_time',
            ),
        ),
        'ind_order_id' => array(
            'columns' => array(
                0 => 'order_id',
            ),
        ),
        'ind_archive_time' => array(
            'columns' => array(
                0 => 'archive_time',
            ),
        ),
    ),
    'comment' => '归档订单促销规则',
    'engine' => 'innodb',
    'version' => '$Rev:  $',
); 