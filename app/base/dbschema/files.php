<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */


$db['files']=array (
  'columns' => 
  array (
    'file_id' => array('type'=>'number','pkey'=>true,'extra' => 'auto_increment'),
    'file_path' => array('type'=>'text'),
    'file_type' =>array('type'=>array('private'=>'','public'=>''),'default'=>'public'),
    'last_change_time' => array('type'=>'last_modify'),
  ), 
  'comment' => 'storager文件存储信息',
  'version' => '$Rev: 41137 $',
);
