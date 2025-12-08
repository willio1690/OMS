<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

$db['cos_ops'] = array(
    'columns' => array(
        'op_id'   => array(
            'type'     => 'table:account@pam',
            'required' => true,
            'pkey'     => true,
            'editable' => false,
        ),
        'cos_ids' => array(
            'type'            => 'varchar(255)',
            'label'           => '企业组织架构ID',
            'in_list'         => false,
            'default_in_list' => false,
            'filterdefault'   => false,
        ),
    ),
    'comment' => app::get('ome')->_('企业组织管理员'),
    'engine'  => 'innodb',
    'version' => '$Rev:  $',
);
