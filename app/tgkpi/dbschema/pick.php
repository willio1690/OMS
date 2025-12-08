<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

$db['pick']=array (
    'columns'=>
    array(
        'pick_id' =>
            array (
                'type' => 'number',
                'required' => true,
                'editable' => false,
                'pkey' => true,
                'label' => 'ID',
                'extra' => 'auto_increment',
            ),
        'product_id'=>
            array(
                'type'=>'table:products@ome',
                'required'=> true,
                'default_in_list' => false,
                'in_list' => false,
                'label' => '商品ID',
            ),
        'product_bn'=>
            array(
                'type'=>'varchar(32)',
                'required'=> false,
                'default_in_list' => true,
                'in_list' => true,
                'filtertype' => 'yes',
                'filterdefault' => true,
                'label' => '货号',
                'order' => 30,
            ),
        'product_pick_level' =>
            array (
                'type' => 'float(4,2)',
                'required'=> false,
                'default_in_list' => false,
                'in_list' => false,
                'label' => '捡货难度',
            ),
        'pick_num' =>
            array (
                'type' => 'int(8)',
                'required'=> true,
                'default_in_list' => true,
                'in_list' => true,
                'label' => '货品数量',
                'order' => 20,
                'width' => 80,
            ),
        'pick_owner' =>
            array (
                'type' => 'varchar(32)',
                'required'=> true,
                'default_in_list' => true,
                'in_list' => true,
                'label' => '工号',
                'order' => 10,
            ),
        'pick_start_time' =>
            array (
                'type' => 'time',
                'required'=> true,
                'default_in_list' => true,
                'in_list' => true,
                'filterdefault' => true,
                'filtertype' => 'has',
                'label' => '拣货开始时间',
                'order' => 50,
            ),
    	'check_start_time' =>
            array (
                'type' => 'time',
                'default_in_list' => true,
                'in_list' => true,
                'filterdefault' => true,
                'filtertype' => 'has',
                'label' => '校验开始时间',
                'order' => 50,
            ),
        'check_op_id' =>
            array (
                'type' => 'table:account@pam',
                'editable' => false,
                'default_in_list' => false,
                'in_list' => false,
            ),
    	'check_op_name' =>
            array (
                'type' => 'varchar(30)',
                'editable' => false,
                'default_in_list' => true,
                'in_list' => true,
                'label' => '校验员',
            ),
        'op_name' =>
        array (
                'type' => 'varchar(30)',
                'editable' => false,
                'default_in_list' => true,
                'in_list' => true,
                'label' => '最终发货人',
        ),
        'pick_end_time' =>
            array (
                'type' => 'time',
                'required'=> false,
                'default_in_list' => true,
                'in_list' => true,
    			'filterdefault' => true,
                'filtertype' => 'has',
                'label' => '校验完成时间',
                'order' => 60,
            ),
        'pick_error_num' =>
            array (
                'type' => 'mediumint(8)',
                'required'=> true,
                'default_in_list' => true,
                'in_list' => true,
                'label' => '拣错次数',
                'width' => 80,
            ),
        'pick_status' =>
            array (
                'type' => array(
                    'running'=>'捡货中',
                    'checking' =>'校验中',
                    'finish'=>'校验完成',
                    'deliveryed'=>'发货完成',
                    'error'=>'校验错误',
                    'cancel'=>'已取消',
                ),
                'required'=> true,
                'default_in_list' => true,
                'in_list' => true,
                'label' => '状态',
                'order' => 70,
                'width' => 100,
            ),
        'delivery_id' =>
            array (
                'type' => 'table:delivery@wms',
                'required'=> true,
                'default_in_list' => true,
                'in_list' => true,
                'label' => '发货单编号',
                'order' => 40,
            ),
        'print_ident' =>
            array(
                'type' => 'varchar(64)',
                'required' => true,
                'editable' => false,
                'label' => '打印批次号',
                'comment' => '发货单打印的批次号',
            ),
        'print_ident_dly' =>
            array(
                'type' => 'varchar(64)',
                'required' => true,
                'editable' => false,
                'label' => '批次号序列',
            ),
        'delivery_sku_num' =>
            array (
                'type' => 'mediumint(8)',
                'required'=> true,
                'default_in_list' => true,
                'in_list' => true,
                'label' => 'SKU数',
                'order' => 80,
                'width' => 80,
            ),
        'branch_id' =>
            array (
                'type' => 'table:branch@ome',
                'required'=> false,
                'default_in_list' => false,
                'in_list' => true,
                'label' => '仓库ID',
            ),
        'pos_id' =>
            array (
                'type' => 'table:branch_pos@ome',
                'required'=> false,
                'default_in_list' => false,
                'in_list' => true,
                'label' => '货位ID',
            ),
        'branch_pos_position' =>
            array (
                'type' => 'varchar(20)',
                'required'=> false,
                'default_in_list' => false,
                'in_list' => true,
                'label' => '货位位置',
            ),
        'cost_time' =>
            array (
                'type' => 'number',
                'required'=> false,
                'default_in_list' => false,
                'in_list' => true,
                'label' => '拣货耗时',
            ),
    	'check_cost_time' =>
            array (
                'type' => 'number',
                'required'=> false,
                'default_in_list' => false,
                'in_list' => true,
                'label' => '校验耗时',
            ),
    ),
    'index' =>
      array (
        'idx_pick_owner' =>
        array (
            'columns' =>
            array (
              0 => 'pick_owner',
            ),
        ),
        'idx_delivery_id' =>
        array (
            'columns' =>
            array (
              0 => 'delivery_id',
            ),
        ),
        'idx_op_name' => array(
            'columns' => array('op_name')
        ),
        'idx_pick_start_time' => array('columns'=>array('pick_start_time')),
    ),
    'comment' => '拣货绩效',
    'engine' => 'innodb',
    'version' => '$Rev:121321',
);
