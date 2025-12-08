<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class erpapi_shop_matrix_eyee_request_delivery extends erpapi_shop_request_delivery
{
    protected function get_confirm_params($sdf)
    {
        $param = parent::get_confirm_params($sdf);
        $param['send_type']=$sdf['orderinfo']['ship_status']==2?'part':'all';
        return $param;
    }
}