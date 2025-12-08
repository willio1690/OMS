<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
*
* @author chenping<chenping@shopex.cn>
* @version $Id: 2013-3-12 17:23Z
*/
class erpapi_shop_response_plugins_order_sellingagent extends erpapi_shop_response_plugins_order_abstract
{

    /**
     * convert
     * @param erpapi_shop_response_abstract $platform platform
     * @return mixed 返回值
     */

    public function convert(erpapi_shop_response_abstract $platform)
    {
        $sellingagent = array();

        if ($platform->_ordersdf['selling_agent']['member_info']['uname']) {
            $sellingagent['uname']          = $platform->_ordersdf['selling_agent']['member_info']['uname'];
            $sellingagent['name']           = $platform->_ordersdf['selling_agent']['member_info']['name'];
            $sellingagent['level']          = $platform->_ordersdf['selling_agent']['member_info']['level'];
            $sellingagent['birthday']       = $platform->_ordersdf['selling_agent']['member_info']['birthday'];
            $sellingagent['sex']            = $platform->_ordersdf['selling_agent']['member_info']['sex'];
            $sellingagent['email']          = $platform->_ordersdf['selling_agent']['member_info']['email'];
            $sellingagent['addr']           = $platform->_ordersdf['selling_agent']['member_info']['addr'];
            $sellingagent['zip']            = $platform->_ordersdf['selling_agent']['member_info']['zip'];
            $sellingagent['mobile']         = $platform->_ordersdf['selling_agent']['member_info']['mobile'];
            $sellingagent['tel']            = $platform->_ordersdf['selling_agent']['member_info']['telephone'];
            $sellingagent['qq']             = $platform->_ordersdf['selling_agent']['member_info']['qq'];
            $sellingagent['website_name']   = $platform->_ordersdf['selling_agent']['website']['name'];
            $sellingagent['website_domain'] = $platform->_ordersdf['selling_agent']['website']['domain'];
            $sellingagent['website_logo']   = $platform->_ordersdf['selling_agent']['website']['logo'];
            $sellingagent['addon']          = '';


            if ($sellingagent['area_state'] && $sellingagent['area_city'] && $sellingagent['area_district']) {
                $area = $sellingagent['area_state'] . '/' . $sellingagent['area_city'] . '/'.$sellingagent['area_district'];
                kernel::single('ome_func')->region_validate($area);

                $sellingagent['area'] = $area;
            }


            #代销商发货人和发货地址都必须存在
            if($platform->_ordersdf['seller_address'] && $platform->_ordersdf['seller_name']){
                $sellingagent['seller_name']     = $platform->_ordersdf['seller_name'];       #卖家姓名
                $sellingagent['seller_mobile']   = $platform->_ordersdf['seller_mobile'];     #卖家电话号码
                $sellingagent['seller_phone']    = $platform->_ordersdf['seller_phone'];      #卖家电话号码
                $sellingagent['seller_zip']      = $platform->_ordersdf['seller_zip'];        #卖家的邮编
                $sellingagent['seller_address']  = $platform->_ordersdf['seller_address'];    #发货人的详细地址
                $sellingagent['print_status']    = '1';

                if ($sellingagent['seller_state'] && $sellingagent['seller_city'] && $sellingagent['seller_district']) {
                    $area = $sellingagent['seller_state'] . '/' . $sellingagent['seller_city'] . '/'.$sellingagent['seller_district'];
                    kernel::single('ome_func')->region_validate($area);

                    $sellingagent['area'] = $area;
                }
            }
        }

        // 如果是更新
        if ($sellingagent && $platform->_tgOrder['order_id']) {
            $agentModel = app::get('ome')->model('order_selling_agent');
            $oldagent = $agentModel->getList('*',array('order_id'=>$platform->_tgOrder['order_id']),0,1);

            $sellingagent = array_filter($sellingagent,array($this,'filter_null'));
            $sellingagent = array_udiff_assoc((array) $sellingagent, (array) $oldagent[0],array($this,'comp_array_value'));
        }


        return $sellingagent;
    }


    /**
     *
     * @return void
     * @author 
     **/
    public function postCreate($order_id,$sellingagent)
    {
        $agentModel = app::get('ome')->model('order_selling_agent'); 

        $sellingagent['order_id'] = $order_id;
        
        $agentModel->insert($sellingagent);
    }

  /**
   *
   * @param Array 
   * @return void
   * @author 
   **/
  public function postUpdate($order_id,$sellingagent)
  {
    $agentModel = app::get('ome')->model('order_selling_agent'); 
    $agentModel->update($sellingagent,array('order_id'=>$order_id));
  }
}