<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * 微盟售后
 * Class erpapi_shop_matrix_weimobv_request_aftersale
 */
class erpapi_shop_matrix_weimobv_request_aftersale extends erpapi_shop_request_aftersale {
    protected function __afterSaleApi($status, $returnInfo=null) {
        switch( $status ){
            case '3':
                $api_method = SHOP_AGREE_RETURN_GOOD;
                break;
            case '4':
                $api_method = SHOP_CHECK_REFUND_GOOD;
                break;
            case '5':
                $api_method = SHOP_REFUSE_CHANGE_I_GOOD_TMALL;
                break;
            default :
                $api_method = '';
                break;
        }
        return $api_method;
    }

    protected function __formatAfterSaleParams($aftersale,$status) {
        $params = array(
            'refund_id'=>$aftersale['return_bn'],
        );
        return $params;
    }

    /**
     * 卖家确认收货
     * @param $data
     */

    public function returnGoodsConfirm($sdf){
        $title = '售后确认收货['.$sdf['return_bn'].']';
        $data = array(
            'refund_id' => $sdf['return_bn']
        );
        $this->__caller->call(SHOP_RETURN_GOOD_CONFIRM, $data, array(), $title, 10, $sdf['return_bn']);
    }
    
    /**
     * [换货]卖家确认收货
     */
    public function consignGoods($data){
        $title = '店铺('.$this->__channelObj->channel['name'].')换货订单卖家发货,(售后单号:'.$data['dispute_id'].')';
        
        $params = array(
            'dispute_id'             => $data['order_bn'],
            'logistics_no'           => $data['logistics_no'],
            'company_code'           => $data['corp_type'],
            'logistics_company_name' => $data['logistics_company_name'],
        );
        $result = $this->__caller->call(SHOP_EXCHANGE_CONSIGNGOODS, $params, array(), $title, 10, $data['order_bn']);
        if(isset($result['msg']) && $result['msg']){
            $rs['msg'] = $result['msg'];
        }elseif(isset($result['err_msg']) && $result['err_msg']){
            $rs['msg'] = $result['err_msg'];
        }elseif(isset($result['res']) && $result['res']){
            $rs['msg'] = $result['res'];
        }
        $rs['rsp'] = $result['rsp'];
        
        
        $rs['data'] = $result['data'] ? $result['data'] : array();
        // 发货记录
        
        $log_id = uniqid($_SERVER['HOSTNAME']);
        $status = ($rs['rsp']=='succ') ? 'succ' : 'fail';
        $log = array(
            'shopId'           => $this->__channelObj->channel['shop_id'],
            'ownerId'          => '16777215',
            'orderBn'          => $data['order_bn'],
            'deliveryCode'     => $params['logistics_no'],
            'deliveryCropCode' => $params['company_code'],
            'deliveryCropName' => $params['logistics_company_name'],
            'receiveTime'      => time(),
            'status'           => $status,
            'updateTime'       => time(),
            'message'          => $rs['msg'] ? $rs['msg'] : '成功',
            'log_id'           => $log_id,
        );
        
        $shipmentLogModel = app::get('ome')->model('shipment_log');
        $shipmentLogModel->insert($log);
        if ($data['order_id']){
            $orderModel    = app::get('ome')->model('orders');
            
            $updateOrderData = array(
                'sync'           => $status,
                'up_time'        => time(),
            
            );
            $orderModel->update($updateOrderData,array('order_id'=>$data['order_id'],'sync|noequal'=>'succ'));
        }
        return $rs;
    }
}