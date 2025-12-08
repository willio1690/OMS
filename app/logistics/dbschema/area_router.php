<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

$db['area_router']=array (
    'columns' =>
    array (
        'area_id' =>
        array (
            'type' => 'int unsigned',
            'required' => true,
            'pkey' => true,
            'editable' => false,
        ),
        'area_name' =>
        array (
            'type' => 'varchar(50)',
            'required' => true,
            'default' => '',

            'label'=>'地区',
            'width'=>100,
            'default_in_list'=>true,
            'in_list'=>true,
            'editable' => false,
        ),
        'first_dc' =>
        array (
            'type' => 'bool',
            'label'=>'大仓(非门店)优先',
            'width'=>100,
            'default_in_list'=>false,
            'in_list'=>false,
            'editable' => false,
        ),
         'router_area' =>
        array (
            'type' => 'text',
            'label'=>'路由地区',
            'width'=>400,
            'default_in_list'=>true,
            'in_list'=>true,
        ),
        'disabled' =>
        array (
            'type' => 'bool',
            'default' => 'false',
            'editable' => false,
        ),
    ),
    'comment' => '区域路由表',
);
