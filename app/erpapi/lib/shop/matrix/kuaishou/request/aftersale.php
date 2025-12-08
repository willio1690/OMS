<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class erpapi_shop_matrix_kuaishou_request_aftersale extends erpapi_shop_request_aftersale {
    protected function __afterSaleApi($status, $returnInfo=null)
    {
        $api_method = '';
        
        //获取店铺配置(售后状态是否同步给平台)
        if($this->__channelObj->channel['config']){
            //禁止售后状态同步给平台
            if($this->__channelObj->channel['config']['kuaishou_return_sync'] == 'forbid_sync'){
                return $api_method;
            }
        }
        
        //opinion
        switch( $status ){
            case '3':
                $api_method = SHOP_AGREE_RETURN_GOOD;
                break;
            case '5':
                $api_method = SHOP_REFUSE_RETURN_GOOD;
                break;
            case '6'://同意退货
                $api_method = SHOP_AGREE_CHANGE_I_GOOD_TMALL;
                break;
            case '9'://拒绝换货
                $api_method = SHOP_REFUSE_CHANGE_I_GOOD_TMALL;
                break;
            case '10'://拒绝确认收货
                $api_method = SHOP_EXCHANGE_RETURNGOODS_REFUSE;
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
        $specialObj = app::get('ome')->model('return_apply_special');
        $ras = $specialObj->db_dump(array('return_id'=>$aftersale['return_id']), 'special');
        $special = $ras ? json_decode($ras['special'], 1) : array();

        if($aftersale['return_type']=='change'){
            $aftersale['money']=0;
        }
        switch ($status) {
            case '3':
                $params['desc'] = $aftersale['demo'] ? : 'ERP操作';
                $params['refund_amount'] = $aftersale['money'];
                $params['order_status'] = $special['order_status'];
                $params['negotiate_status'] = '1';
                $params['refund_handing_way'] = $special['refund_handing_way'];
                break;
            case '5':
                // $params['reason'] = '100';
                // $params['desc'] = $aftersale['demo'] ? : 'ERP操作';
                // $params['order_status'] = $special['order_status'];
                // $params['negotiate_status'] = '1';

                $params['reasonCode'] = '12';
                $params['rejectDesc'] = $aftersale['demo'] ?: '其他';
                $params['order_status'] = $special['order_status'];
                $params['negotiate_status'] = '1';
                $params['flag'] = 'new';
                $params['refundVersion'] = $special['refund_version'];
                
                break;
            case '6'://同意退货
                $params['refund_amount'] = $aftersale['money'];
                $params['dispute_id'] = $aftersale['return_bn'];
                break;
            case '9'://拒绝退货
                $params['dispute_id'] = $aftersale['return_bn'];
                $params['seller_refuse_reason_id'] = '12';
                $params['leave_message'] = '其他';
                $params['refundVersion'] = $special['updateTime'];
                
            break;
            
            case '10'://拒绝确认收货
                $params = array(
                    'leave_message'     => $aftersale['leave_message'],
                    'leave_message_pics'=> $aftersale['leave_message_pics'],
                    'dispute_id'        => ($aftersale['return_bn'] ? $aftersale['return_bn'] : $aftersale['dispute_id']),
                    'seller_refuse_reason_id'=>$aftersale['seller_refuse_reason_id'],

                );
            break;
            default: break;
        }

        return $params;
    }

    /**
     * 卖家确认收货
     * @param $data
     */
    public function returnGoodsConfirm($sdf){
        $title = '售后确认收货['.$sdf['return_bn'].']';
        $specialObj = app::get('ome')->model('return_apply_special');
        $ras = $specialObj->db_dump(array('return_id'=>$sdf['return_id']), 'special');
        $special = $ras ? json_decode($ras['special'], 1) : array();
        $data = array(
            'refund_id' => $sdf['return_bn'],
            'order_status' => $special['order_status']
        );
        $rfRow = app::get('ome')->model('return_freight')->db_dump(['return_id'=>$sdf['return_id']]);
        if($rfRow['handling_advice'] == '2') {
            $data['returnFreightAmount'] = $rfRow['amount'];
            $data['returnFreightHandlingAdvice'] = '2';
            $data['returnFreightRejectDesc'] = $rfRow['reject_desc'];
            $data['returnFreightRejectImages'] = json_encode([$rfRow['reject_images']]);
        }
        $this->__caller->call(SHOP_RETURN_GOOD_CONFIRM, $data, array(), $title, 10, $sdf['return_bn']);
    }

    /**
     * consignGoods
     * @param mixed $data 数据
     * @return mixed 返回值
     */
    public function consignGoods($data){
        $title = '店铺('.$this->__channelObj->channel['name'].')换货订单卖家发货,(售后单号:'.$data['dispute_id'].')';

        $params = array(
            'dispute_id'            =>  $data['dispute_id'],
            'logistics_no'          =>  $data['logistics_no'],
            'company_code'          =>  $data['corp_type'],
            'logistics_type'        =>  '200',
           
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
            'deliveryCropName' => $data['logistics_company_name'],
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
     * 商家代客填写退货单号
     *
     * @return void
     * @author 
     **/
    public function submitReturnInfo($sdf)
    {
        $title = '店铺('.$this->__channelObj->channel['name'].')商家代客填写退货单号,(退换货单号:'.$sdf['reship_bn'].')';

        $params = [
            'refund_id'               =>  $sdf['reship_bn'],
            'logistics_company_code'  =>  $sdf['return_logi_code'],
            'logistics_waybill_no'    =>  $sdf['return_logi_no'],
        ];

        $res = $this->__caller->call(STORE_KS_SUB_RETURNINFO, $params, array (), $title, 10, $sdf['reship_bn']);

        // if ($res['rsp'] == 'succ' && $data = @json_decode($res['data'],true)) {
        //     $res['data'] = $data['reason_list'];
        // }

        return $res;
    }

}