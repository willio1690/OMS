<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

$db['material_freeze_queue'] = array(
    'columns' => array(
        'id'          => array(
            'type'     => 'bigint unsigned',
            'required' => true,
            'pkey'     => true,
            'extra'    => 'auto_increment',
            'editable' => false,
            'label'    => 'ID',
        ),
        'branch_id'   => array(
            'type'            => 'int(10)',
            'required'        => true,
            'editable'        => false,
            'label'           => '仓库编码',
            'in_list'         => true,
            'default_in_list' => false,
            'order'           => 10,
        ),
        'bm_id'       => array(
            'type'            => 'int(10)',
            'required'        => true,
            'editable'        => false,
            'label'           => '基础物料编码',
            'in_list'         => true,
            'default_in_list' => true,
            'order'           => 20,
        ),
        'quantity'    => array(
            'type'            => 'int(10)',
            'default'         => '0',
            'required'        => true,
            'label'           => '冻结数量',
            'in_list'         => true,
            'default_in_list' => true,
            'order'           => 30,
        ),
        'status'      => array(
            'type'            => array(
                '0' => '未处理',
                '1' => '处理中',
                '2' => '处理完成', // 完成的会被删掉
                '3' => '处理失败',
            ),
            'default'         => '0',
            'required'        => true,
            'label'           => '处理状态',
            'in_list'         => true,
            'default_in_list' => true,
            'order'           => 40,
        ),
        'obj_type'    => array(
            'type'            => 'tinyint(1)',
            'comment'         => '对象类型', // 1 订单预占 2 仓库预占
            'label'           => '对象类型',
            'editable'        => false,
            'in_list'         => true,
            'default_in_list' => true,
            'order'           => 50,
        ),
        'bill_type'   => array(
            'type'            => 'tinyint(1)',
            'label'           => '业务类型',
            'comment'         => '业务类型', // material_lib_basic_material_stock_freeze
            'editable'        => false,
            'default'         => 0,
            'in_list'         => true,
            'default_in_list' => true,
            'order'           => 60,
        ),
        'obj_id'      => array(
            'type'            => 'int unsigned',
            'comment'         => '对象ID',
            'label'           => '对象ID',
            'default'         => 0,
            'editable'        => false,
            'in_list'         => true,
            'default_in_list' => true,
            'order'           => 70,
        ),
        'obj_bn'      => array(
            'type'            => 'varchar(255)',
            'label'           => '对象单号',
            'default_in_list' => true,
            'in_list'         => true,
            'order'           => 80,
        ),
        'obj_item_id' => array(
            'type'            => 'int unsigned',
            'comment'         => '对象明细ID',
            'label'           => '对象明细ID',
            'default'         => 0,
            'editable'        => false,
            'in_list'         => true,
            'default_in_list' => false,
            'order'           => 90,
        ),
        'source'      => array(
            'type'            => 'varchar(255)',
            'label'           => '调用来源方法',
            'default_in_list' => true,
            'in_list'         => true,
            'order'           => 100,
        ),
        'pid'         => array(
            'type'            => 'varchar(20)',
            'label'           => '当前执行脚本的进程ID',
            'default'         => '0',
            'default_in_list' => true,
            'in_list'         => true,
            'order'           => 110,
        ),
        'at_time'     => array(
            'type'            => 'TIMESTAMP',
            'label'           => '创建时间',
            'default'         => 'CURRENT_TIMESTAMP',
            'width'           => 120,
            'in_list'         => true,
            'default_in_list' => true,
            'order'           => 120,
        ),
        'up_time'     => array(
            'type'            => 'TIMESTAMP',
            'label'           => '更新时间',
            'default'         => 'CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP',
            'width'           => 120,
            'in_list'         => true,
            'default_in_list' => true,
            'order'           => 130,
        ),
    ),

    'index'   => array(
        'idx_bm_id'      => array(
            'columns' => array(
                'bm_id',
            ),
        ),
        'idx_status_pid' => array(
            'columns' => array(
                'status',
                'pid',
            ),
        ),
        'idx_at_time'    => array(
            'columns' => array(
                'at_time',
            ),
        ),
        'idx_up_time'    => array(
            'columns' => array(
                'up_time',
            ),
        ),
    ),
    'comment' => '物料总冻结流水队列',
    'engine'  => 'innodb',
    'version' => '$Rev: 1',
);
