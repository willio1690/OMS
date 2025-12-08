<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class logisticsmanager_waybill_ems extends logisticsmanager_waybill_abstract implements logisticsmanager_waybill_interface {
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
            'EMSPACK'=>array('code'=>'EMSPACK','name'=>'快递包裹'),
        );

        if(!empty($logistics_code)) {
            return $logistics[$logistics_code];
        }

        return $logistics;
    }

    /**
     * businessType
     * @param mixed $logistics_code logistics_code
     * @return mixed 返回值
     */
    public function businessType($logistics_code) {
        $businessType = array(
            'EMS'=>1,
            'EYB'=>4,
            'EMSPACK'=>9,
        );

        if(!empty($logistics_code)) {
            return $businessType[$logistics_code];
        }

        return $businessType;
    }

    //获取物流公司编码
    /**
     * logistics_code
     * @param mixed $businessType businessType
     * @return mixed 返回值
     */
    public function logistics_code($businessType) {
        $logistics_code = array(
            1 => 'EMS',
            4 => 'EYB',
            9=>'EMSPACK',
        );

        if(!empty($businessType)) {
            return $logistics_code[$businessType];
        }

        return $logistics_code;
    }

    
}