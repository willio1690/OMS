<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

$db['misc_task'] = array(
    'columns' => array(
        'id' => array(
            'type' => 'int unsigned',
            'required' => true,
            'pkey' => true,
            'editable' => false,
            'extra' => 'auto_increment',
        ),
        'obj_id' => array(
            'type' => 'int unsigned',
            'required' => true,
            'editable' => false,
            'comment' => '操作对象ID'
        ),
        'obj_type' => array(
            'type' => 'varchar(32)',
            'required' => true,
            'editable' => false,
            'comment' => '操作类型：timing_confirm_order->延时定时审单'
        ),
        'exec_time' => array(
            'type' => 'time',
            'required' => true,
            'editable' => false,
            'comment' => '执行时间'
        ),
        'extend_info' => array(
            'type' => 'text',
            'editable' => false,
            'label' => 'JSON扩展信息',
            'in_list' => false,
            'default_in_list' => false,
            'order' => 90,
        ),
        'create_time' => array(
            'type' => 'time',
            'editable' => false,
            'comment' => '创建时间'
        )
    ),
    'index' => array(
        'ind_exec_time' => array(
            'columns' => array('exec_time'),
        ),
        'ind_obj_type' => array(
            'columns' => array('obj_type', 'obj_id'),
            'prefix' => 'unique',
        ),
    ),
    'comment' => '定时触发表',
    'engine' => 'innodb',
    'version' => '$Rev: 44513 $',
);