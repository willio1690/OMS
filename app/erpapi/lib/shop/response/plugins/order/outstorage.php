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
class erpapi_shop_response_plugins_order_outstorage extends erpapi_shop_response_plugins_order_abstract
{

    /**
     * convert
     * @param erpapi_shop_response_abstract $platform platform
     * @return mixed 返回值
     */

    public function convert(erpapi_shop_response_abstract $platform)
    {
        $outstorage = array();

        $outstorage['order_bn']          = $platform->_ordersdf['order_bn'];
        $outstorage['shop_id']      = $platform->__channelObj->channel['shop_id'];
        $outstorage['order_id']     = null;

        return $outstorage;
    }

    /**
     *
     * @return void
     * @author 
     **/
    public function postCreate($order_id,$outstorage)
    {

      $outstorage['order_id'] = $order_id;
      kernel::single('erpapi_router_request')->set('shop',$outstorage['shop_id'])->delivery_outstorage($outstorage);
    }

  /**
   *
   * @param Array 
   * @return void
   * @author 
   **/
  public function postUpdate($order_id,$outstorage)
  {
  }
}