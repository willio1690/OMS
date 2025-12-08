<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * 苏宁退款
 * Class erpapi_shop_matrix_kaola_request_finance
 */
class erpapi_shop_matrix_suning4zy_request_finance extends erpapi_shop_request_finance{

    protected function _updateRefundApplyStatusApi($status, $refundInfo=null){
        $api_method = '';
        switch($status){
            case '2':
                $api_method = SHOP_AGREE_REFUND;
                break;
            case '3':
                $api_method = SHOP_REFUSE_REFUND;
                break;
        }
        return $api_method;
    }

    protected function _updateRefundApplyStatusParam($refund,$status){
        $oOrder = app::get('ome')->model('orders');

        $params = array(
            "refund_id" => $refund["refund_apply_bn"]
        );
        if ($status == '3') { //拒绝
            $order = $oOrder->getList('tostr',array('order_id'=>$refund['order_id']),0,-1);
            $platform = json_decode($order[0]['tostr'],true);
            //如果平台订单来自天猫
            if($platform['platform']=='tmall'){
                $pic_proof = $refund['refuse_proof'];
                $file_name = substr($pic_proof,strripos($pic_proof,'/')+1);
                $params['pic_proof'] = base64_encode($pic_proof);
                $params['file_name'] = $file_name;
            }
            $params['reason'] = $refund['refuse_message'];
            $params['refund_id'] = $refund['refund_apply_bn'];
        }
        return $params;
    }
   
}
