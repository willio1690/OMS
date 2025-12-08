<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * @author chenping@shopex.cn
 * @describe BMS退货单
 */
class erpapi_wms_matrix_bms_request_reship extends erpapi_wms_request_reship
{
    /**
     * 退换货接口名
     * 
     * @return void
     * @author 
     */

    protected function _search_list_apiname()
    {
        return WMS_BMS_BILL_QUERY;
    }

    /**
     * 退换货列表参数
     * 
     * @return void
     * @author 
     */
    protected function _search_list_params($sdf)
    {
        $order_type = array('returnedin'=>'501','exchangedin' => '502');

        $params = parent::_search_list_params($sdf);
        $params['order_type'] = $order_type[$sdf['iostock_type']];

        return $params;
    }

    #退货单创建
    public function create($sdf){
        return $this->error('BMS不支持退货单创建');
    }

    public function cancel($sdf){
        return $this->succ('允许直接取消');
    }

    # 退货单查询
    public function search($sdf)
    {
        return $this->error('BMS不支持退货单查询');
    }
}