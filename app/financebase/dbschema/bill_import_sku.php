<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

$db['bill_import_sku'] = array(
    'comment' => '商品sku明细',
    'columns' => array(
        'id'          => array(
            'type'     => 'int unsigned',
            'required' => true,
            'pkey'     => true,
            'extra'    => 'auto_increment',
            'editable' => false,
        ),
        'summary_id'        => array(
            'type'            => 'int',
            'comment'         => '汇总id',
            'default_in_list' => true,
        ),
        'import_id'        => array(
            'type'            => 'int',
            'comment'         => '导入id',
            'default_in_list' => true,
        ),

        'confirm_status'   => array(
            'type'            => array(
                '0' => '未确认',
                '1' => '已确认',
                '2' => '已取消',
            ),
            'label'           => '确认状态',
            'comment'           => '确认状态,0:未确认1:已确认',
            'default'         => '0',
            'editable'        => false,
            'default_in_list' => true,
            'in_list'         => true,
            'filterdefault'   => true,
            'filtertype'      => 'normal',
            'order' => 6,
        ),
        'split_status'   => array(
            'type'            => array(
                '0' => '未处理',
                '1' => '已拆分',
                '2' => '已红冲',
            ),
            'label'           => '拆分状态',
            'default'         => '0',
            'editable'        => false,
            'default_in_list' => true,
            'in_list'         => true,
            'order' => 6,
        ),

        'pay_serial_number'        => array(
            'type'            => 'varchar(255)',
            'label'           => '支付流水号',
            'comment'           => '支付流水号',
            'editable'        => false,
            'filtertype'      => 'normal',
            'filterdefault'   => true,
            'in_list'         => true,
            'default_in_list' => true,
            'order' => 2,
        ),

        'increment_service_sn'        => array(
            'type'            => 'varchar(255)',
            'label'           => '增值服务单号',
            'comment'           => '增值服务单号 sku分组条件',
            'editable'        => false,
            'searchtype'      => 'has',
            'filtertype'      => 'normal',
            'filterdefault'   => true,
            'in_list'         => true,
            'default_in_list' => true,
        ),

        'expenditure_time'        => array(
            'type'            => 'time',
            'label'           => '支付时间',
            'comment'           => '支付时间',
            'editable'        => false,
            'filtertype'      => 'normal',
            'filterdefault'   => false,
            'in_list'         => false,
            'default_in_list' => false,
            'order' => 3,
        ),

        'expenditure_money'        => array(
            'type'            => 'money',
            'label'           => '支付金额',
            'comment'           => '支付金额',
            'filtertype' => 'number',
            'filterdefault' => true,
            'in_list' => true,
            'default_in_list' => true,
            'order'=>5,
            'width' => 80,
        ),

        'cost_project'        => array(
            'type'            => 'varchar(255)',
            'label'           => '费用项',
            'comment'           => '费用项 费用项分组条件',
            'editable'        => false,
            'filtertype'      => 'normal',
            'filterdefault'   => true,
            'in_list'         => true,
            'default_in_list' => true,
        ),

        'relation_sn'        => array(
            'type'            => 'varchar(255)',
            'label'           => '关联单据号',
            'comment'           => '关联单据号',
            'editable'        => false,
            'filtertype'      => 'normal',
            'filterdefault'   => false,
            'in_list'         => false,
            'default_in_list' => false,
        ),


        'service_provider'        => array(
            'type'            => 'varchar(255)',
            'label'           => '服务商',
            'comment'           => '服务商',
            'editable'        => false,
            'filtertype'      => 'normal',
            'filterdefault'   => false,
            'in_list'         => false,
            'default_in_list' => false,
        ),
        'submit_time'        => array(
            'type'            => 'time',
            'label'           => '提交时间',
            'comment'           => '提交时间',
            'editable'        => false,
            'filtertype'      => 'normal',
            'filterdefault'   => false,
            'in_list'         => false,
            'default_in_list' => false,
        ),
        'status'        => array(
            'type'            => 'varchar(255)',
            'label'           => '状态',
            'comment'           => '状态',
            'editable'        => false,
            'filtertype'      => 'normal',
            'filterdefault'   => false,
            'in_list'         => false,
            'default_in_list' => false,
        ),
        'bm_id'        => array(
            'type'            => 'table:basic_material@material',
            'label'           => 'sku编码(商品编码)',
            'comment'           => 'sku编码',
            'editable'        => false,
            'filtertype'      => 'normal',
            'filterdefault'   => false,
            'in_list'         => false,
            'default_in_list' => false,
        ),
        'sku'        => array(
            'type'            => 'varchar(255)',
            'label'           => 'sku编码(商品编码)',
            'comment'           => 'sku编码',
            'editable'        => false,
            'in_list'         => true,
            'default_in_list' => true,
        ),
        'product_name'        => array(
            'type'            => 'varchar(255)',
            'label'           => '商品名称',
            'comment'           => '商品名称',
            'editable'        => false,
            'filtertype'      => 'normal',
            'filterdefault'   => false,
            'in_list'         => false,
            'default_in_list' => false,
        ),
        'plan_operation_num'        => array(
            'type'            => 'varchar(255)',
            'label'           => '计划操作数量',
            'comment'           => '计划操作数量',
            'editable'        => false,
            'searchtype'      => 'has',
            'filtertype'      => 'normal',
            'filterdefault'   => false,
            'in_list'         => false,
            'default_in_list' => false,
        ),

        'actual_operation_num'        => array(
            'type'            => 'varchar(255)',
            'label'           => '实际操作数量',
            'comment'           => '实际操作数量',
            'editable'        => false,
            'filtertype'      => 'normal',
            'filterdefault'   => false,
            'in_list'         => false,
            'default_in_list' => false,
        ),
        'confirm_time'        => array(
            'type'            => 'time',
            'label'           => '确认时间',
            'comment'           => '确认时间',
            'editable'        => false,
            'filtertype'      => 'normal',
            'filterdefault'   => true,
            'in_list'         => true,
            'default_in_list' => true,
        ),

        'confirm_account'        => array(
            'type'            => array(
                '0' => '未对账',
                '1' => '金额不对',
                '2' => '已对账',
            ),
            'label'           => '对账状态',
            'comment'           => '对账状态',
            'editable'        => false,
            'default'         => '0',
            'in_list'         => true,
            'default_in_list' => true,
        ),
        'crc_unique'        => array(
            'type'            => 'varchar(255)',
            'label'           => '唯一编号',
            'comment'           => '唯一编号',
            'editable'        => false,
            'default'         => '0',
            'in_list'         => false,
            'default_in_list' => false,
        ),

        'op_id' =>
            array (
                'type' => 'table:account@pam',
                'label'           => '确认人',
                'comment'         => '确认人',
                'editable' => false,
                'required' => true,
                'filterdefault'   => true,
                'in_list'         => true,
                'default_in_list' => true,
            ),
    ),
    'index'   => array(
        'ind_summary_id'  => array(
            'columns' => array(
                'summary_id',
            ),
        ),
        'ind_import_id'  => array(
            'columns' => array(
                'import_id',
            ),
        ),
        'ind_crc_unique' => array('columns' => array(0 => 'crc_unique')),
        'ind_pay_serial_number'  => array(
            'columns' => array(
                'pay_serial_number',
            ),
        )
    ),
    'engine'  => 'innodb',
    'version' => '$Rev:  $',
);
