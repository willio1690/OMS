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
class erpapi_shop_matrix_360buy_request_finance extends erpapi_shop_request_finance
{
    /**
     * 添加Refund
     * @param mixed $refund refund
     * @return mixed 返回值
     */

    public function addRefund($refund){
        $rs = array('rsp'=>'fail','msg'=>'','data'=>'');
        if (!$refund) {
            $rs['msg'] = 'no refund'; return $rs;
        }

        $params = array();

        if($refund['is_aftersale_refund']){
            $api_name = STORE_AG_LOGISTICS_WAREHOUSE_UPDATE;

            $title = '退货入仓回传(订单号:'.$refund['order_bn'].'退款单号:'.$refund['refund_bn'].')';

            $order = app::get('ome')->model('orders')->db_dump(array('order_bn'=>$refund['order_bn']),'ship_name,member_id');

            $member = array ();
            if ($order['member_id']) {
            	$member = app::get('ome')->model('members')->db_dump($order['member_id'],'uname');
            }


            $refundOriginalObj = app::get('ome')->model('return_product');
            $refundOriginalInfo = $refundOriginalObj->db_dump($refund['return_id'], 'return_bn');

            $ship_name = $order['ship_name'];
            if ($ship_name && $encrytPos = strpos($ship_name , '>>')){
                $ship_name = substr($ship_name , 0, $encrytPos);
            }

            $opinfo = kernel::single('ome_func')->getDesktopUser();
            $params = array(
				// 'buId'        => $refund['return_bn'] ? $refund['return_bn'] : $refundOriginalInfo['return_bn'],
                'operatePin'      => $opinfo['op_id'],
                'operateNick'     => $opinfo['op_name'],
                'serviceId'       => $refund['return_bn'] ? $refund['return_bn'] : $refundOriginalInfo['return_bn'],
                'tid'             => $refund['order_bn'],
                'receivePin'      => $ship_name,
                'receiveName'     => $ship_name,
                'packingState'    => '20',
                'qualityState'    => '30',
                'invoiceRecord'   => '20',
                'judgmentReason'  => '125',
                'accessoryOrGift' => '0',
                'appearanceState' => '20',
                'receiveRemark'   => '售后退款',
            );
        }else{
            $api_name = STORE_AG_SENDGOODS_CANCEL;
            $title = '取消发货(订单号:'.$refund['order_bn'].'退款单号:'.$refund['refund_bn'].')';

            $params = array(
				'refund_id' => $refund['refund_bn'],
				'tid'       => $refund['order_bn'],
				'status'    => $refund['cancel_dly_status'] ? $refund['cancel_dly_status'] : 'FAIL', //取消发货状态成功SUCCESS
            );
        }

        $callback = array(
            'class' => get_class($this),
            'method' => 'addRefundCallback',
            'params' => array(
				'shop_id'         => $refund['shop_id'],
				'tid'             => $refund['order_bn'],
				'refund_apply_id' => $refund['apply_id'],
				'params'          => $refund,
				'obj_type'        => 'AG',
				'obj_bn'          => $params['refund_id'],
				'method'          => $api_name,

            )
        );

        return $this->__caller->call($api_name,$params,$callback,$title,10,$refund['order_bn']);
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

        $failApiModel = app::get('erpapi')->model('api_fail');
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
            if ($order_id) kernel::single('ome_order_func')->update_order_pay_status($order_id,true, __CLASS__.'::'.__FUNCTION__);
            #bugfix:解决如果退款单请求先到并生成单据于此同时由于网络超时造成退款申请失败，从而造成退款申请单状态错误问题。
            $refund_applyObj = app::get('ome')->model('refund_apply');
            $refundapply_detail = $refund_applyObj->getList('refund_apply_bn',array('apply_id'=>$refund_apply_id));
            $refundsObj = app::get('ome')->model('refunds');
            $refunds_detail = $refundsObj->getList('refund_id',array('refund_bn'=>$refundapply_detail[0]['refund_apply_bn'],'status'=>'succ'));
            if(!$refunds_detail){
                $refund_applyObj->update(array('status'=>'6'), array('status|notin'=>array('4','3'),'apply_id'=>$refund_apply_id));
                //操作日志
                $oOperation_log = app::get('ome')->model('operation_log');
                $oOperation_log->write_log('order_refund@ome',$order_id,'订单:'.$order_bn.'发起退款请求,前端拒绝退款，退款失败');
            }
        }
        $failApiModel->publish_api_fail($callback_params['method'],$callback_params,$response);

        return $this->callback($response, $callback_params);
    }

    /**
     * 获取RefundMessage
     * @param mixed $refundinfo refundinfo
     * @return mixed 返回结果
     */
    public function getRefundMessage($refundinfo){
        if (!$refundinfo['refund_bn']) return false;
        $params = array(
            'refId'=>  $refundinfo['refund_bn'],
        );

        $title = '获取店铺退款凭证';
        $result = $this->__caller->call(SHOP_REFUND_NEGOTIATION_GET, $params, array(), $title, 10, $refundinfo['refund_bn']);

        if($result['data']) {
            $data = json_decode($result['data'], 1);
            $result['data'] = [];
            if(is_array($data) && is_array($data['data']) && is_array($data['data']['data'])) {
                $statusText = [
                    '10' => '协商完成-客户接受',
                    '20' => '协商完成-客户拒绝',
                    '30' => '协商中-客户未处理',
                    '40' => '协商完成-超时关闭',
                    '50' => '协商完成-服务单审核关闭',
                    '60' => '协商完成-多聊两句',
                    '70' => '协商完成-交易纠纷',
                    '80' => '协商完成-修改服务单',
                    '90' => '协商完成-客户沟通'
                ];
                $refundMessage = $data['data']['data'];
                $refundMessage['negotiationStatusText'] = $statusText[$refundMessage['negotiationStatus']];
                if(empty($refundMessage['negotiationCloseDetail'])) {
                    $refundMessage['negotiationCloseDetail'] = [];
                }
                $result['data']  = array (
                    'refund_messages' => array (
                        'refund_message' => $refundMessage
                    ),
                );
            }
        }

        return $result;
    }
}