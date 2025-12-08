<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

$db['business_branch'] = array(
    'columns' => array(
        'bs_id' => array(
            'type' => 'mediumint(8)',
            'required' => true,
            'label' => '经销商ID',
        ),
        'branch_id' => array(
            'type' => 'number',
            'required' => true,
            'label' => '仓库ID',
        ),
    ),
    'index' => array(
        'index_bs_id' => array('columns' => array('bs_id')),
        'index_branch_id' => array('columns' => array('branch_id'),'prefix'=>'unique'),
    ),
    'comment' => '经销商仓库',
    'engine' => 'innodb',
    'version' => '$Rev:  $',
);