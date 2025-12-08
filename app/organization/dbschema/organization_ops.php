<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

$db['organization_ops'] = array(
    'columns' => array(
        'op_id'   => array(
            'type'     => 'table:account@pam',
            'required' => true,
            'pkey'     => true,
            'editable' => false,
            'label'    => '操作员ID',
        ),
        'org_ids' => array(
            'type'            => 'varchar(255)',
            'label'           => '门店组织架构ID',
            'in_list'         => false,
            'default_in_list' => false,
            'filterdefault'   => false,
        ),
    ),
    'comment' => '门店组织架构管理员关联表',
    'engine'  => 'innodb',
    'version' => '$Rev:  $',
);