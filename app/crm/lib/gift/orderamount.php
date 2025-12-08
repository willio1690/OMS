<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * Created by PhpStorm.
 * User: yaokangming
 * Date: 2019/3/19
 * Time: 18:04
 */
class crm_gift_orderamount
{

    /**
     * 处理
     * @param mixed $ruleBase ruleBase
     * @param mixed $sdf sdf
     * @return mixed 返回值
     */

    public function process($ruleBase, $sdf) {
        $payed = floatval($sdf['payed']);
        $total_amount = floatval($sdf['total_amount']);
        #按付款总额判断
        if($ruleBase['filter_arr']['order_amount']['type'] == 1){
            if($ruleBase['filter_arr']['order_amount']['sign']=='bthan'){
                if($payed<$ruleBase['filter_arr']['order_amount']['max_num']){
                    $reason = '不满足最低付款';
                    return array(false, $reason);
                }
            }else{
                if($payed<$ruleBase['filter_arr']['order_amount']['min_num'] or $payed>=$ruleBase['filter_arr']['order_amount']['max_num']){
                    $reason = '不满足付款区间';
                    return array(false, $reason);
                }
            }
        }
        #按订单总额判断
        if($ruleBase['filter_arr']['order_amount']['type'] == 2){
            if($ruleBase['filter_arr']['order_amount']['sign']=='bthan'){
                if($total_amount<$ruleBase['filter_arr']['order_amount']['max_num']){
                    $reason = '订单总额不满足最低付款';
                    return array(false, $reason);
                }
            }else{
                if($total_amount<$ruleBase['filter_arr']['order_amount']['min_num'] or $total_amount>=$ruleBase['filter_arr']['order_amount']['max_num']){
                    $reason = '订单总额不满足付款区间';
                    return array(false, $reason);
                }
            }
        }
        return array(true);
    }
}