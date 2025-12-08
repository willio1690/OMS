<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * 20180830 by wangjianjun
 */
class erpapi_shop_matrix_kaola_request_aftersale extends erpapi_shop_request_aftersale {
    protected function __afterSaleApi($status, $returnInfo=null) {
        switch( $status ){
            case '3':
                $api_method = SHOP_AGREE_REFUNDGOODS;
                break;
            case '5':
                $api_method = SHOP_REFUSE_REFUNDGOODS;
                break;
            default :
                $api_method = '';
                break;
        }
        return $api_method;
    }

    protected function __formatAfterSaleParams($aftersale,$status) {
        $params = array(
            'refund_id'=>$aftersale['return_bn'],
        );
        switch ($status) {
            case '3':
                break;
            case '5':
                $params['refund_refuse_reason'] = $aftersale['refuse_message'];
                break;
            default: break;
        }
        return $params;
    }
    
}