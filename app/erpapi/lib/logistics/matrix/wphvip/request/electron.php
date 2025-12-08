<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class erpapi_logistics_matrix_wphvip_request_electron extends erpapi_logistics_request_electron
{
    protected $directNum = 1;

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
     * 获取电子面单
     * 
     * @param $sdf
     * @return array|bool
     * author : Joe
     * Date : 2022-02-18 16:25
     */
    public function directRequest($sdf)
    {
        $delivery = $sdf['delivery'][0];

        $shop = app::get("ome")->model('shop')->dump(['shop_id'=>$this->__channelObj->channel['shop_id']]);

        if(empty($shop['addon']['user_id'])){
            return '缺少店铺id,请重新授权';
        }
        // 模板地址
        $corp = app::get('ome')->model('dly_corp')->getList('prt_tmpl_id, protect, protect_rate, minprice', array('corp_id' => $delivery['logi_id']), 0, 1);

        $templateObj = app::get("logisticsmanager")->model('express_template');
        $printTpl    = $templateObj->getList('*', array('template_id' => $corp[0]['prt_tmpl_id']), 0, 1);

        $template = $printTpl[0];

        $this->title = '唯品会获取电子面单' . $this->__channelObj->channel['logistics_code'];

        $this->timeOut   = 20;
        $this->primaryBn = $delivery['delivery_bn'];

        // 发货地址
        $seller = array(
            'city_name'     => $sdf['shop']['city'],
            'region_name'   => $sdf['shop']['area'],
            'town_name'     => $sdf['shop']['street'],
            'address'       => $sdf['shop']['address_detail'],
            'province_name' => $sdf['shop']['province'],
            'name'          => $sdf['shop']['default_sender'],
            'tel'           => $sdf['shop']['mobile'],
            'carriers_code' => $this->__channelObj->channel['logistics_code']
        );

        $is_encrypt        = false;
        $jst               = array('order_bns' => array());
        $TradeOrderInfoDto = $deliveryBnKey = [];

        // 是否加密
        if (!$is_encrypt) {
            $is_encrypt = kernel::single('ome_security_router', $delivery['shop_type'])->is_encrypt($delivery, 'delivery');
        }

        $arr_goods = array();
        foreach ($delivery['package_items'] as $item) {
            $arr_goods[] = [
                'barcode' => $item['bn'],
                'amount'  => $item['count'],
            ];

        }
        $seller['goods'] = $arr_goods;

        list($logisticsServices, $productType) = $this->_getLogisticsServices($corp, $delivery);

        if ($logisticsServices) {
            $seller['service_field_list'] = $logisticsServices;
        }

        $tmp = [
            'order_sn'     => current($delivery['order_bns']),
            'package_type' => 2,
            'store_id'     => $shop['addon']['user_id'],
            'packages'     => json_encode(array($seller)),
            // 'hebao_order_sn_list'     => json_encode($delivery['order_bns']),
        ];

        if (!$arr_goods) {
            $tmp['package_type'] = 1;
        }

        $result = $this->requestCall(STORE_WAYBILL_GET, $tmp, array(), $jst);

        $back = $this->backToResult($result, $delivery, $shop['addon']['user_id']);
        return $back;
    }


    private function _getLogisticsServices($corp, $delivery)
    {
        $logisticsServices = array();
        $productType       = '';
        if ($this->__channelObj->channel['service_code']) {
            $serviceCode = json_decode($this->__channelObj->channel['service_code'], 1);
            foreach ($serviceCode as $k => $v) {
                if ($k == 'cod') {
                    $v['value'] = $delivery['receivable'];
                }

                if ($k == 'insure') {
                    if ($corp['protect'] == 'true' && $v['value'] == '1') {
                        $v['value'] = max($delivery['total_amount'] * $corp['protect_rate'], $corp['minprice']);
                    } else {
                        continue;
                    }
                }

                if (!$v['value']) {
                    continue;
                }

                $logisticsServices[] = [
                    'service_code'      => $k,
                    'service_field_map' => [
                        ($v['key'] ?: 'value') => $v['value'],
                    ]
                ];
            }
        }

        return [$logisticsServices, $productType];
    }


    /**
     * 获取电子面单回调方法
     * 
     * @param $ret
     * @param $deliveryBnKey
     * @param $store_id
     * @return array|bool
     * author : Joe
     * Date : 2022-02-18 19:44
     */
    private function backToResult($ret, $deliveryBnKey,$store_id)
    {
        $waybill = empty($ret['data']) ? array() : json_decode($ret['data'], true);
        $waybill = json_decode($waybill['msg'], 1);
        if (!empty($waybill['result'][0]['error_msg']) || $ret['rsp'] == 'fail') {
            return $waybill['result'][0]['error_msg'] ? $waybill['result'][0]['error_msg'] : false;
        }

        #   pr($this->__channelObj->channel,1);
        $result = array();
        foreach ($waybill['result'] as $val) {
            //获取打印数据
            $print_info = $this->get_print_data($val,$store_id);
            $result[]   = array(
                'succ'         => $print_info['printDatas']['transportNo'] ? true : false,
                'msg'          => '',
                'delivery_id'  => $deliveryBnKey['delivery_id'],
                'delivery_bn'  => $deliveryBnKey['delivery_bn'],
                'logi_no'      => $print_info['printDatas']['transportNo'],
                'print_config' => $print_info['templateUrl'],
                'json_packet'  => $print_info['printDatas']['printData'],
            );
        }
        $this->directDataProcess($result);
        return $result;
    }

    /**
     * 拉取打印内容
     * 
     * @param $sdf
     * @param $store_id
     * @return array|bool
     * author : Joe
     * Date : 2022-02-18 19:44
     */
    public function get_print_data($sdf,$store_id)
    {
        $params = array(
            'transportNos' => json_encode([array('orderSn' => $sdf['order_sn'], 'transportNo' => $sdf['transport_no'])]),
            'carrierCode'  => $sdf['carriers_code'],
            'ownerId'      => $store_id
        );

        $title = "唯品会VIP-获取【{$sdf['transport_no']}】打印数据";

        $result = $this->__caller->call(STORE_WAYBILL_PRINTDATA, $params, array(), $title, 10, $sdf['transport_no']);
//        error_log(date("c")."\t".print_r($result,true)."\n", 3, ROOT_DIR."/logs/qyj.log");


        if ($result['rsp'] == 'succ' && $result['data']) {
            $data = json_decode($result['data'], 1);
            $msg  = json_decode($data['msg'], 1);

            return ['templateUrl' => $msg['result']['templateUrl'], 'printDatas' => $msg['result']['printDatas'][0]];
        } else {
            return false;
        }
    }

    /**
     * 读取打印内容
     * 
     * @param $sdf
     * @return array|bool
     * author : Joe
     * Date : 2022-02-18 16:24
     */
    public function getEncryptPrintData($sdf)
    {
        $objWaybill         = app::get('logisticsmanager')->model('waybill');
        $waybillExtendModel = app::get('logisticsmanager')->model('waybill_extend');
        $arrWaybill         = $objWaybill->dump(array('waybill_number' => $sdf['logi_no']), 'id,status');
        $waybillExtend      = $waybillExtendModel->dump(['waybill_id' => $arrWaybill['id']]);
        $shop = app::get("ome")->model('shop')->dump(['shop_id'=>$this->__channelObj->channel['shop_id']]);
        if (!empty($waybillExtend)) {
            return ['templateUrl' => $waybillExtend['print_config'],'store_id'=>$shop['addon']['user_id'], 'printDatas' => json_decode($waybillExtend['json_packet'], 1)];
        } else {
            return false;
        }
    }

    /**
     * 取消电子面单
     * 
     * @param $waybillNumber
     * author : Joe
     * Date : 2022-02-18 16:24
     */
    public function recycleWaybill($waybillNumber,$delivery_bn = '')
    {
        $shop = app::get("ome")->model('shop')->dump(['shop_id'=>$this->__channelObj->channel['shop_id']]);
        $this->title     = '唯品会vip_' . $this->__channelObj->channel['logistics_code'] . '取消电子面单';
        $this->primaryBn = $waybillNumber;
        $params          = array(
            'transport_list' => json_encode([array('transport_no' => $waybillNumber, 'carriers_code' => $this->__channelObj->channel['logistics_code'])]),
            'store_id'       => $shop['addon']['user_id']
        );
//        $callback = array(
//            'class' => get_class($this),
//            'method' => 'callback'
//        );
        $this->requestCall(STORE_WAYBILL_CANCEL, $params);
    }


}