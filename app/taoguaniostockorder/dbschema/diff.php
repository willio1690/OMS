<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

$db['diff'] = array(
    'columns' => array(
        'diff_id'           => array(
            'type'     => 'int unsigned',
            'required' => true,
            'pkey'     => true,
            'editable' => false,
            'extra'    => 'auto_increment',
        ),
        'diff_bn'           => array(
            'type'            => 'varchar(32)',
            'required'        => true,
            'label'           => '差异单号',
            'is_title'        => true,
            'default_in_list' => true,
            'searchtype'      => 'has',
            'in_list'         => true,
            'width'           => 125,
            'filtertype'      => 'normal',
            'filterdefault'   => true,
        ),
        'original_bn'      => array(
            'type'            => 'varchar(32)',
            'label'           => '原始单号',
            'searchtype'      => 'has',
            'default_in_list' => true,
            'in_list'         => true,
            'comment'         => '来源调拨入库单号',
        ),
        'original_id'      => array(
            'type'    => 'int unsigned',
            'comment' => '来源调拨入库单id',
        ),
        'branch_id'      => array(
            'type'            => 'table:branch@ome',
            'required'        => false,
            'label'           => '原始单收货仓',
            // 'comment'         => '来源仓库',
            'filtertype'      => 'normal',
            'filterdefault'   => true,
            'default_in_list' => true,
            'in_list'         => true,
        ),
        'extrabranch_id'   => array(
            'type'            => 'table:branch@ome',
            'required'        => false,
            'label'           => '原始单发货仓',
            'filtertype'      => 'normal',
            'filterdefault'   => true,
            'default_in_list' => true,
            'in_list'         => true,
        ),
        'diff_status'       => array(
            'type'          => array(
                1 => '未处理',
                2 => '部分处理',
                3 => '全部处理',
                4 => '取消',
            ),
            'default'       => 1,
            'label'         => '处理状态',
            'width'         => 80,
            'editable'      => false,
            'filtertype'    => 'has',
            'filterdefault' => true,
            'in_list'         => true,
            'default_in_list' => true,
        ),
        'check_status'     => array(
            'type'            => array(
                1 => '未审',
                2 => '已审',
            
            ),
            'default'         => 1,
            'label'           => '审核状态',
            'width'           => 80,
            'editable'        => false,
            'in_list'         => true,
            'default_in_list' => true,
            'filterdefault'   => true,
        ),
        'packaging_status'     => array(
            'type'            => array(
                'intact' => '完好',
                'broken' => '有破损',
            ),
            'default'         => 'intact',
            'label'           => '外箱状态',
            'width'           => 80,
            'editable'        => false,
            'in_list'         => true,
            'default_in_list' => true,
        ),
        'operator'         => array(
            'type'            => 'varchar(30)',
            'comment'         => '操作人员',
            'default_in_list' => true,
            'in_list'         => true,
            'label'           => '操作人员',
            'width'           => 80,
        ),
        'create_time'      => array(
            'type'            => 'time',
            'label'           => '创建时间',
            'filtertype'      => 'time',
            'filterdefault'   => true,
            'default_in_list' => true,
            'in_list'         => true,
            'comment'         => '调拨入库单发回调时间',
        ),
//        'last_modified' => array (
//            'label' => '最后更新时间',
//            'type' => 'last_modify',
//            'width' => 130,
//            'editable' => false,
//            'in_list' => true,
//        ),
        'at_time'               => array(
            'type'    => 'TIMESTAMP',
            'label'   => '创建时间',
            'default' => 'CURRENT_TIMESTAMP',
            'width'   => 120,
            'in_list' => false,
        ),
        'up_time'               => array(
            'type'    => 'TIMESTAMP',
            'label'   => '更新时间',
            'default' => 'CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP',
            'width'   => 120,
            'in_list' => false,
        ),
    
    ),
    'index'   => array(
        'ind_diff_bn'           => array(
            'columns' => array(
                0 => 'diff_bn',
            ),
            'prefix' => 'unique'
        ),
        'ind_original_bn'      => array(
            'columns' => array(
                0 => 'original_bn',
            ),
        ),
        'ind_original_id'      => array(
            'columns' => array(
                0 => 'original_id',
            ),
        ),
        'ind_create_time'      => array(
            'columns' => array(
                0 => 'create_time',
            ),
        ),
        'ind_diff_status' => array(
            'columns' => array(
                0 => 'diff_status',
            ),
        ),
        'ind_at_time' => array(
            'columns' => array(
                0 => 'at_time',
            ),
        ),
        'ind_up_time' => array(
            'columns' => array(
                0 => 'up_time',
            ),
        ),
    ),
    'comment' => '调拨入库差异表',
    'engine'  => 'innodb',
    'version' => '$Rev:  51996',
);
