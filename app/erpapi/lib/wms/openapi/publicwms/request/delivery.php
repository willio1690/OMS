<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class erpapi_wms_openapi_publicwms_request_delivery extends erpapi_wms_request_delivery
{
    protected function _format_delivery_create_params($sdf)
    {
        $params = parent::_format_delivery_create_params($sdf);

        $params['payments']     = json_encode((array) $sdf['payments']);
        $params['cost_tax']     = $sdf['cost_tax'];
        $params['member_name']  = $sdf['member_name'];
        $params['buyer_name']   = (string)$sdf['member']['name'];

        return $params;
    }
}
