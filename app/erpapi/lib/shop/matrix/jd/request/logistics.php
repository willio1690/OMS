<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class erpapi_shop_matrix_jd_request_logistics extends erpapi_shop_request_logistics
{
    /**
     * 获取CarrierPlatform
     * @param mixed $sdf sdf
     * @return mixed 返回结果
     */
    public function getCarrierPlatform($sdf)
    {
        $title = '平台承运商履约信息查询';
        $params = ['orderId' => $sdf['order_bn'], 'shippingMethod' => $sdf['shippingMethod']];
        $result = $this->__caller->call(SHOP_JDGXD_LOGISTICS_FULFILLMENT_INFO, $params, [], $title, 10, $sdf['order_bn']);
        return $result;
    }
    
}