<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * 华为商城平台对接
 * 
 * @author wangbiao@shopex.cn
 * @version $Id: Z
 */
class erpapi_shop_matrix_huawei_request_aftersale extends erpapi_shop_request_aftersale
{
    /***
    protected function __afterSaleApi($status, $returnInfo=null)
    {
        switch($status)
        {
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
    ***/
}