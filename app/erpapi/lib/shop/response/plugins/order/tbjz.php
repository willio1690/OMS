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
class erpapi_shop_response_plugins_order_tbjz extends erpapi_shop_response_plugins_order_abstract
{

    /**
     * convert
     * @param erpapi_shop_response_abstract $platform platform
     * @return mixed 返回值
     */

    public function convert(erpapi_shop_response_abstract $platform)
    {
      $jzorder_list = array();

      foreach ((array)$platform->_ordersdf['other_list'] as $other ) {
          if ($other['type'] == 'category') {
              $jzorder_list[] = array(
                'cid' => $other['cid'],
                'oid' => $other['oid'],
              );
          }
      }
              
      return $jzorder_list;
    }

    /**
     *
     * @return void
     * @author 
     **/
    public function postCreate($order_id,$jzorder_list)
    {
        $jzObj = app::get('ome')->model('tbjz_orders');


        foreach ($jzorder_list as $key=>$order ) {
          $jzorder_list[$key]['order_id'] = $order_id;
        }

        $sql = ome_func::get_insert_sql($jzObj,$jzorder_list);
        kernel::database()->exec($sql);
    }
}