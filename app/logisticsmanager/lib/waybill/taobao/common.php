<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * @author ykm 2017/2/17
 * @describe 云栈电子面单服务 通用
 */

class logisticsmanager_waybill_taobao_common {
    protected $checkBoxValue = array('SVC-COD', 'SVC-INSURE', 'SVC-RECEIVER-PAY');
    protected $checkBoxNoValue = array('SVC-PRIOR-DELIVERY', 'SVC-VIP', 'DELIVERY-HEAVY', 'IMPORT', 'EXPORT', 'WAREHOUSE', 'SVC-INSTALL', 'SVC-WAREHOUSE');

//    public function getServiceCode($productType) {
//        $service = array();
//        foreach($productType as $value) {
//            if($value['service_types']['waybill_service_type']) {
//                foreach($value['service_types']['waybill_service_type'] as $val) {
//                    if(in_array($val['code'], $this->checkBoxValue)
//                        || in_array($val['code'], $this->checkBoxNoValue)) {
//                        $service[$val['code']] = array(
//                            'text' => $val['name'],
//                            'code' => $val['code'],
//                            'input_type' => 'checkbox',
//                        );
//                    }
//                }
//            }
//        }
//        return $service;
//    }

    public function getServiceCode($data){
        $service = array();
        if($data['waybill_apply_subscription_info'] && $data['waybill_apply_subscription_info'][0]['branch_account_cols'] && $data['waybill_apply_subscription_info'][0]['branch_account_cols']['waybill_branch_account'])
        {
            $waybill_branch = $data['waybill_apply_subscription_info'][0]['branch_account_cols']['waybill_branch_account'];
            $cp_code = $data['waybill_apply_subscription_info'][0]['cp_code'];
            $customer_options = array();
            foreach($waybill_branch as $val){
                $service_list = $val['service_info_cols']['service_info_dto'];
                if( in_array($cp_code, ['SF','LE04284890', 'LE38288910']) ){
                    if ($val['customer_code_list']){
                        foreach($val['customer_code_list']['string'] as $cv){
                            $cv = $val['brand_code'] . '-' . $cv;
                            $customer_options[$cv] = $cv;
                        }
                    } else {
                        $customer_options[$val['brand_code']] = $val['brand_code'];
                    }
                }
                foreach($service_list as $v){
                    if($service[$v['service_code']]) {
                        // 多网点服务去重
                        continue;
                    }
                    $service[$v['service_code']] = $tmpService = array(
                        'text'  => $v['service_name'],
                        'code'  => $v['service_code'],
                        'required' => $v['required'],
                    );
                    if(empty($v['service_attributes']['service_attribute_dto'])) {
                        $service[$v['service_code']]['input_type'] = 'checkbox';
                        continue;
                    }
                    foreach ($v['service_attributes']['service_attribute_dto'] as $type_obj) {
                        $type_desc_str = $type_obj['type_desc'];
                        $type_desc = json_decode($type_desc_str, true);
                        $attribute = array('input_name'=>$type_obj['attribute_code']);
                        $attributeType = $type_desc['type'] ? $type_desc['type'] : $type_obj['attribute_type'];
                        if ($attributeType === 'enum') {
                            $type_desc['desc'][''] = '请选择';
                            $attribute['input_type'] = 'select';
                            $attribute['options'] = $type_desc['desc'];
                        } elseif($attributeType === 'string') {
                            $attribute['input_type'] = 'text';
                        }else {
                            $attribute['input_type'] = 'checkbox';
                        }
                        if($service[$v['service_code']]['input_type']) {
                            $attribute['text'] = $tmpService['text'] . '(' . $type_obj['attribute_name'] . ')';
                            $attribute['code'] = $tmpService['code'] . '#' . $type_obj['attribute_code'];
                            $service[$attribute['code']] = array_merge($tmpService, $attribute);
                        } else {
                            if(count($v['service_attributes']['service_attribute_dto']) > 1) {
                                $attribute['text'] = $tmpService['text'] . '(' . $type_obj['attribute_name'] . ')';
                            }
                            $service[$v['service_code']] = array_merge($tmpService, $attribute);
                        }
                    }
                }
            }
            if($customer_options) {
                $service['brand_code_customer_code'] = array(
                    'text'          =>  '品牌编码-⽉结卡号',
                    'code'          =>  'brand_code_customer_code',
                    'input_type'    =>  'select',
                    'options'       =>  $customer_options,
                    'required'      =>  'true',
                );
            }
        }
        return $service;
    }

    public function getServiceCodeValue($serviceCode) {
        $serviceCodeValue = array();
        foreach($serviceCode as $k => $val) {
            if(strpos($k, '#')) {
                if($val['value']) {
                    list($k1, $k2) = explode('#', $k);
                    $serviceCodeValue[$k1][$k2] = $val['value'];
                }
                continue;
            }
            if(in_array($k, $this->checkBoxValue) && $val['value'] == 1) {
                $serviceCodeValue[$k] = true;
            } elseif (in_array($k, $this->checkBoxNoValue) && $val['value'] == 1) {
                $serviceCodeValue[$k] = new stdClass();
            } elseif ($val['value']){
                if($val['input_name']) {
                    $serviceCodeValue[$k][$val['input_name']] = $val['value'];
                } else {
                    $serviceCodeValue[$k]['value'] = $val['value'];
                }
            }
        }
        return $serviceCodeValue;
    }
}