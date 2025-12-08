<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

$db['platform_order_items'] = array(
    'columns' => array(
        'plat_item_id' => array(
            'pkey' => 'true',
            'type' => 'int unsigned',
            'extra' => 'auto_increment',
            'required' => true,
            'label' => '订单商品ID',
            'order' => 1,
        ),
        'plat_order_id' => array(
            'type' => 'table:platform_orders@dealer',
            'default' => 0,
            'editable' => false,
            'required' => true,
            'label' => '订单商品ID',
            'in_list' => true,
            'default_in_list' => true,
        ),
        'plat_obj_id' => array(
            'type' => 'table:platform_order_objects@dealer',
            'default' => 0,
            'editable' => false,
            'required' => true,
            'label' => '订单obj_id',
            'in_list' => true,
            'default_in_list' => true,
        ),
        'item_type' => array(
            'type' => 'varchar(50)',
            'default' => 'product',
            'required' => true,
            'editable' => false,
            'comment' => '订单行明细类型,可选值:product(商品),pkg(捆绑商品),gift(赠品),giftpackage(礼包),lkb(福袋),pko(多选一)',
        ),
        'shop_goods_id' => array(
            'type' => 'varchar(50)',
            'required' => true,
            'default' => 0,
            'editable' => false,
            'label' => '平台商品ID',
            'comment' => '平台商品ID'
        ),
        'shop_product_id' => array(
            'type' => 'varchar(50)',
            'editable' => false,
            'required' => true,
            'default' => 0,
            'comment' => '平台SKU ID',
        ),
        'product_id' => array(
            'type' => 'int unsigned',
            'required' => true,
            'default' => 0,
            'editable' => false,
            'comment' => '基础物料ID,关联material_basic_material.bm_id',
        ),
        'bn' => array(
            'type' => 'varchar(40)',
            'editable' => false,
            'is_title' => true,
            'label' => '基础物料编码',
            'comment' => '基础物料编码',
        ),
        'name' => array(
            'type' => 'varchar(200)',
            'editable' => false,
            'label' => '基础物料名称',
            'comment' => '基础物料名称',
        ),
        'nums' => array(
            'type' => 'number',
            'default' => 1,
            'required' => true,
            'editable' => false,
            'comment' => '订单行明细购买数量',
        ),
        'split_num' => array(
            'type' => 'number',
            'default' => 0,
            'required' => true,
            'editable' => false,
            'comment' => '订单行明细拆分数量',
        ),
        'sendnum' => array(
            'type' => 'number',
            'default' => 0,
            'required' => true,
            'editable' => false,
            'comment' => '订单行明细发货数量',
        ),
        'return_num' => array(
            'type' => 'number',
            'default' => 0,
            'editable' => false,
            'label' => '已退货量',
            'comment' => '订单行明细已退货数量',
        ),
        'price' => array(
            'type' => 'money',
            'default' => '0',
            'required' => true,
            'editable' => false,
            'comment' => '子单零售价',
        ),
        'cost' => array(
            'type' => 'money',
            'editable' => false,
            'comment' => '订单行明细成本价',
        ),
        'amount' => array(
            'type' => 'money',
            'default' => '0',
            'required' => true,
            'editable' => false,
            'comment' => '子单零售小价, 公式: price * nums'
        ),
        'pmt_price' => array(
            'type' => 'money',
            'default' => '0',
            'editable' => false,
            'comment' => '子单优惠小计',
        ),
        'sale_price' => array(
            'type' => 'money',
            'default' => '0',
            'editable' => false,
            'comment' => '子单销售价,公式:amount - pmt_price'
        ),
        'divide_order_fee' => array(
            'type' => 'money',
            'editable' => false,
            'label' => '分摊之后的实付金额',
            'comment' => '子单实付,公式: sale_price-part_mjz_discount',
        ),
        'part_mjz_discount' => array(
            'type' => 'money',
            'editable' => false,
            'label' => '优惠分摊',
            'comment' => '子单优惠分摊,按价格贡献比分摊pmt_order'
        ),
        'refund_money' => array (
            'type' => 'money',
            'editable' => false,
            'label' => '退款金额',
            'default'=>0,
            'comment' => '子单退款金额',
        ),
        'ship_status' => array(
            'type' => array(
                0 => '未发货',
                1 => '已发货',
                2 => '部分发货',
                3 => '部分退货',
                4 => '已退货',
            ),
            'default' => '0',
            'label' => '子订单状态',
            'comment' => '子订单状态,0:未发货,1:已发货,2:部分发货,3:部分退货,4:已退货',
        ),
        'is_sh_ship' => array(
            'type' => 'bool',
            'label' => '是否屏蔽发货',
            'default' => 'false',
            'comment' => '是否屏蔽发货,0:否,1:是',
        ),
        's_type' => array(
            'type' => 'varchar(50)',
            'label' => '销售类型',
            'default' => 'zx',
        ),
        'author_id' => array(
            'type' => 'varchar(30)',
            'label' => '活动主播ID',
            'editable' => true,
        ),
        'author_name' => array(
            'type' => 'varchar(50)',
            'label' => '活动主播名',
            'editable' => true,
        ),
        'main_oid' => array(
            'type' => 'varchar(512)',
            'editable' => false,
            'label' => '赠品关联子单号',
        ),
        'is_shopyjdf_type' => array(
            'type' => array(
                0 => '未转换',
                1 => '自发货',
                2 => '代发货',
            ),
            'default' => '0',
            'label' => '发货方式',
        ),
        'betc_id' => array(
            'type' => 'int unsigned',
            'required' => true,
            'default' => 0,
            'label' => '贸易公司ID',
            'editable' => false,
        ),
        'process_status' => array(
            'type' => array(
                'unconfirmed' => '未处理',
                'confirmed' => '已处理',
            ),
            'default' => 'unconfirmed',
            'editable' => false,
            'label' => '处理状态',
        ),
        'erp_order_id' => array(
            'type' => 'int unsigned',
            'label' => 'OMS订单ID',
            'editable' => true,
        ),
        'erp_order_bn' => array(
            'type' => 'varchar(32)',
            'label' => 'OMS订单号',
            'editable' => true,
        ),
        'last_modified' => array(
            'label' => '最后更新时间',
            'type' => 'last_modify',
            'editable' => false,
        ),
        'is_delete' => array(
            'type' => 'bool',
            'default' => 'false',
            'editable' => false,
            'comment' => '删除状态,可选值: false(否),true(是)',
        ),
    ),
    'index' => array(
        'ind_product_bn' => array(
            'columns' => array(
                0 => 'bn',
            ),
        ),
        'ind_item_types' => array(
            'columns' => array(
                0 => 'plat_order_id',
                1 => 'item_type',
            ),
        ),
        'ind_delete_type' => array(
            'columns' => array(
                0 => 'plat_order_id',
                1 => 'is_delete',
                2 => 'is_shopyjdf_type',
            ),
        ),
        'ind_item_betc' => array(
            'columns' => array(
                0 => 'plat_order_id',
                1 => 'betc_id',
                2 => 'is_shopyjdf_type',
            ),
        ),
        'ind_erp_order_id' => array(
            'columns' => array(
                0 => 'erp_order_id',
            ),
        ),
        'ind_erp_order_bn' => array(
            'columns' => array(
                0 => 'erp_order_bn',
            ),
        ),
        'ind_product_status' => array(
            'columns' => array(
                0 => 'product_id',
                1 => 'is_shopyjdf_type',
                2 => 'process_status',
            ),
        ),
    ),
    'engine' => 'innodb',
    'charset' => 'utf8mb4',
    'version' => '$Rev: $',
    'comment' => '分销平台订单Item表',
);