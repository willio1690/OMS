<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class erpapi_shop_matrix_yunmall_request_delivery extends erpapi_shop_request_delivery
{
    protected function get_confirm_params($sdf)
    {
        $param = parent::get_confirm_params($sdf);
        
        $goods = array();
        foreach($sdf['delivery_items'] as $key => $object){
            $obj = array();
            $obj['item_id'] = $object['oid'];
            $obj['sku_id'] = $object['shop_product_id'];
            $obj['sku_num'] = $object['number'];
            $goods[] = $obj;
        }
        $param['goods'] = json_encode($goods);
        
        return $param;
    }
}