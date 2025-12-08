<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

$db['server'] = array(
    'columns' => array(
        'server_id' => array(
            'type'     => 'number',
            'required' => true,
            'pkey'     => true,
            'extra'    => 'auto_increment',
            'editable' => false,
        ),
        'server_bn' => array(
            'type'            => 'varchar(20)',
            'required'        => true,
            'label'           => '服务端编码',
            'in_list'         => true,
            'default_in_list' => true,
        ),
        'name'      => array(
            'type'            => 'varchar(255)',
            'required'        => true,
            'label'           => '服务端名称',
            'editable'        => false,
            'searchtype'      => 'has',
            'filtertype'      => 'normal',
            'filterdefault'   => true,
            'in_list'         => true,
            'default_in_list' => true,
            'is_title'        => true,
            'width'           => 200,
        ),
        'type'      => array(
            'required'        => false,
            'label'           => '类型',
            'type'            => 'varchar(10)',
            'default'         => 'webpos',
            'in_list'         => true,
            'default_in_list' => true,
        ),
        
        'node_id'   => array(
            'type'            => 'varchar(32)',
            'editable'        => false,
            'label'           => '节点ID',
            'editable'        => false,
            'width'           => '120',
            'in_list'         => true,
            'default_in_list' => true,
        ),
        'node_type' => array(
            'type'            => 'varchar(32)',
            'label'           => '节点类型',
            'editable'        => false,
            'in_list'         => true,
            'default_in_list' => true,
        ),
        'config'    => array(
            'type'  => 'longtext',
            'label' => '服务端配置参数',
        ),
    ),
    'index'   => array(
        'ind_server_bn' => array(
            'columns' => array(
                0 => 'server_bn',
            ),
            'prefix'  => 'unique',
        ),
        'ind_type'      => array(
            'columns' => array(
                0 => 'type',
            ),
        ),
        'ind_node_id' => array(
            'columns' => array(
                0 => 'node_id',
            ),
            'prefix'  => 'unique',
        ),
    ),
    'comment' => 'webpos服务端信息表',
    'engine'  => 'innodb',
);
