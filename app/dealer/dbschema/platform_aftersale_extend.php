<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

$db['platform_aftersale_extend'] = array(
    'columns' => array(
        'plat_extend_id' => array(
            'type' => 'int unsigned',
            'required' => true,
            'pkey' => true,
            'extra' => 'auto_increment',
            'label' => 'ID',
        ),
        
        'plat_aftersale_id'=> array(
            'type'     => 'int unsigned',
            'required' => true,
            'label'    => '平台售后单号id',
        ),
        'json_data'=>array(
            'type'     => 'longtext',
            'editable' => false,
            'comment' => 'JSON格式保存',
        ),
        'add_time'=>array( 
            'type' => 'time',
            'label' => '新增时间',
            'in_list' => true,
            'default_in_list' => true,
            'order' => 25,
        ),
        'at_time' => array(
            'type' => 'time',
            'label' => '创建时间',
            'in_list' => true,
            'default_in_list' => true,
             'order' => 26,
        ),
        'up_time' => array(
            'type' => 'time',
            'label' => '更新时间',
            'in_list' => true,
            'default_in_list' => true,
             'order' => 27,
        ),
    ),
    'index' => array (
        'ind_plat_aftersale_id' => array(
            'columns' => array(
                'plat_aftersale_id',
            ),
        ),
        'ind_at_time' => array(
            'columns' => array(
                'at_time',
            ),
        ),
        'ind_up_time' => array(
            'columns' => array(
                'up_time',
            ),
        ),
    ),
    'comment' => 'aftersales',
    'engine' => 'innodb',
    'version' => '$Rev:  $',
);