<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

$db['replenish_suggest'] = array(
    'columns' => array(
        
        'sug_id' => array(
            'type' => 'int unsigned',
            'required' => true,
            'pkey' => true,
            'extra' => 'auto_increment',
            'label' => 'ID',
            'width' => 110,
            'hidden' => true,
            'editable' => false,
        ),

        'task_bn' => array(
            'type' => 'varchar(50)',
            'label' => '补货任务单号',
            'required' => true,
            'default_in_list' => true,
            'in_list' => true,
            'searchtype' => 'head',
            'filtertype' => 'yes',
            'filterdefault' => true,
            'order' => 20,
        ),
        'physics_id'=>array(
            'type'            => 'table:store@o2o',
            'label'           => '门店编码',
            'in_list'         => true,
            'default_in_list' => true,
        ),
        'store_bn' => array(
            'type' => 'varchar(20)',
            'required' => true,
            'label' => '仓库编码',
            'editable' => false,
            'searchtype' => 'nequal',
            'filtertype' => 'normal',
            'filterdefault' => true,
            'width' => 120,
            'order' => 20,
        ),
        'branch_id' => array(
            'type' => 'table:branch@ome',
            'required' => true,
            'label' => '调入仓库',
            'filtertype' => 'normal',
            'filterdefault'   => true,
            'default_in_list' => true,
            'in_list' => true,
            'order' => 30,
        ),
        'out_branch_id' => array(
            'type' => 'table:branch@ome',
           
            'label' => '调出仓库',
            'filtertype' => 'normal',
            'filterdefault'   => true,
            'default_in_list' => true,
            'in_list' => true,
            'order' => 30,
        ),
        'out_branch_bn' => array(
            'type' => 'varchar(20)',
            'label' => '调出仓库',
            'order' => 30,
        ),
        'source'  => array(
            'type'     => 'varchar(50)',
            'default'  => 'local',
            'editable' => false,
            'label' => '来源',
            'in_list' => true,
            'default_in_list' => true,
        ),
        'bill_type'=>array(

            'type'  =>  'varchar(20)',
            'default'=>'normal',
            'label'=>'单据类型',
            'in_list' => true,
            'default_in_list' => true,
        ),
         'sug_status' => array(
            'type' => array(
                '0' => '未确认',
                '1' => '已确认',
                '2' => '已完成',
                '3' => '已作废',
            ),
            'default' => '0',
            'label' => '状态',
            'width' => 90,
            'editable' => false,
            'in_list' => true,
            'default_in_list' => true,
            'filtertype'    => 'has',
            'filterdefault' => true,
            'order' => 21,
        ),
        'product_amount'        => array(
            'type'     => 'money',
            'label'    => '货品合计',
            'required' => true,
            'default'  => 0,
            'in_list'  => true,
        ),
       
        'create_time' => array(
            'type' => 'time',
            'label' => '创建时间',
            'in_list' => true,
            'default' => 0,
            'in_list' => true,
            'default_in_list' => true,
            'order' => 98,
        ),
        'last_modified' => array(
            'label' => '最后更新时间',
            'type' => 'last_modify',
            'width' => 130,
            'editable' => false,
            'in_list'  => true,
            'default_in_list' => true,
            'order' => 99,
        ),
    ),
    'index' => array(
        
        'in_store_bn' => array(
            'columns' => array('store_bn'),
        ),
        
    ),
    'engine'  => 'innodb',
    'version' => '$Rev: $',
    'comment' => '补货建议单据表',
);
