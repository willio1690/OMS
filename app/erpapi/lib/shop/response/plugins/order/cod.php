<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
* 到付
*
* @author chenping<chenping@shopex.cn>
* @version $Id: 2013-3-12 17:23Z
*/
class erpapi_shop_response_plugins_order_cod extends erpapi_shop_response_plugins_order_abstract
{

  public function convert(erpapi_shop_response_abstract $platform)
  {
      $codsdf = array();

      if('true' == $platform->_ordersdf['shipping']['is_cod']){
          if ( in_array($platform->__channelObj->channel['node_type'], array('vjia','360buy','dangdang','yihaodian')) ) {
              foreach((array) $platform->_ordersdf['other_list'] as $val){
                  if($val['type']=='unpaid'){
                      $unpaidprice = $val['unpaidprice'];
                      break;
                  }
              }

              $codsdf['receivable'] = (isset($unpaidprice)) ? $unpaidprice : ($platform->_ordersdf['total_amount'] - $platform->_ordersdf['payed']);
          }else{
              $codsdf['receivable'] = $platform->_ordersdf['total_amount'];
          }
          $codsdf['order_id'] = null;
      }

      return $codsdf; 
  }

    /**
     * 到付保存
     *
     * @return void
     * @author 
     **/
    public function postCreate($order_id,$codinfo)
    {
        $orderExtendObj = app::get('ome')->model('order_extend'); 

        $codinfo['order_id'] = $order_id;

        $orderExtendObj->save($codinfo);
    }

  /**
   * 到付更新
   *
   * @param Array 
   * @return void
   * @author 
   **/
  public function postUpdate($order_id,$codinfo)
  {
    $orderExtendObj = app::get('ome')->model('order_extend'); 

    $codinfo['order_id'] = $order_id;

    $orderExtendObj->save($codinfo);
  }
}