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
class erpapi_shop_matrix_shopex_bbc_request_finance extends erpapi_shop_matrix_shopex_request_finance {

    protected function _updateRefundApplyStatusApi($status, $refundInfo=null){
        $api_method = '';
        switch($status){
            case '3':
                $api_method = SHOP_REFUSE_REFUND;#拒绝退款接口
                break;
        }
        return $api_method;
    }

    protected function _updateRefundApplyStatusParam($refund, $status){
        $params = array();
        $params['refund_id']  = $refund['refund_apply_bn'];
        $params['refuse_message']=$refund['refuse_message'];
        return $params;
    }

    protected function _setParams($refund){
        $params = parent::_setParams($refund);
        $refund_apply_id = $params['refund_apply_id'];
        $refund_applyObj = app::get('ome')->model('refund_apply');
        $refundapply_detail = $refund_applyObj->db->selectrow("SELECT p.return_bn FROM sdb_ome_refund_apply AS r LEFT JOIN sdb_ome_return_product as p ON r.return_id=p.return_id WHERE r.apply_id=".$refund_apply_id);

        $params['return_bn'] = $refundapply_detail['return_bn'];
        return $params;
    }
}