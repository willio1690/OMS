<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */


$db['users'] = array(
    'columns' => array(
        'user_id'     => array(
            'type'            => 'table:account@pam',
            'required'        => true,
//      'sdfpath' => 'pam_account/account_id',
            'pkey'            => true,
            'label'           => app::get('desktop')->_('用户名'),
            'width'           => 110,
            'editable'        => false,
            'hidden'          => true,
            'in_list'         => true,
            'default_in_list' => true,
        ),
        'status'      => array(
            'type'            => 'intbool',
            'default'         => '0',
            'label'           => app::get('desktop')->_('启用'),
            'width'           => 100,
            'required'        => true,
            'editable'        => true,
            'in_list'         => true,
            'default_in_list' => true,
        ),
        'name'        => array(
            'type'            => 'varchar(30)',
            'label'           => app::get('desktop')->_('姓名'),
            'width'           => 110,
            'editable'        => true,
            'in_list'         => true,
            'default_in_list' => true,
        ),
        'mobile'      => array(
            'type'            => 'varchar(30)',
            'width'           => 110,
            'label'           => app::get('desktop')->_('手机号码'),
            'in_list'         => true,
            'default_in_list' => true,
        ),
        'email'      => array(
            'type'            => 'varchar(64)',
            'width'           => 110,
            'label'           => app::get('desktop')->_('邮箱'),
            'in_list'         => true,
            'default_in_list' => true,
        ),
        'lastlogin'   => array(
            'type'            => 'time',
            'default'         => 0,
            'required'        => true,
            'label'           => app::get('desktop')->_('最后登陆时间'),
            'width'           => 110,
            'editable'        => false,
            'in_list'         => true,
            'default_in_list' => true,
        ),
        'config'      => array(
            'type'     => 'serialize',
            'editable' => false,
            'comment'  => '配置信息',
        ),
        'favorite'    => array(
            'type'     => 'longtext',
            'editable' => false,
            'comment'  => '收藏菜单',
        ),
        'super'       => array(
            'type'            => 'intbool',
            'default'         => '0',
            'required'        => true,
            'label'           => app::get('desktop')->_('超级管理员'),
            'width'           => 75,
            'editable'        => false,
            'in_list'         => true,
            'default_in_list' => true,
        ),
        'lastip'      => array(
            'type'     => 'varchar(20)',
            'editable' => false,
            'comment'  => '上次登录ip',
        ),
        'logincount'  => array(
            'type'     => 'number',
            'default'  => 0,
            'required' => true,
            'label'    => app::get('desktop')->_('登陆次数'),
            'width'    => 110,
            'editable' => false,
            //'in_list' => true,
        ),
        'disabled'    => array(
            'type'     => 'bool',
            'default'  => 'false',
            'required' => true,
            'editable' => false,
        ),
        'op_no'       => array(
            'type'            => 'varchar(50)',
            'label'           => app::get('desktop')->_('工号'),
            'width'           => 100,
            'editable'        => true,
            'in_list'         => true,
            'default_in_list' => true,
        ),
        'memo'        => array(
            'type'     => 'text',
            'label'    => app::get('desktop')->_('备注'),
            'width'    => 270,
            'editable' => false,
            'in_list'  => true,
        ),
        'modifyip'    => array(
            'type'    => 'ipaddr',
            'comment' => '修改IP',
        ),
        'create_time' => array(
            'type'    => 'time',
            'label'   => '新建时间',
            'width'   => 120,
            'in_list' => false,
            'order'   => 11,
        ),
        'last_modify' => array(
            'type'    => 'last_modify',
            'label'   => '最后更新时间',
            'width'   => 120,
            'in_list' => true,
            'order'   => 11,
        ),
        'is_lock'     => array(
            'type'    => 'intbool',
            'label'   => '是否锁定',
            'default' => '0',
            'width'   => 120,
            'in_list' => true,
            'order'   => 11,
        ),
        'lock_reason' => array(
            'type'    => 'varchar(255)',
            'label'   => '锁定原因',
            'default' => '',
            'width'   => 120,
            'in_list' => true,
            'order'   => 11,
        ),
        'session_id' => array(
            'type' => 'varchar(32)',
            'editable' => false,
            'label' => 'session_id',
            'in_list' => false,
            'order' => 99,
        ),
    ),
    'comment' => app::get('desktop')->_('商店后台管理员表'),
    'index'   => array(
        'ind_disabled' => array(
            'columns' => array(
                0 => 'disabled',
            ),
        ),
        'ind_last_modify' => array(
            'columns' => array(
                0 => 'last_modify',
            ),
        ),
        'ind_lastlogin' => array(
            'columns' => array(
                0 => 'lastlogin',
            ),
        ),
    ),
    'engine'  => 'innodb',
    'version' => '$Rev: 40912 $',
);
