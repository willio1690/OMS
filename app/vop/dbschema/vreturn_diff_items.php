<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

$db['vreturn_diff_items']=[
    'columns' => [
        'id' => [
            'type' => 'int unsigned',
            'required' => true,
            'pkey' => true,
            'extra' => 'auto_increment',
            'editable' => false,
            'order' => 1,
        ],
        'diff_id' => [
            'type' => 'int unsigned',
            'required' => true,
            'label' => '差异单ID',
        ],
        'material_bn' => [
            'type' => 'varchar(200)',
            'label' => '基础物料编码',
            'in_list' => true,
            'order' => 10,
        ],
        'material_name' => [
            'type' => 'varchar(255)',
            'label' => '基础物料名称',
            'in_list' => true,
            'order' => 20,
        ],
        'bm_id' =>[
            'type' => 'number',
        ],
        'item_sku' => [
            'type' => 'varchar(32)',
            'label' => '商品条形码',
            'in_list' => true,
            'required' => true,
            'order' => 30,
        ],
        'po_bn' => [
            'type' => 'varchar(32)',
            'label' => '采购单号',
            'in_list' => true,
            'order' => 40,
        ],
        'schedule_id' => [
            'type' => 'varchar(32)',
            'label' => '档期号',
            // 'in_list' => true,
        ],
        'is_complete_name' => [
            'type' => 'varchar(10)',
            'label' => '外箱是否完整',
            'in_list' => true,
        ],
        'box_id' => [
            'type' => 'varchar(50)',
            'label' => '箱号',
            'in_list' => true,
            'order' => 50,
        ],
        'order_sn' => [
            'type' => 'varchar(32)',
            'label' => '关联的SO',
            'in_list' => true,
            'order' => 60,
        ],
        'rv_difference_no' => [
            'type' => 'varchar(32)',
            'label' => '退供差异单号',
            'in_list' => true,
            'order' => 70,
        ],
        'is_anti_theft_code_name' => [
            'type' => 'varchar(10)',
            'label' => '是否填写唯一码',
            'order' => 80,
        ],
        'anti_theft_code' => [
            'type' => 'varchar(32)',
            'label' => '唯一码',
            'in_list' => true,
            'order' => 90,
        ],
        'anti_theft_code_used_by_str' => [
            'type' => 'text',
            'label' => '唯一码使用方',
            'in_list' => true,
            'order' => 100,
        ],
        'anti_theft_code_approval_remark' => [
            'type' => 'text',
            'label' => '唯一码审批备注',
            'in_list' => true,
            'order' => 110,
        ],
        'no_unique_code_reason_desc' => [
            'type' => 'text',
            'label' => '唯一码缺失原因',
            'in_list' => true,
            'order' => 120,
        ],
        'record_quantity' => [
            'type' => 'int',
            'label' => '退供数',
            'in_list' => true,
            'order' => 130,
        ],
        'real_quantity' => [
            'type' => 'int',
            'label' => '实收数',
            'in_list' => true,
            'order' => 140,
        ],
        'diff_quantity' => [
            'type' => 'int',
            'label' => '差异数',
            'in_list' => true,
            'order' => 150,
        ],
        'pay_quantity' => [
            'type' => 'int',
            'label' => '赔偿数',
            'in_list' => true,
            'order' => 160,
        ],
        'price' => [
            'type' => 'decimal(20,8)',
            'label' => '净结算单价',
            'in_list' => true,
            'order' => 31,
        ],
        'diff_amount' => [
            'type' => 'decimal(20,8)',
            'label' => '差异金额',
            'in_list' => true,
            'order' => 32,
        ],
        'vendor_feedback_name' => [
            'type' => 'text',
            'label' => '供应商反馈差异情况',
            'in_list' => true,
            'order' => 170,
        ],
        'is_return_name' => [
            'type' => 'varchar(10)',
            'label' => '是否寄回',
            'in_list' => true,
            'order' => 180,
        ],
        'vendor_note' => [
            'type' => 'text',
            'label' => '供应商差异说明',
            'in_list' => true,
            'order' => 190,
        ],
        'sku_img' => [
            'type' => 'longtext',
            'label' => '证明图片',
            // 'in_list' => true,
            'order' => 200,
        ],
        'return_price_discount' => [
            'type' => 'decimal(20,8)',
            'label' => '确认赔偿折扣',
            'in_list' => true,
            'order' => 210,
        ],
        'return_diff_amount' => [
            'type' => 'decimal(20,8)',
            'label' => '确认结算总额',
            'in_list' => true,
            'order' => 220,
        ],
        'status' => [
            'type' => 'varchar(20)',
            'label' => '状态',
            'in_list' => true,
            'order' => 230,
        ],
        'status_note' => [
            'type' => 'varchar(255)',
            'label' => '驳回原因',
            'in_list' => true,
            'order' => 240,
        ],
        'video_url' => [
            'type' => 'varchar(255)',
            'label' => '视频上传地址',
            'in_list' => true,
            'order' => 250,
        ],
        'status_code' => [
            'type' => 'tinyint(1)',
            'label' => '状态编码',
            'in_list' => true,
            'order' => 260,
        ],

        'at_time'           => [
            'type'    => 'TIMESTAMP',
            'label'   => '创建时间',
            'default' => 'CURRENT_TIMESTAMP',
            'width'           => 130,
            'in_list' => true,
        ],
        'up_time'           => [
            'type'    => 'TIMESTAMP',
            'label'   => '更新时间',
            'default' => 'CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP',
            'width'           => 130,
            'in_list' => true,
        ],
    ],
    'index' => [
        'ind_at_time' => ['columns' => ['at_time']],
        'ind_up_time' => ['columns' => ['up_time']],
        'ind_diff_id' => ['columns' => ['diff_id']],
        'ind_unique' => ['columns' => ['item_sku','po_bn','box_id','anti_theft_code'], 'prefix' => 'unique'],
    ],
    'comment' => '退供差异账单明细',
    'engine' => 'innodb',
    'version' => '$Rev: $',
];
