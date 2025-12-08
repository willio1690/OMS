<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

$db['branch_corp']=array (
  'columns' =>
  array (
      'id' =>
      array (
          'type' => 'number',
          'required' => true,
          'pkey' => true,
          'extra' => 'auto_increment',
          'editable' => false,
      ),
      'branch_id' =>
      array (
          'type' => 'table:branch@ome',
          'required' => true,
          'editable' => false,
      ),
      'corp_id' =>
      array (
          'type' => 'table:dly_corp@ome',
          'required' => true,
          'editable' => false,
      ),
  ),
  'index' =>
  array (
      'ind_branch_id_corp_id' =>
      array (
          'columns' =>
          array (
                  0 => 'branch_id',
                  1 => 'corp_id',
          ),
          'prefix' => 'unique',
      ),
  ),
  'comment' => '仓库物流关系表',
  'engine' => 'innodb',
  'version' => '$Rev: 51996',
);