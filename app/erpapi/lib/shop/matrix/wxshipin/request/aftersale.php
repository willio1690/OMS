<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * @Author: xueding@shopex.cn
 * @Date: 2023/4/20
 * @Describe: 售后请求
 */
class erpapi_shop_matrix_wxshipin_request_aftersale extends erpapi_shop_request_aftersale
{

    protected function __afterSaleApi($status, $returnInfo=null) {
        switch( $status ){
            
            case '9'://拒绝退货
                $api_method = SHOP_REFUSE_CHANGE_I_GOOD_TMALL;
                break;
            case '6'://同意退货
                $api_method = SHOP_AGREE_CHANGE_I_GOOD_TMALL;
                break;
            default :
                $api_method = '';
                break;
        }
        return $api_method;
    }

    protected function __formatAfterSaleParams($aftersale,$status) {
        $shop_id = $this->__channelObj->channel['shop_id'];
       
        $params = array(
            'dispute_id'     =>$aftersale['return_bn'],
           
        );
        switch ($status) {
           
            case '9':
                $params['seller_refuse_reason_id'] = '1';
                $params['leave_message']         = '请联系客服';
               
                break;
             case '6'://同意退货
                $params['address_id'] = '1';
             break;
            
            default: break;
        }
        return $params;
    }

   
    /**
     * consignGoods
     * @param mixed $data 数据
     * @return mixed 返回值
     */

    public function consignGoods($data){
        
        $title = '店铺('.$this->__channelObj->channel['name'].')换货订单卖家发货,(售后单号:'.$data['dispute_id'].')';
        $api_name = SHOP_EXCHANGE_CONFIRMCONSIGN;
        $params = array(
            'dispute_id'            =>  $data['dispute_id'],
            'logistics_no'          =>  $data['logistics_no'],
            'corp_type'            =>  $data['corp_type'],
            'company_code'          =>  $data['corp_type'],
            'logistics_company_name'=>  $data['logistics_company_name'],
        );
        
        $result = $this->__caller->call($api_name, $params, array(), $title, 10, $data['order_bn']);
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
            'deliveryCropCode' =>$params['corp_type'],
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

    /**
     * refuseReturnGoods
     * @param mixed $data 数据
     * @return mixed 返回值
     */
    public function refuseReturnGoods($data){

        $title = '店铺('.$this->__channelObj->channel['name'].')拒绝确认发货,(售后单号:'.$data['dispute_id'].')';
        $result = $this->__caller->call(SHOP_EXCHANGE_RETURNGOODS_REFUSE, $data, array(), $title, 10, $data['dispute_id']);

        if(isset($result['msg']) && $result['msg']){
            $rs['msg'] = $result['msg'];
        }elseif(isset($result['err_msg']) && $result['err_msg']){
            $rs['msg'] = $result['err_msg'];
        }elseif(isset($result['res']) && $result['res']){
            $rs['msg'] = $result['res'];
        }
        $rs['rsp'] = $result['rsp'];
        $rs['data'] = $result['data'] ? json_decode($result['data'], true) : array();
        return $rs;

    }

   

    
}
