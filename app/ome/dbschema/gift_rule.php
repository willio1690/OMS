<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * User: jintao
 * Date: 2016/3/18
 */
$db['gift_rule']=array (
    'columns' =>
        array (
            'id' => array(
                'type' => 'int unsigned',
                'required' => true,
                'pkey' => true,
                'editable' => false,
                'extra' => 'auto_increment',
                'order' => 10,
                'label' => '规则编号',
                'in_list' => true,
                'default_in_list' => true,
            ),
            'title' => array (
                'type' => 'varchar(100)',
                'required' => true,
                'editable' => false,
                'in_list' => true,
                'default_in_list' => true,
                'label' => '规则名称',
                'searchtype' => 'has',
                'filtertype' => 'normal',
                'order' => 20
            ),
            'is_exclude' => array(
                'type' => array(
                    '1' => '是',
                    '2' => '否',
                ),
                'default' => 1,
                'label' => '是否排他',
                'in_list' => true,
                'default_in_list' => true,
                'order' => 26,
            ),
            'gift_bn' => array (
                'type' => 'varchar(100)',
                'required' => false,
                'editable' => false,
                'in_list' => false,
                'default_in_list' => false,
                'label' => '商家编码',
                'order' => 30
            ),
            'gift_ids' => array (
                'type' => 'varchar(500)',
                'required' => false,
                'editable' => false,
                'in_list' => false,
                'default_in_list' => false,
                'label' => '赠品ID',
                'order' => 35
            ),
            'gift_num' => array (
                'type' => 'varchar(500)',
                'required' => false,
                'editable' => false,
                'in_list' => false,
                'default_in_list' => false,
                'label' => '赠品数量',
                'order' => 35
            ),
            'shop_id' => array (
                'type' => 'table:shop@ome',
                'required' => false,
                'editable' => false,
                'label' => '适用店铺',
                'filtertype' => 'normal',
                'filterdefault' => true,
                'in_list' => false,
                'default_in_list' => false,
                'width' => 150,
                'order' => 50
            ),
            'shop_ids' => array (
                'type' => 'varchar(500)',
                'required' => false,
                'editable' => false,
                'label' => '适用多店铺',
            ),
            'create_time' => array (
                'type' => 'time',
                'required' => true,
                'label' => '创建时间',
                'filtertype' => 'time',
                'filterdefault' => true,
                'in_list' => true,
                'default_in_list' => true,
                'width' => 150,
                'order' => 60
            ),
            'modified_time' => array (
                'type' => 'time',
                'required' => true,
                'label' => '修改时间',
                'filtertype' => 'time',
                'filterdefault' => true,
                'in_list' => true,
                'default_in_list' => true,
                'width' => 150,
                'order' => 65
            ),
            'time_type' => array (
                'type' => array(
                    'sendtime' => '订单处理时间',
                    'createtime' => '订单创建时间',
                    'pay_time' => '订单付款时间',
                    'other' => '其他',
                ),
                'required' => false,
                'editable' => true,
                'label' => '时间类型',
                'in_list' => true,
                'default_in_list' => true,
                'width' => 100,
                'order' => 67,
            ),
            'start_time' => array (
                'type' => 'time',
                'required' => false,
                'editable' => true,
                'label' => '开始时间',
                'filtertype' => 'time',
                'filterdefault' => true,
                'in_list' => true,
                'default_in_list' => true,
                'width' => 150,
                'order' => 70,
            ),
            'end_time' => array (
                'type' => 'time',
                'required' => false,
                'editable' => true,
                'label' => '结束时间',
                'filtertype' => 'time',
                'filterdefault' => true,
                'in_list' => true,
                'default_in_list' => true,
                'width' => 150,
                'order' => 80,
            ),
            'status' => array(
                'type' => array(
                    '0' => '关闭',
                    '1' => '开启',
                ),
                'default' => 1,
                'label'=>'规则状态',
                'in_list' => true,
                'default_in_list' => true,
                'width' => 70,
                'order' => 90,
            ),
            'disable' => array(
                'type' => array(
                    'true' => '是',
                    'false' => '否',
                ),
                'default' => 'false',
                'label'=>'删除状态',
                'in_list' => false,
                'default_in_list' => false,
                'width' => 70,
                'order' => 90,
            ),
            'filter_arr' => array(
                'type' => 'longtext',
                'default' => 1,
                'label'=>'促销条件',
                'default_in_list' => false,
                'in_list' => false,
                'order' => 150,
            ),
            'priority' => array(
                'type' => 'int(10)',
                'default' => 0,
                'label'=>'优先级',
                'default_in_list' => true,
                'in_list' => true,
                'width' => 60,
                'order' => 250,
            ),
            'trigger_type' => array (
                'type' => array(
                    'order_audit' => '订单审核',
                    'order_complete' => '订单完成',
                ),
                'default' => 'order_audit',
                'editable' => true,
                'label' => '触发节点',
                'in_list' => true,
                'default_in_list' => true,
                'order' => 28,
            ),
            'defer_day' => array(
                'type' => 'smallint(5)',
                'default' => 0,
                'label' => '延迟天数',
                'default_in_list' => true,
                'in_list' => true,
                'order' => 29,
            ),
        ),
    'index' => array(
        'ind_status' => array (
            'columns' => array (
                'status'
            ),
        ),
    ),
    'comment' => '赠品规则表',
    'engine' => 'innodb',
    'version' => '$Rev:  $',
);
