<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * 20180831 by wangjianjun
 */
class erpapi_shop_matrix_kaola_request_finance extends erpapi_shop_request_finance{

    protected function _updateRefundApplyStatusApi($status, $refundInfo=null){
        $api_method = '';
        switch($status){
            case '2':
                $api_method = SHOP_AGREE_REFUND;
                break;
            case '3':
                $api_method = SHOP_REFUSE_REFUND;
                break;
        }
        return $api_method;
    }

    protected function _updateRefundApplyStatusParam($refund,$status){
        $params = array(
            "refund_id" => $refund["refund_apply_bn"]
        );
        if ($status == '3') { //拒绝
            $params['refund_refuse_reason'] = $refund['refuse_message'];
        }
        if ($status == '2') { //接受
            //$params['refund_remark'] = "";
            //$params['pic'] = "";
        }
        return $params;
    }
   
}
