<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class ome_sales_original_platform_factory {
    static protected $coupon = [];
    static protected $api_version = '1.0';
    protected $platfomAmountType = [
    ];
    protected $platfomPayAmountType = [
    ];
    protected $platfomActualAmountType = [
        'calcActuallyPay'
    ];

    /** @return ome_sales_original_platform_factory */
    public function init($orderId, $shopType) {
        $filter = [];
        $filter['order_id'] = $orderId;
        self::$coupon = app::get('ome')->model('order_coupon')->getList('*', $filter);
        try {
            $obj = kernel::single('ome_sales_original_platform_'.$shopType);
            if(method_exists($obj, 'initOther')) {
                $obj->initOther($orderId, $shopType);
            }
        } catch (\Throwable $th) {
            $obj = $this;
        }
        self::$api_version = app::get('ome')->model('orders')->db_dump($orderId, 'api_version')['api_version'];
        return $obj;
    }

    public function getPlatformAmount($obj) {
        $amount = 0;
        foreach(self::$coupon as $v) {
            if($v['oid'] == $obj['oid']) {
                if(self::$api_version >= 3) {
                    if($v['coupon_type'] == 1) {
                        //$amount += $v['total_amount'];
                    }
                } else {
                    if(in_array($v['type'], $this->platfomAmountType)) {
                        $amount += $v['total_amount'];
                    }
                }
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
            if($v['oid'] == $obj['oid']) {
                if(self::$api_version >= 3) {
                    if($v['coupon_type'] == 3) {
                        $amount += $v['total_amount'];
                    }
                } else {
                    if(in_array($v['type'], $this->platfomPayAmountType)) {
                        $amount += $v['total_amount'];
                    }
                }
            }
        }
        return $amount;
    }

    /**
     * 获取ActualAmount
     * @param mixed $obj obj
     * @param mixed $platformPayAmount platformPayAmount
     * @return mixed 返回结果
     */
    public function getActualAmount($obj, &$platformPayAmount) {
        $amount = 0;
        $use_amount = false;
        foreach(self::$coupon as $v) {
            if($v['oid'] == $obj['oid']) {
                if(in_array($v['type'], $this->platfomActualAmountType)) {
                    $amount += $v['total_amount'];
                }
            }
            if(in_array($v['type'], $this->platfomActualAmountType) && $v['total_amount'] > 0) {
                $use_amount = true;
            }
        }
        if($use_amount) {
            $platformPayAmount = $obj['divide_order_fee'] - $amount;
            return $amount;
        }
        return $obj['divide_order_fee'] - $platformPayAmount;
    }
}
