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
class crm_gift_ordertype
{

    /**
     * 处理
     * @param mixed $ruleBase ruleBase
     * @param mixed $sdf sdf
     * @return mixed 返回值
     */

    public function process($ruleBase, $sdf) {
        if ($ruleBase['filter_arr']['order_types']) {
            $normal_selected  = false;
            $presale_selected = false;
            foreach ($ruleBase['filter_arr']['order_types'] as $order_t) {
                if ($order_t == "normal") {
                    $normal_selected = true;
                }
                if ($order_t == "presale") {
                    $presale_selected = true;
                }
            }
            //$ruleBase['filter_arr']['order_type']普通订单normal和预售订单presale都选的情况下认为是全部订单 不考虑此条件
            if ($normal_selected && $presale_selected) {
            } else {
                if (!in_array($sdf['order_type'], $ruleBase['filter_arr']['order_types'])) {
                    return [false, '不符合指定订单类型'];
                }
            }
        }
        return array(true);
    }
}