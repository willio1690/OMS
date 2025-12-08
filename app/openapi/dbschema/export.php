<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

$db['export'] = array(
    'columns' => array(
        'id'           => array(
            'type'     => 'int unsigned',
            'required' => true,
            'pkey'     => true,
            'extra'    => 'auto_increment',
            'label'    => 'ID',
            'hidden'   => true,
            'editable' => false,
        ),
        'code'         => array(
            'type'            => 'varchar(64)',
            'required'        => true,
            'label'           => '编码',
            'searchtype'      => 'normal',
            'editable'        => false,
            'filtertype'      => 'yes',
            'filterdefault'   => true,
            'in_list'         => true,
            'default_in_list' => true,
            'order'           => 200,
        ),
        'name'         => array(
            'type'            => 'varchar(255)',
            'required'        => true,
            'label'           => '名称',
            'is_title'        => true,
            'searchtype'      => 'head',
            'editable'        => false,
            'filtertype'      => 'normal',
            'filterdefault'   => true,
            'in_list'         => true,
            'default_in_list' => true,
            'order'           => 250,
        ),
        'bill_time'    => array(
            'type'            => 'time',
            'label'           => '报表时间',
            'editable'        => false,
            'in_list'         => true,
            'default_in_list' => true,
            'order'           => 300,
            'filtertype'      => 'normal',
            'filterdefault'   => true,
        ),
        'property'     => array(
            'type'            => 'varchar(255)',
            'label'           => '类型',
            'editable'        => false,
            'in_list'         => true,
            'default_in_list' => true,
            'order'           => 350,
            'filtertype'      => 'normal',
            'filterdefault'   => true,
        ),
        'status'       => array(
            'type'            => 'varchar(255)',
            'label'           => '状态',
            'editable'        => false,
            'in_list'         => true,
            'default_in_list' => true,
            'order'           => 450,
            'filtertype'      => 'normal',
            'filterdefault'   => true,
        ),
        'download_url' => array(
            'type'            => 'varchar(255)',
            'label'           => '下载地址',
            'editable'        => false,
            'in_list'         => true,
            'default_in_list' => true,
            'order'           => 150,
        ),
        'start_time'   => array(
            'type'            => 'time',
            'label'           => '开始时间',
            'editable'        => false,
            'in_list'         => true,
            'default_in_list' => true,
            'order'           => 500,
            'filtertype'      => 'normal',
            // 'filterdefault'   => true,
        ),
        'end_time'     => array(
            'type'            => 'time',
            'label'           => '结束时间',
            'editable'        => false,
            'in_list'         => true,
            'default_in_list' => true,
            'order'           => 510,
            'filtertype'      => 'normal',
            // 'filterdefault'   => true,
        ),
        'create_time'  => array(
            'type'            => 'time',
            'label'           => '创建时间',
            'editable'        => false,
            'in_list'         => true,
            'default_in_list' => true,
            'order'           => 550,
            'filtertype'      => 'normal',
            // 'filterdefault'   => true,
        ),
        'last_modify'  => array(
            'type'            => 'last_modify',
            'label'           => '更新时间',
            'editable'        => false,
            'in_list'         => true,
            'default_in_list' => true,
            'order'           => 650,
        ),
        'disabled'     => array(
            'type'     => 'bool',
            'required' => true,
            'default'  => 'false',
            'editable' => false,
        ),
    ),
    'comment' => '导出任务配置表',
    'index'   => array(
        'uni_code'        => array(
            'columns' => array(
                0 => 'code',
            ),
            'prefix'  => 'UNIQUE',
        ),
        'ind_name'        => array(
            'columns' => array(
                0 => 'name',
            ),
        ),
        'ind_create_time' => array(
            'columns' => array(
                0 => 'create_time',
            ),
        ),
        'ind_bill_time' => array(
            'columns' => array(
                0 => 'bill_time',
            ),
        ),
        'ind_start_time' => array(
            'columns' => array(
                0 => 'start_time',
            ),
        ),
        'ind_end_time' => array(
            'columns' => array(
                0 => 'end_time',
            ),
        ),
        'ind_property' => array(
            'columns' => array(
                0 => 'property',
            ),
        ),
        'ind_status' => array(
            'columns' => array(
                0 => 'status',
            ),
        ),
        'ind_disabled' => array(
            'columns' => array(
                0 => 'disabled',
            ),
        ),
    ),
    'engine'  => 'innodb',
    'version' => '$Rev:  $',
);
