<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * @author ykm 2018/04/25
 * @describe 发货处理
 */

class erpapi_shop_matrix_gegejia_request_delivery extends erpapi_shop_request_delivery
{

    protected function get_confirm_params($sdf)
    {
        $param = parent::get_confirm_params($sdf);
        $skuList = array();
        foreach($sdf['orderinfo']['order_objects'] as $value) {
            $skuList[] = array(
                'bn' => $value['shop_goods_id'] != -1 ? $value['shop_goods_id'] : $value['bn'],
                'quantity' => $value['quantity']
            );
        }
        $param['skuList'] = json_encode($skuList);
        return $param;
    }
}