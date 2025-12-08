<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * @author ykm 2018/4/12
 * @describe 财务相关
 */

class erpapi_shop_matrix_shunguang_request_finance extends erpapi_shop_request_finance
{
    protected function _updateRefundApplyStatusApi($status, $refundInfo=null){
        $apiName = '';
        if(in_array($status, array('2', '3'))) {
            $apiName = SHOP_ADD_REFUND_RPC;
        }
        return $apiName;
    }

    protected function _updateRefundApplyStatusParam($refund, $status){
        $sdf = array(
            'refund_id' => $refund['refund_apply_bn'],
            'agree' => $status == 2 ? 1 : ($status == 3 ? 2 : ''),
            'handle_remark' => kernel::single('desktop_user')->get_name() . '操作,' . $refund['refuse_message']
        );
        return $sdf;
    }
}