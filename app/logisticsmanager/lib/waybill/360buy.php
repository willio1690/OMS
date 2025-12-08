<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class logisticsmanager_waybill_360buy extends logisticsmanager_waybill_abstract implements logisticsmanager_waybill_interface{
    /**
     * template_cfg
     * @return mixed 返回值
     */
    public function template_cfg() {
        $arr = array(
            'template_name' => '京东',
            'shop_name' => '京东',
            'print_url' => 'http://prod-oms-app-cprt.jdwl.com/OpenCloudPrint/setup.exe',
            'template_url' => 'https://open.jd.com/home/home#/index',
            'shop_type' => ['360buy','jd'],
            'control_type' => 'jd',
            'request_again' => true
        );
        return $arr;
    }
    //获取物流公司
    /**
     * logistics
     * @param mixed $logistics_code logistics_code
     * @param mixed $shop_id ID
     * @return mixed 返回值
     */
    public function logistics($logistics_code = '', $shop_id = '') {
        $logistics = array(
            'SOP'=>array('code'=>'SOP','name'=>'SOP'),
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
            'SOP'=>1,
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
            1 => 'SOP',
        );

        if(!empty($businessType)) {
            return $logistics_code[$businessType];
        }

        return $logistics_code;
    }

    

    /**
     * 获取缓存中的运单号前动作
     *
     * @return void
     * @author 
     **/
    public function pre_get_waybill()
    {
        $rs = array('rsp'=>'succ','msg'=>'','data'=>'');
        
        if ($this->_shop['addon']['type'] != 'SOP') {
            $rs['rsp'] = 'fail';
        }

        return $rs;
    }

    
    public function service_code($param)
    {
        $cpCode  = $param['logistics'];
        $service = array(
            'SOP'          => array(
                'PRODUCT-TYPE' => array(
                    'text' => '产品类型',
                    'code' => 'PRODUCT-TYPE',
                    'input_type' => 'select',
                    'options' => array(
                        '1'  => '特惠送',
                        '2'  => '特快送',
                        '4'  => '城际闪送',
                        '7'  => '微小件',
                        '8'  => '生鲜专送',
                        '16' => '生鲜特快',
                        '17' => '生鲜特惠',
                        '20' => '函数达',
                        '21' => '特惠包裹',
                        '24' => '特惠小件',
                        '22' => '医药冷链',
                        '26' => '冷链专送',
                        '30' => '电商特惠',
                    )
                )
            )
        );
        return isset($service[$cpCode]) ? $service[$cpCode] : [];
    }

    
}