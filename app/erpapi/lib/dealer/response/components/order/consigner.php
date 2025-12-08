<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
* 发货人信息（未使用这个插件）
*
* @author chenping<chenping@shopex.cn>
* @version $Id: b2cv1.php 2013-3-12 17:23Z
*/
class erpapi_dealer_response_components_order_consigner extends erpapi_dealer_response_components_order_abstract
{
    /**
     * 添加发货人信息
     *
     * @return void
     **/

    public function convert()
    {   
//        if ($this->_platform->_ordersdf['consigner']) {
//            $area_state = $this->_platform->_ordersdf['consigner']['area_state']; //省
//            $area_city = $this->_platform->_ordersdf['consigner']['area_city']; //市
//            $area_district = $this->_platform->_ordersdf['consigner']['area_district']; //区
//            $area_street = $this->_platform->_ordersdf['consigner']['area_street']; //镇、街道
//
//            $this->_platform->_newOrder['consigner']['name']   = $this->_platform->_ordersdf['consigner']['name'];
//            $this->_platform->_newOrder['consigner']['area']   = $area_state .'/'. $area_city .'/'. $area_district; //地区
//            $this->_platform->_newOrder['consigner']['addr']   = $this->_platform->_ordersdf['consigner']['addr'];
//            $this->_platform->_newOrder['consigner']['zip']    = $this->_platform->_ordersdf['consigner']['zip'];
//            $this->_platform->_newOrder['consigner']['tel']    = $this->_platform->_ordersdf['consigner']['telephone'];
//            $this->_platform->_newOrder['consigner']['email']  = $this->_platform->_ordersdf['consigner']['email'];
//            $this->_platform->_newOrder['consigner']['mobile'] = $this->_platform->_ordersdf['consigner']['mobile'];
//            $this->_platform->_newOrder['consigner']['r_time'] = $this->_platform->_ordersdf['consigner']['r_time'];
//
//            //添加四级地区(镇、街道)
//            if($area_street){
//                $this->_platform->_newOrder['consigner']['area'] .= '/'. $area_street;
//            }
//        }
    }

    /**
     * 更新发货人信息
     *
     * @return void
     **/
    public function update()
    {
//        if ($this->_platform->_ordersdf['consigner']) {
//            $area_state = $this->_platform->_ordersdf['consigner']['area_state']; //省
//            $area_city = $this->_platform->_ordersdf['consigner']['area_city']; //市
//            $area_district = $this->_platform->_ordersdf['consigner']['area_district']; //区
//            $area_street = $this->_platform->_ordersdf['consigner']['area_street']; //镇、街道
//
//            //地区
//            $area = $area_state .'/'. $area_city .'/'. $area_district;
//
//            //添加四级地区(镇、街道)
//            if($area_street){
//                $area .= '/' . $area_street;
//            }
//
//            kernel::single('ome_func')->region_validate($area);
//
//            $consigner['name']   = $this->_platform->_ordersdf['consigner']['name'];
//            $consigner['area']   = $area;
//            $consigner['addr']   = $this->_platform->_ordersdf['consigner']['addr'];
//            $consigner['zip']    = $this->_platform->_ordersdf['consigner']['zip'];
//            $consigner['tel']    = $this->_platform->_ordersdf['consigner']['telephone'];
//            $consigner['email']  = $this->_platform->_ordersdf['consigner']['email'];
//            $consigner['mobile'] = $this->_platform->_ordersdf['consigner']['mobile'];
//            $consigner['r_time'] = $this->_platform->_ordersdf['consigner']['r_time'];
//
//            $diff_consigneer = array_udiff_assoc($consigner, $this->_platform->_tgOrder['consigner'],array($this,'comp_array_value'));
//            if ($diff_consigneer) {
//                $this->_platform->_newOrder['consigner'] = $diff_consigneer;
//            }
//        }
    }
}