<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * @desc
 * @author: jintao
 * @since: 2016/7/20
 */
class erpapi_shop_matrix_meilishuo_request_finance extends erpapi_shop_request_finance {

    protected function _updateRefundApplyStatusApi($status, $refundInfo=null){
        $api_method = '';
        switch($status){
            #同意退款
            case '2':
                $api_method = SHOP_MEILISHUO_REFUND_GOOD_RETURN_AGREE;
                break;
        }
        return $api_method;
    }

    protected function _updateRefundApplyStatusParam($refund,$status){
        $params = array(
            'refund_id'  =>$refund['refund_apply_bn'],
            //'addr_id'  => '',
        );
        return $params;
    }
}