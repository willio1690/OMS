<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * @author ykm 2015-12-15
 * @describe 京东请求电子面单类
 */
class erpapi_logistics_matrix_360buy_request_electron extends erpapi_logistics_request_electron
{
    private $__saleplat = array(
        '360buy' => '0010001',
        'jd'     => '0010001',
        'taobao' => '0010002',
        'suning' => '0010003',
        'amazon' => '0010004',
        'other'  => '0030001',
    );

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
     * etmsRangeCheck
     * @param mixed $params 参数
     * @return mixed 返回值
     */
    public function etmsRangeCheck($params)
    {
        $delivery = $params['delivery'];unset($params['delivery']);

        $this->title     = $this->__channelObj->channel['name'] . $this->__channelObj->channel['channel_type'] . '是否京配';
        $this->primaryBn = $params['tid'];

        // 是否加密
        $is_encrypt = kernel::single('ome_security_router', $delivery['shop_type'])->is_encrypt($delivery, 'delivery');
        // 云鼎解密
        $gateway = '';
        if ($is_encrypt) {
            $params['s_node_id']   = $delivery['shop']['node_id'];
            $params['s_node_type'] = $delivery['shop_type'];
            $params['order_bns']   = implode(',', $delivery['order_bns']);

            $gateway = $delivery['shop_type'];
        }

        return $this->requestCall(STORE_ETMS_RANGE_CHECK, $params, array(), array(), $gateway);
    }
    /**
     * directRequest
     * @param mixed $sdf sdf
     * @return mixed 返回值
     */
    public function directRequest($sdf)
    {
        $this->primaryBn = $sdf['primary_bn'];
        $this->title     = $this->__channelObj->channel['name'] . $this->__channelObj->channel['channel_type'] . '获取电子面单';
        $delivery        = $sdf['delivery'];
        $params          = array(
            'preNum' => $sdf['preNum'],
        );
        $back               = $this->requestCall(STORE_ETMS_WAYBILLCODE_GET, $params, array(), $sdf);
        $back['etms_check'] = $sdf['etms_check'];
        return $this->backToResult($back, $delivery);
    }

    /**
     * backToResult
     * @param mixed $back back
     * @param mixed $delivery delivery
     * @return mixed 返回值
     */
    public function backToResult($back, $delivery)
    {
        $data      = empty($back['data']) ? '' : json_decode($back['data'], true);
        $etmsCheck = empty($back['etms_check']) ? array() : $back['etms_check'];
        if (empty($data['resultInfo']['deliveryIdList'])) {
            return $back['msg'] ? $back['msg'] : false;
        }

        //是否为京东打印控件 将运单号上传青龙系统，获取打印面单的数据  add  by  fire 202100526 
        // if($this->isJdPrintControl($delivery['logi_id'])){
        //    list($jdbusinesscode) = explode('|||', $this->__channelObj->channel['shop_id']); 

        //     //运单号上传青龙系统
        //     $res = kernel::single('ome_event_trigger_logistics_electron')->delivery($delivery['delivery_id'],$data['resultInfo']['deliveryIdList'][0]);

        //     if($res){
                
        //     }else{
        //         $getJdPrintDataFail = true;
        //     }
        // }
    
        $result = array();
        foreach($data['resultInfo']['deliveryIdList'] as $val) {
            if($getJdPrintDataFail){
                $val = '';
            }
            $result[] = array(
                'succ' => $val ? true : false,
                'msg' => (string) $msg,
                'delivery_id' => $delivery['delivery_id'],
                'delivery_bn' => $delivery['delivery_bn'],
                'logi_no' => $val,
                'json_packet' => json_encode($etmsCheck),
                'noWayBillExtend' => false,
            );
            break;
        }
        $this->directDataProcess($result);
        return $result;
    }

    /**
     * delivery
     * @param mixed $sdf sdf
     * @return mixed 返回值
     */
    public function delivery($sdf)
    {
        $delivery         = $sdf['delivery'];
        $shop             = $sdf['shop'];
        $dlyCorp          = $sdf['dly_corp'];
        $totalAmount      = $sdf['total_amount'];
        $orderBn          = $sdf['order_bn'];
        $receivableAmount = $sdf['receivable_amount'];
        $sameRequest      = $sdf['same_request'];
        $this->title      = '京东电子面单物流回传';
        $this->primaryBn  = $delivery['logi_no'];
        $logData          = array(
            'logi_no'      => $delivery['logi_no'],
            'delivery_id'  => $delivery['delivery_id'],
            'channel_type' => '360buy',
        );
        $params                  = array();
        $params['deliveryId']    = $delivery['logi_no']; //运单号
        $params['salePlat']      = $this->__saleplat[$delivery['shop_type']] ? $this->__saleplat[$delivery['shop_type']] : $this->__saleplat['other']; //销售平台编码
        $params['orderId']       = $delivery['delivery_id']; //ERP发货单
        $params['thrOrderId']    = substr($orderBn, 0, 98); //京东订单  不能超过100所以先截取
        $params['senderName']    = $shop['default_sender']; //寄件人姓名 必填
        $params['senderAddress'] = $shop['address_detail']; //寄件人地址 必填
        if ($shop['tel']) {
            $params['senderTel'] = $shop['tel']; //寄件人电话
        }
        if ($shop['mobile']) {
            $params['senderMobile'] = $shop['mobile']; //寄件人手机
        }
        if ($shop['zip']) {
            $params['senderPostcode'] = $shop['zip']; //寄件人邮编
        }
        $params['receiveName']    = $delivery['ship_name']; //收件人姓名 必填
        $params['receiveAddress'] = $delivery['ship_addr']; //收件人地址 必填
        if ($delivery['ship_province']) {
            $params['province'] = $delivery['ship_province']; //收件人省
        }
        if ($delivery['ship_city']) {
            $params['city'] = $delivery['ship_city']; //收件人市
        }
        if ($delivery['ship_district']) {
            $params['county'] = $delivery['ship_district']; //收件人县
        }
        if ($delivery['ship_tel']) {
            $params['receiveTel'] = $delivery['ship_tel']; //收件人电话
        }
        if ($delivery['ship_mobile']) {
            $params['receiveMobile'] = $delivery['ship_mobile']; //收件人手机
        }
        if ($delivery['ship_zip']) {
            $params['postcode'] = $delivery['ship_zip']; //收件人邮编
        }
        $params['packageCount'] = 1; //包裹数量
        $params['weight']       = ($delivery['weight']) > 0 ? sprintf("%.2f", (max(0, $delivery['weight']) / 1000)) : 0; //重量
        $params['vloumn']       = 0; //体积
        //是否代收货款
        if ($delivery['is_cod'] == 'true') {
            $params['collectionValue'] = 1; //是否代收货款
            $params['collectionMoney'] = $receivableAmount; //代收货款金额
        } else {
            $params['collectionValue'] = 0; //是否代收货款
        }
        if ($dlyCorp['protect'] == 'true') {
            $params['guaranteeValue']       = 1; //是否保价
            $params['guaranteeValueAmount'] = sprintf('%.2f', max($totalAmount * $dlyCorp['protect_rate'], $dlyCorp['minprice']));
        } else {
            $params['guaranteeValue'] = 0; //是否保价
        }

        // 是否加密
        $is_encrypt = false;
        if (!$is_encrypt) {
            $is_encrypt = kernel::single('ome_security_router',$delivery['shop_type'])->is_encrypt($delivery,'delivery');
            if($is_encrypt && in_array($delivery['shop_type'], array('360buy','jd'))) {

                $encryptTid = explode(',', $orderBn);
                $encryptTid = current($encryptTid);
                $encryptOrder = kernel::database()->selectrow('select order_id from sdb_ome_orders where order_bn="'.$encryptTid.'" and shop_id="'.$delivery['shop_id'].'"');
                if($encryptOrder) {
                    $original = app::get('ome')->model('order_receiver')->db_dump(['order_id'=>$encryptOrder['order_id']], 'encrypt_source_data');
                    if($original) {
                        $encrypt_source_data = json_decode($original['encrypt_source_data'], 1);

                    }
                }

                if($encrypt_source_data['oaid']) {
                    $params['oaid'] = $encrypt_source_data['oaid'];
                    $is_encrypt = false;
                    if($index = strpos($params['receiveTel'] , '>>')) {

                        $params['receiveTel'] = substr($params['receiveTel'] , 0, $index);
                        
                    }
                    if($index = strpos($params['receiveMobile'] , '>>')) {
                        
                         $params['receiveMobile'] = substr($params['receiveMobile'] , 0, $index);
                    }

                    if($index = strpos($params['receiveName'] , '>>')) {
                        
                         $params['receiveName'] = substr($params['receiveName'] , 0, $index);
                    }
                    if($index = strpos($params['receiveAddress'] , '>>')) {
                        
                         $params['receiveAddress'] = substr($params['receiveAddress'] , 0, $index);
                    }
                    if($index = strpos($params['ship_addr'] , '>>')) {
                        
                         $params['ship_addr'] = substr($params['ship_addr'] , 0, $index);
                    }
                    
                }else{

                    if($index = strpos($params['receiveTel'] , '>>')) {
                        $params['receiveTel'] = substr($params['receiveTel'] , $index + 2, -5);
                        $is_encrypt = false;
                    }
                    if($index = strpos($params['receiveMobile'] , '>>')) {
                        $params['receiveMobile'] = substr($params['receiveMobile'] , $index + 2, -5);
                        $is_encrypt = false;
                    }

                }

                
                
            }
        }

        // 云鼎解密
        $gateway = '';
        if ($is_encrypt) {
            $params['s_node_id']   = $delivery['shop']['node_id'];
            $params['s_node_type'] = $delivery['shop_type'];
            $params['order_bns']   = $orderBn;

            $gateway = $delivery['shop_type'];
        }

        //京东打印控件走同步请求
        if($this->isJdPrintControl($delivery['logi_id'])){
            $isAsync = false;
        }
     
        $res = $this->deliveryCall(STORE_ETMS_WAYBILL_SEND,$logData,$params,$gateway,$isAsync);
        if(!empty($sameRequest)) {
            foreach($sameRequest as $value) {
                $params['thrOrderId'] = substr($value['order_bn'],0,98);//京东订单  不能超过100所以先截取
                if ($delivery['is_cod'] == 'true') {
                    $params['collectionMoney'] = $value['receivable_amount']; //代收货款金额
                }
                if ($dlyCorp['protect'] == 'true') {
                    $totalAmount                    = $value['total_amount'];
                    $params['guaranteeValueAmount'] = sprintf('%.2f', max($totalAmount * $dlyCorp['protect_rate'], $dlyCorp['minprice']));
                }
                $res = $this->deliveryCall(STORE_ETMS_WAYBILL_SEND,$logData,$params,$gateway,$isAsync);
            }
        }
        return $res;
    }

    /**
     * 获取EncryptPrintData
     * @param mixed $sdf sdf
     * @return mixed 返回结果
     */
    public function getEncryptPrintData($sdf){


        $mapCode = $params = [];

        list($jdBusinesscode) = explode('|||', $this->__channelObj->channel['shop_id']);

        $mapCode['ewCustomerCode'] = $jdBusinesscode; 

        $wmsDelivery = app::get('wms')->model('delivery')->dump($sdf['delivery_id'], 'shop_id,outer_delivery_bn,logi_number');
        $shop = app::get('ome')->model('shop')->dump($wmsDelivery['shop_id'],'tbbusiness_type');


        $omeDelivery = app::get('ome')->model('delivery')->dump(['delivery_bn' => $wmsDelivery['outer_delivery_bn']],'delivery_id');

        // 运单号回写
        $res = kernel::single('ome_event_trigger_logistics_electron')->delivery($omeDelivery['delivery_id']);
        if (is_array($res) && $res['rsp'] == 'fail') {
            return ['rsp' => 'fail', 'msg' => $res['msg']];
        }

        $this->title = '获取京东打印数据';
        $this->primaryBn = $sdf['logi_no'];

        $orderBns = kernel::single('ome_extint_order')->getOrderBns($wmsDelivery['outer_delivery_bn']);

        $params['cp_code'] = 'JD';

        $waybillInfo = array();
        $waybillInfo['orderNo']       = array_pop($orderBns);
        $waybillInfo['popFlag']       = $shop['tbbusiness_type'] == 'SOP'?1:0;
        $waybillInfo['wayBillCode']   = $sdf['logi_no'];
        $waybillInfo['jdWayBillCode'] = $sdf['logi_no'];

        $pdKey = 0;

        if ($sdf['batch_logi_no']) {
            $waybillInfo['packageCode'] = $sdf['batch_logi_no'];

            if ($wmsDelivery['logi_number'] > 1){
                // 更新包裹数
                $this->updatePackage([
                    'logi_no'       => $sdf['logi_no'],
                    'logi_number'   => $wmsDelivery['logi_number']
                ]);

                list(,$sn) = explode('-', $sdf['batch_logi_no']);

                if ($sn && $sn > 0) $pdKey = $sn - 1;
            }
        }

        $params['map_code']        = json_encode($mapCode); 
        $params['waybill_infos']   = json_encode([$waybillInfo]); 
        $params['object_id']       = substr(time(), 4).uniqid();

        $this->title     = '获取京东打印数据';
        $this->primaryBn = $sdf['logi_no'];

        $back = $this->requestCall(STORE_USER_DEFINE_AREA, $params);
        $back['msg'] = $back['res'];
        if($back['rsp'] == 'succ'){
             $data      = json_decode($back['data'],true);
             $back['data'] = $data['jingdong_printing_printData_pullData_responce']['returnType']['prePrintDatas'][$pdKey]['perPrintData']?:'';
        }
        return $back;  
    }

    /**
     * 更新Package
     * @param mixed $sdf sdf
     * @return mixed 返回值
     */
    public function updatePackage($sdf) {
        $this->title = '更新物流包裹数';
        $this->primaryBn = $sdf['logi_no'];
        $params = [];
        $params['logistics_no'] = $sdf['logi_no'];
        $params['package_num'] = $sdf['logi_number'];
        $back = $this->requestCall(STORE_ETMS_PACKAGE_UPDATE, $params);
        return $back;
    }
}
