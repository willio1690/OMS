<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * sunjing
 */
class erpapi_shop_matrix_pinduoduo_request_finance extends erpapi_shop_request_finance
{
    


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
            $refundOriginalInfo = $refundOriginalObj->getList('return_bn,platform_aftersale_bn', array('return_id'=>$refund['return_id']) , 0 , 1);

            $params = array(
                'refund_id'         => $refund['return_bn'] ? $refund['return_bn'] : $refundOriginalInfo[0]['return_bn'],
                'tid'               => $refund['order_bn'],
                'warehouse_status'  => 1, //退货已入库标记
                'logistics_no'      => $refund['logistics_no'],
            );

            if($refundOriginalInfo[0]['platform_aftersale_bn']){
                $params['refund_id'] = $refundOriginalInfo[0]['platform_aftersale_bn'];
            }
        }else{
            $api_name = STORE_AG_SENDGOODS_CANCEL;
            $title = '店铺('.$this->__channelObj->channel['name'].')取消发货(订单号:'.$refund['order_bn'].'退款单号:'.$refund['refund_bn'].')';

            $params = array(
                'refund_id' => $refund['refund_bn'],
                'tid' => $refund['order_bn'],
                'status' => $refund['cancel_dly_status'] ? $refund['cancel_dly_status'] : 'FAIL', //取消发货状态成功SUCCESS

            );
        }

       

        $rs = $this->__caller->call($api_name,$params,array(),$title,10,$refund['order_bn']);
        return $rs;
    }


}