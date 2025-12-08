<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

$db['bill_import_summary'] = array(
    'comment' => '导入数据汇总',
    'columns' => array(
        'id'          => array(
            'type'     => 'int unsigned',
            'required' => true,
            'pkey'     => true,
            'extra'    => 'auto_increment',
            'editable' => false,
        ),
        'import_id'        => array(
            'type'            => 'int',
            'comment'         => '导入id',
            'default_in_list' => true,
        ),
        'confirm_status'   => array(
            'type'            => 'tinyint',
            'label'           => '确认状态',
            'comment'           => '确认状态,0:未确认1:已确认,2:部分确认',
            'default'         => '0',
            'editable'        => false,
            'default_in_list' => true,
            'in_list'         => true,
            'filterdefault'   => true,
            'filtertype'      => 'normal',
        ),
        'type'        => array(
            'type'            => 'varchar(50)',
            'label'           => '导入类型',
            'comment'         => 'order:按单号导入,sku:按商品sku明细导入,sale:按销售周期导入,jzt:京准通',
            'editable'        => false,
            'searchtype'      => 'has',
            'filtertype'      => 'normal',
            'filterdefault'   => true,
            'in_list'         => true,
            'default_in_list' => true,
        ),
        'pay_serial_number'        => array(
            'type'            => 'varchar(255)',
            'label'           => '支付流水号',
            'comment'           => '支付流水号 单号分组条件',
            'editable'        => false,
            'searchtype'      => 'has',
            'filtertype'      => 'normal',
            'filterdefault'   => true,
            'in_list'         => true,
            'default_in_list' => true,
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
        'cost_project'        => array(
            'type'            => 'varchar(255)',
            'label'           => '费用项',
            'comment'           => '费用项 费用项分组条件',
            'editable'        => false,
            'searchtype'      => 'has',
            'filtertype'      => 'normal',
            'filterdefault'   => true,
            'in_list'         => true,
            'default_in_list' => true,
        ),
        'expenditure_money'        => array(
            'type'            => 'money',
            'comment'           => '支出总额',
            'label'           => '支出金额',
            'in_list'         => true,
            'default_in_list' => true,
        ),
        'op_id' =>
            array (
                'type' => 'table:account@pam',
                'editable' => false,
                'required' => true,
            ),
    ),
    'index'   => array(
        'ind_import_id'  => array(
            'columns' => array(
                'import_id',
            ),
        ),
        'ind_pay_serial_number'  => array(
            'columns' => array(
                'pay_serial_number',
            ),
        ),
    ),
    'engine'  => 'innodb',
    'version' => '$Rev:  $',
);
