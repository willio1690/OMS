<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * @author ykm 2015-12-15
 * @describe 申通请求电子面单类
 */
class erpapi_logistics_matrix_sf_request_electron extends erpapi_logistics_request_electron
{

    public $node_type = 'sf';
    public $to_node   = '1588336732';
    public $shop_name = '顺丰官方电子面单';

    /**
     * 获取BindApiVersion
     * @return mixed 返回结果
     */

    public function getBindApiVersion()
    {
        $api_version = 'v1';
        $sfAccount = explode('|||',$this->__channelObj->channel['shop_id']);
        if (isset($sfAccount[4]) && $sfAccount[4] == 'v2') {
            $api_version = $sfAccount[4];
        }
        return $api_version;
    }

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
        $serviceCode = array();
        if($this->__channelObj->channel['service_code']) {
            $serviceCode = @json_decode($this->__channelObj->channel['service_code'], 1);
        }
        if($serviceCode['SVC-ZMD']['value'] == '1') {
            $main_order_no = '';
            $waybill_cid = '';
            kernel::single('wms_event_trigger_logistics_data_electron_common')
                ->checkChildRqOrdNo($delivery['delivery_bn'], $main_order_no, $waybill_cid);
            if ($main_order_no) {
                $params = array(
                    "orderid" => $main_order_no . base_shopnode::node_id('ome'),
                    "parcel_quantity" => 1,
                );
                $back = $this->requestCall(STORE_SF_ZD_ORDERSERVICE, $params, array(), $sdf);
                return $this->backToResult($back, $delivery);
            }
        }
        $shopInfo        = $sdf['shop'];
        $totalAmount     = $sdf['total_amount'];
        $dlyCorp         = $sdf['dly_corp'];
        $deliveryItems   = $sdf['delivery_item'];

        $cargos = $this->getCargos($deliveryItems);

        $jAddress    = $shopInfo['address_detail'] ? $shopInfo['province'] . $shopInfo['city'] . $shopInfo['area'] . $shopInfo['address_detail'] : '_SYSTEM';
        $dAddress    = $delivery['ship_addr'] ? $delivery['ship_province'] . $delivery['ship_city'] . $delivery['ship_district'] . $delivery['ship_addr'] : '_SYSTEM';
        $delivery_bn = $delivery['delivery_bn'];
        $params      = array(
            'orderid' => $delivery['delivery_bn'] . base_shopnode::node_id('ome'),
            'express_type'       => $this->__channelObj->channel['logistics_code'],
            'j_company'          => $shopInfo['shop_name'],
            'j_contact'          => $shopInfo['default_sender'] ? $shopInfo['default_sender'] : '_SYSTEM',
            'j_tel'              => $shopInfo['mobile'] ? $shopInfo['mobile'] : ($shopInfo['tel'] ? $shopInfo['tel'] : '_SYSTEM'),
            'j_province'         => $shopInfo['province'],
            'j_city'             => $this->isMunicipality($shopInfo['province']) ? $shopInfo['province'] : $shopInfo['city'],
            'j_address'          => $this->charFilter($jAddress),
            'd_company'          => $this->charFilter($delivery['ship_name']),
            'd_contact'          => $this->charFilter($delivery['ship_name']),
            'd_tel'              => $delivery['ship_mobile'] ? $delivery['ship_mobile'] : $delivery['ship_tel'],
            'd_province'         => $delivery['ship_province'],
            'd_city'             => $this->isMunicipality($delivery['ship_province']) ? $delivery['ship_province'] : $delivery['ship_city'],
            'd_address'          => $this->charFilter($dAddress),
            'parcel_quantity'    => 1,
            'cargo'              => $this->charFilter(htmlspecialchars($cargos['cargo'])),
            'cargo_total_weight' => $delivery['net_weight'] ? sprintf("%.2f", $delivery['net_weight'] / 1000) : '',
        );

        //货到付款
        if ($delivery['is_cod'] == 'true') {
            list($sysAccount, $passWord, $pay_method, $custid) = explode('|||', $this->__channelObj->channel['shop_id']);
            $params['sf_cod']                                  = 'COD';
            $params['sf_cod_value']                            = $totalAmount;
            $params['sf_cod_value1']                           = $custid;
        }
        if ($dlyCorp['protect'] == 'true') {
            $params['sf_insure']       = 'INSURE';
            $params['sf_insure_value'] = max($totalAmount * $dlyCorp['protect_rate'], $dlyCorp['minprice']);
        }

        // 丰密
       // if ($serviceCode['SVC-FM']['value'] == '1') {
            $params['routelabelForReturn'] = 0;
            $params['routelabelService'] = 1;
        //}

        // 快递叫单
        if ($serviceCode['SVC-ISDOCALL']['value'] == '1') {
            $params['is_docall'] = '1';

            $timedocall = explode(',', $serviceCode['SVC-TIMEDOCALL']['value']);
            sort($timedocall);
            foreach ($timedocall as $value) {

                $tt = strtotime(date('Y-m-d ' . $value));
                if (($delivery['create_time'] + 3600) < $tt) {
                    $params['sendstarttime'] = date('Y-m-d H:i:s', $tt);
                    break;
                }
            }

            if (!$params['sendstarttime'] && $timedocall[0]) {
                $params['sendstarttime'] = date('Y-m-d H:i:s', strtotime(date('Y-m-d ' . $timedocall[0])) + 86400);
            }
        }

        // 是否加密
        $is_encrypt = false;
        if (!$is_encrypt) {
            $is_encrypt = kernel::single('ome_security_router', $delivery['shop_type'])->is_encrypt($delivery, 'delivery');
        }
        // 云鼎解密
        $gateway = '';
        $jst     = array('order_bns' => $delivery['order_bns']);
        if ($is_encrypt) {
            $params['s_node_id']     = $delivery['shop']['node_id'];
            $params['s_node_type']   = $delivery['shop_type'];
            $params['ship_province'] = $delivery['ship_province'];
            $params['ship_city']     = $delivery['ship_city'];
            $params['ship_district'] = $delivery['ship_district'];
            $params['order_bns']     = implode(',', $delivery['order_bns']);

            $gateway = $delivery['shop_type'];
        }

        $back = $this->requestCall(STORE_SF_ORDERSERVICE, $params,array(),$jst, $gateway);

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
        if ($back['res'] == '8016') {

            $back = $this->searchRequest($delivery);
        }
        $data = empty($back['data']) ? '' : json_decode($back['data'], true);
        if (empty($data)) {
            return $back['msg'] ? $back['msg'] : false;
        }

        $json_packet = array();
        if ($data['mapping_mark']) {
            $json_packet['mapping_mark'] = $data['mapping_mark'];
        }

        if (is_array($data['rls_info']) && $data['rls_info']['rls_detail']) {
            $json_packet['rls_detail'] = $data['rls_info']['rls_detail'];
        }

        if ($data['mailno_zd']) {
            $json_packet['mailno_md'] = $data['mailno'];
            $data['mailno'] = $data['mailno_zd'];
        }

        $msg = '';
        if (empty($data['mailno']) && $data['filter_result'] == '3') {
            $msg = '收货地址不可达（请到顺丰后台关闭地址可达验证）';
        }
        $position = $data['rls_info']['rls_detail']['@destRouteLabel'] ? : $data['destcode'];
        $result   = array();
        $result[] = array(
            'succ'        => $data['mailno'] ? true : false,
            'msg'         => $msg,
            'delivery_id' => $delivery['delivery_id'],
            'delivery_bn' => $delivery['delivery_bn'],
            'logi_no'     => $data['mailno'],
            'position'    => $position,
            'json_packet' => json_encode($json_packet),
        );

        $this->directDataProcess($result);
        return $result;
    }

    //单条查询请求
    /**
     * 搜索Request
     * @param mixed $sdf sdf
     * @return mixed 返回值
     */
    public function searchRequest($sdf)
    {
        $this->title       = '搜索顺丰面单_' . $this->__channelObj->channel['logistics_code'] . '获取电子面单';
        $params['orderid'] = $sdf['delivery_bn'] . base_shopnode::node_id('ome');
        return $this->requestCall(STORE_SF_ORDERSEARCHSERVICE, $params);
    }

    #获取货物名称
    /**
     * 获取Cargos
     * @param mixed $deliveryItems deliveryItems
     * @return mixed 返回结果
     */
    public function getCargos($deliveryItems)
    {
        $cargo        = '';
        $cargo_count  = '';
        $cargo_unit   = '';
        $cargo_amount = '';
        foreach ($deliveryItems as $item) {
            $cargo .= $item['product_name'] . '/';
            $cargo_count .= $item['number'] . ',';
            $unit = '件';
            $cargo_unit .= $unit . ',';
            $amount = '100';
            $cargo_amount .= $amount . ',';
        }
        if ($cargo) {
            $cargo = trim($cargo, '/');
        }
        if ($cargo_count) {
            $cargo_count = trim($cargo_count, ',');
        }
        if ($cargo_unit) {
            $cargo_unit = trim($cargo_unit, ',');
        }
        if ($cargo_amount) {
            $cargo_amount = trim($cargo_amount, ',');
        }

        $cargos = array(
            'cargo'        => $cargo,
            'cargo_count'  => $cargo_count,
            'cargo_unit'   => $cargo_unit,
            'cargo_amount' => $cargo_amount,
        );
        return $cargos;
    }

    /**
     * 获取AccessToken
     * @param mixed $sdf sdf
     * @return mixed 返回结果
     */
    public function getAccessToken($sdf) {
        $this->title       = '顺丰面单_' . $this->__channelObj->channel['logistics_code'] . '获取access_tocken';
        $this->primaryBn = 'access_token';
        $return = $this->requestCall(STORE_SF_ACCESS_TOKEN, []);
        if($return['data']) {
            $return['data'] = json_decode($return['data'], 1);
        }
        return $return;
    }
}
