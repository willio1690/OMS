<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * 全类型单据
 *
 * @author chenping@shopex.cn
 * @time 2017/12/11 14:04:37
 */
class erpapi_wms_request_bill extends erpapi_wms_request_abstract
{
    protected $_order_type = array(
        'all'          => '',
        'delivery'     => '',
        'stockin'      => '',
        'stockout'     => '',
        'purchasein'   => '',
        'purchaseout'  => '',
        'returnedin'   => '',
        'inventoryin'  => '',
        'inventoryout' => '',
      );

    /**
     * 查询出单列表
     * 
     * @return void
     * @author 
     */

    public function search_list($sdf)
    {
       $title = $this->__channelObj->channel['channel_name'].'单据列表查询';

       $apiname = $this->_search_list_apiname();

       if (!$apiname) return $this->error('接口暂不支持');

       $params = array(
            'order_type' => $this->_order_type[$sdf['iostock_type']] ? $this->_order_type[$sdf['iostock_type']] : '',
            'page_no'    => $sdf['page_no'] ? $sdf['page_no'] : 1,
            'page_size'  => $sdf['page_size'] ? $sdf['page_size'] : 50,
       );

       $rs = $this->__caller->call($apiname, $params, null, $title, 5);

       return $rs;
    }

    /**
     * 出库单接口名
     * 
     * @return void
     * @author 
     */
    protected function _search_list_apiname()
    {
        return null;
    }
}