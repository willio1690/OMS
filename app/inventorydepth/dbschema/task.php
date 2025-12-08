<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */


$db['task'] = array(
    'comment' => '活动任务信息',
    'columns' => array(
        'task_id' => array(
            'type'     => 'mediumint(8) unsigned',
            'required' => true,
            'pkey'     => true,
            'extra'    => 'auto_increment',
            'label'    => 'ID',
            'comment'  => ''
        ),
        
        'task_name' => array(
            'type'            => 'varchar(255)',
            'required'        => true,
            'label'           => app::get('inventorydepth')->_('活动名称'),
            'in_list'         => true,
            'default_in_list' => true,
            'comment'         => ''
        ),
        
        'operator' => array(
            'type'     => 'table:account@pam',
            'required' => false,
            'label'    => app::get('inventorydepth')->_('操作人'),
            'in_list'  => true,
            'comment'  => '',
        ),
        'update_time' => array(
            'type'     => 'last_modify',
            'required' => false,
            'label'    => app::get('inventorydepth')->_('最后更新时间'),
            'in_list'  => true,
            'comment'  => ''
        ),
        'operator_ip' => array(
            'type'     => 'ipaddr',
            'required' => false,
            'label'    => app::get('inventorydepth')->_('操作人IP'),
            'comment'  => ''
        ),
        'shop_id' =>
        array (
          'type' => 'table:shop@ome',
          'label' => '来源店铺',
          'width' => 75,
          'editable' => false,
          'in_list' => true,
          'filtertype' => 'normal',
          'filterdefault' => true,
        ),
        'start_time' => array(
            'type'            => 'time',
            'required'        => false,
            'label'           => app::get('inventorydepth')->_('开始时间'),
            'in_list'         => true,
            'default_in_list' => true,
            'filterdefault'   => true,
            'filtertype'      => 'normal',
            'comment'         => ''
        ),
        'end_time' => array(
            'type'            => 'time',
            'required'        => false,
            'label'           => app::get('inventorydepth')->_('结束时间'),
            'in_list'         => true,
            'default_in_list' => true,
            'filterdefault'   => true,
            'filtertype'      => 'normal',
            'comment'         => ''
        ),
        'disabled' => array(
            'type' => array(
                'true' => '开启',
                'false' => '关闭',
               
            ),
            'in_list'  => true,
            'default' => 'true',
            'label' => '启用状态',      
        ),
    ),
    
    'engine' => 'innodb',
    'version' => '$Rev: $'
);
