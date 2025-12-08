<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

$db['basic_material_conf_special'] = array(
    'columns' => array(
        'bm_id' => array(
            'type' => 'int unsigned',
            'required' => true,
            'width' => 110,
            'hidden' => true,
            'editable' => false,
            'pkey' => true,
        ),
        'openscan' => array(
            'type' => 'varchar(20)',
            'label' => '是否开启特殊扫码配置',
            'editable' => false,
            'default'  => 'close',
        ),
        'fromposition' => array(
            'type' => 'number',
            'label' => '开始识别位数',
            'editable' => false,
            'default'  => 0,
        ),
        'toposition' => array(
            'type' => 'number',
            'label' => '结束识别位数',
            'editable' => false,
            'default'  => 1,
        ),
    ),
    'comment' => '基础物料特殊扫码配置表',
);