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
class erpapi_shop_response_plugins_order_fxw extends erpapi_shop_response_plugins_order_abstract
{

    /**
     * convert
     * @param erpapi_shop_response_abstract $platform platform
     * @return mixed 返回值
     */

    public function convert(erpapi_shop_response_abstract $platform)
    {
      $fxw = array();

      if ($platform->_ordersdf['dealer_order_id']) {
        $fxw['dealer_order_id'] = $platform->_ordersdf['dealer_order_id'];
      }
              
      return $fxw;
    }

    /**
     *
     * @return void
     * @author 
     **/
    public function postCreate($order_id,$fxw)
    {
      $fxw['order_id'] = $order_id;
      
      app::get('ome')->model('fxw_orders')->insert($fxw);
    }
}