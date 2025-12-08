<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class logisticsmanager_waybill_wlb extends logisticsmanager_waybill_abstract implements logisticsmanager_waybill_interface{
    //获取物流公司
    /**
     * logistics
     * @param mixed $logistics_code logistics_code
     * @param mixed $shop_id ID
     * @return mixed 返回值
     */
    public function logistics($logistics_code = '', $shop_id = '') {
        $logistics = array(
            'EMS'=>array('code'=>'EMS','name'=>'普通EMS'),
            'EYB'=>array('code'=>'EYB','name'=>'经济EMS'),
            'SF'=>array('code'=>'SF','name'=>'顺丰'),
            'ZJS'=>array('code'=>'ZJS','name'=>'宅急送'),
        );

        if(!empty($logistics_code)) {
            return $logistics[$logistics_code];
        }

        return $logistics;
    }

    //获取服务编码
    /**
     * service_code
     * @param mixed $logistics_code logistics_code
     * @return mixed 返回值
     */
    public function service_code($logistics_code) {
        $service_code = array(
            'EMS' => 'EMS',
            'EYB' => 'EMS',
            'SF' => 'SF',
            'ZJS' => 'ZJS',
        );

        if(!empty($logistics_code)) {
            return $service_code[$logistics_code];
        }

        return $service_code;
    }

    //获取面单类型
    /**
     * pool_type
     * @param mixed $logistics_code logistics_code
     * @return mixed 返回值
     */
    public function pool_type($logistics_code) {
        $pool_type = array(
            'EMS' => 'T01',
            'EYB' => 'T02',
            'SF' => 'SF',
            'ZJS' => 'ZJS',
        );

        if(!empty($logistics_code)) {
            return $pool_type[$logistics_code];
        }

        return $pool_type;
    }

    //获取物流公司编码
    /**
     * logistics_code
     * @param mixed $service_code service_code
     * @param mixed $pool_type pool_type
     * @return mixed 返回值
     */
    public function logistics_code($service_code, $pool_type) {
        $key = $service_code.$pool_type;
        $logistics_code = array(
            'EMST01' => 'EMS',
            'EMST02' => 'EYB',
            'SFSF' => 'SF',
            'ZJSZJS' => 'ZJS',
        );

        if(!empty($key)) {
            return $logistics_code[$key];
        }

        return $logistics_code;
    }

    
}