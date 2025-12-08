<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * [小红书]店铺退款业务请求Lib类
 */
class erpapi_shop_matrix_xhs_request_finance extends erpapi_shop_request_finance
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
     * 添加Refund
     * @param mixed $refund refund
     * @return mixed 返回值
     */

    public function addRefund($refund){
        $api_method = SHOP_AGREE_REFUND;
    
        $title = '店铺('.$this->__channelObj->channel['name'].')更新[交易退款状态],(订单号:'.$refund['order_bn'].'退款单号:'.$refund['refund_bn'].')';
        
        $shop_id = $this->__channelObj->channel['shop_id'];
        $shop_type = $this->__channelObj->channel['shop_type'];

        $params['audit_result'] = '200'; //同意
        $params['returns_id'] = $refund['refund_bn']; //同意
        $aliag_status           = app::get('ome')->getConf('shop.aliag.config.' . $shop_id);
        if ($aliag_status) {
            $params['auto_refund'] = false;  //禁用 自动退款/极速退款 true/false
        } else {
            $params['auto_refund'] = true;  //启用自动退款/极速退款 true/false
        }
        
        return $this->__caller->call($api_method, $params, [], $title, 10, $refund['order_bn']);
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
        $shop_id = $this->__channelObj->channel['shop_id'];
        $shop_type = $this->__channelObj->channel['shop_type'];
        
        if($status == '3') {
            $params['audit_result'] = '500'; //拒绝
        }else{
            $params['audit_result'] = '200'; //同意
            $aliag_status           = app::get('ome')->getConf('shop.aliag.config.' . $shop_id);
            if ($aliag_status) {
                $params['auto_refund'] = false;  //是否禁用 自动退款/极速退款 true/false
            } else {
                $params['auto_refund'] = true;  //是否禁用 自动退款/极速退款 true/false
            }
            
        }
        
        //拒绝原因
        if($refund['refuse_message']){
            $params['audit_description'] = $refund['refuse_message'];
            $params['reject_reason'] = 1;
        }
        
        return $params;
    }
}
