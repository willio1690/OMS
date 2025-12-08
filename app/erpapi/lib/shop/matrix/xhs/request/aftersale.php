<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * 新小红书
 */
class erpapi_shop_matrix_xhs_request_aftersale extends erpapi_shop_request_aftersale
{
    protected function __afterSaleApi($status, $returnInfo=null)
    {
        switch($status)
        {
            case '3':
                $api_method = SHOP_AGREE_REFUNDGOODS;
                break;
            case '5':
                $api_method = SHOP_REFUSE_REFUNDGOODS;
                break;
            default :
                $api_method = '';
                break;
        }
        
        return $api_method;
    }
    
    protected function __formatAfterSaleParams($aftersale, $status)
    {
        $params = array(
            'returns_id' => $aftersale['return_bn'], //退货单号
        );
        
        switch ($status)
        {
            case '3':
                //同意退货
                $params['audit_result'] = '200'; //同意
                
                //获取退货单信息
                $reshipObj = app::get('ome')->model('reship');
                $reshipInfo = $reshipObj->dump(array('return_id'=>$aftersale['return_id']), 'reship_id');
                $reshipInfo['reship_id'] = intval($reshipInfo['reship_id']);
                
                //获取退回寄件地址
                $filter = array('shop_type'=>'xhs');
                $return_address = app::get('ome')->model('return_address')->getList('*',$filter, 0,1,'cancel_def ASC');
                
                //params
                $receiver_info = array(
                        //'code' => '', //非必填，退回仓库编码，只有使用小红书退货服务才需要填
                        'country' => '中国', //非必填，国家
                        'province' => $return_address[0]['province'], //非必填， 省份
                        'city' => $return_address[0]['city'], //非必填，城市
                        'district' => $return_address[0]['country'], //非必填  区
                        'street' => $return_address[0]['addr'], //非必填，街道信息
                );
                $params['receiver_info'] = json_encode($receiver_info);
                if(in_array($aftersale['kinds'],['change','reship'])){
                    $params['action'] = 2;
                }else{
                    $params['action'] = 1;
                }
                break;
            case '5':
                //拒绝退货参数
                $params['audit_result'] = '500'; //拒绝
                $params['action'] = 3;
                $params['reason'] = 99;
                //拒绝原因
                $refuse_message = ($aftersale['memo'] ? $aftersale['memo'] : $aftersale['content']);
                if($refuse_message){
                    $params['audit_description'] = $refuse_message;
                    $params['description'] = $aftersale['refuse_message'] ? $aftersale['refuse_message'] : $refuse_message;
                    $params['reject_reason'] = 1;
                }
                break;
            default: break;
        }
        
        return $params;
    }

    /**
     * 卖家确认收货
     * @param $data
     */

    public function returnGoodsConfirm($sdf)
    {
        $title = '售后确认收货['.$sdf['return_bn'].']';
        $data = array(
            'refund_id' => $sdf['return_bn'],
            'action' => '1'
        );
        $this->__caller->call(SHOP_RETURN_GOOD_CONFIRM, $data, array(), $title, 10, $sdf['return_bn']);
    }
    
    public function consignGoods($data){
        $title = '售后换货订单卖家发货['.$data['dispute_id'].']';
        $api_name = SHOP_EXCHANGE_CONSIGNGOODS;
        
        $params = $this->_formatConsignGoodsParams($data);
        //【确认收货并发货】时走新接口
        if (kernel::single('ome_reship_const')->isNewExchange($data['flag_type'])) {
            $api_name               = SHOP_EXCHANGE_CONFIRMCONSIGN;
            $params['company_code'] = $data['corp_type'];
            $params['company_name'] = $data['logistics_company_name'];
            unset($params['corp_type'], $params['logistics_company_name']);
        }
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
        
        return $this->_consignGoodsBack($rs, $data);
    }
    
    protected function _consignGoodsBack($rs, $data){
        $log_id = uniqid($_SERVER['HOSTNAME']);
        $rsp = $rs['rsp'] =='success' ? 'succ' : $rs['rsp'];
        $status = ($rsp=='succ') ? 'succ' : 'fail';
        $log = array(
            'shopId'           => $this->__channelObj->channel['shop_id'],
            'ownerId'          => '16777215',
            'orderBn'          => $data['order_bn'],
            'deliveryCode'     => $data['logistics_no'],
            'deliveryCropCode' => isset($data['corp_type']) ? $data['corp_type'] : $data['company_code'],
            'deliveryCropName' => isset($data['logistics_company_name']) ? $data['logistics_company_name'] : $data['company_name'],
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
     * _formatConsignGoodsParams
     * @param mixed $data 数据
     * @return mixed 返回值
     */
    public function _formatConsignGoodsParams($data)
    {
        $params = array(
            'dispute_id'             => $data['dispute_id'],
            'logistics_no'           => $data['logistics_no'],
            'corp_type'              => $data['corp_type'],
            'logistics_type'         => '200',
            'logistics_company_name' => $data['logistics_company_name'],
            'company_code'           => $data['corp_type'],
            'company_name'           => $data['logistics_company_name'],
        );
        return $params;
    }
}