<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

$db['order_electronic_items'] = array(
    'columns' => array(
        'item_id'             => array(
            'type'     => 'int unsigned',
            'required' => true,
            'pkey'     => true,
            'extra'    => 'auto_increment',
            'editable' => false,
            'label'    => '电子发票开票信息明细id',
        ),
        'id'                  => array(
            'type'     => 'table:order@invoice',
            'label'    => '来源发票编号',
            'required' => true,
            'editable' => false,
        ),
        'invoice_code'        => array(
            'type'     => 'varchar(32)',
            'label'    => '发票代码',
            'editable' => false,
        ),
        'invoice_no'          => array(
            'type'     => 'varchar(32)',
            'label'    => '发票号码',
            'editable' => false,
        ),
        'serial_no'           => array(
            'type'     => 'varchar(20)',
            'label'    => '开票流水号',
            'editable' => false,
        ),
        'billing_type'        => array(
            'type'     => array(
                1 => '蓝票',
                2 => '红票',
            ),
            'default'  => 1,
            'label'    => '电子发票开票类型',
            'editable' => false,
        ),
        'create_time'         => array(
            'type'    => 'time',
            'default' => '0',
            'label'   => '创建时间',
        ),
        'update_tmall_status' => array(
            'type'     => array(
                1 => '未更新',
                2 => '已更新',
            ),
            'default'  => 1,
            'label'    => '更新天猫状态',
            'editable' => false,
        ),
        'upload_tmall_status' => array(
            'type'     => array(
                1 => '未上传',
                2 => '已上传',
                3 => '上传失败',
            ),
            'default'  => 1,
            'label'    => '上传天猫状态',
            'editable' => false,
        ),
        'url'                 => array(
            'type'    => 'varchar(255)',
            'label'   => '发票地址',
            'in_list' => true,
        ),
        'invoice_action_type' => array(
            'type' => array(
                '1' => '交易成功', // 开票
                '2' => '退货、退款成功', // 冲红
                '3' => '电子换纸质',
                '4' => '换发票内容',
            ),
        ),
        'content'             => array(
            'type' => 'longtext',
        ),
        'last_modified'       => array(
            'label'    => '最后更新时间',
            'type'     => 'last_modify',
            'width'    => 130,
            'editable' => false,
            'in_list'  => true,
        ),
        'logi_no' => array(
            'type'            => 'varchar(50)',
            'label'           => '物流单号',
            'comment'         => '物流单号',
            'editable'        => false,
            'width'           => 110,
            'in_list'         => true,
            'default_in_list' => true,
            'filtertype'      => 'normal',
            'filterdefault'   => true,
        ),
        'logi_name' => array(
            'type'            => 'varchar(50)',
            'label'           => '物流公司编码',
            'comment'         => '物流公司编码',
            'editable'        => false,
            'width'           => 110,
            'in_list'         => true,
            'default_in_list' => true,
        ),
        //新的发票地址，本地路径base_file_id
        'file_id' => array(
            'type'    => 'int(10)',
            'label'   => '发票文件ID',
            'in_list' => true,
            'default' => 0,
        ),
        'invoice_status' => array(
            'type' => array(
                0 => '已开蓝',
                2 => '已撤销',
                3 => '已作废',
                5 => '已红冲',
                10 => '开蓝中',
                20 => '红冲中',
                22 => '已拆分',
                30 => '作废中',
                44 => '已关闭',
                99 => '待开蓝',
            ),
            'default' => '99',
            'label' => '开票状态',      // 此处系原样接受矩阵返回状态
            'editable' => false,
        ),
        'red_confirm_no' => array(
            'type' => 'varchar(64)',
            'label' => '红字发票信息确认单号',
            'editable' => false,
        ),
        'red_confirm_uuid' => array(
            'type' => 'varchar(32)',
            'label' => '红字确认信息UUID',
            'editable' => false,
        ),
        'red_confirm_status' => array(
            'type' => array(
                0 => '-',
                1 => '无需确认',
                2 => '销方录入待购方确认',
                3 => '购方录入待销方确认',
                4 => '购销双方已确认',
                5 => '作废（销方录入购方否认）',
                6 => '作废（购方录入销方否认）',
                7 => '作废（超 72 小时未确认）',
                8 => '作废（发起方已撤销） ',
                9 => '作废（确认后撤销）',
                99 => '申请中',    // oms中间字段
                500 => '申请失败',    // oms中间字段
            ),
            'default' => '0',
            'label' => '红字确认单状态',      // 此处系原样接受矩阵返回状态
            'editable' => false,
        ),
        'channel_id' => array(
            'type' => 'table:channel@invoice',
            'label' => '开票渠道',
            'in_list' => false,
            'default_in_list' => false,
            'width' => 70,
            'editable' => false,
            'order' => 10,
        ),
        'file_path'           => array(
            'type'            => 'text',
            'label'           => '文件原始URL',
        ),
        'xml_file_id' => array(
            'type'    => 'int(10)',
            'label'   => '发票文件ID',
            'default' => 0,
        ),
        'ofd_file_id' => array(
            'type'    => 'int(10)',
            'label'   => '发票文件ID',
            'default' => 0,
        ),
    ),
    'index'   => array(
        'idx_billing_type'    => array(
            'columns' => array(
                0 => 'billing_type',
            ),
        ),
        'ind_id_billing_type' => array(
            'columns' => array(
                0 => 'id',
                1 => 'billing_type',
            ),
            'prefix'  => 'unique',
        ),
        'idx_create_time'     => array('columns' => array('create_time')),
        'idx_invoice_no'      => array('columns' => array('invoice_no')),
        'idx_serial_no'      => array('columns' => array('serial_no')),
    ),
    'comment' => '电子发票开票信息明细表',
    'engine'  => 'innodb',
    'version' => '$Rev:  $',
);
