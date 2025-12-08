<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

$db['warehouse']=array (
    'columns' => array (
            'branch_id' =>
            array (
                    'type' => 'number',
                    'required' => true,
                    'pkey' => true,
                    'extra' => 'auto_increment',
                    'editable' => false,
            ),
            'branch_bn' =>
            array (
                    'type' => 'varchar(32)',
                    'required' => true,
                    'in_list' => true,
                    'default_in_list' => true,
                    'label' => '仓库编号',
                    'order' => 5,
            ),
            'branch_name' =>
            array (
                    'type' => 'varchar(80)',
                    'required' => true,
                    'editable' => false,
                    'is_title' => true,
                    'searchtype' => 'has',
                    'filtertype' => 'normal',
                    'filterdefault' => true,
                    'in_list' => true,
                    'default_in_list' => true,
                    'width' => 130,
                    'label' => '仓库名',
                    'order' => 6,
            ),
            'uname' =>
            array (
                    'type' => 'varchar(100)',
                    'editable' => false,
                    'label' => '联系人姓名',
                    'in_list' => true,
                    'default_in_list' => true,
                    'order' => 10,
            ),
            'mobile' =>
            array (
                    'type' => 'varchar(100)',
                    'editable' => false,
                    'label' => '手机',
                    'in_list' => true,
                    'default_in_list' => true,
                    'order' => 11,
            ),
            'phone' =>
            array (
                    'type' => 'varchar(100)',
                    'editable' => false,
                    'label' => '电话',
                    'in_list' => true,
                    'default_in_list' => true,
                    'order' => 12,
            ),
            'email' =>
            array (
                    'type' => 'varchar(50)',
                    'editable' => false,
                    'label' => 'Email',
                    'in_list' => true,
                    'order' => 13,
            ),
            'zip' =>
            array (
                    'type' => 'varchar(20)',
                    'editable' => false,
                    'label' => '邮编',
                    'in_list' => true,
                    'order' => 14,
            ),
            'area' =>
            array (
                    'type' => 'varchar(255)',
                    'editable' => false,
                    'order' => 15,
            ),
            'address' =>
            array (
                    'type' => 'varchar(200)',
                    'editable' => false,
                    'label' => '地址',
                    'in_list' => true,
                    'order' => 16,
            ),
            'warehouse_type' =>
            array (
                    'type' => [
                        '0' =>  '存储仓库',
                        '1' =>  '店仓',
                        '2' =>  '省仓',
                        '3' =>  '退供收货仓',
                    ],
                    'editable' => false,
                    'label' => '仓库类型',
                    'default_in_list' => true,
                    'in_list' => true,
                    'filtertype' => 'normal',
                    'filterdefault' => true,
                    'order' => 17,
            ),
            'status' => 
            array(
                    'type'            => array(
                        '0'     => '无效',
                        '1'     => '有效',
                    ),
                    'label'     => '状态',
                    'filtertype' => 'normal',
                    'filterdefault' => true,
                    'default_in_list' => true,
                    'in_list'   => true,
                    'order'     => 18,
            ),
            'cooperation_no' =>
            array (
                    'type' => 'varchar(255)',
                    'editable' => false,
                    'label' => '常态合作编码',
                    'in_list' => true,
                    'order' => 19,
            ),
            // 'wcontent'     => array(
            //     'type'    => 'longtext',
            //     'comment' => '仓库配置原数据',
            // ),
            'ccontent'     => array(
                'type'    => 'longtext',
                'comment' => '合作编码原数据',
            ),
    ),
    'index' => array (
            'ind_branch_bn' =>
            array (
                    'columns' =>
                    array (
                            0 => 'branch_bn',
                    ),
                    'prefix' => 'unique',
            ),
    ),
    'comment' => '唯品会仓库表',
    'engine' => 'innodb',
    'version' => '$Rev: $',
);