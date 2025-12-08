<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * @Author: xueding@shopex.cn
 * @Vsersion: 2022/10/13
 * @Describe: 通知事件模板数据结构
 */

$db['event_template'] = array(
    'columns' => array(
        'template_id'   => array(
            'type'     => 'int unsigned',
            'required' => true,
            'pkey'     => true,
            'extra'    => 'auto_increment',
            'label'    => 'ID',
            'width'    => 110,
            'hidden'   => true,
            'editable' => false,
        ),
        'template_bn'   => array(
            'type'            => 'varchar(50)',
            'label'           => '模板编码',
            'comment'         => '模板编码',
            'editable'        => false,
            'in_list'         => true,
            'default_in_list' => true,
            'order'           => 10,
        ),
        'template_name' => array(
            'type'            => 'varchar(50)',
            'label'           => '模板名称',
            'comment'         => '模板名称',
            'editable'        => false,
            'in_list'         => true,
            'default_in_list' => true,
            'is_title'        => true,
            'order'           => 20,
        ),
        'event_type'    => array(
            'type'            => 'varchar(50)',
            'label'           => '模板类型',
            'comment'         => '模板类型',
            'width'           => 120,
            'editable'        => false,
            'in_list'         => true,
            'default_in_list' => true,
            'required'        => true,
            'order'           => 30,
        ),
        'send_type'     => array(
            'type'            => array(
//                'sms'    => '短信',
                'email'  => '邮箱',
                'workwx' => '企微',
            ),
            'default'         => 'email',
            'label'           => '发送类型',
            'comment'         => '发送类型',
            'editable'        => false,
            'in_list'         => true,
            'default_in_list' => true,
            'order'           => 40,
        ),
        'content'       => array(
            'type'     => 'longtext',
            'label'    => '模板内容',
            'comment'  => '模板内容',
            'editable' => false,
        ),
        'status'        => array(
            'type'            => array(
                '0' => '未审核',
                '1' => '已审核',
                '2' => '审核失败',
                '3' => '已取消',
            ),
            'default'         => '1',
            'label'           => '模板状态',
            'comment'         => '模板状态',
            'editable'        => false,
            'in_list'         => true,
            'default_in_list' => true,
            'order'           => 50,
        ),
        'source'        => array(
            'type'    => 'varchar(10)',
            'label'   => '来源渠道',
            'comment' => '来源渠道',
            'default' => 'local',
        ),
        'disabled'           => array(
            'type'     => 'bool',
            'default'  => 'false',
            'label'   => '启用状态',
            'in_list'         => true,
            'default_in_list' => true,
            'editable' => false,
        ),
        'at_time'       => array(
            'type'            => 'TIMESTAMP',
            'label'           => '创建时间',
            'comment'         => '创建时间',
            'default'         => 'CURRENT_TIMESTAMP',
            'in_list'         => true,
            'default_in_list' => true,
            'order'           => 60,
        ),
        'up_time'       => array(
            'type'            => 'TIMESTAMP',
            'label'           => '修改时间',
            'comment'         => '修改时间',
            'default'         => 'CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP',
            'in_list'         => true,
            'default_in_list' => true,
            'order'           => 70,
        ),
    
    ),
    'comment' => '监控事件模板',
    'index'   => array(
        'uni_template_bn' => array('columns' => array('template_bn',), 'prefix' => 'UNIQUE',),
    ),
    'engine'  => 'innodb',
    'version' => '$Rev:  $',
);
