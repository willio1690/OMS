<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

$db['carrier']=array (
    'columns' => array (
            'cid' =>
            array (
                    'type' => 'number',
                    'required' => true,
                    'pkey' => true,
                    'extra' => 'auto_increment',
                    'editable' => false,
            ),
            'carrier_code' =>
            array (
                    'type' => 'varchar(80)',
                    'required' => true,
                    'in_list' => true,
                    'default_in_list' => true,
                    'searchtype' => 'nequal',
                    'filterdefault' => true,
                    'filtertype' => 'yes',
                    'label' => '承运商编号',
                    'order' => 5,
            ),
            'carrier_name' =>
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
                    'label' => '承运商名称',
                    'order' => 6,
            ),
            'shop_id' =>
            array (
                    'type' => 'table:shop@ome',
                    'label' => '来源店铺',
                    'editable' => false,
                    'required' => true,
                    'in_list' => true,
                    'default_in_list' => true,
                    'filtertype' => 'normal',
                    'filterdefault' => true,
                    'order' => 7,
            ),
            'tms_carrier_id' =>
            array (
                    'type' => 'varchar(30)',
                    'label' => 'tms端承运商ID',
                    'editable' => false,
                    'required' => false,
            ),
            'carrier_shortname' =>
            array (
                    'type' => 'varchar(80)',
                    'required' => false,
                    'editable' => false,
                    'in_list' => false,
                    'default_in_list' => false,
                    'width' => 130,
                    'label' => '承运商简称',
            ),
            'carrier_isvalid' =>
            array (
                    'type' => 'number',
                    'label' => '是否启用',
                    'editable' => false,
                    'required' => false,
                    'in_list' => false,
                    'default_in_list' => false,
                    'default' => 0,
            ),
    ),
    'index' => array (
            
    ),
    'comment' => '承运商管理表',
    'engine' => 'innodb',
    'version' => '$Rev: $',
);