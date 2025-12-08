<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
*  品骏对接
 * sunjing@shopex.cn
 *
 */
class logisticsmanager_waybill_pinjun
{
    /**
     * 默认订单来源类型
     * @var String 默认来源
     */
    public static $defaultChannelsType = 'OTHER';

    /**
     * 获取物流公司编码
     * @param Sring $logistics_code 物流代码
     */

    public function logistics($logistics_code = '') {
        $logistics = array(
            'PJ' => array('code'=>'PJ','name'=>'标准快递'),
        );

        if (!empty($logistics_code)) {
            return $logistics[$logistics_code];
        }
        return $logistics;
    }
    
    public static $payMethod = array(
        '0' => array('code' => '0', 'name' => '寄付月结'),
        '1' => array('code' => '1', 'name' => '寄付现结'),
        '2' => array('code' => '2', 'name' => '到付现结'),
        '3' => array('code' => '3', 'name' => '到付月结'),

     );

    public function pay_method($method = '') {

        if (!empty($method)) {
            return self::$payMethod[$method];
        }
        return self::$payMethod;
    }
}
