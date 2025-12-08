<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

$db['branch_flow'] = array(
    'columns' => array(
        'id'        => array(
            'type'     => 'number',
            'required' => true,
            'pkey'     => true,
            'extra'    => 'auto_increment',
            'editable' => false,
        ),
        'flow_type' => array(
            'type'            => [
                'purchasein' => '采购入库',
                'damagedin'  => '残次入库',
                
            ],
            'label'           => '类型',
            'in_list'         => true,
            'default_in_list' => true,
        ),
        'content'   => array(
            'type'            => 'longtext',
            'required'        => true,
            'in_list'         => true,
            'default_in_list' => true,
            'label'           => '流向',
            'width'         => '240',
        ),
    ),

    'index'   => array(
        'idx_flow_type' => array(
            'columns' => array(
                'flow_type',
            ),
            'prefix'  => 'unique',
        ),
    ),
    'comment' => '货物流转',
    'engine'  => 'innodb',
    'version' => '$Rev: 51996',
);
