<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * 美团医药
 */
class erpapi_shop_matrix_meituan4bulkpurchasing_request_aftersale extends erpapi_shop_request_aftersale
{
    protected function __afterSaleApi($status, $aftersale)
    {
        switch ($status) {
            case '3':
                $api_method = SHOP_AGREE_RETURN_GOOD;
                break;
            case '5':
                $api_method = SHOP_REFUSE_RETURN_GOOD;
                break;
            default :
                $api_method = '';
                break;
        }
        return $api_method;
    }
    
    /**
     * __formatAfterSaleParams
     * @param mixed $aftersale aftersale
     * @param mixed $status status
     * @return mixed 返回值
     */

    public function __formatAfterSaleParams($aftersale, $status)
    {
        $params = array();
        $params['refund_id'] = $aftersale['return_bn'];
        $params['remark'] = 'erp操作';
        return $params;
    }
}