<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * 福袋商品规则信息表
 * 20180314 by wangjianjun
 */

$db['luckybag_rules'] = array (
  'columns' => array(
    'lbr_id' => array(
        'type' => 'int unsigned',
        'required' => true,
        'pkey' => true,
        'extra' => 'auto_increment',
        'label' => '福袋规则主键ID',
        'hidden' => true,
        'editable' => false,
    ),
    'lbr_name' => array(
        'type' => 'varchar(200)',
        'required' => true,
        'label' => '福袋规则名称',
        'editable' => false,
    ),
    'sm_id' => array(
        'type' => 'int unsigned',
        'required' => true,
        'label' => '销售物料ID',
        'editable' => false,
    ),
    'bm_ids' => array(
        'type' => 'varchar(500)',
        'required' => true,
        'label' => '适用多个基础物料ID',
        'editable' => false,
    ),
    'sku_num' => array(
        'type' => 'number',
        'required' => true,
        'label' => 'sku数量',
        'editable' => false,
    ),
    'send_num' => array(
        'type' => 'number',
        'required' => true,
        'label' => '发货数量',
        'editable' => false,
    ),
    'price' => array(
        'type' => 'money',
        'required' => true,
        'label' => '单品价格',
        'editable' => false,
    ),
  ),
  'index' => array(
    'ind_sm_id' => array('columns' => array(0 => 'sm_id')),
    'ind_lbr_name' => array('columns' => array(0 => 'lbr_name')),
   ),
  'comment' => '福袋商品规则信息表',
  'engine' => 'innodb',
  'version' => '$Rev:  $',
);