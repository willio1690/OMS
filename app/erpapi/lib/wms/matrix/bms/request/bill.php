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
class erpapi_wms_matrix_bms_request_bill extends erpapi_wms_request_bill
{
    protected $_order_type = array(
        'all'          => '',
        'delivery'     => '',
        'stockin'      => '904',
        'stockout'     => '903',
        'purchasein'   => '601',
        'purchaseout'  => '901',
        'returnedin'   => '501',
        'inventoryin'  => '702',
        'inventoryout' => '701',
      );

    /**
     * 出库单接口名
     * 
     * @return void
     * @author 
     */

    protected function _search_list_apiname()
    {
        return WMS_BMS_BILL_QUERY;
    }
}