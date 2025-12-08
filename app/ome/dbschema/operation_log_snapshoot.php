<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

$db['operation_log_snapshoot'] = array(
    'columns' => array(
        'id'        => array(
            'type'     => 'int unsigned',
            'pkey'     => true,
            'extra'    => 'auto_increment',
            'editable' => false,
        ),
        'log_id'    => array(
            'type'     => 'int unsigned',
            'editable' => false,
        ),
        'snapshoot' => array(
            'type'     => 'text',
            'label'    => '快照内容',
            'editable' => false,
        ),
        'updated' => array(
            'type'     => 'text',
            'label'    => '更新后数据',
            'editable' => false,
        ),
        'at_time'   => array(
            'type'    => 'TIMESTAMP',
            'label'   => '创建时间',
            'default' => 'CURRENT_TIMESTAMP',
        ),
        'up_time'   => array(
            'type'    => 'TIMESTAMP',
            'label'   => '更新时间',
            'default' => 'CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP',
        ),
    ),
    'index'   => array(
        'ind_log_id' => array(
            'columns' => array(
                0 => 'log_id',
            ),
        ),
    ),
    'comment' => '操作员记录快照扩展表',
    'engine'  => 'innodb',
    'version' => '$Rev:  $',
);
