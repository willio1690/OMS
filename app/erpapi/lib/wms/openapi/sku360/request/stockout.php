<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * 出库单推送
 *
 * @category
 * @package
 * @author yaokangming<yaokangming@shopex.cn>
 * @version $Id: Z
 */
class erpapi_wms_openapi_sku360_request_stockout extends erpapi_wms_request_stockout
{
    protected function _format_stockout_create_params($sdf)
    {
        $data['orders'][0] = array(
            'order_code' => $sdf['io_bn'],
        );

        $items = array();
        if ($sdf['items']){
            foreach ($sdf['items'] as $k => $v){
                $items[] = array(
                    'product_code' => $v['bn'],
                    'qty' => $v['num'],
                );
            }
        }
        $data['orders'][0]['skus'] = $items;
        $params['data'] = json_encode($data);
        return $params;   
    }
    /**
     * 出库单创建
     *
     * @return void
     * @author
     **/

    public function stockout_create($sdf){
        $stockout_bn = $sdf['io_bn'];

        $iscancel = kernel::single('console_service_commonstock')->iscancel($stockout_bn);
        if ($iscancel) {
            return $this->succ('出库单已取消,终止同步');
        }

        $title = $this->__channelObj->wms['channel_name'] . '出库单添加';

        $callback = array();
        $params = $this->_format_stockout_create_params($sdf);
        $response = $this->__caller->call(WMS_OUTORDER_CREATE, $params, $callback, $title, 10, $stockout_bn);
        $callback_params = array(
            'stockout_bn' => $sdf['io_bn'],
            'io_type' => strtolower($sdf['io_type']),
            'method' => WMS_OUTORDER_CREATE
        );
        if($response) {
            $this->stockout_create_callback($response, $callback_params);
        }
    }

    protected function _format_stockout_cancel_params($sdf)
    {
        $params['data'] = json_encode(array(
            'order_code' => $sdf['io_bn'],
        ));

        return $params;
    }
}