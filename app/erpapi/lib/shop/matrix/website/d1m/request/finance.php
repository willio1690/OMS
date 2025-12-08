<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * Class erpapi_shop_matrix_website_d1m_request_finance
 */
class erpapi_shop_matrix_website_d1m_request_finance extends erpapi_shop_request_finance
{
    protected function _updateRefundApplyStatusApi($status, $refundInfo = null)
    {
        $api_method = '';
        switch ($status) {
            case '2':
            case '3':
                $api_method = D1M_OPEN_REFUND_NOTICE_POST;
                break;
        }
        return $api_method;
    }
    
    protected function _updateRefundApplyStatusParam($refund, $status)
    {
        $params = array(
            "refund_id" => $refund["refund_apply_bn"],
        );
        switch ($status) {
            case '3':
                $params['message']     = $refund['refuse_message'] ?: 'ERP操作';
                $params['refund_type'] = 'refuse';//拒绝
                break;
            case '2':
                $params['message']        = $refund['refuse_message'] ?: 'ERP操作';
                $params['refund_type'] = 'agree';//同意
                break;
        }
        return $params;
    }
    
    /**
     * 更新退款单状态
     */

    public function updateRefundApplyStatus($refund,$status,$mod = 'sync')
    {
        $orderModel = app::get('ome')->model('orders');
        $orderData = $orderModel->getList('order_bn', array('order_id'=>$refund['order_id']), 0, 1);
        $order = $orderData[0];
        
        $api_method = $this->_updateRefundApplyStatusApi($status);
        
        if ($api_method == '' || $mod != 'sync') {
            return false;
        }
        $params = $this->_updateRefundApplyStatusParam($refund, $status);
    
        $paramsJson = [
            'json_data' => json_encode($params)
        ];
        
        $title = '店铺('.$this->__channelObj->channel['name'].')更新[交易退款状态],(订单号:'.$order['order_bn'].'退款单号:'.$refund['refund_apply_bn'].')';
        $callback = array();
        $result = $this->__caller->call($api_method, $paramsJson, $callback, $title, 10, $order['order_bn']);
        // token 异常,发起重试
        if ($result['rsp'] == 'fail' && in_array($result['err_msg'], $this->__resultObj->retryErrorMsgList())) {
            kernel::single('erpapi_router_request')->set('shop', $this->__channelObj->channel['shop_id'])->base_get_access_token();
            $result = $this->__caller->call($api_method, $paramsJson, $callback, $title, 10, $order['order_bn']);
        }
        
        $rs = array();
        if(isset($result['msg']) && $result['msg']){
            $rs['msg'] = $result['msg'];
        }elseif(isset($result['err_msg']) && $result['err_msg']){
            $rs['msg'] = $result['err_msg'];
        }elseif(isset($result['res']) && $result['res']){
            $rs['msg'] = $result['res'];
        }
        $rs['rsp'] = $result['rsp'];
        
        return $rs;
    }
}