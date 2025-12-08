<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * [美团医药]店铺退款业务请求Lib类
 */
class erpapi_shop_matrix_meituan4medicine_request_finance extends erpapi_shop_request_finance
{
    protected function _updateRefundApplyStatusApi($status, $refundInfo = null)
    {
        $api_method = '';
        switch ($status) {
            case '2':
                $api_method = SHOP_AGREE_REFUND;
                break;
            case '3':
                $api_method = SHOP_REFUSE_REFUND;
                break;
        }
        
        return $api_method;
    }
    
    /**
     * 退款申请单接口数据
     * @param  array $refund 退款申请单明细
     * @param  string $status 2:已接受申请、3:已拒绝
     * @return [type]         [description]
     */

    public function _updateRefundApplyStatusParam($refund, $status)
    {
        $params = array();
        $reason_id = $refund['reason_id'];
        if ($status == '3') {
            $params['reject_reason_code'] = $reason_id;
        }
        return $params;
    }
}
