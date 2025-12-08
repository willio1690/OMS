<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class erpapi_shop_matrix_yunmall_request_finance extends erpapi_shop_request_finance
{
    protected function _updateRefundApplyStatusApi($status, $refundInfo=null)
    {
        $api_method = '';
        switch($status)
        {
            case '2':
                $api_method = SHOP_AGREE_RETURN_GOOD;
                break;
            case '3':
                $api_method = SHOP_REFUSE_RETURN_GOOD;
                break;
        }
        
        return $api_method;
    }

    /**
     * 更新退款单参数
     * 
     * @param array $refund
     * @param string $status
     * @return array
     */
    protected function _updateRefundApplyStatusParam($refund, $status)
    {
        $params = array(
            'after_sale_order_no' => $refund['refund_apply_bn'], //退款申请单号
        );
        $shop_id = $this->__channelObj->channel['shop_id'];
        $shop_type = $this->__channelObj->channel['shop_type'];
        
        if($status == '3') {
            $params['reject_reason'] = 'ERP操作'; //拒绝
        }
        return $params;
    }
}
