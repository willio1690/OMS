<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * @author chenping@shopex.cn
 * @time 2017/12/8 17:46:23
 * @describe BMS入库单
 */
class erpapi_wms_matrix_bms_request_stockin extends erpapi_wms_request_stockin
{
    /**
     * 入库单接口名
     * 
     * @return void
     * @author 
     */

    protected function _search_list_apiname()
    {
        return WMS_BMS_BILL_QUERY;
    }

    /**
     * 入库单列表参数
     * 
     * @return void
     * @author 
     */
    protected function _search_list_params($sdf)
    {
        $order_type = array('purchasein'=>'601','stockin' => '904');

        $params = parent::_search_list_params($sdf);
        $params['order_type'] = $order_type[$sdf['iostock_type']];

        return $params;
    }

    public function create($sdf){
        return $this->error('BMS不支持入库单创建');
    }

    public function cancel($sdf){
        return $this->succ('允许直接取消');
    }

    public function search($sdf){
        return $this->succ('BMS不支持入库单查询');
    }
}