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
class erpapi_wms_matrix_suning_request_stockin extends erpapi_wms_request_stockin
{
    protected $outSysProductField  = '';
    protected $_stockin_pagination = false;

    /**
     * _format_stockin_create_params
     * @param mixed $sdf sdf
     * @return mixed 返回值
     */

    public function _format_stockin_create_params($sdf)
    {
        $params                   = parent::_format_stockin_create_params($sdf);
        $params['tid']            = $sdf['io_bn'];
        $params['subscribe_time'] = date('H:i:s', $sdf['arrive_time']);
        $params['subscribe_date'] = date('Y-m-d', $sdf['arrive_time']);

        return $params;
    }

    protected function _format_stockin_cancel_params($sdf)
    {
        if ($sdf['io_type'] == 'PURCHASE') {
            $po = app::get('purchase')->model('po')->dump(array('po_bn' => $sdf['io_bn']), 'po_id');

            $sdf = kernel::single('console_event_trigger_purchase')->getStockInParam($po);
        } else {
            $iso = app::get('taoguaniostockorder')->model('iso')->dump(array('iso_bn' => $sdf['io_bn']), 'iso_id');

            $sdf = kernel::single('console_event_trigger_otherstockin')->getStockInParam($iso);
        }

        $params        = parent::_format_stockin_create_params($sdf);
        $params['tid'] = $sdf['io_bn'];

        return $params;
    }

}
