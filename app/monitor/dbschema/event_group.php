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

$db['event_group'] = array(
    'columns' => array(
        'group_id'    => array(
            'type'     => 'int unsigned',
            'required' => true,
            'pkey'     => true,
            'extra'    => 'auto_increment',
            'label'    => 'ID',
            'width'    => 110,
            'hidden'   => true,
            'editable' => false,
        ),
        'group_name'  => array(
            'type'            => 'varchar(50)',
            'label'           => '邮件组名称',
            'editable'        => false,
            'in_list'         => true,
            'default_in_list' => true,
            'is_title'        => true,
            'order'           => 5,
        ),
        'receiver_id' => array(
            'type'            => 'text',
            'required'        => true,
            'label'           => '收件人',
            'editable'        => false,
            'in_list'         => true,
            'default_in_list' => true,
            'order'           => 10,
        ),
        'org_id'      => array(
            'type'            => 'varchar(200)',
            'label'           => '运营组织',
            'editable'        => false,
            'width'           => 150,
            'filtertype'      => 'normal',
            'filterdefault'   => true,
            'in_list'         => true,
            'default_in_list' => true,
        ),
        'disabled'    => array(
            'type'            => 'bool',
            'default'         => 'false',
            'label'           => '启用状态',
            'in_list'         => true,
            'default_in_list' => true,
            'editable'        => false,
        ),
        'at_time'     => array(
            'type'            => 'TIMESTAMP',
            'label'           => '创建时间',
            'comment'         => '创建时间',
            'default'         => 'CURRENT_TIMESTAMP',
            'in_list'         => true,
            'default_in_list' => true,
            'order'           => 60,
        ),
        'up_time'     => array(
            'type'            => 'TIMESTAMP',
            'label'           => '修改时间',
            'comment'         => '修改时间',
            'default'         => 'CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP',
            'in_list'         => true,
            'default_in_list' => true,
            'order'           => 70,
        ),
    
    ),
    'comment' => '监控邮件组关联事件信息',
    'index'   => array(),
    'engine'  => 'innodb',
    'version' => '$Rev:  $',
);
