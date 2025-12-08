<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
* 收货人信息
*
* @author chenping<chenping@shopex.cn>
* @version $Id: consignee.php 2013-3-12 17:23Z
*/
class erpapi_dealer_response_components_order_consignee extends erpapi_dealer_response_components_order_abstract
{
    /**
     * 添加收货人信息
     *
     * @return void
     **/

    public function convert()
    {
        if ($this->_platform->_ordersdf['consignee']) {
            $area_state = $this->_platform->_ordersdf['consignee']['area_state']; //省
            $area_city = $this->_platform->_ordersdf['consignee']['area_city']; //市
            $area_district = $this->_platform->_ordersdf['consignee']['area_district']; //区
            $area_street = $this->_platform->_ordersdf['consignee']['area_street']; //镇、街道
            
            //地区
            $ship_area = $area_state .'/'. $area_city .'/'. $area_district;
            
            //四级地区：镇、街道
            if($area_street){
                $ship_area .= '/'. $area_street;
            }
            
            //newOrder
            $this->_platform->_newOrder['ship_name'] = $this->_platform->_ordersdf['consignee']['name'];
            $this->_platform->_newOrder['ship_addr'] = $this->_platform->_ordersdf['consignee']['addr'];
            $this->_platform->_newOrder['ship_mobile'] = $this->_platform->_ordersdf['consignee']['mobile'];
            $this->_platform->_newOrder['ship_tel'] = $this->_platform->_ordersdf['consignee']['telephone'];
            $this->_platform->_newOrder['ship_zip'] = $this->_platform->_ordersdf['consignee']['zip'];
            $this->_platform->_newOrder['ship_time'] = $this->_platform->_ordersdf['consignee']['r_time'];
            $this->_platform->_newOrder['ship_area'] = $ship_area;
        }
    }
    
    /**
     * 更新收货人信息
     *
     * @return void
     **/
    public function update()
    {
        $process_status = array('unconfirmed','confirmed','splitting','splited');
        if ($this->_platform->_ordersdf['consignee'] && in_array($this->_platform->_tgOrder['process_status'], $process_status) && $this->_platform->_tgOrder['ship_status'] == '0') {
            $area_state = $this->_platform->_ordersdf['consignee']['area_state']; //省
            $area_city = $this->_platform->_ordersdf['consignee']['area_city']; //市
            $area_district = $this->_platform->_ordersdf['consignee']['area_district']; //区
            $area_street = $this->_platform->_ordersdf['consignee']['area_street']; //镇、街道
            
            //地区
            $area = $area_state .'/'. $area_city .'/'. $area_district;
            
            //四级地区：镇、街道
            if($area_street){
                $area .= '/' . $area_street;
            }
            
            //phone
            $mobile = $this->_platform->_ordersdf['consignee']['mobile'];
            if(empty($mobile) && $this->_platform->_ordersdf['consignee']['telephone']){
                $mobile = $this->_platform->_ordersdf['consignee']['telephone'];
            }
            
            //平台地址信息
            $platformConsignee = array(
                'name' => $this->_platform->_ordersdf['consignee']['name'],
                'area' => $area,
                'mobile' => $mobile,
                'zip' => $this->_platform->_ordersdf['consignee']['zip'],
                'addr' => $this->_platform->_ordersdf['consignee']['addr'],
            );
            
            //OMS地址信息
            $erpConsignee = array(
                'name' => $this->_platform->_tgOrder['consignee']['name'],
                'area' => $this->_platform->_tgOrder['consignee']['area'],
                'mobile' => $this->_platform->_tgOrder['consignee']['mobile'],
                'zip' => $this->_platform->_tgOrder['consignee']['zip'],
                'addr' => $this->_platform->_tgOrder['consignee']['addr'],
            );
            
            //diff
            $diff_consignee = array_diff_assoc($platformConsignee, $erpConsignee);
            if ($diff_consignee) {
                $this->_platform->_newOrder['consignee'] = $diff_consignee;
                
                //格式化为数据字库表里的字段名
                if($diff_consignee['name']){
                    $this->_platform->_newOrder['ship_name'] = $diff_consignee['name'];
                }
                
                if($diff_consignee['area']){
                    $this->_platform->_newOrder['ship_area'] = $diff_consignee['area'];
                }
                
                if($diff_consignee['mobile']){
                    $this->_platform->_newOrder['ship_mobile'] = $diff_consignee['mobile'];
                }
                
                if($diff_consignee['zip']){
                    $this->_platform->_newOrder['ship_zip'] = $diff_consignee['zip'];
                }
                
                if($diff_consignee['addr']){
                    $this->_platform->_newOrder['ship_addr'] = $diff_consignee['addr'];
                }
            }
        }
    }
}