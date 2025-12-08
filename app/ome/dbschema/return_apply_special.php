<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

$db['return_apply_special']=array (
    'columns' =>
        array (
            'id' => 
            array (
              'type' => 'int unsigned',
              'required' => true,
              'pkey' => true,
              'extra' => 'auto_increment',
              'editable' => false,
            ),
            'apply_id' => array (
                'type' => 'int unsigned',
                'editable' => false,
            ),
            'refund_apply_bn' => array (
                'type' => 'varchar(32)',
                'default' => '',
                'label' => '退款申请单号',
            ),
            'return_id' => 
            array(
              'type' => 'int unsigned',
              'editable' => false,
              'comment' => '售后ID',
            ),
            'return_bn' =>
            array (
              'type' => 'varchar(32)',
              'default' => '',
              'label' => '退货记录流水号',
              'comment' => '退货记录流水号',
              'editable' => false,
            ),
            'org_oid' =>
            array (
              'type' => 'varchar(32)',
              'default' => '',
              'label' => '原始子订单号',
              'comment' => '原始子订单号',
              'editable' => false,
            ),
            'org_order_bn' =>
            array (
              'type' => 'varchar(32)',
              'default' => '',
              'label' => '原始订单号',
              'comment' => '原始订单号',
              'editable' => false,
            ),
            'special' => array (
                'type' => 'text',
                'label' => '平台特殊相关',
            ),
            'reship_bn' =>
                array (
                    'type' => 'varchar(32)',
                    'default' => '',
                    'label' => '退货单号',
                    'comment' => '退货单号',
                    'editable' => false,
                ),
        ),
    'index' =>
        array (
            'ind_apply_id' =>
                array (
                    'columns' => array ('apply_id'),
                ),
            'ind_return_id' =>
                array (
                    'columns' =>
                        array (
                            0 => 'return_id',
                        ),
                ),
            'ind_org_order_bn' =>
                array (
                    'columns' =>
                        array (
                            0 => 'org_order_bn',
                        ),
                ),
            'ind_org_oid' =>
                array (
                    'columns' =>
                        array (
                            0 => 'org_oid',
                        ),
            ),
            'ind_reship_bn' =>
                array (
                    'columns' =>
                        array (
                            0 => 'reship_bn',
                        ),
                ),
        ),
    'comment' => '退款单特殊相关',
    'engine' => 'innodb',
    'version' => '$Rev:  $',
    'charset' => 'utf8mb4',
);