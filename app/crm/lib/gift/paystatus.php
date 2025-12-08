<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * ============================
 * @Author:   yaokangming
 * @Version:  1.0
 * @DateTime: 2020/12/29 16:59:59
 * @describe: 类
 * ============================
 */
class crm_gift_paystatus {

    /**
     * 处理
     * @param mixed $ruleBase ruleBase
     * @param mixed $sdf sdf
     * @return mixed 返回值
     */

    public function process($ruleBase, $sdf) {
        //部分支付
        if($ruleBase['filter_arr']['pay_status'] == '3')
        {
            if($sdf['pay_status'] != '3')
            {
                return [false, '不是部分支付的订单'];
            }
        }
        
        //已支付
        if($ruleBase['filter_arr']['pay_status'] == '1')
        {
            if($sdf['pay_status'] != '1')
            {
                return [false, '不是已支付的订单'];
            }
        }
        return [true];
    }
}