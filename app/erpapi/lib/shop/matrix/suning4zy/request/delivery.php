<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * Class erpapi_shop_matrix_suning4zy_request_delivery
 */
class erpapi_shop_matrix_suning4zy_request_delivery extends erpapi_shop_request_delivery
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

        $item_list = array();
        foreach ($sdf['orderinfo']['order_objects'] as $object) {

            if ($object['oid']) $item_list[] = $object['oid'];
        }

        $param['item_list'] = json_encode($item_list);
        
        return $param;
    }
}