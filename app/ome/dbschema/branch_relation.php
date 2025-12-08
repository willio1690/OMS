<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

$db['branch_relation']=array (
  'columns' =>
  array (
    'id' =>
    array (
      'type' => 'int unsigned',
      'required' => true,
      'pkey' => true,
      'extra' => 'auto_increment',
      'editable' => false,
    ),
    'branch_id' =>
    array (
      'type' => 'number',
      'required' => true,
      'editable' => false,
      'comment' => '仓库ID',
    ),
    'relation_branch_bn' =>
    array (
      'type' => 'varchar(32)',
      'editable' => false,
      'comment' => '映射仓库编号',
    ),
    'type' =>
    array (
      'type' => array(
          '3pl' => '天猫3pl订单',
          'wmscd' => '架海金梁订单',
          'vopczc' => '唯品会仓中仓订单',
          'vopjitx' => '唯品会jitx订单',
          'luban' => '抖音区域仓编码',
          'zkh'   => '震坤行区域仓编码',
          'jdlvmi' => '京东云仓',
      ),
      'editable' => false,
      'comment' => '类型',
    ),
  ),
  'index' => array (
          'ind_branch_id' => array(
                  'columns' => array(
                          0 => 'branch_id',
                  ),
          ),
          'ind_relation_type' => array(
                  'columns' => array(
                          0 => 'relation_branch_bn',
                          1 => 'type',
                  ),
          ),
  ),
  'comment' => '平台仓库编码配置表',
  'engine' => 'innodb',
  'version' => '$Rev: 41996 $',
);