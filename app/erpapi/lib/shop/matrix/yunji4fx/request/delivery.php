<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * @author k 2017/10/23
 * @describe 发货处理
 */
class erpapi_shop_matrix_yunji4fx_request_delivery extends erpapi_shop_request_delivery
{
    /**
     * 发货请求参数
     *
     * @return void
     * @author
     **/

    protected function get_confirm_params($sdf)
    {
        $param = parent::get_confirm_params($sdf);
        // 拆单子单回写
        $product_list = array();
        foreach ($sdf['delivery_items'] as $key => $value) {
            if ($value['shop_goods_id'] && $value['shop_goods_id'] != '-1') {
                $product_list[] = array (
                    'bn' => $value['shop_goods_id'],
                    'number'  => $value['number'],
                );
            }
        }
        $param['items'] = json_encode($product_list);
        return $param;
    }
}
