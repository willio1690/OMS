<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class ome_sales_original_platform_luban extends ome_sales_original_platform_factory {
    protected $platfomAmountType = [
        'platform_cost_amount',
    ];
    protected $platfomPayAmountType = [
        'promotion_pay_amount',
    ];

    /**
     * 获取PlatformAmount
     * @param mixed $obj obj
     * @return mixed 返回结果
     */
    public function getPlatformAmount($obj) {
        $amount = 0;
        foreach(self::$coupon as $v) {
            if($v['oid'] == $obj['oid'] && in_array($v['type'], $this->platfomAmountType)) {
                $amount += $v['amount'];
            }
        }
        // echo 'coupon:', var_export(self::$coupon, 1), "\n";
        // echo 'platfomAmountType:', var_export($this->platfomAmountType, 1), "\n";
        return $amount;
    }

    /**
     * 获取PlatformPayAmount
     * @param mixed $obj obj
     * @return mixed 返回结果
     */
    public function getPlatformPayAmount($obj) {
        $amount = 0;
        foreach(self::$coupon as $v) {
            if($v['oid'] == $obj['oid'] && in_array($v['type'], $this->platfomPayAmountType)) {
                $amount += $v['amount'];
            }
        }
        return $amount;
    }
}