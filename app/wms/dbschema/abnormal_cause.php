<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

$db['abnormal_cause'] = array( 
    'columns' => array(
        'ac_id' => array (
            'type' => 'number',
            'pkey' => true,
            'editable' => false,
            'extra' => 'auto_increment',
        ),
        'abnormal_cause' => array(
            'type' => 'varchar(300)',
            'editable' => false,
            'in_list' => true,
            'default_in_list' => true,
            'label' => '异常原因',
            'required' => true,
        ),
    ),
    'index' => array(),
    'comment' => '异常原因信息',
    'engine' => 'innodb',
    'version' => '$Rev: 44513 $',
);
