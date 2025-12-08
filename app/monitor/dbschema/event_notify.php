<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * @Author: xueding@shopex.cn
 * @Vsersion: 2022/10/13
 * @Describe: 预警通知数据结构
 */

$db['event_notify'] = array(
    'columns' => array(
        'notify_id'        => array(
            'type'     => 'int unsigned',
            'required' => true,
            'pkey'     => true,
            'extra'    => 'auto_increment',
            'label'    => 'ID',
            'width'    => 110,
            'hidden'   => true,
            'editable' => false,
        ),
        'template_id'      => array(
            'type'     => 'table:event_template@monitor',
            'required' => true,
            'label'    => '模板',
            'comment'  => '模板',
            'editable' => false,
        ),
        'event_type'       => array(
            'type'            => 'varchar(50)',
            'label'           => '模板类型',
            'comment'         => '模板类型',
            'width'           => 120,
            'editable'        => false,
            'in_list'         => true,
            'default_in_list' => true,
            'required'        => true,
            'order'           => 10,
        ),
        'original_content' => array(
            'type'     => 'text',
            'label'    => '模板原内容',
            'comment'  => '模板原内容',
            'editable' => false,
        ),
        'send_content' => array(
            'type'            => 'longtext',
            'label'           => '发送内容',
            'comment'         => '发送内容',
            'in_list'         => false,
            'default_in_list' => false,
            'editable'        => false,
            'order'           => 20,
            'searchtype'      => 'nequal',
            'filtertype'      => 'normal',
            'filterdefault'   => true,
        ),
        'params'           => array(
            'type'    => 'longtext',
            'label'   => '参数',
            'comment' => '参数',
        ),
        'file_path'        => array(
            'type'    => 'text',
            'label'   => '附件地址',
            'comment' => '附件地址',
        ),
        'send_type'        => array(
            'type'            => array(
                'sms'   => '短信',
                'email' => '邮箱',
                'workwx'=>'企微',
            ),
            'default'         => 'email',
            'label'           => '发送类型',
            'comment'         => '发送类型',
            'editable'        => false,
            'in_list'         => true,
            'default_in_list' => true,
            'filtertype'      => 'normal',
            'filterdefault'   => true,
            'order'           => 30,
        ),
        'status'           => array(
            'type'            => array(
                '0' => '未处理',
                '1' => '已处理',
                '2' => '处理失败',
                '3' => '处理中',
            ),
            'default'         => '0',
            'label'           => '发送状态',
            'comment'         => '发送状态',
            'editable'        => false,
            'in_list'         => true,
            'default_in_list' => true,
            'filtertype'      => 'normal',
            'filterdefault'   => true,
            'order'           => 40,
        ),
        'is_sync'          => array(
            'type'            => 'bool',
            'default'         => 'false',
            'label'           => '发送类型',
            'comment'         => '发送类型',
            'editable'        => false,
            'in_list'         => false,
            'default_in_list' => false,
            'order'           => 50,
        ),
        'send_result'     => array(
            'type'            => 'longtext',
            'label'           => '发送结果',
            'comment'         => '发送结果',
            'in_list'         => true,
            'default_in_list' => true,
            'order'           => 45,
        ),
        'mailing_address' => array(
            'type'     => 'text',
            'label'    => '收信地址',
            'comment'  => '收信地址',
            'in_list'         => true,
            'default_in_list' => true,
            'editable' => false,
        ),
        'org_id'             => array(
            'type'            => 'table:operation_organization@ome',
            'label'           => '运营组织',
            'editable'        => false,
            'width'           => 60,
            'filtertype'      => 'normal',
            'filterdefault'   => true,
            'in_list'         => true,
            'default_in_list' => true,
        ),
        'at_time'          => array(
            'type'            => 'TIMESTAMP',
            'label'           => '创建时间',
            'comment'         => '创建时间',
            'default'         => 'CURRENT_TIMESTAMP',
            'in_list'         => true,
            'default_in_list' => true,
            'order'           => 60,
        ),
        'up_time'          => array(
            'type'            => 'TIMESTAMP',
            'label'           => '修改时间',
            'comment'         => '修改时间',
            'default'         => 'CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP',
            'in_list'         => true,
            'default_in_list' => true,
            'order'           => 70,
        ),
    
    ),
    'comment' => '预警通知',
    'index'   => array(
        'ind_status'     => array('columns' => array('status',)),
        'ind_event_type' => array('columns' => array('event_type',)),
    ),
    'engine'  => 'innodb',
    'version' => '$Rev:  $',
);
