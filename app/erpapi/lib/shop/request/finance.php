<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * User: jintao
 */
class erpapi_shop_request_finance extends erpapi_shop_request_abstract
{
    /**
     * sync_bills_book_get
     * @param mixed $account_id ID
     * @param mixed $start_time start_time
     * @param mixed $end_time end_time
     * @param mixed $journal_types journal_types
     * @param mixed $page_no page_no
     * @param mixed $page_size page_size
     * @return mixed 返回值
     */

    public function sync_bills_book_get($account_id,$start_time,$end_time,$journal_types = '',$page_no = 1,$page_size = 40){}

    /**
     * bills_book_get
     * @param mixed $account_id ID
     * @param mixed $start_time start_time
     * @param mixed $end_time end_time
     * @param mixed $journal_types journal_types
     * @param mixed $page_no page_no
     * @param mixed $page_size page_size
     * @return mixed 返回值
     */
    public function bills_book_get($account_id,$start_time,$end_time,$journal_types = '',$page_no = 1,$page_size = 40){}

    /**
     * bills_book_get_callback
     * @param mixed $response response
     * @param mixed $callback_params 参数
     * @return mixed 返回值
     */
    public function bills_book_get_callback($response, $callback_params){}

    private function get_fee_item($outer_id){}

    /**
     * bills_get
     * @param mixed $start_time start_time
     * @param mixed $end_time end_time
     * @param mixed $page_no page_no
     * @param mixed $page_size page_size
     * @param mixed $time_type time_type
     * @return mixed 返回值
     */
    public function bills_get($start_time,$end_time,$page_no=1,$page_size=40,$time_type=''){}

    /**
     * trade_search
     * @param mixed $shop_id ID
     * @param mixed $shop_name shop_name
     * @param mixed $start_time start_time
     * @param mixed $end_time end_time
     * @param mixed $page page
     * @param mixed $limit limit
     * @return mixed 返回值
     */
    public function trade_search($shop_id,$shop_name,$start_time,$end_time,$page=1,$limit=100){}

    public function bill_account_get($account_id = array()){}

    /**
     * bill_account_get_callback
     * @param mixed $response response
     * @param mixed $callback_params 参数
     * @return mixed 返回值
     */
    public function bill_account_get_callback($response, $callback_params){}

    /**
     * trade_taskresult_get
     * @param mixed $shop_id ID
     * @param mixed $task_id ID
     * @return mixed 返回值
     */
    public function trade_taskresult_get($shop_id,$task_id){}

    /**
     * trade_taskid_get
     * @param mixed $shop_id ID
     * @param mixed $start_time start_time
     * @param mixed $end_time end_time
     * @return mixed 返回值
     */
    public function trade_taskid_get($shop_id,$start_time,$end_time){}

    /**
     * 添加Payment
     * @param mixed $payment payment
     * @return mixed 返回值
     */
    public function addPayment($payment){}

    /**
     * 更新PaymentStatus
     * @param mixed $payment payment
     * @return mixed 返回值
     */
    public function updatePaymentStatus($payment){}

    /**
     * 添加Refund
     * @param mixed $refund refund
     * @return mixed 返回值
     */
    public function addRefund($refund){}

    /**
     * 更新RefundStatus
     * @param mixed $refundinfo refundinfo
     * @return mixed 返回值
     */
    public function updateRefundStatus($refundinfo) {}

    /**
     * 获取RefundMessage
     * @param mixed $refundinfo refundinfo
     * @return mixed 返回结果
     */
    public function getRefundMessage($refundinfo){}

    /**
     * 添加RefundMemo
     * @param mixed $refundinfo refundinfo
     * @return mixed 返回值
     */
    public function addRefundMemo($refundinfo){}

    /**
     * 获取RefundDetail
     * @param mixed $refund_id ID
     * @param mixed $refund_phase refund_phase
     * @param mixed $tid ID
     * @return mixed 返回结果
     */
    public function getRefundDetail($refund_id ,$refund_phase,$tid){}

    protected function _updateRefundApplyStatusApi($status, $refundInfo=null){
        return '';
    }

    protected function _updateRefundApplyStatusParam($refund, $status){
        return array();
    }
    
    /**
     * 格式化平台请求退款返回的状态
     * 
     * @param array $response
     * @return array
     */
    protected function _formatResultStatus($response){
        return $response;
    }

    /**
     * 更新退款单状态
     */
    public function updateRefundApplyStatus($refund,$status,$mod = 'sync')
    {
        $orderModel = app::get('ome')->model('orders');
        $orderData = $orderModel->getList('order_bn', array('order_id'=>$refund['order_id']), 0, 1);
        $order = $orderData[0];
        
        if($refund['shop_type'] == 'luban'){
            $api_method = $this->_updateRefundApplyStatusApi($status, $refund);
        }else{
            $api_method = $this->_updateRefundApplyStatusApi($status);
        }
        
        if ($api_method == '' || $mod != 'sync') {
            return false;
        }
        $params = $this->_updateRefundApplyStatusParam($refund, $status);
        $params['tid'] = $order['order_bn'];
        $title = '店铺('.$this->__channelObj->channel['name'].')更新[交易退款状态],(订单号:'.$order['order_bn'].'退款单号:'.$refund['refund_apply_bn'].')';
        $callback = array();
        $result = $this->__caller->call($api_method, $params, $callback, $title, 10, $order['order_bn']);
        $rs = array();
        if(isset($result['msg']) && $result['msg']){
            $rs['msg'] = $result['msg'];
        }elseif(isset($result['err_msg']) && $result['err_msg']){
            $rs['msg'] = $result['err_msg'];
        }elseif(isset($result['res']) && $result['res']){
            $rs['msg'] = $result['res'];
        }
        $rs['rsp'] = $result['rsp'];
        
        //[抖音平台]格式化退款单状态
        if($rs['rsp'] == 'succ' && $refund['shop_type'] == 'luban'){
            $rs = $this->_formatResultStatus($result);
        }
        
        return $rs;
    }

    /**
     * 
     * @param  [type] $sdf [description]
     * @return [type]      [description]
     */
    public function searchRefund($sdf)
    {
        $title = sprintf('%s退款单查询[%s]', $this->__channelObj->channel['name'], $sdf['refund_apply_bn']);

        $params = [
            'aftersale_id' => $sdf['refund_apply_bn'],
        ];

        $result = $this->__caller->call(SHOP_REFUND_LIST_SEARCH, $params, [], $title, 10, $sdf['order_bn']);

        if ($result['rsp'] == 'succ') {
            $data = @json_decode($result['data'],true);

            $result['data'] = $data;
        }

        return $result;
    }

    public function getRefundStatus($sdf) {return $this->succ('没有接口');}
    
    /**
     * 获取协商退货退款渲染数据
     * 
     * @param array $params 参数数组，包含refund_id等
     * @return array
     */
    public function getNegotiateReturnRenderData($params)
    {
        $title = '获取协商退货退款渲染数据';
        
        // 确保refund_id存在
        if (!isset($params['refund_id'])) {
            return array('rsp' => 'fail', 'msg' => '缺少refund_id参数');
        }
        
        // 获取API方法名
        $api_method = $this->_getNegotiateReturnRenderDataApiMethod();
        if (empty($api_method)) {
            return array('rsp' => 'fail', 'msg' => '未定义API方法');
        }
        
        // 组装参数
        $api_params = $this->_getNegotiateReturnRenderDataApiParams($params);
        
        $res = $this->__caller->call($api_method, $api_params, array(), $title, 10, $params['refund_id']);
        
        if ($res['rsp'] == 'succ') {
            // 如果 data 是字符串，则解析为数组
            if (is_string($res['data'])) {
                $data = @json_decode($res['data'], true);
                if ($data) {
                    $res['data'] = $data;
                }
            }
        }
        
        return $res;
    }
    
    /**
     * 查询是否可发起协商
     * 
     * @param array $params 参数数组，包含refund_id等
     * @return array
     */
    public function getNegotiateCanApply($params)
    {
        $title = '查询是否可发起协商';
        
        // 确保refund_id存在
        if (!isset($params['refund_id'])) {
            return array('rsp' => 'fail', 'msg' => '缺少refund_id参数');
        }
        
        // 获取API方法名
        $api_method = $this->_getNegotiateCanApplyApiMethod();
        if (empty($api_method)) {
            return array('rsp' => 'fail', 'msg' => '未定义API方法');
        }
        
        // 组装参数
        $api_params = $this->_getNegotiateCanApplyApiParams($params);
        
        $res = $this->__caller->call($api_method, $api_params, array(), $title, 10, $params['refund_id']);
        
        if ($res['rsp'] == 'succ') {
            // 如果 data 是字符串，则解析为数组
            if (is_string($res['data'])) {
                $data = @json_decode($res['data'], true);
                if ($data) {
                    $res['data'] = $data;
                }
            }
            // 如果 data 已经是数组，直接使用
        }
        
        return $res;
    }
    
    /**
     * 协商退货退款
     * 
     * @param array $params 参数数组
     * @return array
     */
    public function createRefundNegotiation($params)
    {
        $title = '协商退货退款';
        
        // 验证必需参数
        if (!isset($params['negotiate_data']) || !isset($params['detail'])) {
            return array('rsp' => 'fail', 'msg' => '协商请求缺少必要参数');
        }
        
        // 确保refund_id存在
        if (!isset($params['refund_id'])) {
            return array('rsp' => 'fail', 'msg' => '缺少refund_id参数');
        }
    
        if (!isset($params['source'])) {
            return array('rsp' => 'fail', 'msg' => '缺少来源source参数');
        }
        
        // 获取API方法名
        $api_method = $this->_getRefundNegotiationApiMethod();
        if (empty($api_method)) {
            return array('rsp' => 'fail', 'msg' => '未定义API方法');
        }
        
        // 组装参数
        $api_params = $this->_getRefundNegotiationApiParams($params);
        
        // 调用接口
        $refund_id = $params['refund_id'];
        $res = $this->__caller->call($api_method, $api_params, array(), $title, 10, $refund_id);
        
        if ($res['rsp'] == 'succ') {
            // 如果 data 是字符串，则解析为数组
            if (is_string($res['data'])) {
                $data = @json_decode($res['data'], true);
                if ($data) {
                    $res['data'] = $data;
                }
            }
        }
        
        return $res;
    }
    
    /**
     * 获取协商退货退款API方法名
     * 
     * @return string
     */
    protected function _getRefundNegotiationApiMethod()
    {
        return '';
    }
    
    /**
     * 获取协商退货退款API参数
     * 
     * @param array $params 包含negotiate_data, return_detail, return_id的完整参数
     * @return array
     */
    protected function _getRefundNegotiationApiParams($params)
    {
        return $params;
    }
    
    /**
     * 获取协商退货退款渲染数据API方法名
     * 
     * @return string
     */
    protected function _getNegotiateReturnRenderDataApiMethod()
    {
        return '';
    }
    
    /**
     * 获取协商退货退款渲染数据API参数
     * 
     * @param array $params 包含refund_id等参数
     * @return array
     */
    protected function _getNegotiateReturnRenderDataApiParams($params)
    {
        return $params;
    }
    
    /**
     * 获取协商是否可发起API方法名
     * 
     * @return string
     */
    protected function _getNegotiateCanApplyApiMethod()
    {
        return '';
    }
    
    /**
     * 获取协商是否可发起API参数
     * 
     * @param array $params 包含refund_id等参数
     * @return array
     */
    protected function _getNegotiateCanApplyApiParams($params)
    {
        return $params;
    }
}
