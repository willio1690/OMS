<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */


$db['account'] = array(
    'columns'=>array(
        'account_id'=>array('type'=>'number','pkey'=>true,'extra' => 'auto_increment',),
        'account_type'=>array('type'=>'varchar(30)','comment'=>'账户类型'),
        'login_name'=>array(
            'type'=>'varchar(100)',
            'is_title'=>true,
            'required' => true,
            'in_list'=>true,
            'default_in_list'=>true,
            'label'=>'用户名',
        ),
        'login_password'=>array('type'=>'varchar(64)','required' => true,'comment'=>'登录密码'),
        'times'=>array('type'=>'tinyint','required' => false,'comment'=>'失败次数'),
        'login_time'=>array('type'=>'time','required' => false,'comment'=>'登录时间'),
        'disabled'=>array('type'=>'bool','default'=>'false'),
        'createtime'=>array('type'=>'time','comment'=>'创建时间'),
        'is_hash256' => array(
            'type' => [
                '0' => '否',
                '1' => '是',
            ],
            'label' => '是否hash加密',
            'default' => '1',
            'in_list' => true,
            'default_in_list' => true,
        ),
    ),
  'index' => array (
    'account' => array ('columns' => array ('account_type','login_name'),'prefix' => 'UNIQUE'),
  ),
  'engine' => 'innodb',
  'comment' => '授权用户表',
);
