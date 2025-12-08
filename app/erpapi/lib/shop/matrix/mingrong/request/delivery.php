<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * @author ykm 2019/2/15
 * @describe 名融 发货处理
 */

class erpapi_shop_matrix_mingrong_request_delivery extends erpapi_shop_request_delivery
{
    protected function get_confirm_params(&$sdf)
    {
        $param = parent::get_confirm_params($sdf);
        $param['is_split'] = $sdf['is_split'];
        $product_list = array();
        foreach($sdf['delivery_items'] as $k => $item){
            $product_list[] = array(
                'bn' => $item['bn'],
                'num' => $item['number'],
            );
        }

        $param['sku_info'] = json_encode($product_list);

        return $param;
    }


}