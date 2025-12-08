<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

$db['order_taobao']=array(

    'columns' =>
        array(
            'otbid' => array(
                'type' => 'int(11)',
                'pkey' => true,
                'extra' => 'auto_increment',
                'required' => true,
                'label' => '主键ID',
            ),
            
            'order_id' => array(
                'type' => 'int(11)',
                'default' => '0',
                'required' => true,
                'label' => '订单ID',
            ),
            'apply_id' => array(
                'type' => 'varchar(32)',
                'label' => '发票申请ID',
            ),
            'platform_tid' => array(
                'type' => 'varchar(32)',
                'required' => true,
                'label' => '订单号',
            ),
            'sum_price' => array(
                'type' => 'money',
                'default' => '0',
                'required' => true,
                'label' => '不含税总金额',
            ),
            'sum_tax' => array(
                'type' => 'money',
                'default' => '0',
                'required' => true,
                'label' => '总税额',
            ),
            'invoice_amount' => array(
                'type' => 'money',
                'default' => '0',
                'required' => true,
                'label' => '开票金额',
            ),
            'payer_name' => array(
                'type' => 'varchar(150)',
                'label' => '发票抬头',
                'required' => true,
            ),
            'business_type' => array(
                'type' => 'tinyint(1)',
                'default' => '0',
                'label' => '抬头类型',
            ),
            'payer_register_no' => array(
                'type' => 'varchar(32)',
                'label' => '买家税号',
            ),
            'invoice_kind' => array(
                'type' => array(
                    0=>'电子发票',
                    1=>'纸质发票',
                    2=>'专票',
                ),
                'label' => '发票种类',
                'default' => '0',
            ),
            'invoice_type' => array(
                'type' => array(
                    'blue'=>'蓝票',
                    'red'=>'红票',
                ),
                'label' => '发票(开票)类型',
                'default' => 'blue',
            ),
            'trigger_status' => array(
                'type' => array(
                    'buyer_payed'=>'卖家已付款',
                    'sent_goods'=>'卖家已发货',
                    'buyer_confirm'=>'买家确认收货',
                    'refund_seller_confirm'=>'卖家同意退款',
                    'invoice_supply'=>'买家申请补开发票',
                    'invoice_change'=>'买家申请改抬头',
                ),
                'label' => '开票申请的触发类型',
                'default' => 'buyer_payed',
            ),
            'memo' => array (
                'type' => 'longtext',
                'label' => '备注',
            ),
    ),
    'index' => array(
        'ind_order_id'=>array(
            'columns'=>array(
                0=>'order_id',
            ),
        ),
        'ind_platform_tid'=>array(
            'columns'=>array(
                0=>'platform_tid',
            ),
        ),
    ),
    'comment' => '淘系原始开票信息',
    'engine' => 'innodb',
    'version' => '$Rev:  $',
);