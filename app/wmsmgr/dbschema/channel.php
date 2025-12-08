<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

$db['channel'] = array(
    'columns' => array(
        'id'           => array(
            'type'     => 'int unsigned',
            'required' => true,
            'pkey'     => true,
            'label'    => 'ID',
            'editable' => false,
            'extra'    => 'auto_increment',
            'in_list'  => true,
            'default_in_list' => true,
            'order' => 2,
        ),
        'channel_id'   => array(
            'type'     => 'varchar(32)',
            'required' => true,
            'editable' => false,
            'label'    => '渠道ID',
            'in_list'  => true,
            'width'    => 140,
            'default_in_list' => true,
            'order' => 20,
        ),
        'channel_name' => array(
            'type'     => 'varchar(32)',
            'required' => true,
            'editable' => false,
            'label'    => '渠道名称',
            'in_list'  => true,
            'width'    => 200,
            'default_in_list' => true,
            'order' => 22,
        ),
        'node_type'    => array(
            'type'     => 'varchar(32)',
            'required' => true,
            'editable' => false,
            'default'  => 'yjdf',
            'label'    => '渠道类型',
            'in_list' => true,
            'default_in_list' => true,
            'width'    => 140,
            'order' => 24,
        ),
        'createtime'   => array(
            'type'       => 'time',
            'label'      => '创建时间',
            'default'    => 0,
            'in_list'    => true,
            'filtertype' => 'time',
            'default_in_list' => true,
            'order' => 99,
        ),
        'op_id'        => array(
            'type'       => 'table:account@pam',
            'label'      => '操作员',
            'width'      => 110,
            'editable'   => false,
            'filtertype' => 'normal',
            'in_list'    => false,
            'order' => 92,
        ),
        'op_name'      => array(
            'type'     => 'varchar(30)',
            'editable' => false,
            'label'    => '操作人',
            'in_list'  => true,
            'default_in_list' => true,
            'order' => 90,
        ),
        'wms_id'       => array(
            'type'     => 'int(10)',
            'label'    => 'wms_id',
            'required' => true,
            'in_list' => true,
            'default_in_list' => true,
            'order' => 30,
        )
    ),
    'index'   => array(
        'index_channel_id' => array(
            'columns' => array(
                0 => 'channel_id',
            ),
        ),
    ),
    'comment' => '渠道管理列表',
    'engine'  => 'innodb',
    'version' => '$Rev: 41996 $',
);