<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class erpapi_logistics_matrix_jdgxd_request_electron extends erpapi_logistics_request_electron
{
    /**
     * bufferRequest
     * @param mixed $sdf sdf
     * @return mixed 返回值
     */
    public function bufferRequest($sdf)
    {
        return $this->directNum;
    }
    
    /**
     * directRequest
     * @param mixed $sdf sdf
     * @return mixed 返回值
     */
    public function directRequest($sdf)
    {
        $this->primaryBn = $sdf['primary_bn'];
        $delivery        = $sdf['delivery'];
        // $deliveryOrder = $delivery['delivery_order'] ? current($delivery['delivery_order']) : array();
        $params = array(
            'deliveryInfo' => $sdf['deliveryInfo'],
            'orderIdList'  => $sdf['orderIdList'],
        );
        
        $result = $this->requestCall(STORE_WAYBILL_GET, $params);
        
        $returnResult = $this->backToResult($result, $delivery);
        
        return $returnResult;
    }
    
    private function backToResult($back, $delivery)
    {
        $data = empty($back['data']) ? '' : json_decode($back['data'], true);
        $msg  = $back['msg'] ? $back['msg'] : $back['err_msg'];
        
        if ($back['rsp'] == 'fail' || empty($data)) {
            return $msg;
        }
        
        $returnResult = $data['result'] ?? [];
        
        $logi_no  = $returnResult['waybillCode'] ?? '';
        $result[] = array(
            'succ'         => $logi_no ? true : false,
            'msg'          => '',
            'delivery_id'  => $delivery['delivery_id'],
            'delivery_bn'  => $delivery['delivery_bn'],
            'logi_no'      => $logi_no,
            'order_no'     => $returnResult['logisticsOrderNo'] ?? '',
            'carrier_name' => $returnResult['providerName'] ?? '',//履约承运商名称
            'carrier_code' => $returnResult['providerId'] ?? '',//承运商编码
        );
        
        $this->directDataProcess($result);
        
        return $result;
    }
    
    
    /**
     * 获取EncryptPrintData
     * @param mixed $sdf sdf
     * @return mixed 返回结果
     */
    public function getEncryptPrintData($sdf)
    {
        $mapCode = $params = [];
        
        $jdgxd = explode('|||', $this->__channelObj->channel['shop_id']);
        
        // 京东大件用ewCustomerCode
        $mapCode['eCustomerCode'] = $jdgxd[2];
        
        $wmsDelivery = app::get('wms')->model('delivery')->dump($sdf['delivery_id'], 'shop_id,outer_delivery_bn');
        $shop        = app::get('ome')->model('shop')->dump($wmsDelivery['shop_id'], 'tbbusiness_type');
        
        $this->title     = '获取京东打印数据';
        $this->primaryBn = $sdf['logi_no'];
        
        $orderBns = kernel::single('ome_extint_order')->getOrderBns($wmsDelivery['outer_delivery_bn']);
        
        // $params['cp_code'] = 'JD';
        
        $waybillInfo                  = array();
        $waybillInfo['orderNo']       = array_pop($orderBns);
        $waybillInfo['popFlag']       = $shop['tbbusiness_type'] == 'SOP' ? 1 : 0;
        $waybillInfo['wayBillCode']   = $sdf['logi_no'];
        $waybillInfo['jdWayBillCode'] = $sdf['logi_no'];
        
        if ($sdf['batch_logi_no']) {
            $waybillInfo['packageCode'] = $sdf['batch_logi_no'];
        }
        
        $params['map_code']      = json_encode($mapCode);
        $params['waybill_infos'] = json_encode([$waybillInfo]);
        $params['object_id']     = substr(time(), 4) . uniqid();
        
        $back        = $this->requestCall(STORE_USER_DEFINE_AREA, $params);
        $back['msg'] = $back['res'];
        if ($back['rsp'] == 'succ') {
            $data         = json_decode($back['data'], true);
            $back['data'] = $data['jingdong_printing_printData_pullData_responce']['returnType']['prePrintDatas'][0]['perPrintData'] ?: '';
        }
        return $back;
    }
    
}