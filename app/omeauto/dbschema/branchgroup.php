<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

$db['branchgroup'] = array(
    'columns' =>
        array(
            'bg_id' =>
                array(
                    'type' => 'int',
                    'required' => true,
                    'pkey' => true,
                    'extra' => 'auto_increment',
                    'editable' => false,
                ),
            'name' =>
                array(
                    'type' => 'varchar(200)',
                    'required' => true,
                    'editable' => false,
                    'in_list' => true,
                    'default_in_list' => true,
                    'width' => 130,
                    'label' => '分组名称',
                    'order' => 10
                ),
            'branch_group' =>
                array(
                    'type' => 'varchar(255)',
                    'default' => 0,
                    'editable' => false,
                    'width' => 200,
                    'label' => '仓库',
                    'order' => 30
                ),
            'createtime' =>
                array(
                    'type' => 'time',
                    'label' => '创建时间',
                    'width' => 130,
                    'editable' => false,
                    'in_list' => true,
                    'default_in_list' => false,
                    'order' => 40
                ),
            'last_modified' =>
                array(
                    'label' => '最后修改时间',
                    'type' => 'last_modify',
                    'width' => 130,
                    'editable' => false,
                    'in_list' => true,
                    'default_in_list' => true,
                    'order' => 50
                ),
        ),
    'engine' => 'innodb',
    'version' => '$Rev:  $',
);