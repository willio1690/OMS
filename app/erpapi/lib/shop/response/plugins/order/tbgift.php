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
class erpapi_shop_response_plugins_order_tbgift extends erpapi_shop_response_plugins_order_abstract
{

    /**
     * convert
     * @param erpapi_shop_response_abstract $platform platform
     * @return mixed 返回值
     */

    public function convert(erpapi_shop_response_abstract $platform)
    {
      $tbgift = array();

      if ('true' == app::get('ome')->getConf('ome.preprocess.tbgift')) {
          $tbgift['gift']     = $platform->_ordersdf['other_list'];
          $tbgift['order_id'] = null;
      }

      return $tbgift;
    }

    /**
     *
     * @return void
     * @author 
     **/
    public function postCreate($order_id,$tbgift)
    {
      kernel::single('ome_preprocess_tbgift')->save($order_id,$tbgift['gift']);
    }
}