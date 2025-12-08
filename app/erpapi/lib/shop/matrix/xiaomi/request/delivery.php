<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * @author ykm 2020/2/18 9:39:22
 * @describe 发货处理
 */

class erpapi_shop_matrix_xiaomi_request_delivery extends erpapi_shop_request_delivery
{

    protected function get_confirm_params($sdf)
    {
        $logistics_list = array();
        foreach ($sdf['orderinfo']['order_objects'] as $object) {
            $item = current($object['order_items']);
            if ($object['shop_goods_id'] && $object['shop_goods_id'] > 0) {
                $logistics_list[] = array(
                    'bn'=>$item['shop_product_id'],
                    'company_code' => $sdf['logi_type'], // 物流编号
                    'logistics_no' => $sdf['logi_no'], // 运单号
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