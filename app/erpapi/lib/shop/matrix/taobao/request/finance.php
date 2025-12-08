<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class erpapi_shop_matrix_taobao_request_finance extends erpapi_shop_request_finance
{
    /**
     * 实时获取[支付宝]交易记录
     * @access public
     * @param String $start_time 开始时间
     * @param String $end_time 结束时间
     * @param Int $page 当前请求页码
     * @param Int $limit 每页请求数量
     */
    public function trade_search($start_time,$end_time,$page=1,$limit=100,$alipay_order_no='',$merchant_order_no=''){
        $rs = array('rsp'=>'fail','msg'=>'','msg_code'=>'','msg_id'=>'','data'=>'');
        if ( empty($start_time) || empty($end_time)){
            $rs['msg'] = 'start_time,end_time不能为空';
            return $rs;
        }

        $params = array(
            // 'order_status' => 'ACC_FINISHED',#TODO:不获取交易数据，即在线支付的,交易数据交由任务号接口获取，如果不指定,则只能在程序段进行过滤
            'start_time' => $start_time,
            'end_time' => $end_time,
            'page_no' => $page,
            'page_size' => $limit,
        );
        if($alipay_order_no)$params['alipay_order_no']=$alipay_order_no;
        if($merchant_order_no)$params['merchant_order_no']=$merchant_order_no;
        $callback = array();//实时接口不需要设置
        
        $title = sprintf('获取[支付宝]交易记录%s~%s',$start_time,$end_time);

        $result = $this->__caller->call(SHOP_USER_TRADE_SEARCH,$params,$callback,$title,10,$this->__channelObj->channel['name']);

        #错误代码：w01001 为时间跨度不能超过7天
        $rs['rsp'] = $result['rsp'] == 'success' ? 'succ' : $result['rsp'];
        $rs['msg'] = $result['err_msg'];
        $rs['msg_code'] = $result['err_code'];
        $rs['msg_id'] = $result['msg_id'];

        if (isset($result['data']) && $result['data']){
            $data = json_decode(iconv('gbk','utf-8',$result['data']),true);
            $rs['data'] = array(
                'total_results' => $data['total_results'],
                'total_pages'   => $data['total_pages'],
                'total_records' => $data['trade_records'] ? $data['trade_records']['trade_record'] : array(),//交易记录列表
            );
        }else{
            $rs['data'] = array();
        }
        return $rs;
    }

    #获取退款凭证
    /**
     * 获取RefundMessage
     * @param mixed $refundinfo refundinfo
     * @return mixed 返回结果
     */
    public function getRefundMessage($refundinfo){
        if (!$refundinfo['refund_bn']) return false;
        $params = array(
            'refund_id'=>  $refundinfo['refund_bn'],
        );
        $title = '获取店铺退款凭证';
        $result = $this->__caller->call(SHOP_GET_REFUND_MESSAGE, $params, array(), $title, 10, $refundinfo['refund_bn']);
        if($result['data']) {
            $result['data'] = json_decode($result['data'], true);
        }
        return $result;
    }

    /**
     * 添加RefundMemo
     * @param mixed $refundinfo refundinfo
     * @return mixed 返回值
     */
    public function addRefundMemo($refundinfo){
        $orderModel = app::get('ome')->model('orders');
        $order = $orderModel->dump($refundinfo['order_id'], 'order_bn');
        $params = array();
        $params['refund_id'] = $refundinfo['refund_apply_bn'];
        $params['content'] = $refundinfo['content'];
        $params['image'] = $refundinfo['imagebinary'];
        $callback = array(
            'class' => get_class($this),
            'method' => 'callback',
        );
        $title = '店铺('.$this->__channelObj->channel['name'].')添加[退款留言]'.'(订单号:'.$order['order_bn'].'退款单号:'.$refundinfo['refund_apply_bn'].')';;
        $rs = $this->__caller->call(SHOP_ADD_REFUND_MESSAGE,$params,$callback,$title,10,$order['order_bn']);
        return $rs;
    }

    #单拉获取退款单详情
    /**
     * 获取RefundDetail
     * @param mixed $refund_id ID
     * @param mixed $refund_phase refund_phase
     * @param mixed $tid ID
     * @return mixed 返回结果
     */
    public function getRefundDetail($refund_id ,$refund_phase,$tid) {
        $params = array();
        $params['refund_id'] = $refund_id;
        $params['refund_phase'] = $refund_phase;
        $params['tid'] = $tid;
        $title = "店铺(".$this->__channelObj->channel['name'].")获取前端店铺".$tid."的退款单详情";
        $rsp = $this->__caller->call(SHOP_GET_TRADE_REFUND_RPC,$params,array(),$title,10,$tid);
        if($rsp['data']) {
            $rsp['data'] = json_decode($rsp['data'], true);
        }
        return $rsp;
    }

    protected function _updateRefundApplyStatusApi($status, $refundInfo=null){
        $api_method = '';
        switch($status){
            case '3':
                $api_method = SHOP_REFUSE_REFUND;
                break;
        }
        return $api_method;
    }

    protected function _updateRefundApplyStatusParam($refund,$status){
        $oRefund_taobao = app::get('ome')->model('refund_apply_taobao');
        $refundData = $oRefund_taobao->getList('oid',array('refund_apply_bn'=>$refund['refund_apply_bn']), 0, 1);
        $refundRow = $refundData[0];
        $params = array(
            'refund_id'  =>$refund['refund_apply_bn'],
            'refuse_proof'=>$refund['refuse_proof'],
            'refuse_message'=>$refund['refuse_message'],
        );
        if ($status == '3') {#退款单拒绝
            $params['oid'] = $refundRow['oid'];
        }
        return $params;
    }

    /**
     * 财务科目
     * 
     * @return void
     * @author
     * */
    public function bill_account_get($account_id = array())
    {
        $rs = array('rsp'=>'fail','msg'=>'未找到接口','msg_code'=>'','msg_id'=>'','data'=>'');
        return $rs;
        $denytime = array(
            0 => array(mktime(9,30,0,date('m'),date('d'),date('Y')),mktime(11,0,0,date('m'),date('d'),date('Y'))),
            1 => array(mktime(14,0,0,date('m'),date('d'),date('Y')),mktime(17,0,0,date('m'),date('d'),date('Y'))),
            2 => array(mktime(20,0,0,date('m'),date('d'),date('Y')),mktime(22,30,0,date('m'),date('d'),date('Y'))),
            2 => array(mktime(1,0,0,date('m'),date('d'),date('Y')),mktime(3,0,0,date('m'),date('d'),date('Y'))),
        );

        $now = time();
        foreach ($denytime as $value) {
            if ($value[0]<=$now && $now<=$value[1]) {
                $rs['msg'] = 'deny time';
                // return $rs;
            }
        }

        $params = array(
            'fields' => 'account_id,account_code,,account_name,account_type,related_order,gmt_create,gmt_modified,status',
        );

        if ($account_id) $params['aids'] = implode(',', $account_id);

        $title = '店铺[' . $this->__channelObj->channel['name'] . ']获取财务科目';

        // $callback = array(
        //     'class' => get_class($this),
        //     'method' => 'bill_account_get_callback',
        // );

        $return = $this->__caller->call(SHOP_BILL_ACCOUNTS_GET,$params,null,$title,10);

        return $this->bill_account_get_callback($return,null);
    }

    private $outer_account_id = array('3200052031','3200053031','3200013031','3200058031','3200060031','3200059031','3200061031','3200011031','3200063031','3200036031','3200065031','3200066031','3210085031','3200038031','3200062031','3200102041','3200034031','3200050031','3200037031','3200030031','3200021031','3200039031','3122765031','3200084031','3200084031','3200032031','3200027031','3200045031','3200031031');

        /**
     * bill_account_get_callback
     * @param mixed $response response
     * @param mixed $callback_params 参数
     * @return mixed 返回值
     */
    public function bill_account_get_callback($response, $callback_params)
    {
        if ($response['rsp'] == 'succ' && $response['data']) {
            $data = @json_decode($response['data'], true);
            $feeTypeModel = app::get('finance')->model('bill_fee_type');
            $feeItemModel = app::get('finance')->model('bill_fee_item');
            foreach ((array) $data['accounts']['top_account_dto'] as $account) {
                // 判断科目类型
                $fee_type = $feeTypeModel->dump(array('outer_account_type' => $account['account_type']));
                if (!$fee_type) continue;

                // 判断科目是否存在
                $fee_item = $feeItemModel->dump(array('fee_item_code' => $account['account_code'], 'channel' => 'tmall'));
                $item = array(
                    'fee_item_id'      => $fee_item ? $fee_item['fee_item_id'] : null,
                    'fee_type_id'      => $fee_type['fee_type_id'],
                    'fee_item'         => $account['account_name'],
                    'inlay'            => 'true',
                    'channel'          => 'tmall',
                    'createtime'       => time(),
                    'fee_item_code'    => $account['account_code'],
                    'outer_account_id' => $account['account_id'],
                    'related_order'    => ($fee_type['fee_type_id'] == '1' || in_array($account['account_id'], $this->outer_account_id)) ? 'true' : 'false',
                );
                $feeItemModel->save($item);
            }
        }

        return $this->callback($response, $callback_params);
    }

    /**
     * 添加Refund
     * @param mixed $refund refund
     * @return mixed 返回值
     */
    public function addRefund($refund){
        $rs = array('rsp'=>'fail','msg'=>'','data'=>'');
        if (!$refund) {
            $rs['msg'] = 'no refund';
            return $rs;
        }
    
        $params = array();
    
        if($refund['is_aftersale_refund']){
            $api_name = STORE_AG_LOGISTICS_WAREHOUSE_UPDATE;
            $title = '店铺('.$this->__channelObj->channel['name'].')退货入仓回传(订单号:'.$refund['order_bn'].'退款单号:'.$refund['refund_bn'].')';
    
            $refundOriginalObj = app::get('ome')->model('return_product');
            $refundOriginalInfo = $refundOriginalObj->getList('return_bn', array('return_id'=>$refund['return_id']) , 0 , 1);
            
            
            
            $params = array(
                'refund_id' => $refund['return_bn'] ? $refund['return_bn'] : $refundOriginalInfo[0]['return_bn'],
                'warehouse_status' => 1, //退货已入库标记
            );
        }else{
            $api_name = STORE_AG_SENDGOODS_CANCEL;
            $title = '店铺('.$this->__channelObj->channel['name'].')取消发货(订单号:'.$refund['order_bn'].'退款单号:'.$refund['refund_bn'].')';
    
            $params = array(
                'refund_id' => $refund['refund_bn'],
                'tid' => $refund['order_bn'],
                'status' => $refund['cancel_dly_status'] ? $refund['cancel_dly_status'] : 'FAIL', //取消发货状态成功SUC
            );
        }
        $callback = array(
            'class' => get_class($this),
            'method' => 'addRefundCallback',
            'params' => array(
                'shop_id' => $refund['shop_id'],
                'tid' => $refund['order_bn'],
                'refund_apply_id' => $refund['apply_id']
            )
        );
        
        $rs = $this->__caller->call($api_name,$params,$callback,$title,10,$refund['order_bn']);
        return $rs;
    }

    /**
     * 添加RefundCallback
     * @param mixed $response response
     * @param mixed $callback_params 参数
     * @return mixed 返回值
     */
    public function addRefundCallback($response, $callback_params)
    {
        $status = $response['rsp'];
        if ($status != 'succ'){
            $shop_id = $callback_params['shop_id'];
            $order_bn = $callback_params['tid'];
            $refund_apply_id = $callback_params['refund_apply_id'];
            $oOrder = app::get('ome')->model('orders');
            $order_detail = $oOrder->dump(array('order_bn'=>$order_bn,'shop_id'=>$shop_id), 'order_id,pay_status');
            if (!$order_detail) {
                $oOrder = app::get('archive')->model('orders');
                $order_detail = $oOrder->dump(array('order_bn'=>$order_bn,'shop_id'=>$shop_id), 'order_id,pay_status');
            }
            $order_id = $order_detail['order_id'];
            //状态回滚，变成已支付/部分付款/部分退款
            kernel::single('ome_order_func')->update_order_pay_status($order_id, true, __CLASS__.'::'.__FUNCTION__);
            #bugfix:解决如果退款单请求先到并生成单据于此同时由于网络超时造成退款申请失败，从而造成退款申请单状态错误问题。
            $refund_applyObj = app::get('ome')->model('refund_apply');
            $refundapply_detail = $refund_applyObj->getList('refund_apply_bn',array('apply_id'=>$refund_apply_id));
            $refundsObj = app::get('ome')->model('refunds');
            $refunds_detail = $refundsObj->getList('refund_id',array('refund_bn'=>$refundapply_detail[0]['refund_apply_bn'],'status'=>'succ'));
            if(!$refunds_detail){
                $refund_applyObj->update(array('status'=>'6'), array('status|notin'=>array('4'),'apply_id'=>$refund_apply_id));
                //操作日志
                $oOperation_log = app::get('ome')->model('operation_log');
                $oOperation_log->write_log('order_refund@ome',$order_id,'订单:'.$order_bn.'发起退款请求,前端拒绝退款，退款失败');
            }
        }
        return $this->callback($response, $callback_params);
    }

    protected $operation_text = [
        'SELLER_REFUSE' => '拒绝',
        'APPLY_DELIVERY_INTERCEPT_V2' => '拦截包裹',
        'NEGOTIATE_TO_RETURN_AND_REFUND' => '协商退货退款',
    ];

    /**
     * refundDetailGet
     * @param mixed $sdf sdf
     * @return mixed 返回值
     */
    public function refundDetailGet($sdf) {
        $params = array(
            'refund_id' => $sdf['refund_apply_bn'],
            'fields' => 'allowedOperations,refund_version',
        );
        $title = '退款详情页渲染';

        $rs = $this->__caller->call(SHOP_REFUND_DETAIL_GET,$params,array(),$title, 6, $sdf['refund_apply_bn']);

        if ($rs['rsp'] == 'succ') {
            $data = json_decode($rs['data'],true);
            $rs['data'] = [];
            if($data['detail']['refund_version']) {
                $rs['data']['refund_version'] = $data['detail']['refund_version'];
                $rs['data']['operation'] = [];
                foreach ($data['detail']['allowed_operations']['operation'] as $key => $value) {
                    $rs['data']['operation'][$value['operation_code']] = $value;
                }
                $rs['data']['not_operation'] = [];
                foreach ($data['detail']['not_allowed_operations']['operation'] as $key => $value) {
                    $value['operation_text'] = (string) $this->operation_text[$value['operation_code']];
                    $rs['data']['not_operation'][$value['operation_code']] = $value;
                }
            } else {
                $rs['rsp'] = 'fail';
            }
        }

        return $rs;
    }

    /**
     * intercept
     * @param mixed $sdf sdf
     * @return mixed 返回值
     */
    public function intercept($sdf) {
        $params = array(
            'refund_id' => $sdf['refund_apply_bn'],
            'refund_version' => $sdf['refund_version'],
        );
        $title = '发起拦截';

        $rs = $this->__caller->call(SHOP_REFUND_INTERCEPT,$params,array(),$title, 6, $sdf['refund_apply_bn']);

        return $rs;
    }

    /**
     * negotiatereturnRender
     * @param mixed $sdf sdf
     * @return mixed 返回值
     */
    public function negotiatereturnRender($sdf) {
        $params = array(
            'refund_id' => $sdf['refund_apply_bn'],
        );
        $title = '协商退货退款渲染';

        $rs = $this->__caller->call(SHOP_REFUND_NEGOTIATERETURN_RENDER,$params,array(),$title, 6, $sdf['refund_apply_bn']);
        if($rs['data']) {
            $data = json_decode($rs['data'], 1);
            $rs['data'] = $data['data'];
            if($rs['data']['max_refund_fee']['max_refund_fee']) {
                $rs['data']['max_refund_fee']['max_refund_fee'] = $rs['data']['max_refund_fee']['max_refund_fee'] / 100;
            }
        }
        return $rs;
    }

    /**
     * negotiatereturn
     * @param mixed $sdf sdf
     * @return mixed 返回值
     */
    public function negotiatereturn($sdf) {
        $params = array(
            'refund_id' => $sdf['refund_id'],
            'refund_version' => $sdf['refund_version'],
            'refund_fee' => round($sdf['refund_fee'] * 100),
            'address_id' => $sdf['address_id'],
        );
        $title = '协商退货退款';

        $rs = $this->__caller->call(SHOP_REFUND_NEGOTIATERETURN,$params,array(),$title, 6, $sdf['refund_id']);

        return $rs;
    }

    /**
     * 获取NotifyOid
     * @param mixed $sdf sdf
     * @return mixed 返回结果
     */
    public function getNotifyOid($sdf) {
        $title = '退款消息查询';

        $params = [
            'tid' => $sdf['order_bn'],
        ];

        $result = $this->__caller->call(SHOP_REFUND_NOTIFY_GET, $params, [], $title, 10, $sdf['order_bn']);

        if ($result['rsp'] == 'succ' && $result['data']) {
            $data = @json_decode($result['data'],true);
            $result['data'] = [];
            if($data['data']) {
                $result['data'] = $data['data'];
            }
        }

        return $result;
    }

    /**
     * 获取RefundStatus
     * @param mixed $sdf sdf
     * @return mixed 返回结果
     */
    public function getRefundStatus($sdf) {
        $title = '退款状态查询';

        $params = [
            'tid' => $sdf['order_bn'],
        ];

        $result = $this->__caller->call(SHOP_REFUND_STATUS_GET, $params, [], $title, 10, $sdf['order_bn']);

        if ($result['rsp'] == 'succ' && $result['data']) {
            $data = @json_decode($result['data'],true);
            $result['data'] = [];
            if($data['data']['result_list']['query_refund_status_response']) {
                $result['data'] = $data['data']['result_list']['query_refund_status_response'];
            }
        }

        return $result;
    }
    
    /**
     * 获取协商退货退款API方法名
     * 
     * @return string
     */
    protected function _getRefundNegotiationApiMethod()
    {
        return SHOP_REFUND_NEGOTIATION_CREATE;
    }
    
    /**
     * 获取协商退货退款API参数
     * 
     * @param array $params 包含negotiate_data, detail, return_id的完整参数
     * @return array
     */
    protected function _getRefundNegotiationApiParams($params)
    {
        $negotiate_data = $params['negotiate_data'];
        $refund_id = $params['refund_id'];
        
        // 淘宝协商退货退款参数组装
        $api_params = array(
            'refund_id' => $refund_id,
            'refund_version' => $negotiate_data['refund_version'] ?: (time() * 1000),
            'negotiate_type' => $negotiate_data['negotiate_type'],
            'refund_type' => (int)$negotiate_data['refund_type_code'], // 使用用户选择的退款类型
            'reason_id' => (int)$negotiate_data['negotiate_reason_id'],
            'negotiate_text' => $negotiate_data['negotiate_text'],
        );
        if(isset($params['source']) && $params['source'] == 'return_product'){
            $api_params['negotiate_version'] = 'newNegotiate';
        }
        
        // 添加可选参数
        if (!empty($negotiate_data['negotiate_refund_fee'])) {
            $api_params['refund_fee'] = intval($negotiate_data['negotiate_refund_fee'] * 100);
        }
        
        if (!empty($negotiate_data['negotiate_address_id'])) {
            $api_params['address_id'] = (int)$negotiate_data['negotiate_address_id'];
        }
        
        return $api_params;
    }
    
    /**
     * 获取协商退货退款渲染数据API方法名
     * 
     * @return string
     */
    protected function _getNegotiateReturnRenderDataApiMethod()
    {
        return SHOP_REFUND_NEGOTIATION_GET;
    }
    
    /**
     * 获取协商退货退款渲染数据API参数
     * 
     * @param array $params 包含refund_id等参数
     * @return array
     */
    protected function _getNegotiateReturnRenderDataApiParams($params)
    {
        return array(
            'refund_id' => $params['refund_id'],
            'negotiate_version' => $params['negotiate_version'],//协商版本
        );
    }
    
    /**
     * 获取协商是否可发起API方法名
     * 
     * @return string
     */
    protected function _getNegotiateCanApplyApiMethod()
    {
        return SHOP_REFUND_NEGOTIATE_CANAPPLY_GET;
    }
    
    /**
     * 获取协商是否可发起API参数
     * 
     * @param array $params 包含refund_id等参数
     * @return array
     */
    protected function _getNegotiateCanApplyApiParams($params)
    {
        return array(
            'refund_id' => $params['refund_id'],
        );
    }
}
