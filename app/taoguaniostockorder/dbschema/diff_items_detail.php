<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

$db['diff_items_detail'] = array(
    'columns' => array(
        'items_detail_id'  => array(
            'type'     => 'int unsigned',
            'required' => true,
            'pkey'     => true,
            'extra'    => 'auto_increment',
        ),
        'diff_items_id'        => array(
            'type'     => 'table:diff_items@taoguaniostockorder',
            'required' => true,
            'default'  => 0,
            'editable' => false,
        ),
        'diff_id'        => array(
            'type'     => 'table:diff@taoguaniostockorder',
            'required' => true,
            'default'  => 0,
            'editable' => false,
        ),
        'diff_reason'         =>array(
            'type'            => array(
                'less'   =>  '短发',
                'lost'   =>  '丢失',
                'wrong'  =>  '错发',
                'more'   =>  '超发',
            ),
            'label'           => '差异原因',
            'in_list'         => true,
            'default_in_list' => true,
        ),
        'diff_memo'           => array(
            'type'            => 'varchar(200)',
            'label'           => '备注',
            'width'           => 160,
            'editable'        => false,
            'in_list'         => true,
            'default_in_list' => true,
        ),
        'responsible'       => array(
            'type'          => array(
                1 => 'N/A',
                2 => '发货方',
                3 => '收货方',
                4 => '第三方物流',
            ),
            'default'       => 1,
            'label'         => '责任方',
            'width'         => 60,
            'editable'      => false,
        ),
        'diff_status'       => array(
            'type'          => array(
                1 => '未处理',
                2 => '处理中',
                3 => '已处理',
                4 => '取消',
            ),
            'default'       => 1,
            'label'         => '处理状态',
            'width'         => 60,
            'editable'      => false,
            'filtertype'    => 'has',
            'filterdefault' => true,
        ),
        'operator'         => array(
            'type'            => 'varchar(30)',
            'comment'         => '操作人员',
            'default_in_list' => true,
            'in_list'         => true,
            'label'           => '操作人员',
        ),
        'product_id'    => array(
            'type'     => 'table:products@ome',
            'required' => true,
            'comment'         => '基础主档ID',
        ),
        'product_name'  => array(
            'type'            => 'varchar(200)',
            'editable'        => false,
            'in_list'         => true,
            'default_in_list' => true,
            'label'           => '基础主档名',
        ),
        'bn'            => array(
            'type'            => 'varchar(30)',
            'editable'        => false,
            'in_list'         => true,
            'default_in_list' => true,
            'label'           => '基础主档编码',
        ),
        'unit'          => array(
            'type'            => 'varchar(20)',
            'editable'        => false,
            'in_list'         => true,
            'default_in_list' => true,
            'label'           => '单位',
        ),
        'price'         => array(
            'type'            => 'decimal(20,3)',
            'label'           => '价格',
            'required'        => true,
            'editable'        => false,
            'in_list'         => true,
            'default_in_list' => true,
        ),
        'price_retail'              => array(
            'type'            => 'decimal(20,3)',
            'label'           => '零售价格',
            'comment'         => '零售价格',
            'width'           => 65,
            'editable'        => false,
            'in_list'         => true,
            'default_in_list' => true,
        ),
        'nums'          => array(
            'type'            => 'number',
            'editable'        => false,
            'in_list'         => true,
            'default_in_list' => true,
            'label'           => '数量',
        ),
        'up_time'                   => array(
            'type'    => 'TIMESTAMP',
            'label'   => '更新时间',
            'default' => 'CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP',
            'width'   => 120,
            'in_list' => false,
            'order'   => 11,
        ),
        'at_time'                   => array(
            'type'            => 'TIMESTAMP',
            'label'           => '创建时间',
            'default'         => 'CURRENT_TIMESTAMP',
            'width'           => 120,
            'in_list'         => false,
            'default_in_list' => false,
            'order'           => 11,
        ),
    ),
    'index'   => array(
        
        'ind_bn'         => array(
            'columns' => array(
                0 => 'bn',
            ),
        ),
        'ind_diff_reason'         => array(
            'columns' => array(
                0 => 'diff_reason',
            ),
        ),
    ),
    'comment' => '差异单明细责任判定表',
    'engine'  => 'innodb',
    'version' => '$Rev:  $',
);
