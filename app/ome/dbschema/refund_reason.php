<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

$db['refund_reason']=array (
    'columns' =>
        array (
            'id' =>
                array (
                    'type' => 'int unsigned',
                    'required' => true,
                    'pkey' => true,
                    'editable' => false,
                    'extra' => 'auto_increment',
                ),
            'reason' =>
                array (
                    'type' => 'varchar(255)',
                    'default' => '',
                    'label' => '退款原因',
                    'editable' => false,
                    'order' => 20,
                    'is_title' => true,
                    'in_list' => true,
                    'default_in_list' => true,
                ),
            'create_time' =>
                array (
                    'type' => 'time',
                    'label' => '创建时间',
                    'editable' => false,
                    'order' => 30,
                    'in_list' => true,
                    'default_in_list' => true,
                ),
            'last_modify' =>
                array (
                    'type' => 'last_modify',
                    'label' => '修改时间',
                    'editable' => false,
                    'order' => 40,
                    'in_list' => true,
                    'default_in_list' => true,
                ),
        ),
    'comment' => '退款原因',
    'index' =>
        array (
        ),
    'engine' => 'innodb',
    'version' => '$Rev: 41103 $',
    'commit' => '退款原因表'
);