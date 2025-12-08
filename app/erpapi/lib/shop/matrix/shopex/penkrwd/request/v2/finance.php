<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class erpapi_shop_matrix_shopex_penkrwd_request_v2_finance extends erpapi_shop_matrix_shopex_fy_request_finance {

    protected function _updateRefundApplyStatusApi($status, $refundInfo=null){
        $api_method = '';
        switch($status){
            case '2':
            case '3':
                $api_method = SHOP_ADD_REFUND_RPC;#更新退款申请状态
                break;
        }
        return $api_method;
    }
    protected function _setParams($refund){
        $params=parent::_setParams($refund);
        $params['refund_type']=$refund['refund_type'];
        $params['status']= 'refund';
        $params['refund_type']='apply';
        if($refund['apply_id']){
            $refund_apply_info = app::get('ome')->model('refund_apply')->getList('return_id,memo',array('apply_id'=>$refund['apply_id']));
        }

        if($refund_apply_info[0]['return_id']){
            $return_info = app::get('ome')->model('return_product')->getList('memo',array('return_id'=>$refund_apply_info[0]['return_id']), 0, 1);
        }
        if($return_info[0]['memo']){
            $params['memo']=$return_info[0]['memo'];//全民分销需要通过备注获取退款商品(售后单生成)
        }else{
            $params['memo']=$refund_apply_info[0]['memo'];//全民分销需要通过备注获取退款商品(退款申请单生成)
        }
        return $params;
    }
    protected function _updateRefundApplyStatusParam($refund, $status){
        $params = array();
        if($status==3||$status==2){
            $params['memo']  = $refund['memo'];
            $params['refund_type']  = 'apply';
            $params['status'] = $status==2 ? 'succ':'cancel';
        }
        return $params;
    }

}