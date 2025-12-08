<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * @author ykm 2022/6/30 16:04:01
 * @describe
 */
class erpapi_shop_matrix_tmall_request_maochao_bookingrefund extends erpapi_shop_request_bookingrefund {
    
    /**
     * orderMsgUpdate
     * @param mixed $sdf sdf
     * @return mixed 返回值
     */

    public function orderMsgUpdate($sdf){
        $order = $sdf['order'];
        $orderExtend = $sdf['order_extend'];
        $extend_field = @json_decode($orderExtend['extend_field'], 1);
        $params = [
            "supplier_id"=> $extend_field['supplierId'],
            "biz_order_code"=> $order['order_bn'],
            "business_model"=> $extend_field['businessModel'],
            'cancel_result'=> $order['pause_status'] ? 'true' : 'false',
            'cancel_reason'=> $order['pause_fail_msg'] ? : ($order['pause_status'] ? '' : '发货了')
        ];
        $title = '订单退款回传平台';
        $rsp = $this->__caller->call(SHOP_SUPPLIER_ORDER_CANCEL_BACK, $params, array(), $title, 10, $params['biz_order_code']);
        if($rsp['data']) {
            $rsp['data'] = json_decode($rsp['data'], 1);
        }
        $args = func_get_args();
        $retry = array(
            'rsp' => $rsp['rsp'],
            'err_msg' => $rsp['err_msg'],
            'msg_id' => $rsp['msg_id'],
            'obj_bn' => $order['order_bn'],
            'obj_type' => 'bookingrefund_back',
            'channel' => 'shop',
            'channel_id' => $this->__channelObj->channel['channel_id'],
            'method' => 'bookingrefund_orderMsgUpdate',
            'args' => $args,
        );
        $retry['rsp'] = $rsp['rsp'] == 'succ' 
                            ? ($rsp['data']['retry'] ? 'fail' : 'succ')
                            : 'fail';
        app::get('erpapi')->model('api_fail')->saveSyncRequest($retry);
        return $rsp;
    }
}