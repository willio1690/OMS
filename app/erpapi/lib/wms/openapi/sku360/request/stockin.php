<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * 入库单推送
 *
 * @category
 * @package
 * @author yaokangming<yaokangming@shopex.cn>
 * @version $Id: Z
 */
class erpapi_wms_openapi_sku360_request_stockin extends erpapi_wms_request_stockin
{
    protected function _format_stockin_create_params($sdf)
    {
        $data['purchases'][0] = array(
            'purchase_code' => $sdf['io_bn'],
            'plan_arrival_date' => $sdf['arrive_time'] ? date('Y-m-d H:i:s', $sdf['arrive_time']) : '',
        );
        $items = array();
        if ($sdf['items']){
            foreach ((array) $sdf['items'] as $k => $v){
                

                $items[] = array(
                    'product_code'    => $v['bn'],
                    'qty'   => $v['num'],
                );
            }
        }
        $data['purchases'][0]['skus'] = $items;
        $params['data'] = json_encode($data);
        return $params;
    }

    /**
     * 入库单创建
     *
     * @return void
     * @author
     **/

    public function stockin_create($sdf){
        $stockin_bn = $sdf['io_bn'];

        $iscancel = kernel::single('console_service_commonstock')->iscancel($stockin_bn);
        if ($iscancel) {
            return $this->succ('入库单已取消,终止同步');
        }

        $title = $this->__channelObj->wms['channel_name'].'入库单添加';
        $callback = array();
        $params = $this->_format_stockin_create_params($sdf);
        $response = $this->__caller->call(WMS_INORDER_CREATE, $params, $callback, $title, 10, $stockin_bn);
        $callback_params = array(
            'stockin_bn' => $sdf['io_bn'],
            'io_type' => strtolower($sdf['io_type']),
            'method' => WMS_INORDER_CREATE
        );
        if($response) {
            $this->stockin_create_callback($response, $callback_params);
        }
    }

    protected function _format_stockin_cancel_params($sdf)
    {
        $params['data'] = json_encode(array(
            'purchase_code' => $sdf['io_bn'],
        ));
        return $params;
    }
}