<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * User: wangjianjun
 * Date: 2017/1/17
 */
$db['gift_set_logs']=array (
    'columns' => array (
        'id' => array(
            'type' => 'int unsigned',
            'required' => true,
            'pkey' => true,
            'editable' => false,
            'extra' => 'auto_increment',
        ),
        'op_user' => array(
            'type' => 'varchar(50)',
            'label'=>'操作人',
            'in_list' => true,
            'default_in_list' => true,
            'searchtype' => 'has',
            'filtertype' => 'normal',
            'filterdefault' => 'true',
            'order' => 20,
        ),
        'set_gift_taobao' =>
        array(
            'type' => array(
                    'on' => '启用',
                    'off' => '关闭',
            ),
            'label'=>'启用淘宝赠品',
            'in_list' => true,
            'default_in_list' => true,
            'order' => 30,
        ),
        'set_gift_erp' =>
        array(
            'type' => array(
                    'on' => '启用',
                    'off' => '关闭',
            ),
            'label'=>'启用本地赠品',
            'in_list' => true,
            'default_in_list' => true,
            'order' =>40,
        ),
        'create_time' => array(
            'type' => 'time',
            'label'=>'操作时间',
            'in_list' => true,
            'default_in_list' => true,
            'order' => 10,
        ),
    ),
    'index' => array (
        'ind_op_user' => array (
            'columns' => array (
                'op_user',
            ),
        ),
    ),
    'comment' => '赠品设置日志表',
    'engine' => 'innodb',
    'version' => '$Rev:  $',
);