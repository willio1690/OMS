<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * [小红书]店铺退款业务请求Lib类
 */
class erpapi_shop_matrix_xiaohongshu_request_finance extends erpapi_shop_request_finance
{
    protected function _updateRefundApplyStatusApi($status, $refundInfo=null)
    {
        $api_method = '';
        switch($status)
        {
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
     * 更新退款单参数
     * 
     * @param array $refund
     * @param string $status
     * @return array
     */

    protected function _updateRefundApplyStatusParam($refund, $status)
    {
        $params = array(
            'returns_id' => $refund['refund_apply_bn'], //退款申请单号
        );
        
        if($status == '3') {
            $params['audit_result'] = '500'; //拒绝
        }else{
            $params['audit_result'] = '200'; //同意
        }
        
        //拒绝原因
        if($refund['refuse_message']){
            $params['audit_description'] = $refund['refuse_message'];
            $params['reject_reason'] = 1;
        }
        
        return $params;
    }
}
