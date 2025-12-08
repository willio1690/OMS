<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class logisticsmanager_waybill_yto4gj {

    public function logistics($logistics_code='', $channel=array()) {
        if(!$channel) {
            return !empty($logistics_code) ? '' : array();
        }
        $channel_id = $channel['channel_id'];
        static $logistics = array();
        if(!$logistics[$channel_id]) {
            $rs = kernel::single('erpapi_router_request')->set('logistics', $channel_id)->electron_getLogistics(array());
            if($rs['rsp'] == 'fail' || empty($rs['data'])) {
            } else {
                if($rs['data']['productKinds']) {
                    foreach ($rs['data']['productKinds'] as $key => $value) {
                        $logistics[$channel_id][$value['productCode']] = array('code'=>$value['productCode'], 'name'=>$value['productCnname']);
                    }
                }
            }
        }
        if (!empty($logistics_code)) {
            return $logistics[$channel_id][$logistics_code];
        }
        return $logistics[$channel_id];
    }

    /**
     * service_code
     * @param mixed $param param
     * @return mixed 返回值
     */
    public function service_code($param) {
        return array(); //没有场景，不清楚下单时额外服务的value怎么传，先不做
        $sdf = array('cp_code'=>$param['logistics']);
        $rs = kernel::single('erpapi_router_request')->set('logistics', $param['channel_id'])->electron_getServiceCode($sdf);
        if($rs['rsp'] == 'fail' || empty($rs['data'])) {
            return array();
        }
        $service = array();
        if($rs['data']['extraServices']) {
            $service = $this->__getServiceCode($rs['data']['extraServices']);
        }
        return $service;
    }

    private function __getServiceCode($data) {}
}