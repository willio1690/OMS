<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

$db['pick_stockout']=array (
    'columns' => array (
        'bill_id' =>
            array (
              'type' => 'number',
              'required' => true,
              'default' => 0,
              'label' => '拣货单编号',
              'editable' => false,
        ),
        'stockout_id' =>
            array (
                    'type' => 'number',
                    'required' => true,
                    'default' => 0,
                    'label' => '出库单编号',
                    'editable' => false,
            ),
    ),
    'index' => array (
            
    ),
    'comment' => '拣货出库单关联表',
    'engine' => 'innodb',
    'version' => '$Rev: $',
);