<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * 苏宁退款
 * Class erpapi_shop_matrix_kaola_request_finance
 */
class erpapi_shop_matrix_kuaishou_request_finance extends erpapi_shop_request_finance{

    protected function _updateRefundApplyStatusApi($status, $refundInfo=null){
        $api_method = '';
        switch($status){
            case '2':
                $api_method = SHOP_AGREE_RETURN_GOOD;
                break;
            case '3':
                $api_method = SHOP_REFUSE_RETURN_GOOD;
                break;
        }
        return $api_method;
    }

    protected function _updateRefundApplyStatusParam($refund,$status){
        $params = array(
            "refund_id" => $refund["refund_apply_bn"],
        );
        $specialObj = app::get('ome')->model('return_apply_special');
        $ras = $specialObj->db_dump(array('apply_id'=>$refund['apply_id']), 'special');
        $special = $ras ? json_decode($ras['special'], 1) : array();
        switch($status){
            case '3':
                $params['reasonCode'] = '100';
                $params['rejectDesc'] = $refund['refuse_message'] ? : 'ERP操作';
                $params['order_status'] = $special['order_status'];
                $params['negotiate_status'] = '1';
                $params['flag'] = 'new';
                $params['refundVersion'] = $special['refund_version'];
                break;
            case '2':
                $params['desc'] = $refund['refuse_message'] ? : 'ERP操作';
                $params['refund_amount'] = $refund['money'];
                $params['order_status'] = $special['order_status'];
                $params['negotiate_status'] = '1';
                $params['refund_handing_way'] = $special['refund_handing_way'];
                break;
        }
        return $params;
    }
}
