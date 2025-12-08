<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * @author ykm 2015-12-15
 * @describe EMS请求电子面单类
 */
class erpapi_logistics_matrix_ems_request_electron extends erpapi_logistics_request_electron
{

    public $node_type = 'ems';
    public $to_node = '1815770338';
    /**
     * bufferRequest
     * @param mixed $sdf sdf
     * @return mixed 返回值
     */

    public function bufferRequest($sdf){
        $wbFilter = array(
            'channel_id'=>$this->__channelObj->channel['channel_id'],
            'status'=>0,
        );
        $waybillObj = app::get('logisticsmanager')->model('waybill');
        $count = $waybillObj->count($wbFilter);
        if($count < $this->cacheLimit) {
            $this->title = '获取EMS官方电子面单';
            $this->timeOut = 1;
            $this->primaryBn = 'EMSGetWaybill';
            $emsObj = kernel::single('logisticsmanager_waybill_ems');
            $params = array(
                'billNoAmount' => $this->everyNum, //单据数量
                'businessType' => $emsObj->businessType($this->__channelObj->channel['logistics_code']), //单据类型
            );
            $callback = array(
                'class' => get_class($this),
                'method' => 'bufferRequestCallBack',
                'params' => array('channel_id' => $this->__channelObj->channel['channel_id']),
            );
            $this->requestCall(STORE_WAYBILLPRINTDATA_GET, $params, $callback);
        }
        return true;
    }

    /**
     * directRequest
     * @param mixed $sdf sdf
     * @return mixed 返回值
     */
    public function directRequest($sdf){
        return false;
    }

    protected function bufferBackToRet($rlt) {
        $data = empty($rlt['data']) ? '' : json_decode($rlt['data'], true);
        if(empty($data['assignId'])) {
            return array();
        }
        $arrWaybill = array();
        foreach($data['assignId'] as $val){
            $arrWaybill[] = $val['billno'];
        }
        return $arrWaybill;
    }

    /**
     * delivery
     * @param mixed $sdf sdf
     * @return mixed 返回值
     */
    public function delivery($sdf) {
        $delivery = $sdf['delivery'];
        $shop = $sdf['shop'];
        $this->title = 'EMS官方电子面单物流回传';
        $this->primaryBn = $delivery['logi_no'];
        $logData = array(
            'logi_no' => $delivery['logi_no'],
            'delivery_id' => $delivery['delivery_id']
        );
        $emsObj = kernel::single('logisticsmanager_waybill_ems');
        $businessType = $emsObj->businessType($this->__channelObj->channel['logistics_code']);
        $printDatas['printData'][] = array(
            'bigAccountDataId' => $delivery['logi_no'], //大客户数据的唯一标识，如某电商公司的配货单号 必填
            'billno' => $delivery['logi_no'], //详情单号，和配货单号对应 必填
            'scontactor' => $shop['default_sender'], //寄件人姓名 必填
            'scustMobile' => $shop['mobile'], //寄件人联系方式1 必填
            'scustTelplus' => $shop['tel'], //寄件人联系方式2
            'scustPost' => $shop['zip'], //寄件人邮编 必填
            'scustAddr' => $shop['address_detail'], //寄件人地址 必填
            'scustComp' =>  '', //寄件人公司
            'tcontactor' => $delivery['ship_name'], //收件人姓名 必填
            'tcustMobile' => $delivery['ship_mobile'], //收件人联系方式1 必填
            'tcustTelplus' => $delivery['ship_tel'], //收件人联系方式2
            'tcustPost' => $delivery['ship_zip'], //收件人邮编 必填
            'tcustAddr' => $delivery['ship_addr'], //收件人地址 必填
            'tcustComp' => '', //收件人公司
            'tcustProvince' => $delivery['ship_province'], //到件省 必填
            'tcustCity' => $delivery['ship_city'], //到件市 必填
            'tcustCounty' => $delivery['ship_district'], //到件县 必填
            'weight' => $delivery['weight'] ? $delivery['weight']/1000 : 0.00, //寄件重量
            'length' => 0.00, //物品长度
            'insure' => 0.00, //保价
            'cargoType' => '', //内件类型：（文件、物品）
            'remark' => '', //备注
            'customerDn' => '',
            'businessType' => $businessType
        );
        $params = array(
            'printKind' => 2,
            'printDatas' => json_encode($printDatas)
        );
        return $this->deliveryCall(STORE_PRINT_DATA_CREATE,$logData,$params);
    }
}