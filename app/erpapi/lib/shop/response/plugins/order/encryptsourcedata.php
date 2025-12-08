<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * ============================
 * @Author:   yaokangming
 * @Version:  1.0
 * @DateTime: 2021/7/14 18:03:54
 * @describe: 敏感数据原始收件人
 * ============================
 */
class erpapi_shop_response_plugins_order_encryptsourcedata extends erpapi_shop_response_plugins_order_abstract
{

    /**
     * convert
     * @param erpapi_shop_response_abstract $platform platform
     * @return mixed 返回值
     */

    public function convert(erpapi_shop_response_abstract $platform)
    {
        $sdf = array();
        $encrypt_source_data = [];
        if($platform->_ordersdf['index_field']){
          if(is_string($platform->_ordersdf['index_field'])) {
              $encrypt_source_data = json_decode($this->_ordersdf['index_field'],true);
          } else {
              $encrypt_source_data = $platform->_ordersdf['index_field'];
          }
        }
        if($platform->_ordersdf['member_info']['buyer_open_uid']) {
            $encrypt_source_data['buyer_open_uid'] = $platform->_ordersdf['member_info']['buyer_open_uid'];
        }

        // wxshipin 代发的订单密文
        if ($platform->_ordersdf['extend_field']['delivery_info']['ewaybill_order_code']) {
            $encrypt_source_data['ewaybill_order_code'] = $platform->_ordersdf['extend_field']['delivery_info']['ewaybill_order_code'];
        }

        if($encrypt_source_data) {
            $sdf['encrypt_source_data'] = json_encode($encrypt_source_data,JSON_UNESCAPED_SLASHES);
        }
        //收货人地区
        if($platform->_ordersdf['consignee']['area_state'] || $platform->_ordersdf['consignee']['area_city']){
            $tmp = array(
                    'ship_province' => $platform->_ordersdf['consignee']['area_state'],
                    'ship_city' => $platform->_ordersdf['consignee']['area_city'],
                    'ship_district' => $platform->_ordersdf['consignee']['area_district'],
                    'ship_town' => $platform->_ordersdf['consignee']['area_street'],
            );
            $sdf = array_merge($sdf, $tmp);
        }
        //平台收货人地区ID
        if($platform->_ordersdf['extend_field']['province_id'] || $platform->_ordersdf['extend_field']['city_id']){
            $tmp = array(
                    'platform_country_id' => $platform->_ordersdf['extend_field']['country_id'],
                    'platform_province_id' => $platform->_ordersdf['extend_field']['province_id'],
                    'platform_city_id' => $platform->_ordersdf['extend_field']['city_id'],
                    'platform_district_id' => $platform->_ordersdf['extend_field']['district_id'],
                    'platform_town_id' => $platform->_ordersdf['extend_field']['town_id'],
            );
            $sdf = array_merge($sdf, $tmp);
        }
        
        return $sdf; 
    }

    /**
     * 保存
     *
     * @return void
     * @author 
     **/
    public function postCreate($order_id,$data)
    {
        $orderExtendObj = app::get('ome')->model('order_receiver');
        
        $sdf = $data;
        $sdf['order_id'] = $order_id;
        
        $orderExtendObj->save($sdf);
    }

    /**
    * 更新
    *
    * @param Array 
    * @return void
    * @author 
    **/
    public function postUpdate($order_id,$data)
    {
        $orderExtendObj = app::get('ome')->model('order_receiver');
        
        //sdf
        $sdf = $data;
        $sdf['order_id'] = $order_id;
        
        $orderExtendObj->save($sdf);
    }
}