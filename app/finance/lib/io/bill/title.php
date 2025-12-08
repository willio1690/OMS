<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */


class finance_io_bill_title{
    private static $title = array(
        'normal' => array(
            'order_bn' => '*:订单号',
            'date' => '*:日期',
            'fee_item' => '*:费用项',
            'fee_obj' => '*:费用对象',
            'member' => '*:交易对方',
            'price' => '*:金额',
            'credential_number' => '*:凭据号',
        ),
        'ar' => array(
            '1'=> array(
                'trade_time' => '*:账单日期',
                'serial_number' => '*:业务流水号',
                'member' => '*:客户/会员',
                'type' => '*:业务类型',
                'order_bn' => '*:订单号',
                'relate_order_bn' => '*:关联订单号',
                'sale_money' => '*:商品成交金额',
                'fee_money' => '*:运费收入',
                'money' => '*:应收金额',
            ),
            '2'=> array(
                'serial_number' => '*:业务流水号',
                'bn' => '*:商品货号',
                'name' => '*:商品名称',
                'nums' => '*:数量',
                'money' => '*:金额',
            ),
        ),
        'yihaodian' => array(
            'date' => '可结算日期',
            'order_bn' => '订单号',
            'sale' =>'销售额',
            'refund' =>'退款额',
            'fee' =>'佣金',
            'ship' =>'配送费',
            'is_js' =>'是否已结款',
            'js_type' =>'结算类型',
            'goods_bn' => '商品编号',
        ),
        'jingdong_tuotou' => array(
            'date' => '关单时间',
            'order_bn' => '订单编号',
            'sale' => '货款',
            'fee' => '佣金',
            'service' => '打包服务费',
            'ship' => '配送费',
        ),
        'jingdong_tuihuo' => array(
            'goods_bn' => '商品编号',
            'date' => '退货时间',
            'order_bn' => '订单编号',
            'sale' => '货款',
            'fee' => '佣金',
            'ship' => '配送费',
        ),
        'jingdong_jushou' => array(
            'order_bn' => '订单编号',
            'ship' => '拒收配送费',
            'downtime' => '下单时间',
        ),
        'zhifubao' => array(
            'order_bn' => '*:订单号',
            'date' => '*:发生时间',
            'member' => '*:对方帐号',
            'price' => '*:收入金额（+元）',
            'credential_number' => '*:交易流水号',
            'memo' => '*:备注',
            'business_type' => '*:业务类型',
            'expenditure' => '*:支出金额（-元）',
        ),
    );

    public static function getTitle($type = ''){
        return ($type && isset(self::$title[$type])) ? self::$title[$type] : array();
    }
}
?>