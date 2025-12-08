<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * @author sunjing@shopex.cn
 * @describe 发货处理
 */

class erpapi_shop_matrix_yangsc_request_delivery extends erpapi_shop_request_delivery
{

    protected function get_confirm_params($sdf)
    {

        // 货号对应平台商品ID
        $logistics_list = array();
        foreach ((array) $sdf['delivery_items'] as $item) {
            if ($item['shop_goods_id'] && $item['shop_goods_id'] != '-1') {
                $logistics_list[] = array(
                    'shop_goods_id' => $item['shop_goods_id'],
                    'company_code'  => $sdf['logi_type'], // 物流编号
                    'logistics_no'  => $sdf['logi_no'], // 运单号
                    'num'           => $item['number'],
                );
            }
        }

        $param = array(
            'tid'          => $sdf['orderinfo']['order_bn'], // 订单号
            'logistics_list' => json_encode($logistics_list)
        );
        
  
        return $param;
    }
}