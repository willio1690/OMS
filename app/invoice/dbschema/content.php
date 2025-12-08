<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

$db['content']=array(
    'columns' =>
        array(
            'content_id' => array(
                'type' => 'int unsigned',
                'required' => true,
                'pkey' => true,
                'editable' => false,
                'label' => '发票内容ID',
                'extra' => 'auto_increment',
            ),
            'content_name' => array(
                'type' => 'varchar(100)',
                'label' => '发票内容名称',
                'in_list' => true,
                'default_in_list' => true,
                'width' => 120,
            ),
    ),
    'index' => array(
        'idx_content_name' => array(
            'columns'=>array(
                    0=>'content_name',
            ),
        ),
    ),
    'comment' => '发票内容表',
    'engine' => 'innodb',
    'version' => '$Rev:  $',
);