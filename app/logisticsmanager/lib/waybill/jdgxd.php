<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class logisticsmanager_waybill_jdgxd extends logisticsmanager_waybill_abstract implements logisticsmanager_waybill_interface
{
    /**
     * template_cfg
     * @return mixed 返回值
     */
    public function template_cfg()
    {
        $arr = array(
            'template_name' => '京东',
            'shop_name'     => '京东',
            'print_url'     => 'http://prod-oms-app-cprt.jdwl.com/OpenCloudPrint/setup.exe',
            'template_url'  => 'https://open.jd.com/home/home#/index',
            'shop_type'     => ['360buy', 'jd'],
            'control_type'  => 'jd',
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
    public function logistics($logistics_code = '', $shop_id = '')
    {
        $logistics = array(
            'jd'              => array('code' => 'jd', 'name' => '京东快递'),
            'jingdongkuaiyun' => array('code' => 'jingdongkuaiyun', 'name' => '京东快运'),
            'DEBANGWULIU'     => array('code' => 'DEBANGWULIU', 'name' => '德邦物流'),
            'YTO'             => array('code' => 'YTO', 'name' => '圆通快递', 'mode' => 'join'),
        );
        
        if (!empty($logistics_code)) {
            return $logistics[$logistics_code];
        }
        
        return $logistics;
    }
    
    
    //获取物流公司编码
    /**
     * logistics_code
     * @param mixed $businessType businessType
     * @return mixed 返回值
     */
    public function logistics_code($businessType)
    {
        $logistics_code = array(
            1 => 'SOMS_GXD',
        );
        
        if (!empty($businessType)) {
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
        $rs = array('rsp' => 'succ', 'msg' => '', 'data' => '');
        
        if ($this->_shop['addon']['type'] != 'SOMS_GXD') {
            $rs['rsp'] = 'fail';
        }
        
        return $rs;
    }
    
    public function pay_method($method = '')
    {
        $payMethod = array(
            '1' => array('code' => '1', 'name' => '平台结算'),
            '2' => array('code' => '2', 'name' => '自行结算'),
        );
        
        if (!empty($method)) {
            return $payMethod[$method];
        }
        return $payMethod;
    }
    
    
}