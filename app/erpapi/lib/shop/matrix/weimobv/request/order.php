<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * Created by PhpStorm.
 * User: gehuachun
 * Date: 2018/11/20
 * Time: 6:30 PM
 */
class erpapi_shop_matrix_weimobv_request_order extends erpapi_shop_request_order
{

    protected function __formatUpdateOrderShippingInfo($order) {
        $consignee_area = $order['consignee']['area'];
        if(strpos($consignee_area,":")){
            $t_area            = explode(":",$consignee_area);
            $t_area_1          = explode("/",$t_area[1]);
            $receiver_state    = $t_area_1[0];
            $receiver_city     = $t_area_1[1];
            $receiver_district = $t_area_1[2];
            $receiver_town = $t_area_1[3]; //街道
        }
        $params = array();
        $params['tid']               = $order['order_bn'];
        $params['receiver_name']     = $order['consignee']['name']?$order['consignee']['name']:'';
        $params['receiver_phone']    = $order['consignee']['telephone']?$order['consignee']['telephone']:'';
        $params['receiver_mobile']   = $order['consignee']['mobile']?$order['consignee']['mobile']:'';
        $params['receiver_state']    = $receiver_state ? $receiver_state : '';
        $params['receiver_city']     = $receiver_city ? $receiver_city : '';
        $params['receiver_district'] = $receiver_district ? $receiver_district : '';
        $params['receiver_street'] = $receiver_town ? $receiver_town : ''; //镇、街道
        $params['receiver_address']  = $order['consignee']['addr']?$order['consignee']['addr']:'';
        $params['receiver_zip']      = $order['consignee']['zip']?$order['consignee']['zip']:'';
        
        //获取平台省市区code
        $regionInfo = kernel::single('eccommon_platform_regions')->getPlatformRegions($params, 'weimobv');
        $params['provinceCode'] = $regionInfo['provinceCode']; //省
        $params['cityCode'] = $regionInfo['cityCode']; //市
        $params['countyCode'] = $regionInfo['districtCode']; //区
        $params['areaCode'] = (string)$regionInfo['townCode']; //区
        
        return $params;
    }
}