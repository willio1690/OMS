<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

$db['branch_ops'] = array(
    'columns' => array(
        'branch_id' => array(
            'type'     => 'table:branch@ome',
            'required' => true,
            'pkey'     => true,
            'editable' => false,
            'comment'  => '门店仓库ID',
        ),
        'op_id'     => array(
            'type'     => 'table:account@pam',
            'required' => true,
            'pkey'     => true,
            'editable' => false,
            'comment'  => '管理员ID',
        ),
        'org'       => array(
            'type'     => 'varchar(255)',
            'editable' => false,
            'comment'  => '组织名称',
        ),
    ),
    'comment' => '管理员门店仓库权限表',
    'engine'  => 'innodb',
    'version' => '$Rev:  $',
);
