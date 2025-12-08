<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * ============================
 * @Author:   yaokangming
 * @Version:  1.0
 * @DateTime: 2020/6/11 14:41:31
 * @describe: 类
 * ============================
 */

class erpapi_wms_matrix_suning_request_stockout extends erpapi_wms_request_stockout
{
    protected $outSysProductField   = '';
    protected $_stockout_pagination = false;

    protected function _format_stockout_create_params($sdf)
    {

        $params                   = parent::_format_stockout_create_params($sdf);
        $params['tid']            = $sdf['io_bn'];
        $params['subscribe_time'] = date('H:i:s', $sdf['arrive_time']);
        $params['subscribe_date'] = date('Y-m-d', $sdf['arrive_time']);

        return $params;
    }

    protected function _vop_format_stockout_create_params($sdf)
    {

        $params                   = parent::_vop_format_stockout_create_params($sdf);
        $params['tid']            = $sdf['io_bn'];
        $params['subscribe_time'] = date('H:i:s', $sdf['arrive_time']);
        $params['subscribe_date'] = date('Y-m-d', $sdf['arrive_time']);

        return $params;
    }

    protected function _format_stockout_cancel_params($sdf)
    {

        if ($sdf['io_type'] == 'PURCHASE_RETURN') {
            $rp = app::get('purchase')->model('returned_purchase')->dump(array('rp_bn' => $sdf['io_bn']), 'rp_id');

            $sdf = kernel::single('console_event_trigger_purchasereturn')->getStockOutParam($rp);
        } else {
            $iso = app::get('taoguaniostockorder')->model('iso')->dump(array('iso_bn' => $sdf['io_bn']), 'iso_id');

            $sdf = kernel::single('console_event_trigger_otherstockout')->getStockOutParam($iso);
        }

        $params        = parent::_format_stockout_create_params($sdf);
        $params['tid'] = $sdf['io_bn'];

        return $params;
    }
}
