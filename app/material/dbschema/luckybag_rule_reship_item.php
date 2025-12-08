<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * 售后换出福袋货品的规则关系表
 * 20180409 by wangjianjun
 */

$db['luckybag_rule_reship_item'] = array (
  'columns' => array(
    'reship_item_id' => array(
        'type' => 'number',
        'required' => true,
        'label' => '换货明细ID',
        'editable' => false,
    ),
    'lbr_id' => array(
        'type' => 'int unsigned',
        'required' => true,
        'label' => '福袋规则ID',
        'editable' => false,
    ),
  ),
  'index' => array(
    'ind_reship_item_id' => array('columns' => array(0 => 'reship_item_id')),
   ),
  'comment' => '售后换出福袋货品的规则关系表',
  'engine' => 'innodb',
  'version' => '$Rev:  $',
);