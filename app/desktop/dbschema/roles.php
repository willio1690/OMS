<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */


$db['roles']=array (
  'columns' => 
  array (
    'role_id' => 
    array (
      'type' => 'number',
      'required' => true,
      'pkey' => true,
      'width' => 50,
      'label' => app::get('desktop')->_('工作组id'),
      'hidden' => 1,
      'editable' => false,
      'extra' => 'auto_increment',
      'in_list' => true,
    ),
    'role_name' => 
    array (
      'type' => 'varchar(100)',
      'required' => true,
      'label' => app::get('desktop')->_('角色名'),
      'width' => 310,
      'in_list' => true,
      'is_title' => true,
      'searchtype'      => 'has',
      'default_in_list' => true,
    ),
    'workground' => 
    array (
      'label' => app::get('desktop')->_('操作权限'),
      'type' => 'text',
      'editable' => false,
      'in_list' => false,
      'hidden' => true,
    ),
    'data_authority' => 
    array (
      'label' => app::get('desktop')->_('数据权限'),
      'type' => 'text',
      'editable' => false,
      'in_list' => false,
      'hidden' => true,
    ),
  ),
  'comment' => '管理员角色表',
  'version' => '$Rev: 40654 $',
);
